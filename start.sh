#!/bin/sh
set -eu

cd /var/www/html

PORT="${PORT:-10000}"

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

cat > /etc/nginx/http.d/default.conf <<EOF
server {
    listen ${PORT};
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    server_tokens off;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=(self)" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
php artisan storage:link || true
php artisan optimize:clear || true
# Apply migrations on each deploy/start so shell access is not required.
php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm -D
exec nginx -g 'daemon off;'