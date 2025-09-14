```markdown
# Termux Chat (PHP) — Simple file-based chat for Termux

Fitur:
- Chat sederhana berbasis file (data/messages.txt).
- Riwayat pesan disimpan di data/messages.txt (setiap baris JSON).
- Daftar user disimpan di data/users.json.
- Username dapat diset saat instalasi atau lewat form awal.
- Bisa dijalankan di Termux menggunakan PHP built-in server.

File penting:
- termux_chat.php — script utama (server + UI).
- data/users.json — metadata user (dibuat otomatis).
- data/messages.txt — riwayat pesan (dibuat otomatis).
- data/config.json — (opsional) berisi {"username":"yourname"} untuk set default saat pertama buka.
- install.sh — (opsional) script bantu untuk membuat config awal.

Instalasi cepat (Termux):
1. Update dan pasang PHP:
   pkg update && pkg install php -y

2. Salin termux_chat.php dan install.sh ke folder (mis. ~/termux-chat).
   Jika kamu menggunakan repo ini, cukup clone repo.

3. (Opsional) Jalankan install untuk set username awal:
   bash install.sh
   Script akan menulis data/config.json dengan username yang kamu masukkan.

4. Jalankan server:
   php -S 0.0.0.0:8080 termux_chat.php

5. Buka di perangkat:
   - Di Termux browser: http://localhost:8080
   - Di perangkat lain pada jaringan yang sama: http://<IP-Termux>:8080

Catatan penggunaan:
- Sistem ini sangat sederhana, tidak ada autentikasi kuat — siapa pun yang tahu nama pengguna dapat menggunakannya.
- Untuk mengubah username, hapus cookie `chat_user` di browser atau buka halaman setup (jika belum ada config).
- Online/offline: user dianggap online jika terlihat dalam 60 detik terakhir.
- Pesan disimpan sebagai baris JSON di data/messages.txt, contohnya:
  {"from":"user1","to":"user2","time":1694670000,"text":"Halo"}

Keamanan & pengembangan:
- Jangan gunakan ini di internet publik tanpa TLS dan autentikasi.
- Untuk memperbaiki:
  - Tambahkan login/password
  - Gunakan database untuk skala
  - Tambahkan sanitasi lebih ketat dan rate-limit

Jika mau, saya bisa:
- Tambahkan dukungan grup chat
- Tambahkan notifikasi push / WebSocket (butuh server tambahan)
- Menambahkan enkripsi pesan sederhana

```
