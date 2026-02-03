<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RoleDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Role;
use App\Models\RoleDetail;

class RoleController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_role_read()');

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
        $total = DB::select('CALL sp_role_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_role_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }

    /**
     * allDetails
     *
     * @return void
     */
    public function allDetails() {
        $data = DB::select('CALL sp_roledetail_read(?)', [0]);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * show
     *
     * @return void
     */
    public function show($id) {
        $data = DB::select('CALL sp_roledetail_read(?)', [$id]);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return RedirectResponse
     */
    public function store(Request $request){
        $request->validate([
            'nama' => 'required'
        ]);

        try {
            $data = [
                $request->nama,
                $request->user,
            ];

            $id = DB::select('CALL sp_role_create(?, ?)', $data)[0]->id;

            foreach($request->details as $key => $value){
                $dataDetails = [
                    $id,
                    $value['menu_details_id'],
                    $request->user
                ];

                DB::select('CALL sp_roledetail_create(?, ?, ?)', $dataDetails);
            }

            return response()->json(['success' => true, 'data' => $id], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Role', 'err' => $e->getMessage()]);
        }
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id){
        $request->validate([
            'nama' => 'required'
        ]);

        try{
            $data = [
                $request->nama,
                $request->user,
                $id // role_id
            ];

            DB::select('CALL sp_role_update(?, ?, ?)', $data);

            $notDeletedId = [];
            foreach($request->details as $key => $value){
                $dataDetails = [
                    $id, // role_id
                    $value['menu_details_id'],
                    $request->user,
                    '0'
                ];

                array_push($notDeletedId, $value['menu_details_id']);
                DB::select('CALL sp_roledetail_update(?, ?, ?, ?)', $dataDetails);
            }
            $roleDetail = RoleDetail::where('role_id', $id)->get();

            foreach ($roleDetail as $k => $v) {
                if(!in_array($v['menu_id'], $notDeletedId)){
                    $dataDetails = [
                        $v['id'], // role_id
                        $v['menu_id'],
                        $request->user,
                        '1'
                    ];

                    DB::select('CALL sp_roledetail_update(?, ?, ?, ?)', $dataDetails);
                }
            }

            return response()->json(['success' => true, 'data' => $id], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Role', 'err' => $e->getMessage()]);
        }
    }

     /**
     * destroy
     *
     * @param  mixed $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id){
        $data = DB::select('CALL sp_role_delete(?, ?)', array($id, $request->deletedBy));
        return response()->json(['success' => true, 'data' => $data], 200);
    }
}
