#!/bin/sh
set -e
cd /var/www/html

echo "[entrypoint] chờ MySQL ${DB_HOST}:${DB_PORT} ..."
until php -r '
  try {
    new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"),
      getenv("DB_USERNAME"), getenv("DB_PASSWORD")
    );
    exit(0);
  } catch (Exception $e) { exit(1); }
' 2>/dev/null; do
  sleep 2
done
echo "[entrypoint] MySQL sẵn sàng."

# Volume om-storage được Docker seed từ image ở lần tạo đầu, nhưng nếu
# volume đã tồn tại từ bản build cũ thì các thư mục mới sẽ thiếu.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public \
         storage/backups
chown -R www-data:www-data storage bootstrap/cache

# Chỉ container `app` chạy migrate; scheduler đặt RUN_MIGRATIONS=false
# để hai container không migrate cùng lúc.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] chạy migrate ..."
  php artisan migrate --force
fi

# Phải xoá TRƯỚC mọi lệnh artisan: manifest cũ được nạp ngay lúc bootstrap,
# nên chính package:discover cũng chết nếu nó còn đó.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# php-fpm phải khởi động bằng root (master root, worker www-data).
# Mọi thứ còn lại — scheduler, artisan thủ công — chạy bằng www-data, nếu không
# file log/backup do root tạo sẽ khiến php-fpm không ghi được nữa.
if [ "$1" = "php-fpm" ]; then
  exec "$@"
fi
exec setpriv --reuid=www-data --regid=www-data --init-groups "$@"
