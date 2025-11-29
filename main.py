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

# Initialize LLM (Google Gemini Pro)
llm = ChatGoogleGenerativeAI(
    model="gemini-1.5-flash-001",
    temperature=0.7,
    max_tokens=1024,
    max_retries=2,
)

# RAG Prompt
template = """Answer the question based only on the following context:
{context}

Question: {question}

If the context doesn't contain the answer, say "I don't have specific information about that in my knowledge base, but here is a general answer based on my training:" and then provide a general answer.
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
