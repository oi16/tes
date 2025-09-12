import time
import os
import webbrowser

def jam_digital():
    while True:
        # Hapus layar
        os.system('clear')

        # Tampilkan jam saat ini
        jam = time.strftime("%H:%M:%S")
        tanggal = time.strftime("%d-%m-%Y")
        hari = time.strftime("%A")

        # Tampilkan jam digital
        print("\033[1;34m")  # Warna hijau
        print("##################################")
        print("#    menu zy                     #")
        print(f"#  {jam}                      #")
        print(f"#  {tanggal}                   #")
        print(f"#  {hari}                     #")
        print("#                                #")
        print("##################################")
        print("\033[0m")  # Warna default

        # Tunggu 0.1 detik sebelum memperbarui jam
        time.sleep(0.1)

def buka_website():
    print("Pilih website yang ingin dibuka:")
    print("1. Google")
    print("2. Bing")
    print("3. DuckDuckGo")
    print("4. sfile")
    print("5. zymusic")

    pilihan = input("Masukkan pilihan Anda: ")

    if pilihan == "1":
        webbrowser.open("https://www.google.com")
    elif pilihan == "2":
        webbrowser.open("https://www.bing.com")
    elif pilihan == "3":
        webbrowser.open("https://duckduckgo.com")
    elif pilihan == "4":
        webbrowser.open("https://sfile.mobi")
    elif pilihan == "5":
        webbrowser.open("https://zynihbot")
    else:

        print("Pilihan tidak valid.")

def main():
    while True:
        print("Menu:")
        print("1. Jam Digital")
        print("2. Buka Website")
        print("3. Keluar")

        pilihan = input("Masukkan pilihan Anda: ")

        if pilihan == "1":
            jam_digital()
        elif pilihan == "2":
            buka_website()
        elif pilihan == "3":
            print("Keluar dari program.")
            break
        else:
            print("Pilihan tidak valid.")

if __name__ == "__main__":
    main()
