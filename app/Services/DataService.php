<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Stock;

class DataService
{
    /**
     * Save Orders
     */
    public function saveOrders(array $orders)
    {
        foreach ($orders as $order) {
            Order::updateOrCreate(
                [
                    'g_number' => $order['g_number'], // Unique identifier for orders
                ],
                [
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
        }
    }

    /**
     * Save Sales
     */
    public function saveSales(array $sales)
    {
        foreach ($sales as $sale) {
            Sale::updateOrCreate(
                [
                    'g_number' => $sale['g_number'], // Unique identifier for sales
                ],
                [
                    'date' => $sale['date'] ?? null,
                    'last_change_date' => $sale['last_change_date'] ?? now()->format('Y-m-d'),
                    'supplier_article' => $sale['supplier_article'] ?? '',
                    'tech_size' => $sale['tech_size'] ?? '',
                    'barcode' => $sale['barcode'] ?? null,
                    'total_price' => $sale['total_price'] ?? 0,
                    'discount_percent' => $sale['discount_percent'] ?? 0,
                    'is_supply' => $sale['is_supply'] ?? null,
                    'is_realization' => $sale['is_realization'] ?? null,
                    'promo_code_discount' => $sale['promo_code_discount'] ?? null,
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
        }
    }

    /**
     * Save Incomes
     */
    public function saveIncomes(array $incomes)
    {
        foreach ($incomes as $income) {
            Income::updateOrCreate(
                [
                    'income_id' => $income['income_id'], // Unique identifier for incomes
                ],
                [
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
        }
    }

    /**
     * Save Stocks
     */
    public function saveStocks(array $stocks)
    {
        foreach ($stocks as $stock) {
            Stock::updateOrCreate(
                [
                    'nm_id' => $stock['nm_id'],
                    'date' => $stock['date'],
                ],
                [
                    'last_change_date' => $stock['last_change_date'] ?? now()->format('Y-m-d'),
                    'supplier_article' => $stock['supplier_article'] ?? '',
                    'tech_size' => $stock['tech_size'] ?? '',
                    'barcode' => $stock['barcode'] ?? null,
                    'quantity' => $stock['quantity'] ?? 0,
                    'is_supply' => $stock['is_supply'] ?? null,
                    'is_realization' => $stock['is_realization'] ?? null,
                    'quantity_full' => $stock['quantity_full'] ?? 0,
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
        }
    }
}
