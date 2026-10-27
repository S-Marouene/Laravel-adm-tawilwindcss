#!/usr/bin/env bash
#
# stop_dev.sh — stop everything started by auto_dev.sh
#   • screen sessions: app-serve, code-server, ngrok-app, ngrok-vsc (+ any aziz/dev sessions)
#   • artisan serve (port 8000)
#   • code-server     (port 8080)
#   • ngrok tunnels
#   • MySQL service (stopped gracefully, data untouched)
#
# NOTHING is uninstalled — all tools, the .env and configs stay where they are.
# Restart later with: sudo bash auto_dev.sh
#
set -u

log()  { printf '\n\033[1;31m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m✔ %s\033[0m\n' "$*"; }
info() { printf '   %s\n' "$*"; }

SUDO=""
if [ "$(id -u)" -ne 0 ] && command -v sudo >/dev/null 2>&1; then
  SUDO="sudo"
fi
# If the script was launched with sudo, act on root resources too (services run as root)
ROOT_SCREEN=""
if [ "$(id -u)" -ne 0 ] && $SUDO true 2>/dev/null; then
  ROOT_SCREEN="sudo"
fi

# ---------------------------------------------------------------- read DB password from .env (for a clean MySQL shutdown)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_PASS='0npcDATABASE@97'
if [ -f "$PROJECT_DIR/.env" ]; then
  v=$(sed -n 's/^DB_PASSWORD=//p' "$PROJECT_DIR/.env" | head -n1 | tr -d '"')
  [ -n "$v" ] && DB_PASS="$v"
fi

stop_screen() { # $1 = session name
  local name="$1"
  # visible in this user's screens?
  if screen -ls 2>/dev/null | grep -q "[.]${name}\b"; then
    screen -S "$name" -X quit 2>/dev/null || true
    ok "screen session '$name' stopped (current user)"
  elif $ROOT_SCREEN screen -ls 2>/dev/null | grep -q "[.]${name}\b"; then
    $ROOT_SCREEN screen -S "$name" -X quit 2>/dev/null || true
    ok "screen session '$name' stopped (root)"
  else
    info "screen session '$name' : not running (already stopped)"
  fi
}

# ---------------------------------------------------------------- 1) stop ngrok tunnels first
stop_screen ngrok-app
stop_screen ngrok-vsc
pkill -f "ngrok http" 2>/dev/null && ok "leftover ngrok processes killed" || true

# ---------------------------------------------------------------- 2) stop Laravel app (port 8000)
stop_screen app-serve
if pkill -f "artisan serve" 2>/dev/null; then
  ok "artisan serve stopped"
else
  info "artisan serve : not running"
fi

# ---------------------------------------------------------------- 3) stop code-server (port 8080)
stop_screen code-server
pkill -f "code-server" 2>/dev/null && ok "leftover code-server processes killed" || true

# ---------------------------------------------------------------- 4) stop MySQL gracefully
if pgrep -x mysqld >/dev/null 2>&1; then
  log "Stopping MySQL gracefully"
  $SUDO mysqladmin -u root -p"$DB_PASS" shutdown 2>/dev/null \
    || $SUDO mysqladmin shutdown 2>/dev/null \
    || $SUDO service mysql stop 2>/dev/null \
    || pkill -x mysqld
  for _ in $(seq 1 10); do
    pgrep -x mysqld >/dev/null 2>&1 || break
    sleep 1
  done
  pgrep -x mysqld >/dev/null 2>&1 \
    && info "⚠ MySQL still running — check logs" \
    || ok "MySQL stopped cleanly ✔"
else
  info "MySQL : not running (already stopped)"
fi

# ---------------------------------------------------------------- final status
echo ""
log "Status after stop:"
pgrep -x mysqld >/dev/null 2>&1 && info "mysql      : still RUNNING ⚠" || info "mysql      : stopped ✔"
curl -s -o /dev/null http://127.0.0.1:8000 && info "app :8000   : still RUNNING ⚠" || info "app :8000   : stopped ✔"
curl -s -o /dev/null http://127.0.0.1:8080 && info "vscode :8080 : still RUNNING ⚠" || info "vscode :8080 : stopped ✔"
echo ""
ok "Everything stopped. Installed tools/customizations are preserved."
info "To restart later: sudo bash auto_dev.sh"
