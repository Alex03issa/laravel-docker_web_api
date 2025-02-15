### **README.md – Laravel WB API Task**
_**Автоматизированная извлечение и хранение данных нескольких компаний из внешнего API с помощью Laravel и Docker**_

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Статус сборки"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Всего загрузок"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Последняя стабильная версия"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="Лицензия"></a>
</p>

---

## **Инструкции по настройке**

### **Шаг 1: Клонирование репозитория**
```bash
git clone <repository_url>
cd <repository_directory>
```

### **Шаг 2: Настройка переменных среды**
Обновите файл `.env` с правильными настройками базы данных и приложения:
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:<generated_key>
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=laravel_mysql
DB_PORT=3306
DB_DATABASE=Test2Apilaravel
DB_USERNAME=proctocode_user
DB_PASSWORD=newpassword
```

---

## ** Запуск с Docker**
### **Шаг 3: Сборка и запуск контейнеров Docker**


#### ** Фрагмент кода `docker-compose.yml`**
```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: laravel_app
    ports:
      - "8001:8000"  # Приложение доступно на порту 8001
    volumes:
      - .:/var/www/html
    environment:
      DB_CONNECTION: mysql
      DB_PORT: 3309  # Подключение к MySQL на нестандартном порту
      DB_DATABASE: Test2Apilaravel
      DB_USERNAME: proctocode_user 
      DB_PASSWORD: newpassword
    depends_on:
      mysql:
        condition: service_healthy 
    networks:
      - laravel

  mysql:
    image: mysql:8.0
    container_name: laravel_mysql
    restart: always
    environment:
      MYSQL_DATABASE: Test2Apilaravel  
      MYSQL_USER: proctocode_user
      MYSQL_PASSWORD: newpassword
      MYSQL_ROOT_PASSWORD: newpassword
    ports:
      - "3309:3306"  # Меняем внешний порт MySQL
    command: --max_connections=500 --max_allowed_packet=256M --wait_timeout=600 --net_read_timeout=600 --net_write_timeout=600 --innodb_buffer_pool_size=512M --innodb_log_file_size=128M
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - laravel
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

networks:
  laravel:
    driver: bridge

volumes:
  mysql_data:
```

---
```bash
docker-compose up -d --build
```
Это запустит:
- **`laravel_app`** (приложение Laravel)
- **`laravel_mysql`** (база данных MySQL)

---

## **Миграции базы данных**
Выполните следующую команду, чтобы настроить схему базы данных:
```bash
docker exec -it laravel_mysql php artisan migrate
```

### **Обновленная схема базы данных**
| Таблица | Описание |
|----------------|-------------|
| `companies` | Хранит данные компании |
| `accounts` | Хранит счета в компаниях |
| `api_services` | Хранит службы API, назначенные компаниям |
| `api_tokens` | Хранит токены, назначенные счетам и службам |
| `orders` | Хранит данные о заказах |
| `sales` | Хранит данные о продажах |
| `stocks` | Хранит данные о запасах (только на текущий день) |
| `incomes` | Хранит данные о доходах |

---

## **Извлечение данных из API**
### **Новая команда: `fetch:local-data`**
Вы можете вручную извлечь данные с помощью:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="OrderService" --token_type="api-key" --dateFrom="2025-01-01" --dateTo="2025-01-15"
```
### **Параметры команды:**
| Параметр | Описание |
|------------|-------------|
| `--account_name=` | Имя учетной записи, извлекающей данные |
| `--api_service_name=` | Имя службы API (заказы, продажи, запасы, доходы) |
| `--token_type=` | Тип токена API (`api-key`, `bearer`, `login-password`) |
| `--dateFrom=` | Дата начала извлечения данных |
| `--dateTo=` | Дата окончания извлечения данных |
| `--token=` | Значение токена |

### ** Примеры:**
Получить **заказы** для определенного аккаунта:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="OrderService" --token_type="api-key" --dateFrom="2025-01-01" --dateTo="2025-01-15" --token="ekfsdfkdgfsdvkfs"
```
Получить **только сегодняшние данные по акциям**:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="StockService" --token_type="api-key" --dateFrom="2025-02-15" --dateTo="2025-02-15" --token="ekfsdfkdgfsdvkfs"
```

---

## **Автоматизация обновлений данных**
Чтобы обновлять **все учетные записи и службы API** **дважды в день**, мы используем планировщик Laravel.

### **Шаг 1: Добавить в планировщик Laravel**
Изменить `app/Console/Kernel.php`:
```php
$schedule->command('update:data')
->timezone('Europe/Moscow')
->twiceDailyAt(8, 18, 00)
->before(function () {
$this->waitForDatabase();
})
->onFailure(function () {
\Log::error( 'Ошибка при обновлении данных!');
});

### **Шаг 2: Запуск планировщика**
```bash
docker exec -it laravel_app php artisan schedule:work
```

### **Обновленная команда `update:data`**
Теперь команда:
- **Проходит по всем компаниям**
- **Находит все учетные записи в каждой компании**
- **Гарантирует, что запрашиваются только допустимые службы API для каждой компании/учетной записи**
- **Автоматически извлекает данные для каждой службы API, доступной для этой учетной записи**
- **Обеспечивает отдельное обновление данных `stocks` (только на сегодня)**

```bash
php artisan update:data
```

---

## ** Обработка API-сервисов**
###**Новая структура API-сервисов**
Раньше API-сервисы не были правильно привязаны к компаниям. Теперь каждый **API-сервис привязан к компании**, что гарантирует:
- Запрашиваются только **действительные API-сервисы для данной компании**
- Учетные записи **извлекают данные только из сервисов, назначенных их компании**

---

## ** Обработка ограничений по скорости API**
Система **автоматически повторяет запросы API** при обнаружении ограничений по скорости (`429 Too Many Requests`).

### **Обновлено `makeRequestWithRetry()`**
Если API возвращает **429**, он автоматически **ждет времени повтора** перед отправкой другого запроса.

```php
public function makeRequestWithRetry($url, $headers = [])
    {
        $retryCount = 0;

        while ($retryCount < $this->maxRetries) {
            try {
                Log::info("Попытка запроса #{$retryCount}: {$url}");

                $response = Http::withHeaders($headers)->get($url);
                Log::info("Ответ от API: Статус - " . $response->status());

                if ($response->status() === 429) {
                    // If API responds with 429 (Too Many Requests)
                    $retryAfter = intval($response->header('Retry-After') ?? ($this->baseDelay * (2 ** $retryCount)));
                    $retryAfter = min($retryAfter, $this->maxWaitTime);

                    Log::warning("Получен 429 Too Many Requests. Повтор через {$retryAfter} секунд...");
                    sleep($retryAfter);
                    $retryCount++;
                    continue;
                }

                // If status is 200, introduce a small delay before the next request
                if ($response->successful()) {
                    usleep(1);
                }

                $response->throw();
                return $response;

            } catch (ConnectionException $e) {
                Log::error("Ошибка соединения: {$e->getMessage()} - Повтор запроса...");
            } catch (RequestException $e) {
                Log::error("HTTP ошибка запроса: {$e->getMessage()} - Повтор...");
            } catch (\Exception $e) {
                Log::error("Общая ошибка API: {$e->getMessage()}");
                throw new \Exception("API запрос не выполнен после {$retryCount} попыток: {$e->getMessage()}");
            }

            // If not a 200 or 429 error, use exponential backoff
            $delay = min($this->baseDelay * (2 ** $retryCount), $this->maxWaitTime);
            Log::warning("Повтор запроса через {$delay} секунд...");
            sleep($delay);
            $retryCount++;
        }

        throw new \Exception("Запрос к API не выполнен после {$this->maxRetries} попыток.");
    }

```

---



