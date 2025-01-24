<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>



# Laravel WB API Task

## Обзор
Этот проект Laravel извлекает и сохраняет данные из тестового API в базу данных MySQL. API предоставляет данные по следующим сущностям:
- **Продажи**
- **Заказы**
- **Запасы (склады)**
- **Доходы**

Проект поддерживает извлечение постраничных данных из API, сохранение их в базе данных и автоматизацию периодической выборки с помощью планировщика задач Laravel.

---

## Предварительные условия

### 1. Используется следующие данные для извлечения данных с помощью API
- **Ключ API**: `E6kUTYrYwZq2tN4QEtyzsbEBk3ie`
- **База данных**: размещена на `freesqldatabase.com`.

---

## Инструкции по настройке

### Шаг 1. Клонирование репозитория
```bash
git clone <repository_url>
cd <repository_directory>
```

### Шаг 2. Настройка переменных среды
Создан файл `.env` в корневом каталоге и было добавлено следующее

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:<generated_key>
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=sql12.freesqldatabase.com
DB_PORT=3306
DB_DATABASE=sql12759387
DB_USERNAME=sql12759387
DB_PASSWORD=AFwmuRTfFm

API_PROTOCOL=http
API_HOST=89.108.115.241
API_PORT=6969
API_BASE_PATH=/api
API_KEY=E6kUTYrYwZq2tN4QEtyzsbEBk3ie
```

### Шаг 3: Сборка и запуск контейнеров Docker
Чтобы запустить проект Laravel в Docker, используйте следующие команды:

```bash
docker-compose up -d --build
```

### Шаг 4: Запуск миграций
Запустите миграции базы данных, чтобы создать необходимые таблицы:

```bash
docker exec -it laravel_app bash
php artisan migrate
```

---

### Миграции базы данных
Схема базы данных включает следующие таблицы:

- **Orders**:
Отслеживает заказы, полученные из API.
Включает такие поля, как `g_number`, `date`, `supplier_article`, `warehouse_name` и т. д.

- **Sales**:
Отслеживает данные о продажах.
Включает такие поля, как `g_number`, `sale_id`, `for_pay`, `price_with_disc` и т. д.

- **Доходы**:
Отслеживает данные о доходах.
Включает такие поля, как `income_id`, `date`, `warehouse_name` и т. д.

- **Склады**:
Отслеживает данные о запасах в складах.
Включает такие поля, как `nm_id`, `quantity`, `warehouse_name` и т. д.

**Примечание:** Данные о складах можно извлекать только на текущий день. Невозможно извлечь в прошлом или будущем

### Контроллеры
Следующие контроллеры обрабатывают выборку и проверку данных API:

- **OrderController**: обрабатывает выборку и хранение заказов.
- **SaleController**: обрабатывает выборку и хранение продаж.
- **IncomeController**: обрабатывает выборку и хранение доходов.
- **StockController**: обрабатывает выборку и хранение запасов. Проверяет, что запасы можно извлечь только на текущий день.

### Службы
Для разделения логики используются две службы:

- **ApiService**:
Обрабатывает выборку данных из API с разбиением на страницы.
Проверяет и создает запросы API динамически, используя переменные среды.

- **DataService**:
Обрабатывает сохранение данных в базе данных с помощью `updateOrCreate`, чтобы избежать дубликатов.

### Middleware
Используется для:
- **Авторизация**: обеспечивает включение во все запросы требуемого ключа API используется в api.php routes.

---

## Извлечение данных

Используется пользовательская команда Artisan для извлечения данных из API.


```bash
php artisan fetch:api-data --type=<type> --fromDate=<fromDate> --toDate=<toDate>
```

### Параметры команды
- `--type`: тип данных для извлечения (`all`, `orders`, `sales`, `incomes`, `stocks`).
- `--fromDate`: Начальная дата для извлечения данных (например, `2025-01-01`).
- `--toDate`: Конечная дата для извлечения данных (например, `2025-01-15`).

### Примеры
Извлечение всех данных (заказы, продажи, доходы) кроме складов потому что дата устаревшая не текущий день:

```bash
php artisan fetch:api-data --type=all --fromDate="2025-01-01" --toDate="2025-01-15"
```
Извлечение всех данных (заказы, продажи, доходы и склады):

```bash
php artisan fetch:api-data --type=all --fromDate="2025-01-24" --toDate="2025-01-24"
```
Извлечение только заказов:

```bash
php artisan fetch:api-data --type=orders --fromDate="2025-01-01" --toDate="2025-01-15"
```

Извлечение только складов (на текущий день):

```bash
php artisan fetch:api-data --type=stocks
```

---

## Автоматизация извлечения с помощью планировщика задач

Автоматизируется периодическую извлечение с помощью планировщика задач Laravel.

1. Откройте `app/Console/Kernel.php` и добавьте:

```php
protected function schedule(Schedule $schedule)
    {
       
        $fromDate = now()->subDay()->format('Y-m-d H:i:s');
        $toDate = now()->format('Y-m-d H:i:s');

        $schedule->command("fetch:api-data --type=orders --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:00');
        $schedule->command("fetch:api-data --type=sales --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:15');
        $schedule->command("fetch:api-data --type=incomes --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:30');
        $schedule->command('fetch:api-data --type=stocks')->dailyAt('02:00');
    }
```

2. Настраиваться задание для запуска планировщика:

Планировщик заданий и новое задание.
Установите действие:

```bash
php H:\Programs\laragon\www\laravel-docker\artisan schedule:run
```
Установите запуск каждую минуту.

---

## Развертывание базы данных

База данных размещена на `freesqldatabase.com`. Ниже приведены сведения о подключении:

- **Хост**: `sql12.freesqldatabase.com`
- **Имя базы данных**: `sql12759387`
- **Имя пользователя**: `sql12759387`
- **Пароль**: `AFwmuRTfFm`
- ​​**Порт**: `3306`

---

## Дополнительная информация

- **Конфигурация Docker**:
`Dockerfile` и `docker-compose.yml` настроены для запуска Laravel с PHP 8.1 и MySQL.

