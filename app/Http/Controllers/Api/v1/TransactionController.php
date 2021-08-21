<?php

namespace App\Http\Controllers\API\v1;

use App\Balance;
use App\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'get transaction fails', 400);
        }

        $carbon = new Carbon($request->date);
        $startDate = $carbon->firstOfMonth()->toDateString();
        $endDate = $carbon->lastOfMonth()->toDateString();

        $transaction = Transaction::whereBetween('created_at', [$startDate, $endDate])->get();
        $balance = Balance::where('user_id', $request->auth->id)->get()[0]->balance;

        return ResponseFormatter::success([
            'balance' => $balance,
            'transaction' => $transaction
        ], 'get transaction success');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'amount' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'create transaction fails', 400);
        }

        $transaction = Transaction::create([
            'user_id' => $request->auth->id,
            'status' => $request->get('status'),
            'amount' => $request->get('amount'),
            'description' => $request->get('description')
        ]);

        $balance = Balance::where('user_id', $request->auth->id)->get()[0]->balance;
        /**
         * Tambah saldo = 1
         * Kurang Saldo = 2
         */
        if ($request->status == '1') {
            $balance = $balance + $request->get('amount');
        } else if ($request->status == '2') {
            $balance = $balance - $request->get('amount');
        }

        Balance::where('user_id', $request->auth->id)->update(['balance' => $balance]);

        return ResponseFormatter::success([
            'balance' => $balance,
            'transaction' => $transaction
        ], 'create transaction success');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $balance = Balance::where('user_id', $request->auth->id)->get()[0]->balance;
        $transaction = Transaction::find($id);

        return ResponseFormatter::success([
            'balance' => $balance,
            'transaction' => $transaction
        ], 'get detail transaction success');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'amount' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'create transaction fails', 400);
        }

        $transaction = Transaction::findOrFail($id);

        $balance = Balance::where('user_id', $request->auth->id)->get()[0]->balance;

        /**
         * untuk update di balik perhitungannya
         * Tambah saldo = 2
         * Kurang Saldo = 1
         */
        if ($request->status == '1') {
            $balance = $balance - $request->get('amount');
        } else if ($request->status == '2') {
            $balance = $balance + $request->get('amount');
        }

        Balance::where('user_id', $request->auth->id)->update(['balance' => $balance]);

        $transaction->amount = $request->amount;
        $transaction->description = $request->description;
        $transaction->save();

        return ResponseFormatter::success([
            'balance' => $balance,
            'transaction' => $transaction
        ], 'update transaction success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $status = $transaction->status;

        $balance = Balance::where('user_id', $request->auth->id)->get()[0]->balance;

        $inTransaction = Transaction::where('status', '1')->where('id', '!=', $transaction->id)->sum('amount');
        $outTransaction = Transaction::where('status', '2')->where('id', '!=', $transaction->id)->sum('amount');

        if ($status == '1') {
            $balance = $outTransaction - $inTransaction;
        } else if ($status == '2') {
            $balance = $outTransaction + $inTransaction;
        }

        Balance::where('user_id', $request->auth->id)->update(['balance' => $balance]);
        $transaction->delete();

        return ResponseFormatter::success([
            'balance' => $balance,
            'transaction' => $transaction
        ], 'delete transaction success');
    }
}
