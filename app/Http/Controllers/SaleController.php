<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiService;
use App\Services\DataService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

use App\Models\Account;

class SaleController extends Controller
{
    protected $apiService;
    protected $dataService;

    public function __construct(ApiService $apiService, DataService $dataService)
    {
        $this->apiService = $apiService;
        $this->dataService = $dataService;
    }

    public function index(Request $request)
    {
        Log::info("Incoming request to fetch sales", ['params' => $request->all()]);

        try {
            $validated = $request->validate([
                'dateFrom' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!$this->isValidDate($value)) {
                            $fail("The $attribute must be in format Y-m-d or Y-m-d H:i:s.");
                        }
                    }
                ],
                'dateTo' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value && !$this->isValidDate($value)) {
                            $fail("The $attribute must be in format Y-m-d or Y-m-d H:i:s.");
                        }
                    }
                ],
                'account_id' => 'required|integer|exists:accounts,id', 
            ]);

            $dateFrom = $validated['dateFrom'];
            $dateTo = $validated['dateTo'] ?? $dateFrom;
            $accountId = $validated['account_id']; 

            Log::info("Fetching sales from API", ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'account_id' => $accountId]);

            // Fetch data from API
            $data = $this->apiService->fetchPaginatedData('sales', $dateFrom, $dateTo, $accountId);

            if (empty($data)) {
                Log::warning("No sales data returned", ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'account_id' => $accountId]);
                return response()->json(['message' => 'No sales data found'], 404);
            }

            // Save data to database
            Log::info("Saving " . count($data) . " sales records to database.");
            $this->dataService->saveSales($data, $accountId);

            Log::info("Sales successfully fetched and saved.");
            return response()->json(['message' => 'Sales fetched and saved successfully', 'records' => count($data)], 200);

        } catch (ValidationException $e) {
            Log::error("Validation error", ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            Log::error("Internal Server Error", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate date format
     */
    private function isValidDate($date)
    {
        $formats = ['Y-m-d', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $date);
            if ($parsedDate && $parsedDate->format($format) === $date) {
                return true;
            }
        }

        return false;
    }
}
