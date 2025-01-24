<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiService;
use App\Services\DataService;
use Illuminate\Validation\ValidationException;

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
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!$this->isValidDate($value)) {
                            $fail("The $attribute must be in format Y-m-d or Y-m-d H:i:s.");
                        }
                    }
                ],
            ]);

            $data = $this->apiService->fetchPaginatedData('sales', $validated['dateFrom'], $validated['dateTo']);
            $this->dataService->saveSales($data);

            return response()->json(['message' => 'Incomes fetched and saved successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
