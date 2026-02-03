<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Item;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

class ItemController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_item_read()');

        return response()->json(['success' => true, 'data' => $data], 200);
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
        $total = DB::select('CALL sp_item_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_item_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

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
            'exp_time' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
                $data = array(
                    $request->nama,
                    $request->exp_time,
                    $request->user
                );

                $item = DB::select('CALL sp_item_create(?, ?, ?)', $data);

                if($item[0]->status == 1){
                    return response()->json(['success' => true, 'user' => $item, 'message' => 'Success To Saved Data Item'], 200);
                }else{
                    return response()->json(['false' => true, 'message' => 'There is already data with the same name.']);
                }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Saved Data Item', 'err' => $e->getMessage()]);
        }
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return ItemResource
     */
    public function update(Request $request, $id){
        $request->validate([
            'nama' => 'required',
            'exp_time' => 'required'
        ]);

        try{
            $item = Item::findOrFail($id);

            $data = array(
                $request->nama,
                $request->exp_time,
                $request->user,
                $id
            );

            $item = DB::select('CALL sp_item_update(?, ?, ?, ?)', $data);

            if($item[0]->status == 0){
                return response()->json(['false' => true, 'message' => $item[0]->messages]);
            }

            return response()->json(['success' => true, 'item' => $item, 'message' => 'Success To Updated Data Item'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Update Data Item', 'err' => $e->getMessage()]);
        }
    }

    /**
    * destroy
    *
    * @param  mixed $id
    * @return ItemResource
    */
    public function destroy(Request $request, $id){
        $item = DB::select('CALL sp_item_delete(?, ?)', array($id, $request->deletedBy));
        return response()->json(['success' => true, 'user' => $item, 'message' => 'Success To Deleted Data Item'], 200);
    }

    public function copy(){
        try {
            $client = new Client([
                'base_uri' => 'https://pc29.chunagon.net/api30/rest/v1/'
            ]);
            $res = $client->request('GET', 'in/initem/index', [
                'query' => [
                    'page' => '1' ,
                    'per-page' => '999999',
                    'sort' => '-editdate'
                ]
            ]);

            $contents = json_decode($res->getBody()->getContents());
            $record = DB::table('rgitem')->orderBy('createddate', 'desc')->first();
            $arr = $contents->data;

            if($record && $arr[0]->editdate >= $record->createddate){
                $arr = array_filter($arr, function($obj) use ($record){
                    if($obj->editdate >= $record->createddate){
                        return true;
                    }

                    return false;
                });
            }

            usort($arr, function($a, $b){
                return strcmp($a->editdate, $b->editdate);
            });

            foreach ($arr as $key => $value) {
                $data = array(
                    $value->itemid,
                    $value->itemnm,
                    $value->itemsubcd,
                    $value->unitstk,
                    $value->itembrandnm,
                    $value->itembrandcd,
                    $value->itemcatcd,
                    $value->itemcatnm,
                    $value->itemsubnm,
                    $value->itemgroupcd,
                    $value->itemgroupnm,
                    $value->editdate
                );

                DB::select('CALL sp_item_copy('.implode(', ', array_fill(0, count($data), "?")).')', $data);
            }

            return response()->json(['success' => true], 200);
        } catch (ConnectException $e) {
            print_r($e->getMessage());
        }
    }
}
