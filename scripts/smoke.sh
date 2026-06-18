#!/usr/bin/env bash
# Black-box API smoke test for a running NoovaPOS instance.
# Usage: bash scripts/smoke.sh http://abcgroup.noovapos.local
set -u

BASE="${1:-http://noovapos.local}"
API="$BASE/api"
pass=0; fail=0

check() { # name  expected_code  curl-args...
  local name="$1"; local want="$2"; shift 2
  local code
  code=$(curl -s -o /dev/null -w "%{http_code}" "$@")
  if [ "$code" = "$want" ]; then
    echo "  ok   $name ($code)"; pass=$((pass+1))
  else
    echo "  FAIL $name (got $code, want $want)"; fail=$((fail+1))
  fi
}

echo "Smoke testing: $BASE"

# SPA / landing loads
check "landing page"            200 "$BASE/"
# Login validates empty body
check "login validation (422)"  422 -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{}' "$API/login"
# Protected app endpoints reject unauthenticated access
check "v1/me requires auth"     401 -H "Accept: application/json" "$API/v1/me"
check "billing requires auth"   401 -H "Accept: application/json" "$API/billing/overview"

echo ""
echo "Passed: $pass   Failed: $fail"
[ "$fail" -eq 0 ]
