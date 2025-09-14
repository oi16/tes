```markdown
# Anonymous Pro - Bash theme installer

This small Python utility installs a privacy-focused Bash theme called "Anonymous Pro".
It writes a theme file to `~/.bash_anonymous_pro` and safely adds a sourcing block to
your `~/.bashrc`, backing up files before changing them.

Features:
- Minimal prompt that shows "anon" instead of your real username/host.
- Short git branch display (if in a git repo).
- Privacy-minded history defaults and helper aliases.
- Install/uninstall/status commands with backups.

Usage:
```bash
python3 bash.py --install     # Install the theme
python3 bash.py --uninstall   # Uninstall and remove sourcing
python3 bash.py --status      # See installation status
python3 bash.py --install --force   # Force re-install
```

After installing, either `source ~/.bashrc` or open a new terminal to see the theme.

Notes and safety:
- The script will back up `~/.bashrc` before changing it. Backups are created with a timestamp.
- Uninstall moves the theme file to a timestamped backup rather than deleting it permanently.
- The theme aims to avoid leaking host or username information in the prompt, but other shell
  scripts or programs may still show identifying information. Use appropriate system-level
  privacy measures if you need stronger guarantees.
```
