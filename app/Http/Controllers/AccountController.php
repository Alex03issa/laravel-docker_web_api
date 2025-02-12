<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    /**
     * Получить список аккаунтов с привязкой к компаниям
     */
    public function index()
    {
        try {
            Log::info('Fetching all accounts');
            $accounts = Account::with('company')->get();
            return response()->json($accounts, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching accounts: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Создать новый аккаунт
     */
    public function store(Request $request)
    {
        try {
            Log::info('Creating new account', ['request' => $request->all()]);

            $validatedData = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'name' => 'required|string|unique:accounts,name',
            ]);

            $account = Account::create($validatedData);

            Log::info('Account created successfully', ['account_id' => $account->id]);

            return response()->json([
                'message' => 'Аккаунт успешно создан',
                'account' => $account
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('Validation error while creating account', ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating account: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to create account'
            ], 500);
        }
    }

    /**
     * Получить аккаунт по ID
     */
    public function show($id)
    {
        try {
            Log::info("Fetching account details", ['account_id' => $id]);

            $account = Account::with('company')->findOrFail($id);

            return response()->json($account, 200);
        } catch (ModelNotFoundException $e) {
            Log::warning("Account not found", ['account_id' => $id]);
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Account not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching account details: " . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch account details'
            ], 500);
        }
    }
}
