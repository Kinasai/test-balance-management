<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BalanceController
{
    public function transfer(Request $request) {
        //POST /api/transfer

        //входящий
        //{
        //  "from_user_id": 1,
        //  "to_user_id": 2,
        //  "amount": 150.00,
        //  "comment": "Перевод другу"
        //}
        User::query()->findOrFail($request->input('from_user_id'));
//        if(!$from_user)
//            return response()->json([
//                'error' => 'User not found [from_user_id]'
//            ], 404);
        User::query()->findOrFail($request->input('to_user_id'));
//        if(!$to_user_id)
//            return response()->json([
//                'error' => 'User not found [to_user_id]'
//            ], 404);

        $validated = $request->validate([
            'from_user_id' => ['required', 'integer', 'exists:users,id'],
            'to_user_id' => ['required', 'integer', 'exists:users,id', 'different:from_user_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['required', 'string', 'max:255']
        ]);

        try{
            return DB::transaction(function () use ($validated) {
                $fromBalance = Balance::query()
                    ->where('user_id', $validated['from_user_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if($fromBalance->amount < $validated['amount']) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient funds'
                    ]);
                }
                $fromBalance->decrement('amount', $validated['amount']);

                $toBalance = Balance::query()->firstOrCreate(
                    ['user_id' => $validated['to_user_id']],
                    ['amount' => 0]
                );

                $toBalance->increment('amount', $validated['amount']);

                //запись транзакций
                Transaction::query()->create([
                    'user_id' => $validated['from_user_id'],
                    'amount' => $validated['amount'],
                    'comment' => $validated['comment'],
                    'type' => TransactionType::transferOut,
                    'related_user_id' => $validated['to_user_id']
                ]);
                Transaction::query()->create([
                    'user_id' => $validated['to_user_id'],
                    'amount' => $validated['amount'],
                    'comment' => $validated['comment'],
                    'type' => TransactionType::transferIn,
                    'related_user_id' => $validated['from_user_id']
                ]);

                return response()->json([
                    'message' => 'Funds withdrawn successfully'
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 409);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Transaction failed'
            ], 500);
        }
    }


    /**
     * @throws ValidationException
     */
    public function getBalance(Request $request, $user_id) {
        //GET /api/balance/{user_id}
        User::query()->findOrFail($user_id);
//        if(!$user)
//            return response()->json([
//                'error' => 'User not found'
//            ], 404);

        $validated = validator(compact('user_id'), [
            'user_id' => ['required', 'integer', 'exists:users,id']
        ])->validate();

        $balance = Balance::query()
            ->where('user_id', $validated['user_id'])
            ->value('amount') ?? 0;

        //{
        //  "user_id": 1,
        //  "balance": 350.00
        //}
        return response()->json([
            'user_id' => (int)$validated['user_id'],
            'balance' => (float)$balance
        ]);

    }

    public function deposit(Request $request) {
        //POST /api/deposit
        //входящий
        //{
        //  "user_id": 1,
        //  "amount": 500.00,
        //  "comment": "Пополнение через карту"
        //}
        User::query()->findOrFail($request->input('user_id'));
//        if(!$user)
//            return response()->json([
//                'error' => 'User not found'
//            ], 404);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['required', 'string', 'max:255']
        ]);

        try{
            return DB::transaction(function () use ($validated) {
                $balance = Balance::query()->firstOrCreate(
                    ['user_id' => $validated['user_id']],
                    ['amount' => 0]
                );

                $balance->increment('amount', $validated['amount']);

                Transaction::query()->create([
                    'user_id' => $validated['user_id'],
                    'amount' => $validated['amount'],
                    'comment' => $validated['comment'],
                    'type' => TransactionType::Deposit
                ]);

                return response()->json([
                    'message' => 'Funds deposited successfully'
                ]);
            });
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Transaction failed'
            ], 500);
        }

    }
    public function withdraw(Request $request) {
        //POST /api/withdraw

        //входящий
        //Баланс не может уходить в минус.
        //{
        //  "user_id": 1,
        //  "amount": 200.00,
        //  "comment": "Покупка подписки"
        //}
        $user = User::query()->findOrFail($request->input('user_id'));
//        if(!$user)
//            return response()->json([
//                'error' => 'User not found'
//            ], 404);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['required', 'string', 'max:255']
        ]);

        try{
            return DB::transaction(function () use ($validated) {
                $balance = Balance::query()
                    ->where('user_id', $validated['user_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if($balance->amount < $validated['amount']) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient funds'
                    ]);
                }
                $balance->decrement('amount', $validated['amount']);

                Transaction::query()->create([
                    'user_id' => $validated['user_id'],
                    'amount' => $validated['amount'],
                    'comment' => $validated['comment'],
                    'type' => TransactionType::Withdraw
                ]);

                return response()->json([
                    'message' => 'Funds withdrawn successfully'
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 409);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Transaction failed'
            ], 500);
        }
    }

}
