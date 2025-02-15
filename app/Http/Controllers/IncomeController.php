<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiServices;
use App\Services\DataService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Income;
use App\Models\Account;
use App\Models\ApiToken;

class IncomeController extends Controller
{
    protected $apiService;
    protected $dataService;

    public function __construct(ApiServices $apiService, DataService $dataService)
    {
        $this->apiService = $apiService;
        $this->dataService = $dataService;
    }

    /**
     * Retrieve local incomes from the database with authentication.
     */
    public function Incomes(Request $request)
    {
        Log::info("Incoming request to fetch local incomes", ['params' => $request->all()]);

        try {
            // Extract headers for authentication
            $authorizationHeader = $request->header('Authorization');
            $apiKey = $request->header('X-Api-Key');
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
                'dateFrom' => 'nullable|date',
                'dateTo' => 'nullable|date',
            ]);

            $accountId = $account->id;

            $latestStoredDate = Income::where('account_id', $accountId)->max('date');

            $dateFrom = $validated['dateFrom'] ?? ($latestStoredDate ? $latestStoredDate : now()->subDays(7)->format('Y-m-d'));
            $dateTo = $validated['dateTo'] ?? now()->format('Y-m-d');

            Log::info("Fetching local incomes from database", [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo, 
                'account_id' => $accountId
            ]);

            $incomes = Income::where('account_id', $accountId)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->orderBy('date', 'desc')
                ->get();

            if ($incomes->isEmpty()) {
                Log::warning("No local incomes found", [
                    'account_id' => $accountId, 
                    'dateFrom' => $dateFrom, 
                    'dateTo' => $dateTo
                ]);
                return response()->json(['message' => 'No local incomes found'], 404);
            }

            Log::info("Retrieved " . count($incomes) . " local incomes from database.");
            return response()->json([
                'message' => 'Local incomes retrieved successfully',
                'incomes' => $incomes
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
