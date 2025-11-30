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
    """JSON 파일에서 문서 로드"""
    documents = []
    if not os.path.exists(data_dir):
        print(f"❌ Data directory {data_dir} does not exist.")
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
                            
                            # 메타데이터에 모든 정보 포함 (유튜브 링크, 타임스탬프 등)
                            metadata = {
                                "source": filename,
                                "source_type": item.get('source_type', 'unknown'),
                                "title": item.get('title', ''),
                                "url": item.get('url_full', ''),
                                "url_full": item.get('url_full', ''),
                                "original_url": item.get('original_url', ''),
                                "timestamp_str": item.get('timestamp_str', ''),
                                "timestamp_seconds": item.get('timestamp_seconds', 0),
                                "id": item.get('id', '')
                            }
                            
                            documents.append(Document(page_content=content, metadata=metadata))
                            
                            # 디버깅: 특정 키워드 확인
                            if "썬마호핑" in content or "선마호핑" in content:
                                print(f"✅ Found '썬마호핑' in {filename} (ID: {item.get('id')})")
                    
                    elif isinstance(data, dict):
                        content = data.get('raw_content', '')
                        if content:
                            metadata = {
                                "source": filename,
                                "source_type": data.get('source_type', 'unknown'),
                                "title": data.get('title', ''),
                                "url": data.get('url_full', ''),
                                "url_full": data.get('url_full', ''),
                                "original_url": data.get('original_url', ''),
                                "timestamp_str": data.get('timestamp_str', ''),
                                "timestamp_seconds": data.get('timestamp_seconds', 0),
                                "id": data.get('id', '')
                            }
                            documents.append(Document(page_content=content, metadata=metadata))
                        
            except Exception as e:
                print(f"❌ Error loading {filename}: {e}")
    
    return documents

def ingest_data():
    """데이터 인제스트 메인 함수"""
    
    if not os.getenv("GOOGLE_API_KEY"):
        print("❌ Error: GOOGLE_API_KEY not found in .env file.")
        return

    print(f"📂 Loading data from {DATA_DIR}...")
    docs = load_json_documents(DATA_DIR)
    
    if not docs:
        print("❌ No documents found to ingest.")
        return

    print(f"✅ Found {len(docs)} documents.")
    
    # 텍스트 분할 - 개선된 설정
    print("✂️ Splitting documents into chunks...")
    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=1500,        # 500 → 1500 (3배 증가)
        chunk_overlap=300,      # 100 → 300 (3배 증가)
        separators=["\n\n", "\n", ". ", " ", ""],  # 문장 단위 우선
        length_function=len,
    )
    
    split_docs = text_splitter.split_documents(docs)
    
    print(f"✅ Created {len(split_docs)} chunks from {len(docs)} documents.")
    
    if split_docs:
        print(f"📏 First chunk length: {len(split_docs[0].page_content)} characters")
        print(f"📝 First chunk preview: {split_docs[0].page_content[:100]}...")
    
    # 벡터 임베딩 생성
    print("🔮 Creating embeddings with Google Gemini...")
    embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
    
    # Chroma DB에 저장
    print("💾 Saving to ChromaDB...")
    vectorstore = Chroma.from_documents(
        documents=split_docs,
        embedding=embeddings,
        persist_directory=PERSIST_DIRECTORY,
        collection_name="travel_knowledge_base"
    )
    
    print(f"✅ Successfully ingested {len(split_docs)} chunks into {PERSIST_DIRECTORY}")
    print(f"📊 Total documents in collection: {vectorstore._collection.count()}")
    
    # 테스트: "썬마호핑" 검색
    print("\n🔍 Testing search for '썬마호핑'...")
    test_results = vectorstore.similarity_search("썬마호핑", k=3)
    print(f"Found {len(test_results)} results:")
    for i, doc in enumerate(test_results, 1):
        print(f"\n--- Result {i} ---")
        print(f"Content: {doc.page_content[:150]}...")
        print(f"Source: {doc.metadata.get('source')}")
        if "썬마호핑" in doc.page_content or "선마호핑" in doc.page_content:
            print("✅ Contains '썬마호핑'!")

if __name__ == "__main__":
    print("=" * 60)
    print("🏰 Iron Land AI - Data Ingestion (Enhanced)")
    print("=" * 60)
    ingest_data()
    print("\n✨ Ingestion complete!")
