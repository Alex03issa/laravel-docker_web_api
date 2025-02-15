<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiServices;
use App\Services\DataService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Account;
use App\Models\ApiToken;

class OrderController extends Controller
{
    protected $apiService;
    protected $dataService;

    public function __construct(ApiServices $apiService, DataService $dataService)
    {
        $this->apiService = $apiService;
        $this->dataService = $dataService;
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

    public function Orders(Request $request)
    {
        Log::info("Incoming request to fetch local orders", ['params' => $request->all()]);

        try {
            $authorizationHeader = $request->header('Authorization');
            $apiKey = $request->header('x-api-key');
            $login = $request->header('X-Login');
            $password = $request->header('X-Password');

            $account = null;

            switch (true) {
                case !empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches):
                    $tokenValue = $matches[1];
                    $apiToken = ApiToken::where('token_value', $tokenValue)->with('account')->first();
                    if (!$apiToken) {
                        Log::warning("Invalid Bearer Token used", ['token' => $tokenValue]);
                        return response()->json(['error' => 'Unauthorized - Invalid Token'], 401);
                    }
                    $account = $apiToken->account;
                    break;

                case !empty($apiKey):
                    $apiToken = ApiToken::where('token_value', $apiKey)->whereHas('tokenType', function ($query) {
                        $query->where('type', 'api-key');
                    })->with('account')->first();
                    if (!$apiToken) {
                        Log::warning("Invalid API Key used", ['api_key' => $apiKey]);
                        return response()->json(['error' => 'Unauthorized - Invalid API Key'], 401);
                    }
                    $account = $apiToken->account;
                    break;

                case !empty($login) && !empty($password):
                    $apiToken = ApiToken::whereHas('tokenType', function ($query) {
                        $query->where('type', 'login-password');
                    })->with('account')->get();

                    foreach ($apiToken as $token) {
                        $credentials = json_decode($token->token_value, true);
                        if ($credentials && isset($credentials['login'], $credentials['password'])) {
                            if ($credentials['login'] === $login && $credentials['password'] === $password) {
                                $account = $token->account;
                                break;
                            }
                        }
                    }

                    if (!$account) {
                        Log::warning("Invalid login/password authentication", ['login' => $login]);
                        return response()->json(['error' => 'Unauthorized - Invalid Login Credentials'], 401);
                    }
                    break;

                default:
                    return response()->json(['error' => 'Unauthorized - Missing Authentication'], 401);
            }

            $validated = $request->validate([
                'dateFrom' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value && !$this->isValidDate($value)) {
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
            ]);

            $accountId = $account->id;

            $latestStoredDate = Order::where('account_id', $accountId)->max('date');

            $dateFrom = $validated['dateFrom'] ?? ($latestStoredDate ? $latestStoredDate : now()->subDays(7)->format('Y-m-d'));
            $dateTo = $validated['dateTo'] ?? now()->format('Y-m-d');

            Log::info("Fetching local orders from database", [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo, 
                'account_id' => $accountId
            ]);

            $orders = Order::where('account_id', $accountId)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->orderBy('date', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                Log::warning("No local orders found", [
                    'account_id' => $accountId, 
                    'dateFrom' => $dateFrom, 
                    'dateTo' => $dateTo
                ]);
                return response()->json(['message' => 'No local orders found'], 404);
            }

            Log::info("Retrieved " . count($orders) . " local orders from database.", ['orders' => $orders]);

            Log::info("Response Data:", [
                'message' => 'Local orders retrieved successfully',
                'orders' => $orders
            ]);

            return response()->json([
                'message' => 'Local orders retrieved successfully',
                'orders' => $orders
            ], 200);

        } catch (ValidationException $e) {
            Log::error("Validation error", ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            Log::error("Internal Server Error", [
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString()
            ]);

            Log::error("Response Error:", [
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}
