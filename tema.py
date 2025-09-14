#!/usr/bin/env bash
# Termux setup script: GitHub CLI + Copilot CLI + Node + SSH key
# Usage: chmod +x termux-github-copilot-setup.sh && ./termux-github-copilot-setup.sh
set -euo pipefail

PREFIX="${PREFIX:-$HOME/.local}"
BIN_DIR="$PREFIX/bin"
mkdir -p "$BIN_DIR"

echo "1) Updating Termux packages..."
pkg update -y
pkg upgrade -y

echo "2) Installing required packages (git, nodejs, npm, openssh, wget, curl, tar)"
pkg install -y git nodejs npm openssh wget curl tar

# Ensure local bin is in PATH for the session
if ! echo "$PATH" | grep -q "$BIN_DIR"; then
  export PATH="$BIN_DIR:$PATH"
fi

# 3) Generate SSH key if missing
SSH_KEY="$HOME/.ssh/id_ed25519"
if [ -f "$SSH_KEY" ] || [ -f "$SSH_KEY.pub" ]; then
  echo "SSH key already exists at $SSH_KEY (or .pub). Skipping generation."
else
  echo "Generating ed25519 SSH key (no passphrase) at $SSH_KEY..."
  mkdir -p "$(dirname "$SSH_KEY")"
  ssh-keygen -t ed25519 -f "$SSH_KEY" -N "" -C "$(whoami)@termux-$(uname -n)"
  echo "SSH public key:"
  cat "${SSH_KEY}.pub"
  if command -v termux-clipboard-set >/dev/null 2>&1; then
    cat "${SSH_KEY}.pub" | termux-clipboard-set && echo "(copied public key to clipboard via termux-clipboard-set)"
  fi
  echo "Add the above public key to your GitHub account -> Settings -> SSH and GPG keys -> New SSH key"
fi

# 4) Install GitHub CLI (gh) if not present
if command -v gh >/dev/null 2>&1; then
  echo "gh already installed: $(command -v gh)"
else
  echo "Installing GitHub CLI (gh) by downloading the latest release for your arch..."
  ARCH="$(dpkg --print-architecture || true)"
  case "$ARCH" in
    aarch64) GH_ASSET_ARCH="linux-arm64" ;;
    arm) GH_ASSET_ARCH="linux-armv6" ;;
    armhf) GH_ASSET_ARCH="linux-armv6" ;;
    amd64|x86_64) GH_ASSET_ARCH="linux-amd64" ;;
    *) GH_ASSET_ARCH="linux-amd64" ;;
  esac

  API_JSON="$(mktemp)"
  curl -sSf "https://api.github.com/repos/cli/cli/releases/latest" -o "$API_JSON"
  DOWNLOAD_URL="$(grep -Eo "https://[^\"]+gh_[^\"]+${GH_ASSET_ARCH}\.tar\.gz" "$API_JSON" | head -n1 || true)"
  rm -f "$API_JSON"
  if [ -z "$DOWNLOAD_URL" ]; then
    echo "Could not find a gh release for architecture $GH_ASSET_ARCH. You can manually install gh later."
  else
    echo "Found gh asset: $DOWNLOAD_URL"
    TMPTAR="$(mktemp --suffix=.tar.gz)"
    curl -L -o "$TMPTAR" "$DOWNLOAD_URL"
    TMPDIR="$(mktemp -d)"
    tar -xzf "$TMPTAR" -C "$TMPDIR"
    # tar will extract a folder like gh_*; find the gh binary
    GH_BIN="$(find "$TMPDIR" -type f -name gh -print -quit)"
    if [ -z "$GH_BIN" ]; then
      echo "Extraction failed or gh binary not found in archive."
      rm -rf "$TMPDIR" "$TMPTAR"
    else
      install -m 0755 "$GH_BIN" "$BIN_DIR/gh"
      echo "Installed gh to $BIN_DIR/gh"
      rm -rf "$TMPDIR" "$TMPTAR"
    fi
  fi
fi

# 5) Install Copilot CLI (attempt via npm)
echo "Installing Copilot CLI (npm global). This may require network and npm permissions."
# Known package name used by GitHub for Copilot CLI is @githubnext/cli (as published). Try that first.
if command -v copilot >/dev/null 2>&1; then
  echo "copilot CLI already installed: $(command -v copilot)"
else
  # Use npm to install the official package if available
  if npm view @githubnext/cli >/dev/null 2>&1; then
    echo "Installing @githubnext/cli via npm..."
    npm install -g @githubnext/cli
  else
    echo "Package @githubnext/cli not found on npm. Attempting fallback package name 'github-copilot-cli'..."
    if npm view github-copilot-cli >/dev/null 2>&1; then
      npm install -g github-copilot-cli
    else
      echo "Could not find a known copilot CLI package on npm. You can try to install the Copilot CLI from its GitHub repo manually:"
      echo "  git clone https://github.com/github/copilot-cli.git && cd copilot-cli && npm install -g ."
      echo "Proceeding without copilot CLI installed."
    fi
  fi
fi

echo
echo "-----"
echo "Setup summary:"
echo "- Node/npm installed: $(node -v 2>/dev/null || echo 'node not found') $(npm -v 2>/dev/null || echo '')"
echo "- git: $(git --version 2>/dev/null || echo 'git not found')"
echo "- gh: $(command -v gh || echo 'gh not installed')"
echo "- copilot: $(command -v copilot || echo 'copilot not installed')"
echo "- SSH public key (if generated) at: ${SSH_KEY}.pub"

cat <<'EOF'

NEXT STEPS (interactive):

1) Authenticate gh (GitHub CLI)
   - Run: gh auth login --web
     This will open a browser (or provide a URL) to authenticate and authorize on GitHub.
   - Alternatively, you can create a personal access token (PAT) with appropriate scopes and run:
     echo "MY_TOKEN" | gh auth login --with-token

2) Add your SSH public key to GitHub (if you generated one):
   - Copy the content of ~/.ssh/id_ed25519.pub and add it to https://github.com/settings/keys

3) Copilot CLI usage:
   - If 'copilot' is installed, run: copilot
     The command will usually prompt you to authenticate (it will print a URL to open).
   - If 'copilot' is not installed, try manually installing from the repo:
     git clone https://github.com/github/copilot-cli.git && cd copilot-cli && npm install -g .
     Then run 'copilot' and follow its login flow.

4) If you prefer PAT-based login for Copilot:
   - Create a PAT on GitHub with the scopes requested by the Copilot CLI
   - Set environment variable: export GITHUB_TOKEN="your_pat"

If anything fails, re-run this script or inspect the messages above.

EOF

exit 0
