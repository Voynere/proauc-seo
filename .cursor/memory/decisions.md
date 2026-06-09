# Decisions

## 2026-06-09 — Repository setup

- Production imported via `tar` over SSH (no `rsync` on server).
- GitHub repo `proauc-seo` created; `assets/` and `api/` excluded from Git due to GitHub 2 GB pack limit.
- Agent RAG memory lives in `.cursor/memory/` and `.cursor/knowledge/`, indexed by `.cursor/rag/rag.py`.
