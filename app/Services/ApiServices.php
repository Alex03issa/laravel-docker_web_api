<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class ApiServices
{
    protected $maxRetries = 10;
    protected $baseDelay = 2; // Base delay for retries (2 seconds)
    protected $maxWaitTime = 60; // Max wait time (60 seconds)

    /**
     * Makes an HTTP request with retry logic in case of a 429 Too Many Requests.
     */
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
