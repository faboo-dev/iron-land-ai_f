import os
from typing import List, Dict
from difflib import SequenceMatcher
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from langchain_google_genai import GoogleGenerativeAIEmbeddings, ChatGoogleGenerativeAI
from langchain_community.vectorstores import Chroma
from langchain_core.documents import Document
from langchain_core.prompts import PromptTemplate
from dotenv import load_dotenv

# 환경변수 로드
load_dotenv()

# FastAPI 앱
app = FastAPI(title="Iron Land Travel AI")

# Vector Store & LLM 초기화 (지연 로딩)
embeddings = None
vectorstore = None
llm = None

def init_services():
    """서비스 초기화 (첫 요청 시)"""
    global embeddings, vectorstore, llm
    
    if embeddings is None:
        print("🔄 Initializing embeddings...")
        embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
    
    if llm is None:
        print("🔄 Initializing LLM...")
        llm = ChatGoogleGenerativeAI(
            model="gemini-2.0-flash-exp",
            temperature=0.3,
            max_tokens=2048
        )
    
    if vectorstore is None:
        print("🔄 Initializing vector store...")
        persist_dir = "./chroma_db"
        
        if not os.path.exists(persist_dir):
            print(f"⚠️  Warning: {persist_dir} not found.")
            print("📝 Creating empty vector store...")
            
            dummy_docs = [
                Document(
                    page_content="데이터베이스를 초기화해주세요. ingest.py를 먼저 실행하세요.",
                    metadata={"id": "system_init", "title": "System"}
                )
            ]
            vectorstore = Chroma.from_documents(
                documents=dummy_docs,
                embedding=embeddings,
                collection_name="travel_knowledge_base"
            )
        else:
            vectorstore = Chroma(
                persist_directory=persist_dir,
                embedding_function=embeddings,
                collection_name="travel_knowledge_base"
            )
            print("✅ Vector store loaded")

# 키워드 정규화
KEYWORD_NORMALIZATION = {
    "썬마호핑": ["썬마호핑", "선마호핑", "썬마", "선마", "써마", "섬마"],
    "해적호핑": ["해적호핑", "해적", "해저핑"],
    "클럽세부": ["클럽세부", "클럽", "세부클럽"],
    "한바다": ["한바다", "한 바다"],
}

def normalize_keywords(text: str) -> List[str]:
    """키워드 정규화"""
    normalized = []
    text_lower = text.lower()
    
    for base_keyword, variants in KEYWORD_NORMALIZATION.items():
        for variant in variants:
            if variant.lower() in text_lower:
                normalized.extend(variants)
                break
    
    basic_keywords = text.split()
    stopwords = ["에", "대해", "알려줘", "뭐야", "어때"]
    basic_keywords = [w for w in basic_keywords if w not in stopwords and len(w) > 1]
    
    normalized.extend(basic_keywords)
    return list(set(normalized))

def hybrid_search(query: str, k: int = 30) -> List[Document]:
    """하이브리드 검색"""
    init_services()
    
    keywords = normalize_keywords(query)
    print(f"🔍 Keywords: {keywords}")
    
    vector_results = vectorstore.similarity_search(query, k=k)
    
    keyword_results = []
    all_docs = vectorstore.get()
    
    if all_docs and 'metadatas' in all_docs and 'documents' in all_docs:
        for metadata, content in zip(all_docs['metadatas'], all_docs['documents']):
            doc_keywords = metadata.get('keywords', [])
            if any(kw in doc_keywords for kw in keywords) or any(kw in content for kw in keywords):
                keyword_results.append(Document(
                    page_content=content,
                    metadata=metadata
                ))
    
    combined = []
    seen_ids = set()
    
    for doc_list in [keyword_results, vector_results]:
        for doc in doc_list:
            doc_id = doc.metadata.get('id')
            if doc_id and doc_id not in seen_ids:
                combined.append(doc)
                seen_ids.add(doc_id)
    
    return combined[:20]

PROMPT_TEMPLATE = """당신은 철산랜드 여행 정보 AI입니다.

[절대 규칙]
1. [Context]에 명시되지 않은 내용은 절대 작성 금지
2. 추론/추측 표현 금지
3. 각 문장마다 출처 링크 필수

[Context]
{context}

[질문]
{question}

[답변 형식]
## 철산랜드 여행 기록
(Context 기반 답변, 출처 링크 포함)

## AI 일반 지식
(일반적인 여행 정보)
"""

def generate_answer(query: str) -> Dict:
    """답변 생성"""
    init_services()
    
    retrieved_docs = hybrid_search(query, k=30)
    
    context_parts = []
    for i, doc in enumerate(retrieved_docs[:10], 1):
        title = doc.metadata.get('title', '영상')
        timestamp = doc.metadata.get('timestamp_str', '00:00')
        url = doc.metadata.get('url_full', '')
        content = doc.page_content[:500]
        
        source = f"[{title} ({timestamp})]({url})" if url else f"{title} ({timestamp})"
        context_parts.append(f"[Document {i}]\n출처: {source}\n내용: {content}\n")
    
    context = "\n".join(context_parts)
    
    prompt = PromptTemplate(
        input_variables=["context", "question"],
        template=PROMPT_TEMPLATE
    )
    
    final_prompt = prompt.format(context=context, question=query)
    response = llm.invoke(final_prompt)
    
    return {
        "answer": response.content,
        "sources": [
            {
                "title": doc.metadata.get('title'),
                "timestamp": doc.metadata.get('timestamp_str'),
                "url": doc.metadata.get('url_full'),
            }
            for doc in retrieved_docs[:10]
        ]
    }

class ChatRequest(BaseModel):
    query: str

class ChatResponse(BaseModel):
    answer: str
    sources: List[Dict]

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    """채팅 API"""
    try:
        result = generate_answer(request.query)
        return ChatResponse(**result)
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/")
async def root():
    """헬스체크"""
    return {
        "message": "Iron Land Travel AI is running!",
        "status": "healthy"
    }

@app.get("/health")
async def health():
    """상세 헬스체크"""
    init_services()
    
    return {
        "status": "healthy",
        "embeddings": "initialized" if embeddings else "not initialized",
        "llm": "initialized" if llm else "not initialized",
        "vectorstore": "initialized" if vectorstore else "not initialized"
    }

if __name__ == "__main__":
    import uvicorn
    print("🚀 Starting Iron Land Travel AI...")
    uvicorn.run(app, host="0.0.0.0", port=8000)
