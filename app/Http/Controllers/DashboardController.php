<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller{
    public function index(Request $request) {
        $param = json_decode($request->param);

        $data = [
            $param->jenis,
            $param->id
        ];

        $data = DB::select('CALL sp_dashboard_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data], 200);
    }
}
