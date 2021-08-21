<?php

namespace App\Http\Controllers\API\v1;

use App\Balance;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $balance = Balance::where('user_id', $request->auth->id)->get();
        return ResponseFormatter::success([
            'balance' => $balance
        ], 'Success Get Balance');
    }

    public function store(Request $request)
    {
    }

    public function show($id)
    {
        $balance = new Balance();
        $balance = $balance->find($id);
        if ($balance) {
            return ResponseFormatter::success([
                'balance' => $balance
            ], 'get balance success');
        } else {
            return ResponseFormatter::error([
                'error' => 'balance not found'
            ], 'get balance failed', 400);
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
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'create transaction fails', 400);
        }

        $balance = new Balance();
        $balance = $balance::find($id);
        if ($balance) {
            $balance->status = $request->get('status');
            $balance->balance = $request->get('balance');
            $balance->description = $request->get('description');
            $balance->save();

            return ResponseFormatter::success([
                'balance' => $balance
            ], 'get balance success');
        } else {
            return ResponseFormatter::error([
                'error' => 'balance not found'
            ], 'update transaction fails', 400);
        }
    }

    public function destroy($id)
    {
        $balance = new Balance();
        $balance = $balance::find($id);
        if ($balance) {
            $balance->delete();
            return ResponseFormatter::success([
                'balance' => $balance
            ], 'delete balance success');
        } else {
            return ResponseFormatter::error([
                'error' => 'balance not found'
            ], 'delete transaction fails', 400);
        }
    }
}
