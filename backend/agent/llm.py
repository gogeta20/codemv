import os


OLLAMA_HOST = os.getenv("OLLAMA_HOST", "127.0.0.1:11434")
OLLAMA_CHAT_URL = os.getenv("OLLAMA_CHAT_URL", f"http://{OLLAMA_HOST}/api/chat")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "gemma3:1b")
