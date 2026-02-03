<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Kombo;

class KomboController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_rgkombo_read()');

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function search(Request $request) {
        $param = json_decode($request->param);

        $data = [
            $param->search ?? '',
        ];
        
        // $total = DB::select('CALL sp_rgkombo_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $total = 2;
        $data = DB::select('CALL sp_rgkombo_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }
   
}
