#!/usr/bin/env bash
# Run WordPress Plugin Check against mint-lms using .distignore exclusions (no staging copy in plugins/).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WP_PATH="${WP_PATH:-/var/www/html/wp_local}"
SOURCE_SLUG="mint-lms"
DISTIGNORE="$PLUGIN_DIR/.distignore"

if ! command -v wp >/dev/null 2>&1; then
	echo "wp-cli is required. Install WP-CLI and ensure wp is on PATH." >&2
	exit 1
fi

if ! wp core is-installed --path="$WP_PATH" >/dev/null 2>&1; then
	echo "WordPress must be installed at WP_PATH=$WP_PATH" >&2
	exit 1
fi

if [[ ! -f "$DISTIGNORE" ]]; then
	echo "Missing .distignore at $DISTIGNORE" >&2
	exit 1
fi

exclude_dirs=()
exclude_files=()

while IFS= read -r line || [[ -n "$line" ]]; do
	line="${line//$'\r'/}"
	[[ -z "$line" || "$line" =~ ^[[:space:]]*# ]] && continue
	line="${line#/}"
	[[ "$line" == */* ]] && continue
	if [[ -d "$PLUGIN_DIR/$line" ]]; then
		exclude_dirs+=( "$line" )
	elif [[ -f "$PLUGIN_DIR/$line" ]]; then
		exclude_files+=( "$line" )
	fi
done < "$DISTIGNORE"

# Always exclude dev paths that exist in the working tree but not in .distignore entries above.
for dir in scripts tests .github .git node_modules; do
	if [[ -d "$PLUGIN_DIR/$dir" ]]; then
		exclude_dirs+=( "$dir" )
	fi
done

join_by_comma() {
	local IFS=','
	echo "$*"
}

check_args=( plugin check "$SOURCE_SLUG" --path="$WP_PATH" --ignore-warnings )
if ((${#exclude_dirs[@]})); then
	unique_dirs="$(printf '%s\n' "${exclude_dirs[@]}" | sort -u | tr '\n' ',' | sed 's/,$//')"
	check_args+=( --exclude-directories="$unique_dirs" )
fi
if ((${#exclude_files[@]})); then
	unique_files="$(printf '%s\n' "${exclude_files[@]}" | sort -u | tr '\n' ',' | sed 's/,$//')"
	check_args+=( --exclude-files="$unique_files" )
fi

if ! wp plugin is-installed plugin-check --path="$WP_PATH" >/dev/null 2>&1; then
	wp plugin install plugin-check --activate --path="$WP_PATH"
fi

was_active=0
if wp plugin is-active "$SOURCE_SLUG" --path="$WP_PATH" >/dev/null 2>&1; then
	was_active=1
else
	wp plugin activate "$SOURCE_SLUG" --path="$WP_PATH"
fi

wp "${check_args[@]}"

if [[ "$was_active" -eq 0 ]]; then
	wp plugin deactivate "$SOURCE_SLUG" --path="$WP_PATH" >/dev/null 2>&1 || true
fi

echo "Plugin Check passed for $SOURCE_SLUG (release exclusions from .distignore)."
