#!/usr/bin/env bash
# Opt-in integration suite — NOT part of GitHub CI.
# Needs a WordPress PHPUnit scaffold; otherwise tests skip. See tests/README.md.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "NOTE: Integration is NOT in GitHub CI. Needs WP PHPUnit scaffold; otherwise tests skip." >&2
echo "See tests/README.md" >&2
exec ./vendor/bin/phpunit -c phpunit.xml.dist --testsuite integration "$@"
