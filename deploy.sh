#!/bin/bash
# Останавливаем выполнение, если какая-то команда завершится ошибкой
set -e 

echo "--- Запуск процесса деплоя ---"

# Переходим в папку проекта
cd /var/www/laravelapp

# Включаем режим обслуживания Laravel (пользователи увидят заглушку)
php artisan down --refresh=15 || true

# Сбрасываем возможные мелкие локальные изменения на проде и подтягиваем свежий код
git fetch origin main
git reset --hard origin/main

# Устанавливаем composer-зависимости (без dev-пакетов, с оптимизацией маппинга классов)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Накатываем миграции базы данных (флаг --force обязателен на проде)
php artisan migrate --force

# Полностью очищаем старый кэш и собираем новый
php artisan optimize:clear
php artisan optimize
php artisan view:cache

# Если в проекте используется Filament, оптимизируем и его панели
php artisan filament:optimize || true

sudo systemctl restart php8.3-fpm.service

# Выключаем режим обслуживания — сайт снова онлайн
php artisan up

echo "--- Деплой успешно завершен! ---"
