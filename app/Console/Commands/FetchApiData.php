<?php

namespace App\Console\Commands;

use App\Services\ApiService;
use App\Services\DataService;
use Illuminate\Console\Command;

class FetchApiData extends Command
{
    protected $signature = 'fetch:api-data 
                            {--type=all : Specify the type of data to fetch (all, orders, sales, incomes, stocks)} 
                            {--fromDate= : Start date for fetching data (Y-m-d or Y-m-d H:i:s)} 
                            {--toDate= : End date for fetching data (Y-m-d or Y-m-d H:i:s)}';

    protected $description = 'Fetch and store data from the API (orders, sales, incomes, stocks)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(ApiService $apiService, DataService $dataService)
    {
        $type = $this->option('type');
        $fromDateInput = $this->option('fromDate') ?? now()->subDays(7)->format('Y-m-d');
        $toDateInput = $this->option('toDate') ?? now()->format('Y-m-d');

        $dateFrom = $this->normalizeDate($fromDateInput);
        $dateTo = $this->normalizeDate($toDateInput);

        if (!$dateFrom || !$dateTo) {
            $this->error('Invalid date format. Use Y-m-d or Y-m-d H:i:s.');
            return 1;
        }

        switch ($type) {
            case 'orders':
                $this->fetchOrders($apiService, $dataService, $dateFrom, $dateTo);
                break;
            case 'sales':
                $this->fetchSales($apiService, $dataService, $dateFrom, $dateTo);
                break;
            case 'incomes':
                $this->fetchIncomes($apiService, $dataService, $dateFrom, $dateTo);
                break;
            case 'stocks':
                $this->fetchStocks($apiService, $dataService);
                break;
            case 'all':
                $this->fetchAll($apiService, $dataService, $dateFrom, $dateTo);
                break;
            default:
                $this->error('Invalid type. Use one of the following: all, orders, sales, incomes, stocks.');
                return 1;
        }

        $this->info('Data fetched and stored successfully.');
        return 0;
    }
    private function fetchAll(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo)
    {
        $this->fetchOrders($apiService, $dataService, $dateFrom, $dateTo);
        $this->fetchSales($apiService, $dataService, $dateFrom, $dateTo);
        $this->fetchIncomes($apiService, $dataService, $dateFrom, $dateTo);
    
        $today = now()->format('Y-m-d');
        if ($dateFrom === $today && $dateTo === $today) {
            $this->fetchStocks($apiService, $dataService);
        } else {
            $this->warn("Skipping stocks fetch: Stocks data can only be fetched for the current day.");
        }
    }
    

    private function fetchOrders(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo)
    {
        try {
            $this->info('Fetching orders...');
            $orders = $apiService->fetchPaginatedData('orders', $dateFrom, $dateTo);
            $dataService->saveOrders($orders);
            $this->info('Orders fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error("Error fetching orders: " . $e->getMessage());
            \Log::error("Orders Fetch Error: " . $e->getMessage());
        }
    }

    private function fetchSales(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo)
    {
        try {
            $this->info('Fetching sales...');
            $sales = $apiService->fetchPaginatedData('sales', $dateFrom, $dateTo);
            $dataService->saveSales($sales);
            $this->info('Sales fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error("Error fetching sales: " . $e->getMessage());
            \Log::error("Sales Fetch Error: " . $e->getMessage());
        }
    }

    private function fetchIncomes(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo)
    {
        try {
            $this->info('Fetching incomes...');
            $incomes = $apiService->fetchPaginatedData('incomes', $dateFrom, $dateTo);
            $dataService->saveIncomes($incomes);
            $this->info('Incomes fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error("Error fetching incomes: " . $e->getMessage());
            \Log::error("Incomes Fetch Error: " . $e->getMessage());
        }
    }

    private function fetchStocks(ApiService $apiService, DataService $dataService)
    {
        try {
            $this->info('Fetching stocks...');
            $today = now()->format('Y-m-d');

            $fromDateInput = $this->option('fromDate');
            $toDateInput = $this->option('toDate');

            if (($fromDateInput && $fromDateInput !== $today) || ($toDateInput && $toDateInput !== $today)) {
                $this->warn("Skipping stocks fetch: Stocks data can only be fetched for the current day. Provided dates: fromDate={$fromDateInput}, toDate={$toDateInput}.");
                return;
            }

            $stocks = $apiService->fetchPaginatedData('stocks', $today, $today);
            $dataService->saveStocks($stocks);

            $this->info('Stocks fetched and saved successfully.');
        } catch (\Exception $e) {
            $this->error("Error fetching stocks: " . $e->getMessage());
            \Log::error("Stocks Fetch Error: " . $e->getMessage());
        }
    }

    private function normalizeDate($dateInput)
    {
        $formats = ['Y-m-d', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $dateInput);
            if ($parsedDate && $parsedDate->format($format) === $dateInput) {
                return $parsedDate->format('Y-m-d H:i:s');
            }
        }

        return null;
    }
}
