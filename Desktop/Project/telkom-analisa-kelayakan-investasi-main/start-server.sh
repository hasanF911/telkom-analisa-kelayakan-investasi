#!/bin/bash

# Buat symbolic link storage (wajib untuk Laravel agar file bisa diakses publik)
php artisan storage:link || true

# Clear & cache
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan Laravel dengan PHP built-in server
php -S 0.0.0.0:$PORT -t public
