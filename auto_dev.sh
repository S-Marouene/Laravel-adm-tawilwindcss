#!/usr/bin/env bash
#
# auto_dev.sh — Development environment installer
# Installs (skipping anything already present):
#   composer, screen, nvm + Node.js 26, MySQL, ngrok, OpenCode CLI
# Also makes sure .bashrc loads nvm so node/npm work in new shells.
#
set -euo pipefail

# ---------------------------------------------------------------- helpers
log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m✔ %s\033[0m\n' "$*"; }
info() { printf '   %s\n' "$*"; }

# Run a command, display output, remember failure but keep going
# (unless the caller used "!" prefix, handled per-call with set -e).
SUDO=""
if [ "$(id -u)" -ne 0 ]; then
  if command -v sudo >/dev/null 2>&1; then SUDO="sudo"; fi
fi

export DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------- apt update
if command -v apt-get >/dev/null 2>&1; then
  # silence Cloud Shell's 5s apt-get warning — cover root AND the invoking user
  if [ -n "${SUDO_USER:-}" ]; then
    SUDO_HOME=$(getent passwd "$SUDO_USER" | cut -d: -f6)
    mkdir -p "$SUDO_HOME/.cloudshell"
    touch "$SUDO_HOME/.cloudshell/no-apt-get-warning"
  fi
  mkdir -p "$HOME/.cloudshell"
  touch "$HOME/.cloudshell/no-apt-get-warning"
  log "Updating apt package list"
  $SUDO apt-get update -y
fi

# ---------------------------------------------------------------- composer
if command -v composer >/dev/null 2>&1; then
  ok "composer already installed ($(composer --version 2>/dev/null | head -n1))"
else
  log "Installing composer"
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  ok "composer installed"
fi

# ---------------------------------------------------------------- screen
if command -v screen >/dev/null 2>&1; then
  ok "screen already installed ($(screen --version))"
else
  log "Installing screen"
  $SUDO apt-get install -y screen
  ok "screen installed"
fi

# ---------------------------------------------------------------- nvm + node 26
NVM_VERSION="v0.40.8"
if bash -c 'source "$HOME/.nvm/nvm.sh" 2>/dev/null && command -v nvm >/dev/null 2>&1'; then
  ok "nvm already installed"
else
  log "Installing nvm $NVM_VERSION"
  curl -o- "https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh" | bash
  ok "nvm installed"
fi

# Load nvm for the rest of this script
\. "$HOME/.nvm/nvm.sh"

if nvm ls 26 >/dev/null 2>&1; then
  nvm use 26 >/dev/null
  ok "Node.js 26 already installed ($(node -v))"
else
  log "Installing Node.js 26 via nvm"
  nvm install 26
  ok "Node.js $(node -v) / npm $(npm -v) installed"
fi

# Install default node version in new shells
if ! grep -q 'nvm install 26' "$HOME/.bashrc" 2>/dev/null; then
  printf '\n# Use Node.js 26 by default\nnvm install 26 >/dev/null 2>&1\n' >> "$HOME/.bashrc"
fi

# ---------------------------------------------------------------- mysql
# On Cloud Shell the client may exist without the real server — check the server properly
MYSQL_SERVER_OK=false
dpkg -l mysql-server 2>/dev/null | grep -q '^ii' && MYSQL_SERVER_OK=true

if $MYSQL_SERVER_OK && getent passwd mysql >/dev/null 2>&1 && [ -d /var/lib/mysql ]; then
  ok "MySQL server already installed ($(mysql --version))"
else
  log "Installing MySQL server + client"
  $SUDO apt-get install -y mysql-server mysql-client
  # ensure the mysql user/data dir exist after install
  getent passwd mysql >/dev/null 2>&1 || $SUDO useradd -r -s /bin/false mysql
  $SUDO mkdir -p /var/lib/mysql
  $SUDO chown -R mysql:mysql /var/lib/mysql 2>/dev/null || true
  $SUDO mkdir -p /var/run/mysqld
  $SUDO chown mysql:mysql /var/run/mysqld 2>/dev/null || true
  $SUDO mysqld --initialize-insecure --user=mysql 2>/dev/null || true
  ok "MySQL installed"
fi

# ---------------------------------------------------------------- ngrok
if command -v ngrok >/dev/null 2>&1; then
  ok "ngrok already installed ($(ngrok version 2>/dev/null))"
else
  log "Installing ngrok"
  $SUDO snap install ngrok 2>/dev/null || {
    # Fallback: apt repo method
    curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc \
      | $SUDO tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
    echo "deb https://ngrok-agent.s3.amazonaws.com buster main" \
      | $SUDO tee /etc/apt/sources.list.d/ngrok.list >/dev/null
    $SUDO apt-get update -y && $SUDO apt-get install -y ngrok
  }
  ok "ngrok installed"
fi

# ---------------------------------------------------------------- opencode
if bash -c '\. "$HOME/.nvm/nvm.sh" >/dev/null 2>&1; command -v opencode >/dev/null 2>&1'; then
  ok "OpenCode already installed ($(opencode --version 2>/dev/null))"
else
  log "Installing OpenCode CLI globally via npm"
  npm install -g @opencode/cli
  ok "OpenCode installed"
fi

# ---------------------------------------------------------------- git identity
GIT_NAME="Med Aziz Nefzi"
GIT_EMAIL="medaziznefzi1@gmail.com"
CURRENT_NAME=$(git config --global user.name 2>/dev/null || true)
CURRENT_EMAIL=$(git config --global user.email 2>/dev/null || true)
if [ "$CURRENT_NAME" != "$GIT_NAME" ] || [ "$CURRENT_EMAIL" != "$GIT_EMAIL" ]; then
  log "Configuring git identity"
  git config --global user.name "$GIT_NAME"
  git config --global user.email "$GIT_EMAIL"
  ok "git identity set: $GIT_NAME <$GIT_EMAIL>"
else
  ok "git identity already set: $CURRENT_NAME <$CURRENT_EMAIL>"
fi

# ---------------------------------------------------------------- .bashrc hook
if ! grep -q 'nvm.sh' "$HOME/.bashrc" 2>/dev/null; then
  printf '\n# nvm (Node Version Manager)\nexport NVM_DIR="$HOME/.nvm"\n[ -s "$NVM_DIR/nvm.sh" ] && \\. "$NVM_DIR/nvm.sh"\n' >> "$HOME/.bashrc"
  # the nvm installer already adds this block usually, but be safe
fi
ok ".bashrc loads nvm automatically"

# ================================================================ Laravel .env + MySQL setup
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

# ---------------------------------------------------------------- default credentials
DEFAULT_DB_NAME="laravel_admin"
DEFAULT_DB_USER="root"
DEFAULT_DB_PASS='0npcDATABASE@97'

# ---------------------------------------------------------------- service up (works without systemd, e.g. Cloud Shell)
if pgrep -x mysqld >/dev/null 2>&1; then
  ok "MySQL already running"
else
  log "Starting MySQL service"
  service mysql start 2>/dev/null \
    || $SUDO service mysql start 2>/dev/null \
    || ($SUDO mkdir -p /var/run/mysqld && $SUDO chown mysql:mysql /var/run/mysqld && $SUDO mysqld_safe --skip-grant-tables=0 &) \
    || true
  # wait for it to accept connections (max ~30s)
  for _ in $(seq 1 15); do
    if pgrep -x mysqld >/dev/null 2>&1; then break; fi
    sleep 2
  done
  # if still not up but the datadir was never initialized, initialize it and retry
  if ! pgrep -x mysqld >/dev/null 2>&1 && [ -d /var/lib/mysql ] && [ ! -f /var/lib/mysql/mysql/ibd ]; then
    log "Initializing MySQL data directory"
    $SUDO mysqld --initialize-insecure --user=mysql 2>/dev/null || true
    $SUDO mysqld_safe & sleep 5
    for _ in $(seq 1 10); do
      if pgrep -x mysqld >/dev/null 2>&1; then break; fi
      sleep 2
    done
  fi
  pgrep -x mysqld >/dev/null 2>&1 && ok "MySQL service started" \
    || info "⚠ MySQL failed to start — try: service mysql start"
fi

# ---------------------------------------------------------------- root password (admin access)
# Ensure root keeps a known password so the script can administer MySQL later.
ESCAPED_PASS="$(printf '%s' "$DEFAULT_DB_PASS" | sed "s/'/''/g")"
if $SUDO mysql -u root -e 'SELECT 1;' >/dev/null 2>&1; then
  log "Setting MySQL root password"
  $SUDO mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$ESCAPED_PASS'; FLUSH PRIVILEGES;"
  ok "MySQL root password set to $DEFAULT_DB_PASS"
elif mysql -u root -p"$DEFAULT_DB_PASS" -e 'SELECT 1;' >/dev/null 2>&1; then
  ok "MySQL root password already set and works"
else
  ok "MySQL root password already set to a custom value (skipping)"
fi

# ---------------------------------------------------------------- .env handling
if [ -f .env ]; then
  ok ".env already exists (leaving it untouched)"
else
  if [ -f .env.example ]; then
    log "Creating .env from .env.example"
    cp .env.example .env
    ok ".env created from .env.example"
  else
    log "No .env.example found — creating a minimal .env"
    cat > .env <<'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
EOF
    ok "Minimal .env created"
  fi
fi

# ---------------------------------------------------------------- read DB vars from .env (fall back to defaults)
get_env_var() { # get_env_var KEY DEFAULT  -> prints value from .env or default
  local val
  val=$(sed -n "s/^$1=//p" .env | head -n1 | tr -d '"')
  [ -n "$val" ] && printf '%s' "$val" || printf '%s' "$2"
}
DB_NAME=$(get_env_var DB_DATABASE "$DEFAULT_DB_NAME")
DB_USER=$(get_env_var DB_USERNAME "$DEFAULT_DB_USER")
DB_PASS=$(get_env_var DB_PASSWORD "$DEFAULT_DB_PASS")
DB_HOST=$(get_env_var DB_HOST 127.0.0.1)
DB_PORT=$(get_env_var DB_PORT 3306)

# If any DB var was missing or EMPTY in .env, write the value back in
set_env_var() {
  local key="$1" value="$2"
  if grep -q "^${key}=.*$" .env 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=\"${value}\"|" .env
  else
    printf '%s="%s"\n' "$key" "$value" >> .env
  fi
}
# sync ONLY when the current .env entry is missing or empty
for pair in DB_HOST:"$DB_HOST" DB_PORT:"$DB_PORT" DB_DATABASE:"$DB_NAME" DB_USERNAME:"$DB_USER" DB_PASSWORD:"$DB_PASS"; do
  key="${pair%%:*}"
  if ! grep -q "^${key}=[^[:space:]]" .env 2>/dev/null; then
    set_env_var "$key" "${pair#*:}"
  fi
done
ok "Using credentials from .env: DB_DATABASE=$DB_NAME, DB_USERNAME=$DB_USER, DB_HOST=$DB_HOST, DB_PORT=$DB_PORT"

# ---------------------------------------------------------------- create database (from .env)
MYSQL="mysql -u root -p$DEFAULT_DB_PASS -h 127.0.0.1 -P $DB_PORT"
DB_EXISTS=$($MYSQL -N -e "SHOW DATABASES LIKE '$DB_NAME';" 2>/dev/null | grep -x "$DB_NAME" || true)
if [ -n "$DB_EXISTS" ]; then
  ok "Database '$DB_NAME' already exists"
else
  log "Creating database '$DB_NAME'"
  $MYSQL -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ok "Database '$DB_NAME' created"
fi

# ---------------------------------------------------------------- create DB user if needed (from .env)
if [ "$DB_USER" != "root" ]; then
ESCAPED_USER_PASS="$(printf '%s' "$DB_PASS" | sed "s/'/''/g")"
  log "Ensuring MySQL user '$DB_USER' exists"
  $MYSQL -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$ESCAPED_USER_PASS'; \
             GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; \
             ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$ESCAPED_USER_PASS'; \
             FLUSH PRIVILEGES;"
  ok "User '$DB_USER' ready with privileges on '$DB_NAME'"
else
  ok "DB user is root — reusing root account"
fi

# ---------------------------------------------------------------- verify the .env credentials actually work
if mysql -u "$DB_USER" -p"$DB_PASS" -h 127.0.0.1 -P "$DB_PORT" -e "USE \`$DB_NAME\`;" >/dev/null 2>&1; then
  ok "Credentials verified: user '$DB_USER' connects to '$DB_NAME' ✔"
else
  info "⚠ Could not connect with .env credentials — check DB_HOST/DB_PORT/user manually"
fi

# ---------------------------------------------------------------- app key (early only if artisan missing)
if grep -q '^APP_KEY=$' .env 2>/dev/null && [ ! -f artisan ]; then
  info "APP_KEY is empty and no artisan file yet — it will be generated in the build phase"
fi

# (no early artisan check here — 'php artisan migrate --seed' below proves the DB connection)

# ================================================================ Laravel project build
# ---------------------------------------------------------------- composer install (if needed)
if [ -f artisan ]; then
  if [ ! -f vendor/autoload.php ]; then
    log "Running composer install"
    composer install --no-interaction
    ok "composer install done"
  else
    ok "vendor/ already exists (skipping composer install)"
  fi

  # ---------------------------------------------------------------- APP_KEY
  if grep -q '^APP_KEY=$' .env 2>/dev/null; then
    log "Generating Laravel APP_KEY"
    php artisan key:generate --force
    ok "APP_KEY generated"
  else
    ok "APP_KEY already set"
  fi

  # ---------------------------------------------------------------- migrate + seed
  log "Running migrations with seeders"
  php artisan migrate --seed --force
  ok "migrations + seeders done"

  # ---------------------------------------------------------------- storage link
  if [ ! -L public/storage ]; then
    php artisan storage:link
    ok "storage link created"
  else
    ok "storage link already exists"
  fi
else
  info "No 'artisan' file in $(pwd) — skipping Laravel build steps"
fi

# ---------------------------------------------------------------- npm install + build
if [ -f package.json ]; then
  log "Installing node dependencies"
  npm install --no-audit --no-fund
  ok "npm dependencies installed"

  log "Building frontend assets"
  if grep -q '"vite"' package.json && grep -q '"build"' package.json; then
    npm run build
  fi
  ok "frontend build done"
else
  info "No package.json — skipping npm steps"
fi

# ================================================================ VS Code server (code-server)
# ---------------------------------------------------------------- install
if command -v code-server >/dev/null 2>&1; then
  ok "code-server already installed ($(code-server --version 2>/dev/null | head -n1))"
else
  log "Installing code-server"
  curl -fsSL https://code-server.dev/install.sh | sh
  ok "code-server installed"
fi

# ---------------------------------------------------------------- config: password + port 8080
CODECONFIG_DIR="$HOME/.config/code-server"
CODECONFIG="$CODECONFIG_DIR/config.yaml"
mkdir -p "$CODECONFIG_DIR"
VS_PASS='0npcVScode@198'

if grep -q 'password: ' "$CODECONFIG" 2>/dev/null && grep -qE 'bind-addr:.+0\.0\.0\.0:8080' "$CODECONFIG" 2>/dev/null; then
  ok "code-server already configured (port 8080 + password)"
else
  log "Writing code-server config ($CODECONFIG)"
  cat > "$CODECONFIG" <<'EOF'
bind-addr: 0.0.0.0:8080
auth: password
password: 0npcVScode@198
cert: false
EOF
  ok "code-server configured: bind 0.0.0.0:8080, password: $VS_PASS"
fi

# ---------------------------------------------------------------- start in screen + self-heal on rerun
# If the screen session exists but 8080 is dead (stale session), kill and restart
if screen -ls 2>/dev/null | grep -q '[.]code-server' && ! curl -s -o /dev/null http://127.0.0.1:8080; then
  log "Stale code-server screen session detected — restarting"
  screen -S code-server -X quit 2>/dev/null || true
  sleep 1
fi

if screen -ls 2>/dev/null | grep -q '[.]code-server'; then
  ok "code-server already running in screen session 'code-server'"
elif curl -s -o /dev/null http://127.0.0.1:8080; then
  ok "vscode server already answering on port 8080"
else
  log "Starting code-server on port 8080 inside screen session 'code-server'"
  screen -dmS code-server code-server
  for _ in $(seq 1 10); do
    curl -s -o /dev/null http://127.0.0.1:8080 && break
    sleep 2
  done
  curl -s -o /dev/null http://127.0.0.1:8080 && ok "code-server started on port 8080" \
    || info "⚠ code-server not answering on 8080 — check: sudo screen -r code-server"
fi

# ================================================================ ngrok → VS Code (port 8080)
# ---------------------------------------------------------------- authtoken
NGROK_TOKEN="1sECevPjlkXbgrfxvq1kMgyn9CI_2zJcqQL6K53ScHEZDWPet"

if [ -f "$HOME/.config/ngrok/ngrok.yml" ] && grep -q "$NGROK_TOKEN" "$HOME/.config/ngrok/ngrok.yml" 2>/dev/null; then
  ok "ngrok authtoken already configured"
else
  log "Configuring ngrok authtoken"
  ngrok config add-authtoken "$NGROK_TOKEN"
  ok "ngrok authtoken set"
fi

# ---------------------------------------------------------------- tunnel for code-server (port 8080) + self-heal
# If the tunnel screen exists but is dead (no ngrok API), restart it
if screen -ls 2>/dev/null | grep -q '[.]ngrok-vsc' && ! curl -s -o /dev/null http://127.0.0.1:4040; then
  log "Stale ngrok screen session detected — restarting"
  screen -S ngrok-vsc -X quit 2>/dev/null || true
  sleep 1
fi

if curl -s -o /dev/null http://127.0.0.1:4040; then
  ok "ngrok tunnel already running"
else
  log "Starting ngrok tunnel :8080 inside screen session 'ngrok-vsc'"
  screen -dmS ngrok-vsc ngrok http 8080 --log stdout
  # wait until the tunnel API answers (max ~30s)
  for _ in $(seq 1 15); do
    curl -s -o /dev/null http://127.0.0.1:4040 && break
    sleep 2
  done
fi

# ================================================================ FINAL: show the ngrok link for VS Code
# Wait for the tunnel URL (max ~40s), then print it prominently
NGROK_URL=""
for _ in $(seq 1 20); do
  NGROK_URL=$(curl -s http://127.0.0.1:4040/api/tunnels 2>/dev/null \
    | grep -o '"public_url":"https://[a-z0-9.]*"' | grep -o 'https://[a-z0-9.]*' | tail -n1)
  [ -n "$NGROK_URL" ] && break
  sleep 2
done

if [ -n "$NGROK_URL" ]; then
  echo ""
  echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
  echo -e "\033[1;41m  YOUR VS CODE SERVER (open in browser):\033[0m"
  echo -e "\033[1;4;97;44m  $NGROK_URL  \033[0m"
  echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
  echo ""
else
  echo ""
  echo -e "\033[1;31m⚠ No ngrok URL yet — the tunnel is starting. Get it with:\033[0m"
  echo -e "  \033[1;33mcurl -s http://127.0.0.1:4040/api/tunnels | grep -o 'https://[a-z0-9]*\\.ngrok-free.app'\033[0m"
  echo ""
fi
