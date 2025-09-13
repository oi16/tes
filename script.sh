#!/bin/bash

# Chat via terminal online menggunakan termbin.com
CHAT_FILE="chat.txt"
TERMBIN_URL="termbin.com"
TEMP_LOCAL="chat_local.txt"

echo "=== Terminal Chat Online ==="
echo "Ketik pesan Anda. Untuk keluar, ketik 'exit'."
echo "Setiap pesan akan diunggah ke termbin.com dan dapat dibaca bersama."

# Jika file chat belum ada, buat baru
if [ ! -f "$CHAT_FILE" ]; then
    touch "$CHAT_FILE"
fi

# Kirim chat pertama dan dapatkan link
NCAT="$(which nc || which ncat)"

send_chat() {
    cat "$CHAT_FILE" | $NCAT $TERMBIN_URL 9999
}

while true; do
    # Tampilkan chat terakhir (jika ada)
    if [ -f "$TEMP_LOCAL" ]; then
        clear
        echo "== Pesan terbaru =="
        cat "$TEMP_LOCAL"
        echo "=================="
    fi

    read -p "Anda: " MSG

    if [[ "$MSG" == "exit" ]]; then
        echo "Keluar chat."
        break
    fi

    echo "$(date +"%H:%M") oi16: $MSG" >> "$CHAT_FILE"
    # Upload ke termbin dan simpan link
    CHAT_LINK=$(send_chat)
    echo "$CHAT_LINK" > "$TEMP_LOCAL"

    echo "Pesan diunggah ke: $CHAT_LINK"
    echo "Copy link ini dan share ke teman untuk chat!"
    echo "Tekan [Enter] untuk lanjut..."
    read
done
