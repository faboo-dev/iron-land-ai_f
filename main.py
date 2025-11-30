from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import os
from dotenv import load_dotenv
from langchain_google_genai import GoogleGenerativeAIEmbeddings, ChatGoogleGenerativeAI
from langchain_chroma import Chroma
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables import RunnablePassthrough

# Load environment variables
load_dotenv()

app = FastAPI()

# Initialize Vector DB (Chroma) with Gemini Embeddings
PERSIST_DIRECTORY = "./chroma_db"
embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")

vectorstore = Chroma(
    persist_directory=PERSIST_DIRECTORY,
    embedding_function=embeddings,
    collection_name="travel_knowledge_base"
)

retriever = vectorstore.as_retriever(search_kwargs={"k": 10})

import google.generativeai as genai

# Configure GenAI
genai.configure(api_key=os.getenv("GOOGLE_API_KEY"))

# Initialize LLM (Google Gemini)
llm = ChatGoogleGenerativeAI(
    model="gemini-2.0-flash",
    temperature=0.7,
    max_tokens=1024,
    max_retries=2,
)

@app.on_event("startup")
async def startup_event():
    print("Listing available models...")
    try:
        for m in genai.list_models():
            if 'generateContent' in m.supported_generation_methods:
                print(f"Found model: {m.name}")
    except Exception as e:
        print(f"Error listing models: {e}")

# RAG Prompt
template = """당신은 '철산랜드(Iron Land)'의 AI 어시스턴트입니다.
사용자의 질문에 대해 아래 제공된 [Context]를 바탕으로 답변을 작성하세요.

[Context]:
{context}

[Question]:
{question}

[Guidelines]:
1. **핵심 규칙**: 질문에 있는 **구체적인 대상(예: 해적호핑, 썬마호핑 등)**이 [Context]에 포함되어 있지 않다면, 절대 억지로 답변하지 마세요.
   - [Context]에 해당 대상에 대한 언급이 없다면, **"철산랜드 기록에는 '{question}'에 대한 구체적인 정보가 없습니다."**라고 명확히 말하세요.
   - 엉뚱한 정보(예: 고래상어 투어 등)를 섞어서 답변하지 마세요.

2. **답변 구조**:
   **[🏰 철산랜드 기록]**
   - [Context]에서 질문과 **정확히 일치하는 정보**만 찾아 답변하세요.
   - 답변 중간중간에 (출처: 파일명)을 명시하세요.
   - 정보가 없으면 솔직하게 없다고 말하세요.

   **[🤖 AI 크로스체크]**
   - [철산랜드 기록]에 정보가 부족하거나 없을 경우, 당신의 일반적인 지식을 활용하여 보충 설명을 해주세요.
   - **가격이나 최신 정보**는 "이 정보는 변동될 수 있으니 최신 정보를 검색해보시는 것을 추천합니다."라고 덧붙여주세요.
   - 만약 [철산랜드 기록]에 정보가 아예 없다면, 여기서 당신이 아는 내용을 최대한 상세히 설명해주세요.

3. **톤앤매너**: 친절하고 전문적인 태도를 유지하세요.
"""
prompt = ChatPromptTemplate.from_template(template)

# Load all documents for Keyword Search (Substring Match)
print("Loading all documents for Keyword Search...")
all_docs_data = vectorstore.get()
all_contents = all_docs_data['documents']
all_metadatas = all_docs_data['metadatas']

from langchain_core.documents import Document
cached_docs = []
for i, content in enumerate(all_contents):
    metadata = all_metadatas[i] if all_metadatas else {}
    cached_docs.append(Document(page_content=content, metadata=metadata))
print(f"Loaded {len(cached_docs)} documents for Keyword Search.")

def retrieve_combined(query):
    # 1. Keyword Search (Substring)
    keyword_docs = []
    # Simple heuristic: only do substring search if query is short enough to be a keyword
    # or just always do it. Always do it for robustness.
    for doc in cached_docs:
        if query in doc.page_content:
            keyword_docs.append(doc)
    
    # 2. Vector Search
    vector_docs = retriever.invoke(query)
    
    # 3. Combine & Deduplicate
    seen_content = set()
    final_docs = []
    
    # Prioritize keyword matches
    for doc in keyword_docs:
        if doc.page_content not in seen_content:
            final_docs.append(doc)
            seen_content.add(doc.page_content)
            
    # Add vector matches
    for doc in vector_docs:
        if doc.page_content not in seen_content:
            final_docs.append(doc)
            seen_content.add(doc.page_content)
    
    # Limit to k=10
    return final_docs[:10]

from langchain_core.runnables import RunnableLambda

# RAG Chain
def format_docs(docs):
    formatted_docs = []
    for d in docs:
        source = d.metadata.get('source', 'Unknown')
        content = d.page_content
        formatted_docs.append(f"Source: {source}\nContent: {content}")
    return "\n\n".join(formatted_docs)

rag_chain = (
    {"context": RunnableLambda(retrieve_combined) | format_docs, "question": RunnablePassthrough()}
    | prompt
    | llm
    | StrOutputParser()
)

class ChatRequest(BaseModel):
    query: str
    history: Optional[List[dict]] = None

class ChatResponse(BaseModel):
    answer: str
    sources: List[dict]

@app.get("/")
def read_root():
    return {"status": "ok", "message": "Travel RAG API is running (Gemini Powered)"}

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    try:
        # Retrieve documents
        docs = retriever.invoke(request.query)
        
        # Generate answer
        answer = rag_chain.invoke(request.query)
        
        # Extract sources with details
        sources = []
        for doc in docs:
            sources.append({
                "source": doc.metadata.get("source", "Unknown"),
                "title": doc.metadata.get("title", ""),
                "url": doc.metadata.get("url", ""),
                "timestamp": doc.metadata.get("timestamp", "")
            })
        
        return ChatResponse(answer=answer, sources=sources)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
