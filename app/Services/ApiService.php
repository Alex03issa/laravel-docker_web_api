<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class ApiService
{
    protected $protocol;
    protected $host;
    protected $port;
    protected $basePath;
    protected $apiKey;

    public function __construct()
    {
        $this->protocol = config('app.api_protocol');
        $this->host = config('app.api_host');
        $this->port = config('app.api_port');
        $this->basePath = config('app.api_base_path');
        $this->apiKey = config('app.api_key');
    }

    public function fetchPaginatedData($endpoint, $dateFrom, $dateTo, $additionalParams = [])
    {
        $page = 1;
        $limit = 500;
        $data = [];

        $formattedDateFrom = $this->normalizeDate($dateFrom);
        $formattedDateTo = $this->normalizeDate($dateTo);

        $baseUrl = "{$this->protocol}://{$this->host}:{$this->port}{$this->basePath}";

        $baseParams = [
            'dateFrom' => $formattedDateFrom,
            'dateTo' => $formattedDateTo,
            'limit' => $limit,
            'key' => $this->apiKey,
        ];

        do {
            try {
                $queryParams = array_merge($baseParams, $additionalParams, ['page' => $page]);
                $queryString = http_build_query($queryParams);
                $url = "{$baseUrl}/{$endpoint}?{$queryString}";


                $response = Http::get($url);


                $response->throw();

                $responseData = $response->json();

                if (!empty($responseData['data'])) {
                    $data = array_merge($data, $responseData['data']);
                    $page++;
                } else {
                    break;
                }
            } catch (ConnectionException $e) {
                \Log::error("Connection error: {$e->getMessage()}");
                throw new \Exception("Connection error while accessing {$url}: {$e->getMessage()}");
            } catch (RequestException $e) {
                \Log::error("HTTP request error: {$e->getMessage()}", ['url' => $url, 'query' => $queryParams]);
                throw new \Exception("HTTP request error for {$url}: {$e->getMessage()}");
            } catch (\Exception $e) {
                \Log::error("General error: {$e->getMessage()}", ['url' => $url]);
                throw new \Exception("An error occurred while fetching data from {$url}: {$e->getMessage()}");
            }
        } while (true);

        return $data;
    }

    /**
     * Normalize date input to Y-m-d format.
     *
     * @param string $dateInput
     * @return string
     * @throws \Exception
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

        throw new \Exception("Invalid date format: {$dateInput}. Expected formats: Y-m-d or Y-m-d H:i:s.");
    }

}
