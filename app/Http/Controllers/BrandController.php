<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Brand;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_rgbrand_read()');

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function search(Request $request) {
        $param = json_decode($request->param);

        $data = [
            $param->id ?? 0,
            $param->page ?? 1,
            $param->limit ?? 5,
            $param->search ?? '',
            $param->sortField ?? '',
            $param->sortOrder ?? '-1',
        ];
        $total = DB::select('CALL sp_rgbrand_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_rgbrand_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return json
     */
    public function store(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
                $data = array(
                    $request->nama,
                    $request->type0,
                    $request->user
                );

                $brand = DB::select('CALL sp_rgbrand_create(?, ?, ?)', $data);

                if($brand[0]->status == 1){
                    return response()->json(['success' => true, 'user' => $brand, 'message' => 'Success To Saved Data Brand'], 200);
                }else{
                    return response()->json(['false' => true, 'message' => 'There is already data with the same name.']);
                }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Saved Data Brand', 'err' => $e->getMessage()]);
        }
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     */
    public function update(Request $request, $id){
        $request->validate([
            'nama' => 'required',
            'type0' => 'required'
        ]);

        try{
            $brand = Brand::findOrFail($id);

            $data = array(
                $request->nama,
                $request->type0,
                $request->user,
                $id
            );

            $brand = DB::select('CALL sp_rgbrand_update(?, ?, ?, ?)', $data);

            if($brand[0]->status == 0){
                return response()->json(['false' => true, 'message' => $brand[0]->messages]);
            }

            return response()->json(['success' => true, 'item' => $brand, 'message' => 'Success To Updated Data Brand'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Update Data Brand', 'err' => $e->getMessage()]);
        }
    }

    /**
    * destroy
    *
    * @param  mixed $id
    */
    public function destroy(Request $request, $id){
        $brand = DB::select('CALL sp_rgbrand_delete(?, ?)', array($id, $request->deletedBy));
        return response()->json(['success' => true, 'user' => $brand, 'message' => 'Success To Deleted Data Brand'], 200);
    }

    public function unconfirmuser() {
        $_ipAddr = $_SERVER['REMOTE_ADDR'];
        
        if (in_array($_ipAddr, ['::1', '127.0.0.1'])) {
            try {
                $affectedRows = DB::table('tbl_user')
                                ->where('status', 0)
                                ->where('deleted', 0)
                                ->whereNotNull('pwd_reset_token')
                                ->update([
                                    'deleted' => 1,
                                    'deletedby' => 'cron',
                                    'deleteddate' => Carbon::now(),
                                ]);

                DB::insert('INSERT INTO t_log (log_type, log, createddate) VALUES (?, ?, ?)', [
                                    'cron-job', 
                                    'remove-unconfirm-user', 
                                    date("Y-m-d H:i:s")
                                ]);
                return response()->json(['success' => true, 'data' => $affectedRows, 'total' => $affectedRows], 200);
            }
            catch (\Exception $e) {
                Log::error('Error updating tbl_user: ' . $e->getMessage());
                return response()->json(['success' => false, 'data' => $e->getMessage(), 'total' => 0], 500);
            }
        }
    }
}
