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

    public function Incomes(Request $request)
    {
        Log::info("Incoming request to fetch incomes", ['params' => $request->all()]);

        try {
            // 🔹 Authentication Handling
            $authorizationHeader = $request->header('Authorization');
            $apiKey = $request->header('X-Api-Key');
            $login = $request->header('X-Login');
            $password = $request->header('X-Password');

            $account = null;

            switch (true) {
                case !empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches):
                    $tokenValue = $matches[1];
                    $apiToken = ApiToken::where('token_value', $tokenValue)->with('account')->first();
                    if ($apiToken) {
                        $account = $apiToken->account;
                    }
                    break;

                case !empty($apiKey):
                    $apiToken = ApiToken::where('token_value', $apiKey)
                        ->whereHas('tokenType', fn($query) => $query->where('type', 'api-key'))
                        ->with('account')
                        ->first();
                    if ($apiToken) {
                        $account = $apiToken->account;
                    }
                    break;

                case !empty($login) && !empty($password):
                    $apiToken = ApiToken::whereHas('tokenType', fn($query) => $query->where('type', 'login-password'))
                        ->with('account')
                        ->get();

                    foreach ($apiToken as $token) {
                        $credentials = json_decode($token->token_value, true);
                        if ($credentials && isset($credentials['login'], $credentials['password'])) {
                            if ($credentials['login'] === $login && $credentials['password'] === $password) {
                                $account = $token->account;
                                break;
                            }
                        }
                    }
                    break;
            }

            // 🔹 Extract `account_id` from request, ensuring it's correct
            $requestedAccountId = $request->get('account_id');
            if ($account && $requestedAccountId && $requestedAccountId != $account->id) {
                Log::warning("Account ID mismatch. Requested: {$requestedAccountId}, Authenticated: {$account->id}");
                return response()->json(['error' => 'Unauthorized - Account ID mismatch'], 403);
            }

            if (!$account) {
                Log::warning("Authentication failed, rejecting request.");
                return response()->json(['error' => 'Unauthorized - Missing Authentication'], 401);
            }

            $validated = $request->validate([
                'dateFrom' => 'nullable|date',
                'dateTo' => 'nullable|date',
            ]);

            // 🔹 Use the correct `account_id`
            $accountId = $account->id;

            // 🔹 Determine `dateFrom` and `dateTo`
            $latestStoredDate = Income::where('account_id', $accountId)->max('date');
            $dateFrom = $validated['dateFrom'] ?? ($latestStoredDate ? $latestStoredDate : now()->subDays(7)->format('Y-m-d'));
            $dateTo = $validated['dateTo'] ?? now()->format('Y-m-d');

            Log::info("Fetching incomes from API", [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'account_id' => $accountId
            ]);

            // 🔹 Fetch Incomes from API
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

            // ✅ Save incomes after fetching
            Log::info("Saving retrieved incomes for account ID: {$accountId}");
            $this->dataService->saveIncomes($incomes->toArray(), $accountId);

            return response()->json([
                'message' => 'Incomes fetched and saved successfully',
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
}
