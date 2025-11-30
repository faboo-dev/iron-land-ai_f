from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import os
from dotenv import load_dotenv
from langchain_google_genai import GoogleGenerativeAIEmbeddings, ChatGoogleGenerativeAI
from langchain_chroma import Chroma
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables import RunnablePassthrough, RunnableLambda
from langchain_core.documents import Document

# Load environment variables
load_dotenv()

app = FastAPI()

# Configuration
PERSIST_DIRECTORY = "./chroma_db"
DATA_DIR = "./data"

# Initialize Embeddings
embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")

# Load Vector Store
vectorstore = Chroma(
    persist_directory=PERSIST_DIRECTORY,
    embedding_function=embeddings,
    collection_name="travel_knowledge_base"
)

retriever = vectorstore.as_retriever(search_kwargs={"k": 10})

# Initialize LLM
llm = ChatGoogleGenerativeAI(
    model="gemini-2.0-flash",
    temperature=0.7,
    max_tokens=2048,
    max_retries=2,
)

# Load all documents for Keyword Search
print("📚 Loading documents for Keyword Search...")
all_docs_data = vectorstore.get()
all_contents = all_docs_data['documents']
all_metadatas = all_docs_data['metadatas']

cached_docs = []
for i, content in enumerate(all_contents):
    metadata = all_metadatas[i] if all_metadatas else {}
    cached_docs.append(Document(page_content=content, metadata=metadata))

print(f"✅ Loaded {len(cached_docs)} documents for Keyword Search.")


# ==================== 출처 링크 포맷팅 ====================

def format_source_citation(metadata):
    """사용자 친화적인 출처 링크 생성"""
    
    source_type = metadata.get('source_type', 'unknown')
    title = metadata.get('title', '제목 없음')
    
    if source_type == 'youtube':
        url = metadata.get('url_full', '')
        timestamp = metadata.get('timestamp_str', '')
        
        if timestamp and url:
            return f"📺 [(영상 보기 - {timestamp})]({url})"
        elif url:
            return f"📺 [(영상 보기)]({url})"
        else:
            return f"📺 {title}"
    
    elif source_type in ['blog', 'naver_blog']:
        url = metadata.get('url', metadata.get('original_url', ''))
        if url:
            return f"📝 [(블로그 글 보기)]({url})"
        else:
            return f"📝 {title}"
    
    else:
        url = metadata.get('url', metadata.get('original_url', ''))
        if url:
            return f"🔗 [(자세히 보기)]({url})"
        else:
            return f"📄 {title}"


def format_docs_with_sources(docs):
    """문서 내용 + 출처 링크 포맷팅"""
    
    if not docs:
        return "관련 정보를 찾을 수 없습니다."
    
    formatted_parts = []
    
    for i, doc in enumerate(docs[:5], 1):  # 최대 5개만
        content = doc.page_content
        metadata = doc.metadata
        
        # 출처 링크 생성
        source_citation = format_source_citation(metadata)
        
        # 포맷팅
        formatted = f"""[참고자료 {i}]
{content}

출처: {source_citation}
"""
        formatted_parts.append(formatted)
    
    return "\n\n━━━━━━━━━━━━━━━━━━━━━━━\n\n".join(formatted_parts)


# ==================== Hybrid Retrieval ====================

def retrieve_combined(query):
    """하이브리드 검색: 키워드 + 벡터"""
    
    print(f"🔍 Searching for: '{query}'")
    
    # 1. Keyword Search (정확한 매칭)
    keyword_docs = []
    for doc in cached_docs:
        if query in doc.page_content:
            keyword_docs.append(doc)
    
    print(f"✅ Keyword Search found: {len(keyword_docs)} documents")
    
    # 2. Vector Search (의미 기반)
    vector_docs = retriever.invoke(query)
    print(f"✅ Vector Search found: {len(vector_docs)} documents")
    
    # 3. Combine & Deduplicate
    seen_content = set()
    final_docs = []
    
    # 키워드 매칭 우선
    for doc in keyword_docs:
        if doc.page_content not in seen_content:
            final_docs.append(doc)
            seen_content.add(doc.page_content)
    
    # 벡터 검색 결과 추가
    for doc in vector_docs:
        if doc.page_content not in seen_content:
            final_docs.append(doc)
            seen_content.add(doc.page_content)
    
    print(f"✅ Final combined results: {len(final_docs[:10])} documents")
    
    return final_docs[:10]


# ==================== Prompt Template ====================

template = """당신은 '철산랜드(Iron Land)'의 친근한 여행 가이드 AI입니다.

제공된 정보:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{context}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

사용자 질문: {question}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
답변 작성 가이드:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

자연스러운 대화체로 아래 구조로 답변하세요:

**[인사말]**
"안녕하세요! [질문 내용]에 대해 알려드릴게요 😊"

**[🏰 제 여행 경험에서]**
━━━━━━━━━━━━━━━━━━━━━━━

위 Context에서 찾은 정보를 자연스럽게 설명하세요.
- **중요**: 각 정보 뒤에 출처 링크를 반드시 포함
  예시: "썬마호핑은 가족 여행에 좋습니다. [(영상 보기 - 51:23)](링크)"
- Context에 관련 정보가 없으면:
  "제 여행 기록에는 이 부분에 대한 구체적인 정보가 없네요."

**[💡 추가로 알면 좋은 정보]**
━━━━━━━━━━━━━━━━━━━━━━━

일반적인 배경 지식이나 여행 팁을 제공하세요.
(Context에 없어도 여행 상식으로 알려줄 수 있는 내용)

**[⚠️ 참고하세요]**
━━━━━━━━━━━━━━━━━━━━━━━

• 가격, 영업시간 등은 시즌/환율에 따라 변동될 수 있어요
• 예약하시기 전에 최신 정보를 확인하시는 것을 추천드려요
• 궁금한 점이 더 있으시면 말씀해주세요! 🙋‍♂️

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
출처 표시 규칙 (매우 중요):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ 좋은 예:
"썬마호핑은 가족 단위 여행객에게 추천드려요. [(영상 보기 - 51:23)](https://youtube.com/...)"

❌ 나쁜 예:
"썬마호핑은 가족 단위에 좋습니다. (출처: 202507_youtube_cebu_hopping_002.json)"

출처는 반드시:
1. 유튜브 링크 + 타임스탬프 또는
2. 블로그 원문 링크로 표시

파일명(.json)은 절대 표시하지 마세요!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

친절하고 따뜻한 톤으로 작성하되, 모르는 건 솔직하게 모른다고 말하세요!
"""

prompt = ChatPromptTemplate.from_template(template)


# ==================== RAG Chain ====================

rag_chain = (
    {
        "context": RunnableLambda(retrieve_combined) | RunnableLambda(format_docs_with_sources),
        "question": RunnablePassthrough()
    }
    | prompt
    | llm
    | StrOutputParser()
)


# ==================== API Endpoints ====================

@app.get("/")
def read_root():
    return {
        "status": "ok", 
        "message": "Iron Land Travel AI (Enhanced with Source Links)",
        "version": "2.0"
    }


class ChatRequest(BaseModel):
    query: str
    history: Optional[List[dict]] = None


class ChatResponse(BaseModel):
    answer: str
    sources: List[dict]


@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    try:
        print(f"\n{'='*60}")
        print(f"📨 New query: {request.query}")
        print(f"{'='*60}")
        
        # Retrieve documents
        docs = retrieve_combined(request.query)
        
        # Generate answer
        answer = rag_chain.invoke(request.query)
        
        # Extract sources with full metadata
        sources = []
        for doc in docs[:5]:  # 최대 5개
            source_info = {
                "source_type": doc.metadata.get("source_type", "unknown"),
                "title": doc.metadata.get("title", ""),
                "url": doc.metadata.get("url_full", doc.metadata.get("url", "")),
                "url_full": doc.metadata.get("url_full", ""),
                "original_url": doc.metadata.get("original_url", ""),
                "timestamp_str": doc.metadata.get("timestamp_str", ""),
            }
            sources.append(source_info)
        
        print(f"✅ Answer generated with {len(sources)} sources")
        
        return ChatResponse(answer=answer, sources=sources)
    
    except Exception as e:
        print(f"❌ Error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


if __name__ == "__main__":
    import uvicorn
    print("\n" + "="*60)
    print("🏰 Iron Land Travel AI Server Starting...")
    print("="*60 + "\n")
    uvicorn.run(app, host="0.0.0.0", port=8000)
