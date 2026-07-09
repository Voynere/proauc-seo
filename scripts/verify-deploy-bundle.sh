#!/usr/bin/env bash
# Verify deploy bundle before rsync.
set -euo pipefail

ROOT="${1:?usage: verify-deploy-bundle.sh <bundle-root>}"
LABEL="${2:-bundle}"

require_file() {
	local rel="$1"
	if [ ! -f "$ROOT/$rel" ]; then
		echo "[$LABEL] ERROR: missing $rel"
		exit 1
	fi
}

require_file "wp-content/themes/proautospec/style.css"
require_file "wp-content/themes/proautospec/functions.php"
require_file "wp-content/themes/proautospec/rank-math.php"
require_file "wp-content/themes/proautospec/home.php"

echo "[$LABEL] OK: proautospec bundle"
