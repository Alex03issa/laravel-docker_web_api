<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;

class DataService
{
    /**
     * Save Orders with logging
     */
    public function saveOrders(array $orders, $accountId)
    {
        Log::info("Saving orders for account ID: {$accountId}, total: " . count($orders));

        foreach ($orders as $order) {
            try {
               // Log::debug("Processing order g_number: {$order['g_number']} for account ID: {$accountId}");

                Order::updateOrCreate(
                    ['g_number' => $order['g_number'],
                                 'account_id' => $accountId
                                ], // Unique identifier
                    [
                        'account_id' => $accountId,
                        'date' => $order['date'] ?? null,
                        'last_change_date' => $order['last_change_date'] ?? now()->format('Y-m-d'),
                        'supplier_article' => $order['supplier_article'] ?? '',
                        'tech_size' => $order['tech_size'] ?? '',
                        'barcode' => $order['barcode'] ?? null,
                        'total_price' => $order['total_price'] ?? 0,
                        'discount_percent' => $order['discount_percent'] ?? 0,
                        'warehouse_name' => $order['warehouse_name'] ?? '',
                        'oblast' => $order['oblast'] ?? '',
                        'income_id' => $order['income_id'] ?? null,
                        'odid' => $order['odid'] ?? null,
                        'nm_id' => $order['nm_id'] ?? null,
                        'subject' => $order['subject'] ?? '',
                        'category' => $order['category'] ?? '',
                        'brand' => $order['brand'] ?? '',
                        'is_cancel' => $order['is_cancel'] ?? false,
                        'cancel_dt' => $order['cancel_dt'] ?? null,
                        
                    ]
                );

               // Log::debug("Order g_number: {$order['g_number']} saved successfully.");

            } catch (\Exception $e) {
                Log::error("Error saving order g_number: {$order['g_number']} for account ID: {$accountId} - " . $e->getMessage());
            }
        }
    }

    /**
     * Save Sales 
     */
    public function saveSales(array $sales, $accountId)
    {
        Log::info("Saving sales for account ID: {$accountId}, total: " . count($sales));

        foreach ($sales as $sale) {
            try {
                //Log::debug("Processing sale g_number: {$sale['g_number']} for account ID: {$accountId}");

                Sale::updateOrCreate(
                    ['g_number' => $sale['g_number'],
                                'account_id' => $accountId
                            ], 
                    [
                        'account_id' => $accountId,
                        'date' => $sale['date'] ?? null,
                        'last_change_date' => $sale['last_change_date'] ?? now()->format('Y-m-d'),
                        'supplier_article' => $sale['supplier_article'] ?? '',
                        'tech_size' => $sale['tech_size'] ?? '',
                        'barcode' => $sale['barcode'] ?? null,
                        'total_price' => $sale['total_price'] ?? 0,
                        'discount_percent' => $sale['discount_percent'] ?? 0,
                        'warehouse_name' => $sale['warehouse_name'] ?? '',
                        'country_name' => $sale['country_name'] ?? '',
                        'oblast_okrug_name' => $sale['oblast_okrug_name'] ?? '',
                        'region_name' => $sale['region_name'] ?? '',
                        'income_id' => $sale['income_id'] ?? null,
                        'sale_id' => $sale['sale_id'] ?? '',
                        'odid' => $sale['odid'] ?? null,
                        'spp' => $sale['spp'] ?? 0,
                        'for_pay' => $sale['for_pay'] ?? 0,
                        'finished_price' => $sale['finished_price'] ?? 0,
                        'price_with_disc' => $sale['price_with_disc'] ?? 0,
                        'nm_id' => $sale['nm_id'] ?? null,
                        'subject' => $sale['subject'] ?? '',
                        'category' => $sale['category'] ?? '',
                        'brand' => $sale['brand'] ?? '',
                        'is_storno' => $sale['is_storno'] ?? null,
                        
                    ]
                );

               // Log::debug("Sale g_number: {$sale['g_number']} saved successfully.");

            } catch (\Exception $e) {
                Log::error("Error saving sale g_number: {$sale['g_number']} for account ID: {$accountId} - " . $e->getMessage());
            }
        }
    }

    /**
     * Save Incomes
     */
    public function saveIncomes(array $incomes, $accountId)
    {
        Log::info("Saving incomes: " . count($incomes) . " records.");

        foreach ($incomes as $income) {
            try {
                //Log::debug("Processing income_id: {$income['income_id']}");

                Income::updateOrCreate(
                    ['income_id' => $income['income_id'],
                                 'account_id' => $accountId
                                ], 
                    [
                        'account_id' => $accountId,
                        'number' => $income['number'] ?? '',
                        'date' => $income['date'] ?? null,
                        'last_change_date' => $income['last_change_date'] ?? now()->format('Y-m-d'),
                        'supplier_article' => $income['supplier_article'] ?? '',
                        'tech_size' => $income['tech_size'] ?? '',
                        'barcode' => $income['barcode'] ?? null,
                        'quantity' => $income['quantity'] ?? 0,
                        'total_price' => $income['total_price'] ?? 0,
                        'date_close' => $income['date_close'] ?? null,
                        'warehouse_name' => $income['warehouse_name'] ?? '',
                        'nm_id' => $income['nm_id'] ?? null,
                        
                    ]
                );

                //Log::debug("Income income_id: {$income['income_id']} saved successfully.");

            } catch (\Exception $e) {
                Log::error("Error saving income income_id: {$income['income_id']} - " . $e->getMessage());
            }
        }
    }

    /**
     * Save Stocks 
     */
    public function saveStocks(array $stocks, $accountId)
    {
        Log::info("Saving stocks: " . count($stocks) . " records.");

        foreach ($stocks as $stock) {
            try {
               // Log::debug("Processing stock nm_id: {$stock['nm_id']}");

                Stock::updateOrCreate(
                    ['nm_id' => $stock['nm_id'], 
                                 'date' => $stock['date'],
                                 'account_id' => $accountId
                                ],
                    [
                        'account_id' => $accountId,
                        'last_change_date' => $stock['last_change_date'] ?? now()->format('Y-m-d'),
                        'supplier_article' => $stock['supplier_article'] ?? '',
                        'tech_size' => $stock['tech_size'] ?? '',
                        'barcode' => $stock['barcode'] ?? null,
                        'quantity' => $stock['quantity'] ?? 0,
                        'warehouse_name' => $stock['warehouse_name'] ?? '',
                        'in_way_to_client' => $stock['in_way_to_client'] ?? 0,
                        'in_way_from_client' => $stock['in_way_from_client'] ?? 0,
                        'subject' => $stock['subject'] ?? '',
                        'category' => $stock['category'] ?? '',
                        'brand' => $stock['brand'] ?? '',
                        'sc_code' => $stock['sc_code'] ?? null,
                        'price' => $stock['price'] ?? 0.00,
                        'discount' => $stock['discount'] ?? 0.00,
                        
                    ]
                );

                //Log::debug("Stock nm_id: {$stock['nm_id']} saved successfully.");

            } catch (\Exception $e) {
                Log::error("Error saving stock nm_id: {$stock['nm_id']} - " . $e->getMessage());
            }
        }
    }
}
