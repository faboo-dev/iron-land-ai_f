import os
import json
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain_chroma import Chroma
from langchain_core.documents import Document
from langchain.text_splitter import RecursiveCharacterTextSplitter
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Configuration
DATA_DIR = "./data"
PERSIST_DIRECTORY = "./chroma_db"

def load_json_documents(data_dir):
    documents = []
    if not os.path.exists(data_dir):
        print(f"Data directory {data_dir} does not exist.")
        return []

    for filename in os.listdir(data_dir):
        if filename.endswith(".json"):
            filepath = os.path.join(data_dir, filename)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    
                    if isinstance(data, list):
                        for item in data:
                            content = item.get('raw_content', '')
                            if not content:
                                continue
                                
                            metadata = {
                                "source": filename,
                                "title": item.get('title', ''),
                                "url": item.get('url_full', ''),
                                "timestamp": item.get('timestamp_str', ''),
                                "id": item.get('id', '')
                            }
                            documents.append(Document(page_content=content, metadata=metadata))
                            if "썬마호핑" in content:
                                print(f"✅ Found '썬마호핑' in item {item.get('id')} during loading.")
                            
                    elif isinstance(data, dict):
                        content = data.get('raw_content', '')
                        if content:
                            metadata = {
                                "source": filename,
                                "title": data.get('title', ''),
                                "url": data.get('url_full', ''),
                                "timestamp": data.get('timestamp_str', ''),
                                "id": data.get('id', '')
                            }
                            documents.append(Document(page_content=content, metadata=metadata))
                        
            except Exception as e:
                print(f"Error loading {filename}: {e}")
    return documents

def ingest_data():
    if not os.getenv("GOOGLE_API_KEY"):
        print("❌ Error: GOOGLE_API_KEY not found in .env file.")
        return

    print(f"Loading data from {DATA_DIR}...")
    docs = load_json_documents(DATA_DIR)
    
    if not docs:
        print("No documents found to ingest.")
        return

    print(f"Found {len(docs)} documents. Splitting into chunks for better retrieval...")
    
    # Debug: Print first document length
    if docs:
        print(f"First doc length: {len(docs[0].page_content)}")
        print(f"First doc content preview: {docs[0].page_content[:100]}...")

    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=500, # Reduce chunk size to force splitting
        chunk_overlap=100,
        separators=["\n\n", "\n", " ", ""]
    )
    split_docs = text_splitter.split_documents(docs)
    
    print(f"Created {len(split_docs)} chunks from {len(docs)} documents.")
    if split_docs:
        print(f"First chunk length: {len(split_docs[0].page_content)}")
    
    # Use Google Gemini Embeddings
    embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
    
    vectorstore = Chroma.from_documents(
        documents=split_docs,
        embedding=embeddings,
        persist_directory=PERSIST_DIRECTORY,
        collection_name="travel_knowledge_base"
    )
    
    print(f"Successfully ingested {len(split_docs)} chunks into {PERSIST_DIRECTORY}")
    
    print(f"Successfully ingested {len(docs)} documents into {PERSIST_DIRECTORY}")

if __name__ == "__main__":
    ingest_data()
