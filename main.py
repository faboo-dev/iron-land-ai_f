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

retriever = vectorstore.as_retriever(search_kwargs={"k": 3})

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
사용자의 질문에 대해 아래 제공된 [Context]를 바탕으로 친절하고 상세하게 한국어로 답변해주세요.

[Context]:
{context}

[Question]:
{question}

[Guidelines]:
1. **반드시 한국어로 답변하세요.**
2. 제공된 [Context]에 있는 내용을 최우선으로 사용하여 답변하세요.
3. [Context]에 답변에 필요한 정보가 부족하다면, 당신의 일반적인 지식을 활용하여 답변하되, "철산랜드 기록에는 없지만, 일반적인 정보로는..."이라고 언급해주세요.
4. 답변은 친절하고 전문적인 톤으로 작성하세요.
5. 답변 끝에는 항상 도움이 되었기를 바라는 멘트를 추가하세요.
"""
prompt = ChatPromptTemplate.from_template(template)

# RAG Chain
def format_docs(docs):
    return "\n\n".join([d.page_content for d in docs])

rag_chain = (
    {"context": retriever | format_docs, "question": RunnablePassthrough()}
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
