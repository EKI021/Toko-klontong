1 #langkah awal sampai deployment
sudo apt update && sudo apt upgrade -y

#Instalasi MicroCloud: MicroCloud dapat diinstal menggunakan snap untuk memastikan paket selalu terisolasi dan mutakhir:
sudo snap install microcloud --channel=latest/edge

#Inisialisasi Cluster: Jalankan perintah inisialisasi interaktif untuk menyiapkan node pertama dalam klaster MicroCloud Anda
sudo microcloud init

#Inisialisasi LXD (Jika berjalan mandiri/non-microcloud):
sudo lxd init --auto

2 #Membuat Container Baru: Membuat container Ubuntu di dalam LXD untuk menjalankan layanan aplikasi:
lxc launch ubuntu:24.04 stok-app

#Masuk ke Dalam Container:
lxc exec stok-app -- bash

#Instalasi Lingkungan Pendukung di Container:
Di dalam container, lakukan instalasi PHP, ekstensi database, dan server MySQL sesuai kebutuhan backend aplikasi:
apt update && apt install php-cli php-mysql mysql-server -y

3 #Hybrid Cloud & Tailscale/install tailscale
curl -fsSL https://tailscale.com/install.sh | sh

#Otentikasi Node: Hubungkan node server ke akun Tailscale Anda:
sudo tailscale up

#Mendapatkan IP Tailscale: Cek alamat IP internal Tailscale server Anda dengan perintah:
tailscale ip -4


4 #CI/CD Pipeline (Otomatisasi GitHub Actions)
#Membuat Folder Workflow: Di direktori utama proyek Anda, buat struktur folder .github/workflows/:
mkdir -p .github/workflows

#Konfigurasi Skrip Deployment (deploy.yml) Buat file konfigurasi workflow:
ketik : nano .github/workflows/deploy.yml
isi file dengan script ini : 
name: Deploy Toko Kelontong to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup SSH & Deploy to Server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SSH_HOST }}        # Masukkan IP Tailscale server di GitHub Secrets
          username: ${{ secrets.SSH_USER }}    # Contoh: root
          key: ${{ secrets.SSH_PRIVATE_KEY }}  # SSH Private Key server Anda
          script: |
            cd /root/Toko-klontong
            git pull origin main
            cd toko-backend
            mysql -u root toko_kelontong < database/schema.sql
            echo "Deployment & Database Schema Updated Successfully!"

5 #Pengaturan Secrets di GitHub:

Buka repository GitHub Anda di browser.

Masuk ke menu Settings > Secrets and variables > Actions.

Tambahkan rahasia berikut:

SSH_HOST: Alamat IP Tailscale server (misal: 100.73.166.102).

SSH_USER: Username server (root).

SSH_PRIVATE_KEY: Kunci SSH privat dari komputer Anda yang diizinkan masuk ke server.
