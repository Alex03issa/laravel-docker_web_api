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

### 1. Требования к программному обеспечению
- PHP 8.1+
- Composer
- Docker и Docker Compose
- База данных MySQL

### 2. Переменные среды
Убедитесь, что у вас есть доступ к следующим учетным данным:
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
Создайте файл `.env` в корневом каталоге и добавьте следующее:

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

## Сведения о проекте

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

- **Запасы**:
Отслеживает данные о запасах (складах).
Включает такие поля, как `nm_id`, `quantity`, `warehouse_name` и т. д.

**Примечание:** Запасы можно извлекать только на текущий день.

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

### Промежуточное ПО
Промежуточное ПО используется для:
- **Авторизация**: обеспечивает включение во все запросы требуемого ключа API.
- **Проверка**: проверяет форматы дат (`Y-m-d` или `Y-m-d H:i:s`) для выборки данных.

---

## Извлечение данных

Используйте пользовательскую команду Artisan для извлечения данных из API.

### Синтаксис команды
```bash
php artisan fetch:api-data --type=<type> --fromDate=<fromDate> --toDate=<toDate>
```

### Параметры команды
- `--type`: Укажите тип данных для извлечения (`all`, `orders`, `sales`, `incomes`, `stocks`).
- `--fromDate`: Начальная дата для извлечения данных (например, `2025-01-01`).
- `--toDate`: Конечная дата для извлечения данных (например, `2025-01-15`).

### Примеры
Извлечение всех данных (заказы, продажи, доходы и запасы):

```bash
php artisan fetch:api-data --type=all --fromDate="2025-01-01" --toDate="2025-01-15"
```

Извлечение только заказов:

```bash
php artisan fetch:api-data --type=orders --fromDate="2025-01-01" --toDate="2025-01-15"
```

Извлечение только запасов (на текущий день):

```bash
php artisan fetch:api-data --type=stocks
```

---

## Автоматизация извлечения с помощью планировщика задач

Автоматизируйте периодическую извлечение с помощью планировщика задач Laravel.

1. Откройте `app/Console/Kernel.php` и добавьте:

```php
protected function schedule(Schedule $schedule)
{
$schedule->command('fetch:api-data --type=all')->dailyAt('00:00'); 
}
```

2. Настройте задание cron для запуска планировщика:

```bash
php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
```

---

## Развертывание базы данных

База данных размещена на `freesqldatabase.com`. Ниже приведены сведения о подключении:

- **Хост**: `sql12.freesqldatabase.com`
- **Имя базы данных**: `sql12759387`
- **Имя пользователя**: `sql12759387`
- **Пароль**: `AFwmuRTfFm`
- ​​**Порт**: `3306`

---

## Тестирование

Запуск тестов для проверки функциональности:

```bash
php artisan test
```

### Тесты включают:
- Извлечение данных из API.
- Сохранение данных в базе данных.
- Проверка форматов даты.

---

## Дополнительная информация

- **Конфигурация Docker**:
`Dockerfile` и `docker-compose.yml` настроены для запуска Laravel с PHP 8.1 и MySQL.

- **Контакты**: По вопросам и проблемам обращайтесь к `<your_email_or_username>`.