import os
from langchain_chroma import Chroma
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

PERSIST_DIRECTORY = "./chroma_db"

def test_retrieval(query):
    print(f"Testing retrieval for query: '{query}'")
    
    if not os.path.exists(PERSIST_DIRECTORY):
        print(f"Error: Persistence directory {PERSIST_DIRECTORY} does not exist.")
        return

    try:
        embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
        vectorstore = Chroma(
            persist_directory=PERSIST_DIRECTORY,
            embedding_function=embeddings,
            collection_name="travel_knowledge_base"
        )
        
        # retriever = vectorstore.as_retriever(search_kwargs={"k": 5})
        # docs = retriever.invoke(query)
        
        print(f"Total documents in collection: {vectorstore._collection.count()}")
        
        # docs_and_scores = vectorstore.similarity_search_with_score(query, k=20)
        docs_and_scores = vectorstore.similarity_search_with_score("썬마호핑", k=50)
        
        print(f"Found {len(docs_and_scores)} documents for query '썬마호핑':")
        for i, (doc, score) in enumerate(docs_and_scores):
            if "썬마호핑" in doc.page_content:
                print(f"\n--- Document {i+1} (Score: {score:.4f}) ---")
                print(f"Source: {doc.metadata.get('source', 'Unknown')}")
                print(f"Content Preview: {doc.page_content[:200]}...")
                print("✅ Found '썬마호핑' in this document!")
            
    except Exception as e:
        print(f"Error during retrieval: {e}")

if __name__ == "__main__":
    test_retrieval("썬마호핑")
