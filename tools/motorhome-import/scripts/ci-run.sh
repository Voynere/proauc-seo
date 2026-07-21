#!/usr/bin/env bash
# Run on production via GitHub Actions motorhome-import workflow.
# Usage: ./scripts/ci-run.sh <source> <limit> <dry_run:true|false> <wp_path>
set -euo pipefail

SOURCE="${1:?source required}"
LIMIT="${2:?limit required}"
DRY_RUN="${3:?dry_run true|false required}"
WP_PATH="${4:?wp_path required}"

cd "$(dirname "$0")/.."

if [[ ! -x .venv/bin/python ]]; then
  rm -rf .venv
  python3 -m venv .venv
fi
# shellcheck disable=SC1091
source .venv/bin/activate
pip install -q -U pip
pip install -q -r requirements.txt

python3 - "$WP_PATH" <<'PY'
from pathlib import Path
import sys

wp_path = sys.argv[1]
Path("config.yaml").write_text(
    "\n".join(
        [
            "wordpress:",
            "  url: https://proauc.ru",
            '  user: ""',
            '  app_password: ""',
            f"  wp_path: {wp_path}",
            '  ssh_host: ""',
            "  post_type: avto",
            "  category_id: 1",
            "",
            "import:",
            "  dry_run: true",
            "  limit: 10",
            "  skip_sold_out: true",
            "  fetch_details: true",
            "  sideload_images: true",
            "  jpy_to_rub_rate: 0",
            "  pricing:",
            "    enabled: true",
            "    api_url: https://proauc.ru/api/get-price.php",
            "    country: korea",
            "",
            "http:",
            '  user_agent: "Mozilla/5.0 (compatible; proauc-motorhome-import/0.1)"',
            "  timeout_seconds: 45",
            "  rate_limit: 0.8",
            "",
            "sources:",
            "  fujicars:",
            "    enabled: false",
            "  bobaedream:",
            "    enabled: false",
            "  encar:",
            "    enabled: true",
            '    api_query: "(And.Hidden.N._.(Or.Badge.캠핑카._.Badge.4WD 캠핑카._.Badge.캠핑카/이동사무차.))"',
            "    max_pages: 10",
            "    page_size: 50",
            "    camping_only: true",
            "    use_model_groups: false",
            "",
            "logging:",
            "  level: INFO",
            "",
        ]
    ),
    encoding="utf-8",
)
PY

DRY_FLAG="--no-dry-run"
if [[ "$DRY_RUN" == "true" ]]; then
  DRY_FLAG="--dry-run"
fi

echo "Running: source=$SOURCE limit=$LIMIT dry_run=$DRY_RUN wp_path=$WP_PATH"
python -m motorhome_import run \
  --source "$SOURCE" \
  --limit "$LIMIT" \
  $DRY_FLAG \
  --config config.yaml

echo "Import finished."
