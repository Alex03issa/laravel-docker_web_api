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
DB_HOST=laraval_mysql 
DB_PORT=3309
DB_DATABASE=Test2Apilaravel
DB_USERNAME=proctocode_user
DB_PASSWORD=newpassword
```

### Шаг 3: Сборка и запуск контейнеров Docker
### Описание реализации: Развертывание Laravel с Docker-Compose и нестандартным портом MySQL

#### **1. Развертывание с помощью `docker-compose`**
Приложение развернуто с помощью `docker-compose`, используя два сервиса:
- `app` (PHP)
- `mysql` (MySQL)

Файл `docker-compose.yml` обеспечивает контейнеризацию приложения и базы данных.

#### **2. Изменение порта MySQL**
В стандартной конфигурации MySQL использует порт `3306`, но в данном случае порт был изменен:
- Внутри контейнера MySQL остается на порту `3306`, но внешнее соединение происходит через `3309`.

#### **3. Фрагмент кода `docker-compose.yml`**
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

### **4. Настройки в `.env`**
Чтобы Laravel знал, что база данных находится на порту `3306`, в `.env` файле добавлены соответствующие параметры:
```env
DB_CONNECTION=mysql
DB_HOST=laravel_mysql
DB_PORT=3306
DB_DATABASE=Test2Apilaravel
DB_USERNAME=proctocode_user
DB_PASSWORD=newpassword
```

---

### **5. Запуск контейнеров**
После настройки, приложение можно запустить командой:
```bash
docker-compose up -d --build
```
Эта команда:
- Пересоберет контейнеры, если это необходимо.
- Запустит Laravel-приложение (`app`) и MySQL (`mysql`).
- Обеспечит работу Laravel с MySQL на внешним порту `3309`.

---

### **6. Проверка работы MySQL**
После запуска контейнеров можно проверить соединение с базой данных:
```bash
docker exec -it laravel_mysql mysql -u proctocode_user -p 
```
Если все настроено верно, появится консоль MySQL, где можно выполнить:
```sql
SHOW DATABASES;
```

---


### Шаг 4: Запуск миграций
Запустите миграции базы данных, чтобы создать необходимые таблицы:

```bashX
docker exec -it laravel_mysql php artisan migrate
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

### **4. Структура БД для хранения данных**
Связи:  
- **Компания** (`companies`) → несколько **Аккаунтов** (`accounts`)  
- **Аккаунт** → один **Токен** (`api_tokens`) для одного **Сервиса** (`api_services`) Также имеется **типы Токена** (`token_types`)

#### **Пример миграций:**
```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});

Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->string('account_name');
    $table->timestamps();
});

Schema::create('api_services', function (Blueprint $table) {
    $table->id();
    $table->string('service_name');
    $table->timestamps();
});

Schema::create('api_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('account_id')->constrained()->onDelete('cascade');
    $table->foreignId('api_service_id')->constrained()->onDelete('cascade');
    $table->string('token');
    $table->enum('token_type', ['read', 'write']);
    $table->timestamps();
});

  
Schema::create('token_types', function (Blueprint $table) {
    $table->id();
    $table->string('type')->unique(); // Example: 'bearer', 'api-key', 'basic-auth'
    $table->timestamps();
});


```
Эта структура гарантирует, что **каждый аккаунт** может иметь **только один токен одного типа** для **одного API сервиса**.


**Примечание:** Данные о складах можно извлекать только на текущий день. Невозможно извлечь в прошлом или будущем


### **1. Организация ежедневного обновления данных дважды в день**  
Для этого можно использовать **CRON-задачу** или **Laravel Schedule**, если проект на Laravel.

```bash
docker exec -it laravel_app bash
service cron start
exit
```
```bash
crontab -e
```
 CRON (CRONTAB):  
```sh
* * * * * /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/laravel_scheduler.log 2>&1

```


Если Laravel, то создаём **Console Command**:

- В `UpdateData.php` реализована команда `update:data`, которая:
  - Берёт первый доступный аккаунт.
  - Запрашивает данные за предыдущий день (`fetch:api-data --type=all`).
  - Отдельно обновляет `stocks` на сегодня.
- Используется `Log::info()` для записи хода выполнения в лог.

##### **Запуск в Laravel Schedule**
В `app/Console/Kernel.php` нужно прописать:
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
```
Это запускает обновление в 8 утра и 18 вечера.

Регистрируем в **Kernel.php**:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('update:data')->twiceDaily(6, 18);
}
```
Так обновление будет запускаться автоматически.

---

### **2. Обработка ошибок "Too many requests"**  
Если API ограничивает запросы, то нужно добавить **обработку 429 (Too Many Requests)** и делать паузу перед повтором.

Пример в **ApiService.php**:
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
                    $retryAfter = intval($response->header('Retry-After') ?? ($this->baseDelay * (2 ** $retryCount)));
                    $retryAfter = min($retryAfter, $this->maxWaitTime);
    
                    Log::warning("Получен 429 Too Many Requests. Повтор через {$retryAfter} секунд...");
                    sleep($retryAfter);
                    $retryCount++;
                    continue;
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
    
            $delay = min($this->baseDelay * (2 ** $retryCount), $this->maxWaitTime);
            Log::warning("Повтор запроса через {$delay} секунд...");
            sleep($delay);
            $retryCount++;
        }
    
        throw new \Exception("Запрос к API не выполнен после {$this->maxRetries} попыток.");
    }
    
```
Здесь, если API ответит 429, мы ждём указанное сервером время и повторяем запрос.

---

### **3. Вывод отладочной информации в консоль**  
При отладке важно логировать ошибки и процесс работы.

Пример в **FetchApiData.php**:
```php
use Illuminate\Support\Facades\Log;

public function fetchData()
{
    Log::info('Запуск запроса к API');
    try {
        $response = $this->makeRequest();
        Log::info('Запрос выполнен успешно', ['data' => $response]);
        return $response;
    } catch (\Exception $e) {
        Log::error('Ошибка при запросе к API', ['message' => $e->getMessage()]);
        throw $e;
    }
}
```
В Laravel все логи записываются в **storage/logs/laravel.log**.

---


#### **1. Организация хранения токенов и сервисов**
- В файле `AddApiToken.php` токены привязываются к **аккаунтам (`accounts`)**, **API-сервисам (`api_services`)** и **типам токенов (`token_types`)**.
- Таблица `token_types` хранит разные виды токенов, например, `bearer`, `api-key`, `login-password`.
- `FetchLocalData.php` позволяет использовать разные способы аутентификации (`Bearer`, `API-Key`, `Login/Password`).

#### **2. Добавление новых сущностей через консоль**
В проекте уже есть консольные команды:
- `AddCompany.php`: Добавляет компанию (имя + описание).
- `AddAccount.php`: Добавляет аккаунт в компанию.
- `AddApiService.php`: Добавляет API-сервис (имя + базовый URL).
- `AddTokenType.php`: Добавляет тип токена.
- `AddApiToken.php`: Добавляет API-токен, привязывая его к аккаунту, API-сервису и типу токена.

##### **Примеры вызова команд**
```sh
php artisan add:company "Tech Corp" "Технологическая компания"
php artisan add:account "Tech Corp" "Main Account"
php artisan add:api-service "Google Cloud" "https://cloud.google.com/api"
php artisan add:token-type "bearer"
php artisan add:api-token "Main Account" "Google Cloud" "bearer" "some-api-key"
```



### Контроллеры
Следующие контроллеры обрабатывают выборку и проверку данных API:

- **OrderController**: 
- **SaleController**: 
- **IncomeController**:
- **StockController**:

Этот контроллеры отвечает за обработку заказов, получаемых из API. Реализованы следующие функции:
- **`index(Request $request)`** – загружает заказы из API с учетом параметров даты (`dateFrom`, `dateTo`) и ID аккаунта (`account_id`).
- **`localOrders(Request $request)`** – загружает заказы из локальной базы данных с проверкой аутентификации.
- Проверяет корректность входных данных через `validate()`.
- Если `dateFrom` не указан, по умолчанию загружаются данные за последние 7 дней.
- Используется сервис `ApiService` для извлечения данных из API.
- Полученные данные сохраняются в базу данных с привязкой к `account_id`, чтобы избежать затирания данных разных аккаунтов.
- Ведётся логирование (`Log::info()`), фиксирующее ход выполнения запросов.

Пример логики в `index()`:
```php
public function index(Request $request)
{
    Log::info("Incoming request to fetch orders", ['params' => $request->all()]);

    try {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
        ]);

        $accountId = $validated['account_id'];
        $dateFrom = $validated['dateFrom'] ?? now()->subDays(7)->format('Y-m-d');
        $dateTo = $validated['dateTo'] ?? now()->format('Y-m-d');

        Log::info("Fetching orders from API", ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'account_id' => $accountId]);

        $data = $this->apiService->fetchPaginatedData('orders', $dateFrom, $dateTo, $accountId);

        if (empty($data)) {
            return response()->json(['message' => 'No new orders found'], 404);
        }

        $this->dataService->saveOrders($data, $accountId);
        return response()->json(['message' => 'Orders fetched successfully', 'records' => count($data)], 200);
    } catch (ValidationException $e) {
        return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
    }
}
```

Пример логики в `localOrders()`:
```php
public function localOrders(Request $request)
{
    Log::info("Incoming request to fetch local orders", ['params' => $request->all()]);

    try {
        $authorizationHeader = $request->header('Authorization');
        $apiKey = $request->header('x-api-key');
        $login = $request->header('X-Login');
        $password = $request->header('X-Password');

        $account = $this->authenticateRequest($authorizationHeader, $apiKey, $login, $password);
        if (!$account) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
        ]);

        $orders = Order::where('account_id', $account->id)
            ->whereBetween('date', [$validated['dateFrom'], $validated['dateTo']])
            ->get();

        return response()->json(['message' => 'Local orders retrieved successfully', 'orders' => $orders], 200);
    } catch (ValidationException $e) {
        return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
    }
}
```

Этот механизм гарантирует безопасное получение локальных заказов с поддержкой различных методов аутентификации.




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

### **Параметры команды `fetch:local-data`**
- `type`: Тип загружаемых данных (`orders`, `sales`, `incomes`, `stocks`).
- `account_id`: ID аккаунта, который используется для запроса.
- `--dateFrom`: Начальная дата выборки.
- `--dateTo`: Конечная дата выборки.
- `--bearer`: Токен авторизации Bearer.
- `--api-key`: API-ключ.
- `--login` + `--password`: Авторизация через логин и пароль.

---

### **Примеры использования `fetch:local-data`**
#### **1. Запрос заказов через локальное API**
```bash
php artisan fetch:local-data orders 1 --dateFrom="2025-01-01" --dateTo="2025-01-15"
```
`1` — это `account_id`.

#### **2. Запрос продаж с использованием API-ключа**
```bash
php artisan fetch:local-data sales 2 --dateFrom="2025-01-10" --dateTo="2025-01-15" --api-key="my-api-key"
```
`2` — ID аккаунта, выполняющего запрос.

#### **3. Запрос доходов с авторизацией по Bearer-токену**
```bash
php artisan fetch:local-data incomes 3 --dateFrom="2025-01-20" --dateTo="2025-01-25" --bearer="my-bearer-token"
```
`3` — ID аккаунта.

#### **4. Запрос складских данных на текущий день**
```bash
php artisan fetch:local-data stocks 4
```
 `4` — ID аккаунта. Дата по умолчанию = текущий день.


#### **1. Использование разных аккаунтов в методах получения данных**  
- В файле `FetchApiData.php` предусмотрен параметр `--accountId`, который позволяет загружать данные для разных аккаунтов.  
- Если `--accountId` не указан, используется первый доступный аккаунт из базы данных:  
  ```php
  if (!$accountId) {
      $account = Account::first();
      if (!$account) {
          $this->error("No account found in the database. Provide an --accountId.");
          Log::error("No account found. Cannot proceed.");
          return 1;
      }
      $accountId = $account->id;
      Log::info("Using default account ID: {$accountId}");
  }
  ```  
- Это обеспечивает возможность загрузки данных с учётом нескольких аккаунтов.  

---

#### **2. Добавление `account_id` в таблицы данных и предотвращение затирания**  
- В `FetchApiData.php` и `FetchLocalData.php` данные извлекаются с привязкой к `account_id`, что предотвращает конфликты данных из разных аккаунтов.  
- Поле `account_id` есть во всех таблицах, куда записываются загружаемые данные.  

---

#### **3. Получение только свежих данных (`date`)**  
- В `FetchApiData.php` реализованы параметры `--fromDate` и `--toDate` для ограничения выборки по дате.  
- По умолчанию данные загружаются **только за последние 7 дней**, если не заданы конкретные даты:  
  ```php
  $fromDateInput = $this->option('fromDate') ?? now()->subDays(7)->format('Y-m-d');
  $toDateInput = $this->option('toDate') ?? now()->format('Y-m-d');
  ```
- Это позволяет загружать **только актуальные данные**, не запрашивая старые записи.  
- Аналогичный механизм есть в `FetchLocalData.php`:
  ```php
  $queryParams = http_build_query([
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'account_id' => $accountId,
  ]);
  ```
- Таким образом, API получает данные **только в заданном диапазоне дат**.  

---

#### **1. Использование разных аккаунтов в методах получения данных**
- Все методы сохранения данных (`saveOrders`, `saveSales`, `saveIncomes`, `saveStocks`) принимают `accountId`, что позволяет работать с разными аккаунтами.
- В `updateOrCreate` **включено поле `account_id`**, что предотвращает перезапись данных между аккаунтами.

#### **2. Защита данных от затирания при работе с несколькими аккаунтами**
- В `Order::updateOrCreate`, `Sale::updateOrCreate`, `Income::updateOrCreate`, `Stock::updateOrCreate` явно указано:
  ```php
  ['g_number' => $order['g_number'], 'account_id' => $accountId]
  ```
  Это означает, что у каждого аккаунта будет **своя версия данных** для одного и того же идентификатора.
- В `Stock::updateOrCreate` защита дополнительно включает **поле `date`**, предотвращая дублирование по дням:
  ```php
  ['nm_id' => $stock['nm_id'], 'date' => $stock['date'], 'account_id' => $accountId]
  ```
  Это позволяет сохранять **только актуальные данные за конкретные даты**.

#### **3. Получение только свежих данных**
- В каждом методе **есть поле `last_change_date`**, которое обновляется либо из входных данных, либо текущей датой:
  ```php
  'last_change_date' => $order['last_change_date'] ?? now()->format('Y-m-d')
  ```
  Это гарантирует, что в базе хранятся **актуальные данные**.

- В `FetchApiData.php` опции позволяют **устанавливать диапазон дат** при получении данных:
  ```php
  protected $signature = 'fetch:api-data {--fromDate=} {--toDate=}';
  ```
  Это обеспечивает гибкость при загрузке только новых данных.



## Дополнительная информация

- **Конфигурация Docker**:
`Dockerfile` и `docker-compose.yml` настроены для запуска Laravel с PHP 8.1 и MySQL.

