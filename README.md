# Тестовое задание для Жизньмарт

## Настройка

```bash
cp .env.example .env
composer install
php artisan key:generate
```

После нужно установить значения для `DB_` переменных, либо можно установить
`DB_CONNECTION=sqlite` и закомментировать остальные.

```bash
php artisan migrate --force
php artisan optimize
```

## Использование

Можно настроить на использование web-сервера или для быстрой проверки:

```bash
php artisan serve
```

По адресу http://127.0.0.1:8000/ будет запущен сайт

[Документация по API](/openapi.json)
