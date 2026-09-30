#!/bin/bash
# LUNATIC MOBILE SECURITY LAB — SessionStart hook.
# Pure-PHP project (no package manager, front libs vendored), so there is
# nothing to install. This hook performs a fast health check: PHP presence,
# syntax lint of all PHP, and the dependency-free test suite. It is
# idempotent and non-interactive.
#
# Runs in ASYNC mode: the session starts immediately and this health check
# runs in the background. Safe because the hook installs nothing — no session
# work depends on its completion.
set -euo pipefail

# Must be the first line of output to enable async mode.
echo '{"async": true, "asyncTimeout": 120000}'

cd "${CLAUDE_PROJECT_DIR:-.}"

if ! command -v php >/dev/null 2>&1; then
  echo "LUNATIC hook: PHP not found on PATH — install PHP 8.3+ to run the lab." >&2
  exit 0   # don't block the session
fi

echo "LUNATIC hook: PHP $(php -r 'echo PHP_VERSION;')"

# Syntax lint (fast; fails loudly if a file is broken)
lint_fail=0
while IFS= read -r f; do
  php -l "$f" >/dev/null 2>&1 || { echo "LUNATIC hook: syntax error in $f" >&2; lint_fail=1; }
done < <(find app public config database tests -name '*.php' 2>/dev/null)
[ "$lint_fail" -eq 0 ] && echo "LUNATIC hook: PHP lint OK"

# Dependency-free test suite (uses in-memory SQLite; no MySQL required)
if [ -f tests/run.php ]; then
  if php tests/run.php >/tmp/lunatic_tests.out 2>&1; then
    echo "LUNATIC hook: $(grep -oE '[0-9]+/[0-9]+ passed' /tmp/lunatic_tests.out | head -1) — tests OK"
  else
    echo "LUNATIC hook: test suite reported failures (see 'php tests/run.php')" >&2
  fi
fi

exit 0
