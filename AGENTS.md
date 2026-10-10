<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

## Aturan keamanan (wajib)
- Proyek ini hanya website sekolah (Laravel). Jangan membuka, membaca, atau mengubah folder atau proyek lain di luar folder ini.
- Database yang boleh dipakai hanya: MySQL Laragon port 3307, nama database website-smp-sma-muhmamadiyah-subang. Dilarang tersambung ke database lain atau port lain (misalnya 3306).
- Jangan mengubah file .env atau koneksi database tanpa izin saya.
- Dilarang menjalankan migrate:fresh, migrate:reset, db:wipe, atau perintah penghapus data.
- Perubahan database hanya lewat migrasi baru, dan beri tahu saya dulu sebelum menjalankannya.
- Jangan menjalankan npm audit fix --force.
- Kerjakan di cabang Git baru, bukan langsung di main.
- Sebelum mengerjakan tugas besar, tampilkan rencana singkat dan tunggu persetujuan saya.
