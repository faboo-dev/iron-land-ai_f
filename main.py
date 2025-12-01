import os
from typing import List, Dict, Optional
from difflib import SequenceMatcher
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from langchain_google_genai import GoogleGenerativeAIEmbeddings, ChatGoogleGenerativeAI
from langchain_community.vectorstores import Chroma
from langchain.chains import LLMChain
from langchain.prompts import PromptTemplate
from langchain.schema import Document

# ========================================
# 환경 변수 설정
# ========================================
os.environ["AIzaSyDW2umdbsDWSMkIeX7VsdHoRfrcXp_qsYE"] = "AIzaSyDW2umdbsDWSMkIeX7VsdHoRfrcXp_qsYE"

# FastAPI 앱
app = FastAPI(title="Iron Land Travel AI")

# ========================================
# 1. Vector Store 로드
# ========================================
embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
vectorstore = Chroma(
    persist_directory="./chroma_db",
    embedding_function=embeddings,
    collection_name="travel_knowledge_base"
)

# LLM 설정
llm = ChatGoogleGenerativeAI(
    model="gemini-2.0-flash-exp",
    temperature=0.3,  # 할루시네이션 방지를 위해 낮춤
    max_tokens=2048
)


# ========================================
# 2. 키워드 정규화
# ========================================
KEYWORD_NORMALIZATION = {
    "썬마호핑": ["썬마호핑", "선마호핑", "썬마", "선마", "써마", "섬마", "sunma"],
    "해적호핑": ["해적호핑", "해적", "해저핑", "pirate"],
    "클럽세부": ["클럽세부", "클럽", "세부클럽", "club cebu"],
    "한바다": ["한바다", "한 바다"],
    "바이킹": ["바이킹호핑", "바이킹"],
    "놀자": ["놀자호핑", "놀자"],
    "락빌리지": ["락빌리지", "락 빌리지"],
}

def normalize_keywords(text: str) -> List[str]:
    """
    텍스트에서 정규화된 키워드 추출
    """
    normalized = []
    text_lower = text.lower()
    
    for base_keyword, variants in KEYWORD_NORMALIZATION.items():
        for variant in variants:
            if variant.lower() in text_lower:
                normalized.extend(variants)
                break
    
    # 기본 키워드 추출
    basic_keywords = text.split()
    stopwords = ["에", "대해", "알려줘", "뭐야", "어때", "인가요", "는", "을", "를"]
    basic_keywords = [w for w in basic_keywords if w not in stopwords and len(w) > 1]
    
    normalized.extend(basic_keywords)
    
    return list(set(normalized))


# ========================================
# 3. 하이브리드 검색
# ========================================
def hybrid_search(query: str, k: int = 30) -> List[Document]:
    """
    키워드 + Vector + 퍼지 매칭 하이브리드 검색
    """
    # 1. 키워드 정규화
    keywords = normalize_keywords(query)
    print(f"🔍 Normalized keywords: {keywords}")
    
    # 2. Vector 검색
    vector_results = vectorstore.similarity_search(query, k=k)
    
    # 3. 키워드 매칭 (메타데이터 활용)
    keyword_results = []
    all_docs = vectorstore.get()
    
    if all_docs and 'metadatas' in all_docs and 'documents' in all_docs:
        for i, (metadata, content) in enumerate(zip(all_docs['metadatas'], all_docs['documents'])):
            # 메타데이터 키워드 확인
            doc_keywords = metadata.get('keywords', [])
            if any(kw in doc_keywords for kw in keywords):
                keyword_results.append(Document(
                    page_content=content,
                    metadata=metadata
                ))
            # 본문 키워드 확인
            elif any(kw in content for kw in keywords):
                keyword_results.append(Document(
                    page_content=content,
                    metadata=metadata
                ))
    
    # 4. 퍼지 매칭 (오타 대응)
    fuzzy_results = []
    for doc in vector_results:
        for keyword in keywords:
            if len(keyword) < 3:  # 너무 짧은 키워드는 스킵
                continue
            
            # 본문에서 유사한 단어 찾기
            words = doc.page_content.split()
            for word in words:
                similarity = SequenceMatcher(None, keyword, word).ratio()
                if similarity >= 0.8 and doc not in fuzzy_results:
                    fuzzy_results.append(doc)
                    break
    
    # 5. 결과 병합 및 중복 제거
    combined = []
    seen_ids = set()
    
    # 우선순위: 키워드 > 퍼지 > Vector
    for doc_list in [keyword_results, fuzzy_results, vector_results]:
        for doc in doc_list:
            doc_id = doc.metadata.get('id')
            if doc_id and doc_id not in seen_ids:
                combined.append(doc)
                seen_ids.add(doc_id)
    
    print(f"📊 Search results: {len(keyword_results)} keyword, {len(fuzzy_results)} fuzzy, {len(vector_results)} vector → {len(combined)} total")
    
    return combined[:20]  # 상위 20개 반환


# ========================================
# 4. 출처 포맷팅
# ========================================
def format_source_citation(doc: Document) -> str:
    """
    출처 링크 포맷팅
    """
    title = doc.metadata.get('title', '영상')
    timestamp = doc.metadata.get('timestamp_str', '00:00')
    url = doc.metadata.get('url_full', '')
    
    if url:
        return f"[{title} ({timestamp})]({url})"
    else:
        return f"{title} ({timestamp})"


# ========================================
# 5. 웹 검색 (가격 정보)
# ========================================
def web_search_price(query: str) -> str:
    """
    실시간 가격 정보 웹 검색
    실제로는 Tavily, SerpAPI 등 사용
    여기서는 간단히 구현
    """
    # TODO: 실제 웹 검색 API 연동
    if any(keyword in query for keyword in ['가격', '비용', '얼마']):
        return """
최신 가격 정보 (2025년 12월 기준):
- 하이트래블: 100,000원
- 마이리얼트립: 110,000원대
- 와그: 110,000원대 (썬마스파 1시간 무료)

⚠️ 가격은 시즌과 프로모션에 따라 변동될 수 있습니다.
"""
    return ""


# ========================================
# 6. 할루시네이션 방지 프롬프트
# ========================================
ANTI_HALLUCINATION_PROMPT = """당신은 철산랜드 여행 정보 AI입니다.

[절대 규칙 - 위반 시 답변 무효]
1. ⛔ [Context]에 **명시적으로 적혀있지 않은** 내용은 **절대 작성 금지**
2. ⛔ "~같다", "~일 것이다", "보통 ~이다" 등의 **추론/추측 표현 금지**
3. ⛔ 일반적인 여행 상식을 **절대 추가하지 마세요**
4. ⛔ Context에 없는 구체적 프로그램/활동은 **절대 언급 금지**

[Context]
{context}

[질문]
{question}

[웹 검색 결과]
{web_search}

[답변 형식]
아래 형식으로 답변하세요:

## 🏰 철산랜드 여행 기록

(Context에 있는 내용만 사용)
- 각 정보 뒤에 출처 링크 필수: (출처: [영상 제목 타임스탬프](링크))
- Context에 없는 내용은 "기록에 없음" 명시
- 최소 5~10문장으로 상세히 작성
- 구체적 프로그램/활동은 Context에 명시된 것만 언급

---

## 🤖 AI 일반 지식 (참고용)

(일반적인 세부 호핑투어 정보)
- 일반적인 정보만 간단히
- "위 내용은 일반 정보이며, 철산랜드 기록 참고" 명시

---

## 🌐 최신 정보

{web_search}

---

## 📝 요약

- 철산랜드 기록: (핵심 내용)
- AI 일반 지식: (보충 설명)
- 최신 가격: (가격 정보)
"""

# Prompt Template
prompt_template = PromptTemplate(
    input_variables=["context", "question", "web_search"],
    template=ANTI_HALLUCINATION_PROMPT
)


# ========================================
# 7. RAG Chain
# ========================================
def generate_answer(query: str) -> Dict:
    """
    최종 답변 생성
    """
    # 1. 검색
    retrieved_docs = hybrid_search(query, k=30)
    
    # 2. Context 구성
    context_parts = []
    for i, doc in enumerate(retrieved_docs[:10], 1):  # 상위 10개만 사용
        source = format_source_citation(doc)
        content = doc.page_content[:500]  # 너무 길면 잘라내기
        context_parts.append(f"[Document {i}]\n출처: {source}\n내용: {content}\n")
    
    context = "\n".join(context_parts)
    
    # 3. 웹 검색 (가격 정보)
    web_search_result = web_search_price(query)
    
    # 4. LLM 호출
    chain = LLMChain(llm=llm, prompt=prompt_template)
    response = chain.run(
        context=context,
        question=query,
        web_search=web_search_result
    )
    
    return {
        "answer": response,
        "sources": [
            {
                "title": doc.metadata.get('title'),
                "timestamp": doc.metadata.get('timestamp_str'),
                "url": doc.metadata.get('url_full'),
            }
            for doc in retrieved_docs[:10]
        ],
        "search_stats": {
            "total_found": len(retrieved_docs),
            "used_in_context": 10
        }
    }


# ========================================
# 8. API Endpoints
# ========================================
class ChatRequest(BaseModel):
    query: str

class ChatResponse(BaseModel):
    answer: str
    sources: List[Dict]
    search_stats: Dict

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    """
    채팅 API
    """
    try:
        result = generate_answer(request.query)
        return ChatResponse(**result)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/")
async def root():
    return {"message": "Iron Land Travel AI is running!"}


# ========================================
# MAIN
# ========================================
if __name__ == "__main__":
    import uvicorn
    
    print("🚀 Starting Iron Land Travel AI...")
    print("📍 Server: http://localhost:8000")
    print("📖 Docs: http://localhost:8000/docs")
    
    uvicorn.run(app, host="0.0.0.0", port=8000)
