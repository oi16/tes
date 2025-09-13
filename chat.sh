#!/bin/bash

# Script Terminal Chat: Mengirim pesan ke semua user terminal, dengan nama, dan riwayat di cat.txt

CAT_FILE="cat.txt"

# Fungsi untuk membaca dan menampilkan riwayat chat
function show_history() {
    if [[ -f "$CAT_FILE" ]]; then
        echo "===== Riwayat Chat ====="
        cat "$CAT_FILE"
        echo "========================"
    else
        echo "Belum ada riwayat chat."
    fi
}

# Fungsi untuk mengirim pesan ke semua user terminal
function send_message() {
    local name="$1"
    local message="$2"
    local full_message="[$(date '+%Y-%m-%d %H:%M:%S')] $name: $message"

    # Simpan ke riwayat
    echo "$full_message" >> "$CAT_FILE"

    # Dapatkan semua user yang login di terminal
    users=$(who | awk '{print $1}' | sort | uniq)

    # Kirim pesan ke semua user dengan wall
    echo "$full_message" | wall
}

# Main menu
clear
echo "==== Terminal Chat ===="
show_history
echo ""
read -p "Set Nama Anda: " USERNAME

while true; do
    echo ""
    read -p "$USERNAME > " MESSAGE

    if [[ "$MESSAGE" == "/exit" ]]; then
        echo "Keluar dari chat."
        break
    elif [[ "$MESSAGE" == "/history" ]]; then
        show_history
    else
        send_message "$USERNAME" "$MESSAGE"
    fi
done
