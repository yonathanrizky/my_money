<?php

namespace App\Http\Controllers\Api\v1;

use App\Balance;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $balance = new Balance();
        $data_balance = $balance->where('user_id', $request->auth->id)->orderBy('created_at', 'desc')->get();
        $user_id = $request->auth->id;
        $balance_in = DB::select(
            DB::raw("select sum(balance) as balance from balances where status = '1' and user_id = '$user_id' group by user_id")
        )[0]->balance;

        $balance_out = DB::select(
            DB::raw("select sum(balance) as balance from balances where status = '2' and user_id = '$user_id' group by user_id")
        )[0]->balance;

        $balance = $balance_in - $balance_out;

        $data = [
            'data' => [
                'balance' => $balance,
                'data_balance' => $data_balance
            ]
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'balance' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $balance = Balance::create([
            'user_id' => $request->auth->id,
            'status' => $request->get('status'),
            'balance' => $request->get('balance'),
            'description' => $request->get('description')
        ]);

        return response()->json(compact('balance'), 201);
    }

    public function show($id)
    {
        $balance = new Balance();
        $balance = $balance->find($id);
        if ($balance) {
            return response()->json(compact('balance'), 201);
        } else {
            return response()->json(['error' => 'id not found'], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'balance' => 'required|numeric',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $balance = new Balance();
        $balance = $balance::find($id);
        if ($balance) {
            $balance->status = $request->get('status');
            $balance->balance = $request->get('balance');
            $balance->description = $request->get('description');
            $balance->save();

            return response()->json(compact('balance'), 201);
        } else {
            return response()->json(['error' => 'id not found'], 400);
        }
    }

    public function destroy($id)
    {
        $balance = new Balance();
        $balance = $balance::find($id);
        if ($balance) {
            $balance->delete();
            return response()->json(compact('balance'), 200);
        } else {
            return response()->json(['error' => 'id not found'], 400);
        }
    }
}
