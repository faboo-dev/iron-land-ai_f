import os
from langchain_chroma import Chroma
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain_community.retrievers import BM25Retriever
from langchain.retrievers import EnsembleRetriever
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

PERSIST_DIRECTORY = "./chroma_db"

def test_hybrid(query):
    print(f"Testing Hybrid Retrieval for query: '{query}'")
    
    try:
        embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
        vectorstore = Chroma(
            persist_directory=PERSIST_DIRECTORY,
            embedding_function=embeddings,
            collection_name="travel_knowledge_base"
        )
        
        # 1. Setup Chroma Retriever
        chroma_retriever = vectorstore.as_retriever(search_kwargs={"k": 10})
        
        # 2. Setup Keyword Retriever (Substring Match)
        print("Fetching all documents from Chroma for Keyword Search...")
        all_docs = vectorstore.get()['documents']
        metadatas = vectorstore.get()['metadatas']
        
        from langchain_core.documents import Document
        docs_objects = []
        for i, content in enumerate(all_docs):
            docs_objects.append(Document(page_content=content, metadata=metadatas[i] if metadatas else {}))
            
        print(f"Loaded {len(docs_objects)} documents for Keyword Search.")
        
        # Simple Substring Search
        keyword_docs = []
        for doc in docs_objects:
            if query in doc.page_content:
                keyword_docs.append(doc)
                
        print(f"Keyword Search found {len(keyword_docs)} documents containing '{query}'.")
        
        # Combine results (Keyword first, then Vector)
        # Deduplicate by content
        seen_content = set()
        final_docs = []
        
        for doc in keyword_docs:
            if doc.page_content not in seen_content:
                final_docs.append(doc)
                seen_content.add(doc.page_content)
                
        # Add vector results if needed (up to k=10 total)
        vector_docs = chroma_retriever.invoke(query)
        for doc in vector_docs:
            if doc.page_content not in seen_content:
                final_docs.append(doc)
                seen_content.add(doc.page_content)
                
        docs = final_docs[:10]
        
        print(f"Found {len(docs)} documents:")
        found = False
        for i, doc in enumerate(docs):
            print(f"\n--- Document {i+1} ---")
            print(f"Source: {doc.metadata.get('source', 'Unknown')}")
            print(f"Content Preview: {doc.page_content[:200]}...")
            if "썬마호핑" in doc.page_content:
                print("✅ Found '썬마호핑' in this document!")
                found = True
        
        if not found:
            print("❌ '썬마호핑' NOT found in top results.")
            
    except Exception as e:
        print(f"Error during retrieval: {e}")

if __name__ == "__main__":
    test_hybrid("썬마호핑")
