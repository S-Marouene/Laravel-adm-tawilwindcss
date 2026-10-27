#!/usr/bin/env bash
#
# auto_dev.sh — Development environment installer
# Installs (skipping anything already present):
#   composer, screen, nvm + Node.js 26, MySQL, ngrok, OpenCode CLI
# Also makes sure .bashrc loads nvm so node/npm work in new shells.
# Serves the Laravel app on 0.0.0.0:8000 and exposes it via ngrok (no code-server).
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

# ---------------------------------------------------------------- apt update (only when needed)
if command -v apt-get >/dev/null 2>&1; then
  # silence Cloud Shell's 5s apt-get warning — cover root AND the invoking user
  if [ -n "${SUDO_USER:-}" ]; then
    SUDO_HOME=$(getent passwd "$SUDO_USER" | cut -d: -f6)
    mkdir -p "$SUDO_HOME/.cloudshell"
    touch "$SUDO_HOME/.cloudshell/no-apt-get-warning"
  fi
  mkdir -p "$HOME/.cloudshell" 2>/dev/null || true
  touch "$HOME/.cloudshell/no-apt-get-warning" 2>/dev/null || true

  # check whether any apt-managed component is still missing
  APT_NEEDED=false
  command -v screen >/dev/null 2>&1 || APT_NEEDED=true
  dpkg -l mysql-server 2>/dev/null | grep -q '^ii' || APT_NEEDED=true
  command -v ngrok >/dev/null 2>&1 || APT_NEEDED=true

  APT_STAMP_DIR="$HOME/.cache/auto_dev"
  APT_STAMP="$APT_STAMP_DIR/apt-update.stamp"

  if ! $APT_NEEDED; then
    ok "All apt packages already installed (skipping apt-get update)"
  elif [ -f "$APT_STAMP" ] && ! find "$APT_STAMP" -mmin +1440 >/dev/null 2>&1; then
    ok "apt package list updated recently (< 24h) — skipping apt-get update"
  else
    log "Updating apt package list"
    $SUDO apt-get update -y
    mkdir -p "$APT_STAMP_DIR"
    touch "$APT_STAMP"
    ok "apt update done (next update skipped for 24h unless something new is needed)"
  fi
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
if dpkg -l mysql-server 2>/dev/null | grep -q '^ii'; then MYSQL_SERVER_OK=true; fi

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

# ---------------------------------------------------------------- auto-fix root ownership
# Files created by past "sudo bash auto_dev.sh" / "sudo git" runs break git add,
# artisan and storage writes. Always restore the invoking user as the owner.
if [ -n "$(find "$PROJECT_DIR" -user 0 -print -quit 2>/dev/null)" ]; then
  log "Found root-owned files in the project (caused by a past sudo run) — fixing ownership"
  if [ "$(id -u)" -eq 0 ]; then
    # script itself was run as root → give ownership back to the real user
    if [ -n "${SUDO_USER:-}" ]; then
      chown -R "$SUDO_USER:$(getent passwd "$SUDO_USER" | cut -d: -f3)" "$PROJECT_DIR"
      ok "Ownership restored to $SUDO_USER"
    else
      info "⚠ Running as true root with no SUDO_USER — run 'bash auto_dev.sh' as your normal user instead"
    fi
  else
    $SUDO chown -R "$(id -u):$(id -g)" "$PROJECT_DIR" 2>/dev/null || true
    ok "Ownership restored to $(id -un)"
  fi
else
  ok "Project ownership OK (no root-owned files)"
fi

# ---------------------------------------------------------------- default credentials
DEFAULT_DB_NAME="laravel_admin"
DEFAULT_DB_USER="root"
DEFAULT_DB_PASS='0npcDATABASE@97'

# ---------------------------------------------------------------- service up (works without systemd, e.g. Cloud Shell)
# Port to test — read early from .env so the check matches the real config
MYSQL_PORT=3306
if [ -f .env ]; then
  MYSQL_PORT=$(sed -n 's/^DB_PORT=//p' .env | head -n1 | tr -d '"')
fi
[ -n "$MYSQL_PORT" ] || MYSQL_PORT=3306

# Real readiness test: a TCP connect to 127.0.0.1:$MYSQL_PORT is the most
# reliable signal (pgrep can miss root's mysqld under Cloud Shell /proc rules)
mysql_is_up() {
  if (exec 3<>"/dev/tcp/127.0.0.1/${MYSQL_PORT}") 2>/dev/null; then
    return 0
  fi
  pgrep -x mysqld >/dev/null 2>&1
}

if mysql_is_up; then
  ok "MySQL already running"
else
  log "Starting MySQL service (sudo needed — MySQL runs as its own system user)"
  # start directly WITH sudo: the non-privileged 'service mysql start' hangs
  # forever instead of failing fast, so it must never be attempted first
  $SUDO service mysql start 2>/dev/null || true
  # wait for it to accept connections (max ~30s)
  for _ in $(seq 1 15); do
    mysql_is_up && break
    sleep 2
  done
  # if still not up but the datadir was never initialized, initialize it and retry
  if ! mysql_is_up && [ -d /var/lib/mysql ] && [ ! -f /var/lib/mysql/mysql/ibd ]; then
    log "Initializing MySQL data directory"
    $SUDO mysqld --initialize-insecure --user=mysql 2>/dev/null || true
    $SUDO mysqld_safe & sleep 5
    for _ in $(seq 1 10); do
      mysql_is_up && break
      sleep 2
    done
  fi
  mysql_is_up && ok "MySQL service started" \
    || info "⚠ MySQL failed to start — try: sudo service mysql start"
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

  # ---------------------------------------------------------------- migrate (only if pending)
  if php artisan migrate:status --no-ansi 2>/dev/null | grep -q 'Pending'; then
    log "Pending migrations found — running migrations with seeders"
    php artisan migrate --seed --force
    ok "migrations + seeders done"
  else
    ok "Database already migrated (skipping migrate)"
  fi

  # ---------------------------------------------------------------- seed only NEW seeder classes
  # Registry (~/.cache/auto_dev/seeders-ran.list) records every seeder class
  # already executed, with a hash of its file — so only genuinely NEW seeder
  # files get seeded on a later run.
  SEEDER_REG_DIR="$HOME/.cache/auto_dev"
  SEEDER_REG="$SEEDER_REG_DIR/seeders-ran.list"
  mkdir -p "$SEEDER_REG_DIR" 2>/dev/null || true
  touch "$SEEDER_REG" 2>/dev/null || true

  if [ -d database/seeders ]; then
    RAN_ANY=false
    # evaluated ONCE before the loop: an empty registry means "first run after
    # enabling this feature" — we assume the existing DB is already seeded and
    # just register all current seeders, instead of re-seeding everything
    if [ -s "$SEEDER_REG" ]; then REG_IN_USE=true; else REG_IN_USE=false; fi
    for seeder in database/seeders/*.php; do
      seeder_name="$(basename "$seeder" .php)"
      if [ "$seeder_name" = "DatabaseSeeder" ]; then continue; fi   # parent seeder — re-running it would seed everything twice
      file_hash="$(sha256sum "$seeder" | cut -d' ' -f1)"
      known_hashes="$(grep -E "^${seeder_name}:" "$SEEDER_REG" 2>/dev/null || true)"
      known_hash="${known_hashes##*:}"
      if [ -z "$known_hash" ]; then
        if $REG_IN_USE; then
          # registry in use → a class we never ran before = NEW seeder
          log "New seeder detected: $seeder_name"
          php artisan db:seed --class="$seeder_name" --force
          RAN_ANY=true
        fi
        printf '%s:%s\n' "$seeder_name" "$file_hash" >> "$SEEDER_REG"
      elif [ "$known_hash" != "$file_hash" ]; then
        info "⚠ seeder '$seeder_name' exists but was modified — NOT re-run automatically (risk of duplicated data)"
        info "  re-run manually if wanted: php artisan db:seed --class=$seeder_name --force"
        printf '%s:%s\n' "$seeder_name" "$file_hash" >> "$SEEDER_REG"
      fi
    done
    $RAN_ANY && ok "new seeders executed ✔" || ok "no new seeders"
  fi

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

# ---------------------------------------------------------------- npm install + build (only if missing)
if [ -f package.json ]; then
  if [ -d node_modules ]; then
    ok "node_modules already exists (skipping npm install)"
  else
    log "Installing node dependencies"
    npm install --no-audit --no-fund
    ok "npm dependencies installed"
  fi

  if grep -q '"vite"' package.json && grep -q '"build"' package.json; then
    # Vite/Laravel writes the build result into public/build — if present, skip
    if [ -f public/build/manifest.json ]; then
      ok "frontend build output already exists (skipping npm run build)"
    else
      log "Building frontend assets"
      npm run build
      ok "frontend build done"
    fi
  else
    ok "No vite build script in package.json — skipping frontend build"
  fi
else
  info "No package.json — skipping npm steps"
fi

# ---------------------------------------------------------------- cleanup legacy code-server sessions
# If previous runs left code-server running, shut it down (script no longer uses it)
if screen -ls 2>/dev/null | grep -q '[.]code-server'; then
  log "Stopping legacy screen session 'code-server'"
  screen -S code-server -X quit 2>/dev/null || true
fi
pkill -x code-server 2>/dev/null && ok "code-server process stopped" || true

# ================================================================ Laravel app: serve on 0.0.0.0
# ---------------------------------------------------------------- start artisan serve in screen "app-serve" + self-heal
if screen -ls 2>/dev/null | grep -q '[.]app-serve' && ! curl -s -o /dev/null http://127.0.0.1:8000; then
  log "Stale app-serve screen session detected — restarting"
  screen -S app-serve -X quit 2>/dev/null || true
  sleep 1
fi

if curl -s -o /dev/null http://127.0.0.1:8000; then
  ok "Laravel app already answering on port 8000"
else
  log "Starting 'php artisan serve' on port 8000 inside screen session 'app-serve'"
  screen -dmS app-serve php artisan serve --host=0.0.0.0 --port=8000
  for _ in $(seq 1 10); do
    curl -s -o /dev/null http://127.0.0.1:8000 && break
    sleep 2
  done
  curl -s -o /dev/null http://127.0.0.1:8000 && ok "Laravel app running on port 8000" \
    || info "⚠ app not answering on 8000 — check: sudo screen -r app-serve"
fi

# ================================================================ ngrok → Laravel app (:8000)
# ---------------------------------------------------------------- authtoken
NGROK_TOKEN="1sECevPjlkXbgrfxvq1kMgyn9CI_2zJcqQL6K53ScHEZDWPet"

if [ -f "$HOME/.config/ngrok/ngrok.yml" ] && grep -q "$NGROK_TOKEN" "$HOME/.config/ngrok/ngrok.yml" 2>/dev/null; then
  ok "ngrok authtoken already configured"
else
  log "Configuring ngrok authtoken"
  ngrok config add-authtoken "$NGROK_TOKEN"
  ok "ngrok authtoken set"
fi

# ---------------------------------------------------------------- direct ngrok tunnel for the app (:8000) + self-heal
# The ngrok process binds its own API port (allow up to 3 tunnels: 4040/4041/4042)
if screen -ls 2>/dev/null | grep -q '[.]ngrok-app'; then
  ok "ngrok app tunnel already running in screen session 'ngrok-app'"
else
  log "Starting ngrok tunnel :8000 inside screen session 'ngrok-app'"
  screen -dmS ngrok-app ngrok http 8000 --log stdout
  for _ in $(seq 1 15); do
    for api in 4040 4041 4042; do
      curl -s "http://127.0.0.1:${api}/api/tunnels" 2>/dev/null | grep -q 'public_url' && break 2
    done
    sleep 2
  done
fi

# ---------------------------------------------------------------- find the tunnel URL from ngrok APIs (4040/4041/4042)
get_tunnel_url_for() { # $1 = port
  local port="$1" api url
  for api in 4040 4041 4042; do
    url=$(curl -s "http://127.0.0.1:${api}/api/tunnels" 2>/dev/null \
      | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
except Exception:
    sys.exit(0)
for t in data.get('tunnels', []):
    if str(t.get('config', {}).get('addr', '')).endswith(':${port}'):
        print(t.get('public_url', ''), end='')
        break
")
    [ -n "$url" ] && { printf '%s' "$url"; return 0; }
  done
  return 1
}

# ---------------------------------------------------------------- wait a bit for the tunnel to be established
for _ in $(seq 1 15); do
  APP_URL_OUT=$(get_tunnel_url_for 8000 || true)
  if [ -n "$APP_URL_OUT" ]; then break; fi
  sleep 2
done

# ---------------------------------------------------------------- keep .env APP_URL in sync with the app tunnel
if [ -n "$APP_URL_OUT" ] && ! grep -q "^APP_URL=\"$APP_URL_OUT\"$" .env 2>/dev/null; then
  log "Updating APP_URL in .env → $APP_URL_OUT"
  sed -i "s|^APP_URL=.*|APP_URL=\"$APP_URL_OUT\"|" .env
  command -v php >/dev/null 2>&1 && php artisan config:clear >/dev/null 2>&1 || true
  ok "APP_URL synced with the app tunnel ✔"
fi

# ---------------------------------------------------------------- FINAL: the public link
echo ""
echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
if [ -n "$APP_URL_OUT" ]; then
  echo -e "\033[1;41m  YOUR APP (Laravel) — open in browser:\033[0m"
  echo -e "\033[1;4;97;44m  $APP_URL_OUT  \033[0m"
else
  echo -e "\033[1;31m  ⚠ App link not ready — check tunnel: sudo screen -r ngrok-app\033[0m"
fi
echo -e "\033[1;32m════════════════════════════════════════════════════════\033[0m"
echo ""
