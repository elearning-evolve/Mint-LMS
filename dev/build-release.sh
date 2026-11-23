#!/usr/bin/env bash
# Build a WordPress.org-ready Mint LMS zip with production Composer autoload (--no-dev).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DISTIGNORE="$ROOT/.distignore"
OUT_DIR="${OUT_DIR:-$ROOT/dist}"
SLUG="mint-lms"

if [[ ! -f "$DISTIGNORE" ]]; then
	echo "Missing .distignore" >&2
	exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
	echo "composer is required on PATH" >&2
	exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
	echo "rsync is required on PATH" >&2
	exit 1
fi

VERSION="$(
	php -r '
		$f = file_get_contents( "mint-lms.php" );
		if ( ! preg_match( "/^\s*\*\s*Version:\s*(.+)$/m", $f, $m ) ) {
			fwrite( STDERR, "Could not read Version from mint-lms.php\n" );
			exit( 1 );
		}
		echo trim( $m[1] );
	'
)"

echo "== Mint LMS release build ${VERSION}"

if [[ ! -d "$ROOT/node_modules" ]]; then
	echo "== npm ci (assets toolchain)"
	npm ci
fi

echo "== npm run build"
npm run build

STAGE_PARENT="$(mktemp -d "${TMPDIR:-/tmp}/mintlms-release.XXXXXX")"
STAGE="$STAGE_PARENT/$SLUG"
mkdir -p "$STAGE"
trap 'rm -rf "$STAGE_PARENT"' EXIT

echo "== Stage plugin tree (exclude .distignore)"
# Build rsync exclude list from .distignore (strip leading slash; skip comments/blank).
EXCLUDE_FILE="$(mktemp)"
while IFS= read -r line || [[ -n "$line" ]]; do
	line="${line//$'\r'/}"
	[[ -z "$line" || "$line" =~ ^[[:space:]]*# ]] && continue
	line="${line#/}"
	printf '%s\n' "$line" >> "$EXCLUDE_FILE"
done < "$DISTIGNORE"
# Never copy VCS / local output into the stage even if .distignore drifts.
printf '%s\n' '.git' 'dist' 'node_modules' 'vendor' >> "$EXCLUDE_FILE"

rsync -a --delete --exclude-from="$EXCLUDE_FILE" "$ROOT/" "$STAGE/"
rm -f "$EXCLUDE_FILE"

# Composer needs the manifest inside the stage to generate a clean --no-dev autoload.
cp "$ROOT/composer.json" "$STAGE/composer.json"
if [[ -f "$ROOT/composer.lock" ]]; then
	cp "$ROOT/composer.lock" "$STAGE/composer.lock"
fi

echo "== composer install --no-dev (stage)"
(
	cd "$STAGE"
	COMPOSER_ALLOW_SUPERUSER="${COMPOSER_ALLOW_SUPERUSER:-0}" \
		composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress
	# Manifests are for packaging only; do not ship them in the zip.
	rm -f composer.json composer.lock
)

AUTOLOAD_FILES="$STAGE/vendor/composer/autoload_files.php"
if [[ ! -f "$STAGE/vendor/autoload.php" ]]; then
	echo "ERROR: vendor/autoload.php missing after --no-dev install" >&2
	exit 1
fi
if [[ -f "$AUTOLOAD_FILES" ]] && grep -E 'phpunit|phpstan|deep-copy|myclabs' "$AUTOLOAD_FILES" >/dev/null 2>&1; then
	echo "ERROR: production autoload_files.php still references dev packages:" >&2
	grep -E 'phpunit|phpstan|deep-copy|myclabs' "$AUTOLOAD_FILES" >&2 || true
	exit 1
fi

# Sanity: plugin bootstrap loads.
php -r "
	define( 'ABSPATH', '/tmp/' );
	// Minimal stubs so Bootstrap is not executed — only autoload must parse.
	require '$STAGE/vendor/autoload.php';
	echo class_exists( 'MintLMS\\\\Bootstrap' ) ? \"autoload_ok\\n\" : \"autoload_fail\\n\";
" | grep -q autoload_ok

mkdir -p "$OUT_DIR"
ZIP_PATH="$OUT_DIR/${SLUG}-${VERSION}.zip"
rm -f "$ZIP_PATH"

echo "== Zip $ZIP_PATH"
(
	cd "$STAGE_PARENT"
	zip -rq "$ZIP_PATH" "$SLUG"
)

# Quick inventory checks (avoid grep -q under pipefail: early close → SIGPIPE → false fail).
ZIP_LIST="$(unzip -l "$ZIP_PATH")"
if printf '%s\n' "$ZIP_LIST" | grep -E '/(tests|node_modules|phpunit|phpstan)/' >/dev/null; then
	echo "ERROR: zip contains excluded paths" >&2
	printf '%s\n' "$ZIP_LIST" | grep -E '/(tests|node_modules|phpunit|phpstan)/' >&2 || true
	exit 1
fi
if ! printf '%s\n' "$ZIP_LIST" | grep -F "${SLUG}/vendor/autoload.php" >/dev/null; then
	echo "ERROR: zip missing vendor/autoload.php" >&2
	exit 1
fi
if ! printf '%s\n' "$ZIP_LIST" | grep -F "${SLUG}/readme.txt" >/dev/null; then
	echo "ERROR: zip missing readme.txt" >&2
	exit 1
fi

BYTES="$(wc -c < "$ZIP_PATH" | tr -d ' ')"
echo "PASS: $ZIP_PATH (${BYTES} bytes)"
