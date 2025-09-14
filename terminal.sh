#!/usr/bin/env bash
# Simple installer for Termux Chat (creates data dir and config)
set -e
DIR="$(cd "$(dirname "$0")" >/dev/null 2>&1 && pwd)"
DATA="$DIR/data"
CONFIG="$DATA/config.json"
USERS="$DATA/users.json"

mkdir -p "$DATA"
echo "Installer Termux Chat"
read -p "Masukkan username awal (contoh: user1): " USERNAME
USERNAME="${USERNAME:-user1}"
# write config
cat > "$CONFIG" <<EOF
{
  "username": "$(printf '%s' "$USERNAME" | sed 's/"/\\"/g')"
}
EOF

# ensure users.json exists
if [ ! -f "$USERS" ]; then
  cat > "$USERS" <<EOF
{
  "$(printf '%s' "$USERNAME" | sed 's/"/\\"/g')": { "name": "$(printf '%s' "$USERNAME")", "last_seen": 0 }
}
EOF
fi

touch "$DATA/messages.txt"
chmod 700 "$DATA"
echo "Selesai. Config ditulis ke $CONFIG"
echo "Jalankan: php -S 0.0.0.0:8080 termux_chat.php"
