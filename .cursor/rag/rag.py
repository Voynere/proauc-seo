#!/usr/bin/env python3
"""Local RAG memory for the proauc-seo Cursor agent. Stdlib only (SQLite FTS5)."""

from __future__ import annotations

import argparse
import hashlib
import re
import sqlite3
import sys
from datetime import datetime, timezone
from pathlib import Path

RAG_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = RAG_DIR.parents[1]
STORE = RAG_DIR / "store.sqlite"
MEMORY_DIR = RAG_DIR.parent / "memory"
KNOWLEDGE_DIR = RAG_DIR.parent / "knowledge"

CODE_PATHS = (
    "README.md",
    "robots.txt",
    "wp-content/themes/proautospec/rank-math.php",
    "wp-content/themes/proautospec/functions.php",
    "wp-content/themes/proautospec/style.css",
)

CHUNK_SIZE = 900


def init_db(conn: sqlite3.Connection) -> None:
    conn.execute(
        """
        CREATE VIRTUAL TABLE IF NOT EXISTS chunks USING fts5(
            source UNINDEXED,
            category UNINDEXED,
            title,
            content,
            tokenize='unicode61 remove_diacritics 2'
        )
        """
    )


def split_query_terms(query: str) -> str:
    terms = re.findall(r"[\w\u0400-\u04FF]+", query.lower())
    if not terms:
        return query
    return " OR ".join(f'"{t}"' for t in terms if len(t) > 1)


def chunk_text(text: str, source: str, category: str, max_chars: int = CHUNK_SIZE) -> list[tuple]:
    text = text.strip()
    if not text:
        return []

    sections: list[tuple[str, str]] = []
    current_title = Path(source).stem
    current_lines: list[str] = []

    for line in text.splitlines():
        if re.match(r"^#{1,3}\s+", line):
            if current_lines:
                sections.append((current_title, "\n".join(current_lines).strip()))
            current_title = re.sub(r"^#{1,3}\s+", "", line).strip()
            current_lines = [line]
        else:
            current_lines.append(line)

    if current_lines:
        sections.append((current_title, "\n".join(current_lines).strip()))

    chunks: list[tuple] = []
    for title, body in sections:
        if len(body) <= max_chars:
            chunks.append((source, category, title, body))
            continue
        start = 0
        part = 1
        while start < len(body):
            piece = body[start : start + max_chars]
            chunks.append((source, category, f"{title} ({part})", piece))
            start += max_chars
            part += 1
    return chunks


def collect_sources() -> list[tuple[Path, str]]:
    sources: list[tuple[Path, str]] = []
    for base, category in ((MEMORY_DIR, "memory"), (KNOWLEDGE_DIR, "knowledge")):
        if not base.exists():
            continue
        for path in sorted(base.rglob("*.md")):
            sources.append((path, category))
    for rel in CODE_PATHS:
        path = PROJECT_ROOT / rel
        if path.exists():
            sources.append((path, "code"))
    return sources


def index_all() -> int:
    MEMORY_DIR.mkdir(parents=True, exist_ok=True)
    KNOWLEDGE_DIR.mkdir(parents=True, exist_ok=True)

    conn = sqlite3.connect(STORE)
    init_db(conn)
    conn.execute("DELETE FROM chunks")

    total = 0
    for path, category in collect_sources():
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except OSError as exc:
            print(f"skip {path}: {exc}", file=sys.stderr)
            continue
        rel = str(path.relative_to(PROJECT_ROOT))
        for source, cat, title, content in chunk_text(text, rel, category):
            conn.execute(
                "INSERT INTO chunks(source, category, title, content) VALUES (?, ?, ?, ?)",
                (source, cat, title, content),
            )
            total += 1

    conn.commit()
    conn.close()
    return total


def search(query: str, limit: int = 6) -> list[sqlite3.Row]:
    conn = sqlite3.connect(STORE)
    conn.row_factory = sqlite3.Row
    init_db(conn)
    fts_query = split_query_terms(query)
    rows = conn.execute(
        """
        SELECT source, category, title, content,
               bm25(chunks) AS score
        FROM chunks
        WHERE chunks MATCH ?
        ORDER BY score
        LIMIT ?
        """,
        (fts_query, limit),
    ).fetchall()
    conn.close()
    return rows


def remember(text: str, tag: str = "note") -> Path:
    MEMORY_DIR.mkdir(parents=True, exist_ok=True)
    target = MEMORY_DIR / f"{tag}.md"
    if not target.exists():
        target.write_text(f"# {tag.capitalize()}\n\n", encoding="utf-8")

    stamp = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")
    entry = f"\n## {stamp}\n\n{text.strip()}\n"
    with target.open("a", encoding="utf-8") as fh:
        fh.write(entry)

    conn = sqlite3.connect(STORE)
    init_db(conn)
    rel = str(target.relative_to(PROJECT_ROOT))
    for source, category, title, content in chunk_text(entry, rel, "memory"):
        conn.execute(
            "INSERT INTO chunks(source, category, title, content) VALUES (?, ?, ?, ?)",
            (source, category, title, content),
        )
    conn.commit()
    conn.close()
    return target


def format_results(rows: list[sqlite3.Row]) -> str:
    if not rows:
        return "Ничего не найдено. Запустите: python3 .cursor/rag/rag.py index"
    parts = []
    for i, row in enumerate(rows, 1):
        parts.append(
            f"### [{i}] {row['category']} — {row['source']} / {row['title']}\n{row['content']}\n"
        )
    return "\n".join(parts)


def cmd_index(_: argparse.Namespace) -> int:
    count = index_all()
    print(f"Indexed {count} chunks -> {STORE}")
    return 0


def cmd_query(args: argparse.Namespace) -> int:
    if not STORE.exists():
        index_all()
    rows = search(args.query, args.limit)
    print(format_results(rows))
    return 0


def cmd_remember(args: argparse.Namespace) -> int:
    path = remember(args.text, args.tag)
    print(f"Saved to {path.relative_to(PROJECT_ROOT)}")
    return 0


def cmd_status(_: argparse.Namespace) -> int:
    if not STORE.exists():
        print("Index: not built")
        return 0
    conn = sqlite3.connect(STORE)
    init_db(conn)
    count = conn.execute("SELECT COUNT(*) FROM chunks").fetchone()[0]
    by_cat = conn.execute(
        "SELECT category, COUNT(*) FROM chunks GROUP BY category ORDER BY category"
    ).fetchall()
    conn.close()
    print(f"Store: {STORE}")
    print(f"Chunks: {count}")
    for cat, n in by_cat:
        print(f"  {cat}: {n}")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(description="proauc-seo agent RAG memory")
    sub = parser.add_subparsers(dest="cmd", required=True)

    p_index = sub.add_parser("index", help="Rebuild search index")
    p_index.set_defaults(func=cmd_index)

    p_query = sub.add_parser("query", help="Search project memory")
    p_query.add_argument("query", help="Search query")
    p_query.add_argument("-n", "--limit", type=int, default=6)
    p_query.set_defaults(func=cmd_query)

    p_remember = sub.add_parser("remember", help="Append fact/decision to memory")
    p_remember.add_argument("text", help="Text to remember")
    p_remember.add_argument(
        "--tag",
        default="note",
        choices=("note", "fact", "decision", "seo", "deploy", "bugfix"),
    )
    p_remember.set_defaults(func=cmd_remember)

    p_status = sub.add_parser("status", help="Show index stats")
    p_status.set_defaults(func=cmd_status)

    args = parser.parse_args()
    return args.func(args)


if __name__ == "__main__":
    raise SystemExit(main())
