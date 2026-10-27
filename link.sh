#!/usr/bin/env bash
#
# dev_link.sh — Show the current ngrok link of the Laravel app at any time.
# Reads it live from the ngrok API (so it keeps working even if the
# terminal was cleared, or you opened a brand-new shell).
#
set -uo pipefail

ok()   { printf '\033[1;32m✔ %s\033[0m\n' "$*"; }
info() { printf '   %s\n' "$*"; }
err()  { printf '\033[1;31m✗ %s\033[0m\n' "$*"; }

# ---------------------------------------------------------------- find the tunnel URL (APIs 4040/4041/4042)
tunnel_url() {
  local api url
  for api in 4040 4041 4042; do
    url=$(curl -s --max-time 2 "http://127.0.0.1:${api}/api/tunnels" 2>/dev/null \
      | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
except Exception:
    sys.exit(0)
for t in data.get('tunnels', []):
    if str(t.get('config', {}).get('addr', '')).endswith(':8000'):
        print(t.get('public_url', ''))
        break
" 2>/dev/null || true)
    [ -n "$url" ] && printf '%s' "$url" && return 0
  done
  return 1
}

# ---------------------------------------------------------------- wait briefly in case the tunnel is still booting
URL=""
for _ in $(seq 1 5); do
  if URL=$(tunnel_url); then break; fi
  sleep 1
done

if [ -n "$URL" ]; then
  ok "Laravel app is live:"
  echo ""
  echo -e "\033[1;4;97;44m  ${URL}  \033[0m"
  echo ""
  info "local: http://127.0.0.1:8000 · web inspect: http://127.0.0.1:4040"
else
  err "no ngrok tunnel found for port 8000"
  info "start the dev environment with:  bash auto_dev.sh"
  exit 1
fi
