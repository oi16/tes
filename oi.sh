#!/bin/sh

CHAT_FILE="chat.txt"

echo "Welcome to Terminal Chat!"
echo "Type your messages below. Type 'exit' to quit."

touch "$CHAT_FILE"
tail -n 0 -f "$CHAT_FILE" &

while true; do
    read -p "You: " MSG
    if [ "$MSG" = "exit" ]; then
        break
    fi
    echo "$(whoami): $MSG" >> "$CHAT_FILE"
done

kill %1
echo "Chat ended."
