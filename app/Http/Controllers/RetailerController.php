<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Retailer;

class RetailerController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_retailer_read()');

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function show($id) {
        // $param = json_decode($request->param);

        $data = DB::select('select * from rgshop where id=:id', [
            ':id' => $id
        ]);
        return response()->json(['data' => $data], 200);
    }

    public function search(Request $request) {
        $param = json_decode($request->param);

        $data = [
            $param->id,
            $param->page,
            $param->limit,
            $param->search ?? '',
            $param->sortField ?? '',
            $param->sortOrder ?? '-1',
        ];
        $total = DB::select('CALL sp_retailer_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_retailer_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

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
            'nama' => 'required',
            'email' => 'required',
            'wanumber' => 'required',
            'phonenumber' => 'required',
            'address' => 'required',
            'user_id' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
                $data = array(
                    $request->nama,
                    $request->address,
                    $request->email,
                    $request->user_id,
                    $request->wanumber,
                    $request->phonenumber,
                    $request->user
                );

                $shop = DB::select('CALL sp_retailer_create(?, ?, ?, ?, ?, ?, ?)', $data);

                if($shop[0]->status == 1){
                    return response()->json(['success' => true, 'data' => $shop, 'message' => 'Saved Data Retailer'], 200);
                }else{
                    return response()->json(['false' => true, 'message' => 'There is already data with the same name.']);
                }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Retailer', 'err' => $e->getMessage()]);
        }
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return RetailerResource
     */
    public function update(Request $request, $id){
        $request->validate([
            'nama' => 'required',
            'email' => 'required',
            'wanumber' => 'required',
            'phonenumber' => 'required',
            'address' => 'required',
            'user_id' => 'required'
        ]);

        try{
            $shop = Retailer::findOrFail($id);

            $data = array(
                $request->nama,
                $request->address,
                $request->email,
                $request->user_id,
                $request->wanumber,
                $request->phonenumber,
                $request->user,
                $id
            );

            $shop = DB::select('CALL sp_retailer_update(?, ?, ?, ?, ?, ?, ?, ?)', $data);

            if($shop[0]->status == 0){
                return response()->json(['false' => true, 'message' => $shop[0]->messages]);
            }

            return response()->json(['success' => true, 'data' => $shop, 'message' => 'Updated Data Retailer'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Update Data Retailer', 'err' => $e->getMessage()]);
        }
    }

    /**
    * destroy
    *
    * @param  mixed $id
    * @return RetailerResource
    */
    public function destroy(Request $request, $id){
        $shop = DB::select('CALL sp_retailer_delete(?, ?)', array($id, $request->deletedBy));
        return response()->json(['success' => true, 'data' => $shop, 'message' => 'Deleted Data Retailer'], 200);
    }

    public function shop($id){
        $data = DB::select('CALL sp_retailer_by_shopid_read(?)', array($id));

        return response()->json(['success' => true, 'data' => $data], 200);
    }
}
