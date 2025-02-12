<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use App\Models\Account;

class ApiService
{
    protected $maxRetries = 5;
    protected $baseDelay = 2;
    protected $maxWaitTime = 60;

    /**
     * Fetch paginated data using an API token assigned to a specific account.
     */
    public function fetchPaginatedData($endpoint, $dateFrom, $dateTo, $accountId, $additionalParams = [])
    {
        try {
            Log::info("Fetching data from API for account ID: {$accountId}, endpoint: {$endpoint}");

            // Получаем аккаунт
            $account = Account::with('tokens.apiService')->findOrFail($accountId);
            $token = $account->tokens()->first();

            if (!$token) {
                throw new \Exception("API-токен не найден для аккаунта ID: {$accountId}");
            }

            $baseUrl = $token->apiService->base_url;
            $baseParams = array_merge([
                'dateFrom' => $this->normalizeDate($dateFrom),
                'dateTo' => $this->normalizeDate($dateTo),
                'limit' => 500,
                'key' => $token->token_value,
            ], $additionalParams);

            Log::info("Начало загрузки данных для {$endpoint} с {$baseParams['dateFrom']} по {$baseParams['dateTo']}, используя токен {$token->id}");

            $page = 1;
            $data = [];

            do {
                try {
                    $queryParams = array_merge($baseParams, $additionalParams, ['page' => $page]);
                    $queryString = http_build_query($queryParams);
                    $url = "{$baseUrl}/{$endpoint}?{$queryString}";

                    Log::info("Запрос к API: {$url}");

                    $response = $this->makeRequestWithRetry($url);
                    $responseData = $response->json();

                    if (!empty($responseData['data'])) {
                        $data = array_merge($data, $responseData['data']);
                        Log::info("Страница {$page} загружена, найдено " . count($responseData['data']) . " записей.");
                        $page++;
                    } else {
                        Log::info("Данные закончились на странице {$page}.");
                        break;
                    }
                } catch (ConnectionException $e) {
                    Log::error("Ошибка соединения: {$e->getMessage()}");
                    throw new \Exception("Ошибка соединения с API {$url}: {$e->getMessage()}");
                } catch (RequestException $e) {
                    Log::error("Ошибка HTTP запроса: {$e->getMessage()}", ['url' => $url, 'query' => $queryParams]);
                    throw new \Exception("Ошибка HTTP запроса {$url}: {$e->getMessage()}");
                } catch (\Exception $e) {
                    Log::error("Ошибка при получении данных: {$e->getMessage()}", ['url' => $url]);
                    throw new \Exception("Ошибка получения данных с {$url}: {$e->getMessage()}");
                }
            } while (true);

            Log::info("Завершена загрузка данных. Всего загружено: " . count($data) . " записей.");
            return $data;
        } catch (\Exception $e) {
            Log::error("Общая ошибка при получении данных: " . $e->getMessage());
            throw new \Exception("Ошибка получения данных: " . $e->getMessage());
        }
    }

    /**
     * Makes an HTTP request with retry logic in case of a 429 Too Many Requests.
     */
    public function makeRequestWithRetry($url)
    {
        $retryCount = 0;

        while ($retryCount < $this->maxRetries) {
            try {
                Log::info("Попытка запроса #{$retryCount}: {$url}");

                $response = Http::get($url);
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

    /**
     * Normalize date input to Y-m-d format.
     */
    private function normalizeDate($dateInput)
    {
        $formats = ['Y-m-d', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $dateInput);
            if ($parsedDate && $parsedDate->format($format) === $dateInput) {
                return $parsedDate->format('Y-m-d');
            }
        }

        throw new \Exception("Неправильный формат даты: {$dateInput}. Ожидаемые форматы: Y-m-d или Y-m-d H:i:s.");
    }
}
