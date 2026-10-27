#!/usr/bin/env bash
#
# dev_stop.sh — Stop everything started by auto_dev.sh (the clean, canonical stop script)
#
#   • screen sessions :  app-serve (Laravel) · ngrok-app (tunnel) — including
#                        root-owned leftovers from past "sudo" runs
#   • processes       :  php artisan serve · ngrok
#   • MySQL           :  NOT stopped by default (shared service) — use --stop-db
#
# IMPORTANT: run as your normal user (no sudo), exactly like auto_dev.sh.
#
set -uo pipefail

# ---------------------------------------------------------------- helpers
log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m✔ %s\033[0m\n' "$*"; }
info() { printf '   %s\n' "$*"; }

SUDO=""
if [ "$(id -u)" -ne 0 ] && command -v sudo >/dev/null 2>&1; then
  SUDO="sudo"
fi

STOP_DB=false
for arg in "$@"; do
  if [ "$arg" = "--stop-db" ]; then STOP_DB=true; fi
done

# mysql_is_up — same readiness test as auto_dev.sh: TCP connect to :3306
mysql_is_up() {
  (exec 3<>/dev/tcp/127.0.0.1/3306) 2>/dev/null
}

# stop_screens OWNER_BASE_DIR — kills our named sessions wherever they live
stop_screens() {
  local base="$1" socket name
  [ -d "$base" ] || return 0
  for socket in "$base"/*; do
    [ -e "$socket" ] || continue
    name="${socket##*.}"
    case "$name" in
      app-serve|ngrok-app)
        owner="${base##*/S-}"
        log "Stopping screen session '${name}' (owner: ${owner})"
        screen -S "$name" -X quit 2>/dev/null \
          || $SUDO screen -S "$name" -X quit 2>/dev/null \
          || true
        sleep 1
        if screen -ls 2>/dev/null | grep -q "[.]${name}"; then
          info "⚠ '${name}' still alive — try: screen -S ${name} -X quit"
        else
          ok "'${name}' stopped ✔"
        fi
        ;;
    esac
  done
}

# ---------------------------------------------------------------- stop screens (user-owned + root leftovers)
stop_screens "/run/screen/S-$(id -un)"
stop_screens "/var/run/screen/S-root"

# ---------------------------------------------------------------- kill leftover processes (belt & braces)
if pkill -f "artisan serve" 2>/dev/null; then
  ok "leftover 'php artisan serve' killed"
else
  ok "no leftover 'php artisan serve'"
fi
if pkill -x ngrok 2>/dev/null; then
  ok "leftover ngrok process killed"
else
  ok "no leftover ngrok process"
fi

# ---------------------------------------------------------------- verify ports are really free
PORT_OK=true
if curl -s -o /dev/null --max-time 2 http://127.0.0.1:8000; then
  PORT_OK=false
  info "⚠ port 8000 still answering — something else may be using it"
fi
if curl -s -o /dev/null --max-time 2 http://127.0.0.1:4040; then
  PORT_OK=false
  info "⚠ ngrok API still answering on 4040 — an ngrok process may still be alive"
fi
if $PORT_OK; then
  ok "ports 8000 and 4040 are free ✔"
fi

# ---------------------------------------------------------------- optionally stop MySQL
if $STOP_DB; then
  log "Stopping MySQL service"
  $SUDO service mysql stop 2>/dev/null || true
  for _ in $(seq 1 10); do
    if ! mysql_is_up; then break; fi
    sleep 1
  done
  if mysql_is_up; then
    info "⚠ MySQL still running — try: sudo service mysql stop"
  else
    ok "MySQL stopped cleanly ✔"
  fi
else
  info "MySQL left running (default). Stop it too with: $0 --stop-db"
fi

# ---------------------------------------------------------------- summary
echo ""
echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
echo -e "\033[1;33m  Dev environment stopped. (installed tools / .env untouched)\033[0m"
echo -e "   Start it all again with : \033[1;97mbash auto_dev.sh\033[0m"
echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
echo ""
