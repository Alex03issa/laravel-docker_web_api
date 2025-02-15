Here’s the **updated README.md** reflecting all the modifications, including:
- **Deleted `fetch:api-data` (Replaced with `fetch:local-data`)**
- **Updated database structure (companies, accounts, API tokens, and API services)**
- **Handled multiple API services per company and account**
- **Ensured API services are checked before querying**
- **Improved automated updates and scheduler configuration**
- **Removed obsolete methods**

---

### 📌 **README.md – Laravel WB API Task**  
_**Automated Multi-Company Data Fetching and Storage from External API using Laravel and Docker**_

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

## **📌 Overview**
This Laravel-based project **fetches and stores paginated data from an external API into a MySQL database** while ensuring:
- **Multi-company, multi-account handling**
- **Token-based authentication support (API-Key, Bearer, Login-Password)**
- **Data isolation per account**
- **Automated periodic updates using Laravel’s task scheduler**

### **📌 Key Changes in This Version**
✅ **Removed `fetch:api-data` (No longer needed)**  
✅ **Refactored `fetch:local-data` to fetch from multiple companies and services**  
✅ **Updated database structure to support `companies`, `accounts`, `api_services`, and `api_tokens`**  
✅ **Ensured only valid API services per company/account are queried**  
✅ **Scheduled tasks now execute updates for all available API services**  
✅ **Deleted obsolete migration structures and improved relationships**  

---

## **🛠 Setup Instructions**

### **Step 1: Clone the Repository**
```bash
git clone <repository_url>
cd <repository_directory>
```

### **Step 2: Configure Environment Variables**
Update `.env` file with correct database and application settings:
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:<generated_key>
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=laravel_mysql 
DB_PORT=3309
DB_DATABASE=Test2Apilaravel
DB_USERNAME=proctocode_user
DB_PASSWORD=newpassword
```

---

## **🐳 Running with Docker**
### **Step 3: Build and Start Docker Containers**
```bash
docker-compose up -d --build
```
This will start:
- **`laravel_app`** (Laravel Application)
- **`laravel_mysql`** (MySQL Database)

---

## **🔧 Database Migrations**
Run the following command to set up your database schema:
```bash
docker exec -it laravel_mysql php artisan migrate
```

### **Updated Database Schema**
| Table           | Description |
|----------------|-------------|
| `companies`    | Stores company details |
| `accounts`     | Stores accounts under companies |
| `api_services` | Stores API services assigned to companies |
| `api_tokens`   | Stores tokens assigned to accounts and services |
| `orders`       | Stores order data |
| `sales`        | Stores sales data |
| `stocks`       | Stores stock data (only for the current day) |
| `incomes`      | Stores income data |

---

## **📡 Fetching Data from the API**
### **New Command: `fetch:local-data`**
You can manually fetch data using:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="OrderService" --token_type="api-key" --dateFrom="2025-01-01" --dateTo="2025-01-15"
```
### **Command Options:**
| Option       | Description |
|-------------|------------|
| `--account_name=`   | Name of the account fetching data |
| `--api_service_name=` | Name of the API service (orders, sales, stocks, incomes) |
| `--token_type=` | API token type (`api-key`, `bearer`, `login-password`) |
| `--dateFrom=` | Start date for data fetching |
| `--dateTo=`   | End date for data fetching |
| `--token=`   | Token Value |

### **📌 Examples:**
Fetch **orders** for a specific account:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="OrderService" --token_type="api-key" --dateFrom="2025-01-01" --dateTo="2025-01-15"  --token="ekfsdfkdgfsdvkfs"
```
Fetch **only today's stock data**:
```bash
php artisan fetch:local-data --account_name="MainAccount" --api_service_name="StockService" --token_type="api-key" --dateFrom="2025-02-15" --dateTo="2025-02-15" --token="ekfsdfkdgfsdvkfs"
```

---

## **📅 Automating Data Updates**
To update **all accounts and API services** **twice a day**, we use Laravel's Scheduler.

### **Step 1: Add to Laravel Scheduler**
Edit `app/Console/Kernel.php`:
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

### **Step 2: Run the Scheduler**
```bash
docker exec -it laravel_app php artisan schedule:work
```

### **Updated `update:data` Command**
The command now:
- **Loops through all companies**
- **Finds all accounts under each company**
- **Ensures only valid API services per company/account are queried**
- **Automatically fetches data for each API service available to that account**
- **Ensures `stocks` data is updated separately (only for today)**  

```bash
php artisan update:data
```

---

## **📌 API Services Handling**
### ✅ **New API Service Structure**
Previously, API services were not linked to companies properly. Now, each **API service is linked to a company**, ensuring that:
- Only **valid API services for a given company** are queried
- Accounts **only fetch data from services assigned to their company**

---

## **📌 API Rate Limit Handling**
The system **automatically retries API requests** when rate limits (`429 Too Many Requests`) are encountered.

### **Updated `makeRequestWithRetry()`**
If an API returns **429**, it automatically **waits for the retry-after time** before sending another request.
```php
public function makeRequestWithRetry($url, $headers = [])
{
    $retryCount = 0;

    while ($retryCount < $this->maxRetries) {
        try {
            Log::info("API Request Attempt #{$retryCount}: {$url}");

            $response = Http::withHeaders($headers)->get($url);
            Log::info("API Response Status: " . $response->status());

            if ($response->status() === 429) {
                $retryAfter = intval($response->header('Retry-After') ?? ($this->baseDelay * (2 ** $retryCount)));
                sleep(min($retryAfter, $this->maxWaitTime));
                $retryCount++;
                continue;
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("API Error: " . $e->getMessage());
        }

        $retryCount++;
    }

    throw new \Exception("API request failed after multiple retries.");
}
```

---
