---
name: agent-memory
description: >-
  Search and update proauc-seo RAG memory (project facts, SEO, server, theme).
  Use when starting work on proauc.ru, when context from past sessions is needed,
  or when saving decisions for future agents.
---

# Agent Memory (proauc-seo)

## Query memory

```bash
python3 .cursor/rag/rag.py query "Rank Math catalog SEO"
python3 .cursor/rag/rag.py query "SSH deploy proauc.ru"
```

## Save to memory

```bash
python3 .cursor/rag/rag.py remember "Описание решения" --tag seo
```

## Rebuild index

```bash
python3 .cursor/rag/rag.py index
python3 .cursor/rag/rag.py status
```

## Sources

- `.cursor/memory/` — накопленные факты и решения (journal по тегам)
- `.cursor/knowledge/` — структурированная документация проекта
- Key code files indexed automatically (rank-math.php, functions.php, robots.txt)

See also [agent-memory rule](../rules/agent-memory.mdc).
