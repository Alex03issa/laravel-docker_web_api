<?php

namespace App\Console\Commands;

use App\Services\ApiService;
use App\Services\DataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Account;

class FetchApiData extends Command
{
    protected $signature = 'fetch:api-data 
                            {--type=all : Specify data type (all, orders, sales, incomes, stocks)} 
                            {--fromDate= : Start date (Y-m-d or Y-m-d H:i:s)} 
                            {--toDate= : End date (Y-m-d or Y-m-d H:i:s)} 
                            {--accountId= : Specify account ID}';

    protected $description = 'Fetch and store data from the API (orders, sales, incomes, stocks) using a specific account';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(ApiService $apiService, DataService $dataService)
    {
        Log::info("Starting API Data Fetch Command", ['params' => $this->options()]);

        $type = $this->option('type');
        $fromDateInput = $this->option('fromDate') ?? now()->subDays(7)->format('Y-m-d');
        $toDateInput = $this->option('toDate') ?? now()->format('Y-m-d');
        $accountId = $this->option('accountId');

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

        $dateFrom = $this->normalizeDate($fromDateInput);
        $dateTo = $this->normalizeDate($toDateInput);

        if (!$dateFrom || !$dateTo) {
            $this->error('Invalid date format. Use Y-m-d or Y-m-d H:i:s.');
            Log::error("Invalid date format: fromDate={$fromDateInput}, toDate={$toDateInput}");
            return 1;
        }

        switch ($type) {
            case 'orders':
                $this->fetchOrders($apiService, $dataService, $dateFrom, $dateTo, $accountId);
                break;
            case 'sales':
                $this->fetchSales($apiService, $dataService, $dateFrom, $dateTo, $accountId);
                break;
            case 'incomes':
                $this->fetchIncomes($apiService, $dataService, $dateFrom, $dateTo, $accountId);
                break;
            case 'stocks':
                $this->fetchStocks($apiService, $dataService, $accountId);
                break;
            case 'all':
                $this->fetchAll($apiService, $dataService, $dateFrom, $dateTo, $accountId);
                break;
            default:
                $this->error('Invalid type. Use: all, orders, sales, incomes, stocks.');
                Log::error("Invalid type provided: {$type}");
                return 1;
        }

        $this->info('Data fetch completed successfully.');
        Log::info("API Data Fetch Command Completed Successfully");
        return 0;
    }

    private function fetchAll(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo, $accountId)
    {
        $this->fetchOrders($apiService, $dataService, $dateFrom, $dateTo, $accountId);
        $this->fetchSales($apiService, $dataService, $dateFrom, $dateTo, $accountId);
        $this->fetchIncomes($apiService, $dataService, $dateFrom, $dateTo, $accountId);

        if ($dateFrom === now()->format('Y-m-d') && $dateTo === now()->format('Y-m-d')) {
            $this->fetchStocks($apiService, $dataService, $accountId);
        } else {
            $this->warn("Skipping stocks fetch: Stocks can only be fetched for today's date.");
        }
    }



    private function fetchOrders(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo, $accountId)
    {
        try {
            $this->info("Fetching orders for account ID: {$accountId}...");
            $orders = $apiService->fetchPaginatedData('orders', $dateFrom, $dateTo, $accountId);

            if (!empty($orders)) {
                $dataService->saveOrders($orders, $accountId);
                $this->info("Orders saved successfully. Records: " . count($orders));
            } else {
                $this->warn("No new orders found.");
            }
        } catch (\Exception $e) {
            $this->error("Error fetching orders: " . $e->getMessage());
            Log::error("Orders Fetch Error: " . $e->getMessage());
        }
    }

    

    private function fetchSales(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo, $accountId)
    {
        try {
            $this->info("Fetching sales for account ID: {$accountId}...");
            $sales = $apiService->fetchPaginatedData('sales', $dateFrom, $dateTo, $accountId);

            if (!empty($sales)) {
                $dataService->saveSales($sales, $accountId);
                $this->info("Sales saved successfully. Records: " . count($sales));
            } else {
                $this->warn("No new sales found.");
            }
        } catch (\Exception $e) {
            $this->error("Error fetching sales: " . $e->getMessage());
            Log::error("Sales Fetch Error: " . $e->getMessage());
        }
    }

    private function fetchIncomes(ApiService $apiService, DataService $dataService, $dateFrom, $dateTo, $accountId)
    {
        try {
            $this->info("Fetching incomes for account ID: {$accountId}...");
            $incomes = $apiService->fetchPaginatedData('incomes', $dateFrom, $dateTo, $accountId);

            if (!empty($incomes)) {
                $dataService->saveIncomes($incomes, $accountId);
                $this->info("Incomes saved successfully. Records: " . count($incomes));
            } else {
                $this->warn("No new incomes found.");
            }
        } catch (\Exception $e) {
            $this->error("Error fetching incomes: " . $e->getMessage());
            Log::error("Incomes Fetch Error: " . $e->getMessage());
        }
    }

    private function fetchStocks(ApiService $apiService, DataService $dataService, $accountId)
    {
        try {
            $this->info("Fetching stocks for account ID: {$accountId}...");
            $today = now()->format('Y-m-d');

            if (($this->option('fromDate') && $this->option('fromDate') !== $today) ||
                ($this->option('toDate') && $this->option('toDate') !== $today)) {
                $this->warn("Skipping stocks fetch: Stocks data can only be fetched for today.");
                return;
            }

            $stocks = $apiService->fetchPaginatedData('stocks', $today, $today, $accountId);

            if (!empty($stocks)) {
                $dataService->saveStocks($stocks, $accountId);
                $this->info("Stocks saved successfully. Records: " . count($stocks));
            } else {
                $this->warn("No new stock data found.");
            }
        } catch (\Exception $e) {
            $this->error("Error fetching stocks: " . $e->getMessage());
            Log::error("Stocks Fetch Error: " . $e->getMessage());
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
