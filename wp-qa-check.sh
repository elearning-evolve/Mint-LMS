#!/usr/bin/env bash
# Run the SAME checks as the EVOLVE pipeline (GitHub + GitLab) on your laptop, before you push.
#   1. PHP syntax (php -l) on every PHP file
#   2. PHPCS: WordPress standard + pipeline excludes + EvolveQA (globals, REST, AJAX)
# Rules are read live from elearning-evolve/ci-workflows tag v1, so this never drifts from the pipeline.
#
# Usage (from the plugin repo root):
#   bash wp-qa-check.sh            files changed vs origin/<default branch> + uncommitted changes (what the PR/MR checks)
#   bash wp-qa-check.sh --all      every PHP file
#   bash wp-qa-check.sh --fix      run phpcbf (auto-fix) on those files first, then check
#   bash wp-qa-check.sh file.php   only these files
# Exit 0 = the pipeline will pass. Errors fail the pipeline, warnings do not.
# Needs: git, php, composer (only the first time, unless PHPCS_BIN is set).
set -uo pipefail
TOOLS="${WP_QA_HOME:-$HOME/.cache/wp-qa}"
REF="${WP_QA_REF:-v1}"
mkdir -p "$TOOLS"
git rev-parse --show-toplevel >/dev/null 2>&1 || { echo "Run this inside a git repo (the plugin root)."; exit 2; }
cd "$(git rev-parse --show-toplevel)"

# --- 1. pipeline rules (ci-workflows @ v1) ---
CW="$TOOLS/ci-workflows"
if [ -d "$CW/.git" ]; then git -C "$CW" fetch -q -f --tags origin 2>/dev/null; else git clone -q https://github.com/elearning-evolve/ci-workflows.git "$CW" || { echo "cannot download ci-workflows"; exit 2; }; fi
git -C "$CW" checkout -q -f "$REF" 2>/dev/null || { echo "tag $REF not found in ci-workflows"; exit 2; }
WF="$CW/.github/workflows/wp-qa.yml"
get_default() { awk -v k="      $1:" '$0==k {f=1; next} f && /default:/ {sub(/^[^:]*default:[ ]*/, ""); gsub(/^'"'"'|'"'"'$/, ""); print; exit}' "$WF"; }
STANDARD=$(get_default phpcs-standard); PHPCS_EXCLUDE=$(get_default phpcs-exclude); EXCLUDE_PATHS=$(get_default exclude-paths)
EVOLVE="$CW/phpcs/EvolveQA"
[ -n "$STANDARD" ] && [ -d "$EVOLVE" ] || { echo "could not read the pipeline rules from $WF"; exit 2; }

# --- 2. PHPCS + WPCS (repo's own, else a private copy in $TOOLS) ---
if [ -n "${PHPCS_BIN:-}" ]; then :
elif [ -x vendor/bin/phpcs ] && [ -f composer.json ] && grep -q '"wp-coding-standards/wpcs"' composer.json; then PHPCS_BIN=vendor/bin/phpcs
else
  if [ ! -x "$TOOLS/wpcs/vendor/bin/phpcs" ]; then
    echo "First run: installing WPCS into $TOOLS/wpcs ..."
    mkdir -p "$TOOLS/wpcs" && ( cd "$TOOLS/wpcs" && echo '{}' > composer.json \
      && composer config -q allow-plugins.dealerdirect/phpcodesniffer-composer-installer true \
      && composer require -q --no-interaction "wp-coding-standards/wpcs:^3.1" dealerdirect/phpcodesniffer-composer-installer ) \
      || { echo "composer install failed"; exit 2; }
  fi
  PHPCS_BIN="$TOOLS/wpcs/vendor/bin/phpcs"
fi
PHPCBF_BIN="$(dirname "$PHPCS_BIN")/phpcbf"

# --- 3. same ruleset the pipeline builds ---
RULESET="$TOOLS/ruleset-$$.xml"; trap 'rm -f "$RULESET" "$TOOLS/files-$$"' EXIT
REPO_RULESET=""; for f in .phpcs.xml phpcs.xml .phpcs.xml.dist phpcs.xml.dist; do [ -f "$f" ] && { REPO_RULESET="$f"; break; }; done
installed=" Generic Squiz PEAR PSR1 PSR2 PSR12 Zend MySource $("$PHPCS_BIN" -i | sed -e 's/.*are //' -e 's/ and /, /' | tr -d ',') "
{
  echo '<?xml version="1.0"?>'; echo '<ruleset name="WP QA local">'
  if [ -n "$REPO_RULESET" ]; then echo "  <rule ref=\"$PWD/$REPO_RULESET\">"
  else
    echo '  <arg name="extensions" value="php"/>'
    for d in $EXCLUDE_PATHS .composer-cache; do echo "  <exclude-pattern>*/${d}/*</exclude-pattern>"; done
    echo "  <rule ref=\"$STANDARD\">"
  fi
  for s in $PHPCS_EXCLUDE; do case "$installed" in *" ${s%%.*} "*) echo "    <exclude name=\"$s\"/>" ;; esac; done
  echo '  </rule>'
  echo "  <rule ref=\"$EVOLVE\"/>"
  echo '</ruleset>'
} > "$RULESET"

# --- 4. which files ---
LIST="$TOOLS/files-$$"
case "${1:-}" in
  --all) git ls-files '*.php' | grep -vE "(^|/)($(echo $EXCLUDE_PATHS | tr ' ' '|'))/" > "$LIST" ;;
  ""|--fix)
    BASE=$(git symbolic-ref --short refs/remotes/origin/HEAD 2>/dev/null || echo origin/main)
    git fetch -q origin "${BASE#origin/}" 2>/dev/null
    { git diff --name-only --diff-filter=ACMR "$BASE"...HEAD -- '*.php'; git diff --name-only --diff-filter=ACMR HEAD -- '*.php'; git ls-files --others --exclude-standard -- '*.php'; } | sort -u > "$LIST"
    echo "Checking PHP files changed vs $BASE (+ uncommitted): $(wc -l < "$LIST")" ;;
  *) printf '%s\n' "$@" > "$LIST" ;;
esac

# --- 5. php -l (pipeline: every file, PHP from the plugin's "Requires PHP" up to 8.4) ---
fail=0
echo "== PHP syntax (local PHP $(php -r 'echo PHP_VERSION;'); the pipeline also tests the lowest PHP your plugin supports)"
git ls-files '*.php' | grep -vE "(^|/)($(echo $EXCLUDE_PATHS | tr ' ' '|'))/" | while IFS= read -r f; do
  [ -f "$f" ] && { php -l "$f" >/dev/null 2>&1 || php -l "$f"; }
done | grep -v '^No syntax' | tee "$TOOLS/lint-$$" ; [ -s "$TOOLS/lint-$$" ] && fail=1; rm -f "$TOOLS/lint-$$"

# --- 6. phpcs ---
if [ ! -s "$LIST" ]; then echo "== PHPCS: no changed PHP files"; else
  if [ "${1:-}" = "--fix" ]; then echo "== phpcbf (auto-fix)"; "$PHPCBF_BIN" --standard="$RULESET" -q --file-list="$LIST" >/dev/null; fi
  echo "== PHPCS ($STANDARD + pipeline excludes + EvolveQA, rules from ci-workflows@$REF)"
  "$PHPCS_BIN" --standard="$RULESET" -q -s --report-full --runtime-set ignore_warnings_on_exit 1 --file-list="$LIST" || fail=1
fi
if [ "$fail" = 0 ]; then echo "PASS: the pipeline checks will pass."; else echo "FAIL: fix the errors above (warnings do not block)."; fi
exit $fail
