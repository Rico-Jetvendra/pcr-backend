<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuDetail;

class MenuController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_menu_read()');

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function all() {
        $data = DB::select('CALL sp_all_menu_read()');

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
        $total = DB::select('CALL sp_menu_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_menu_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }

    /**
     * details
     *
     * @return void
     */
    public function details($id) {
        $data = DB::select('CALL sp_menudetail_read(?)', [$id]);

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
            $menuData = [
                $request->nama,
                $request->parent ?? 0,
                $request->link,
                $request->sort,
                $request->editedby
            ];
            $id = DB::select('CALL sp_menu_create(?, ?, ?, ?, ?)', $menuData)[0]->id;

            foreach($request->details as $key => $value){
                $menuDetailsData = [
                    $id,
                    $value['action'],
                    $request->user
                ];
                DB::select('CALL sp_menudetail_create(?, ?, ?)', $menuDetailsData);
            }

            return response()->json(['success' => true, 'id' => $id], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Menu', 'err' => $e->getMessage()]);
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
            $menu = Menu::findOrFail($id);
            $menuData = [
                $request->nama,
                $request->parent ?? 0,
                $request->link,
                $request->sort,
                $request->user,
                $id
            ];
            DB::select('CALL sp_menu_update(?, ?, ?, ?, ?, ?)', $menuData);

            $deletedId = [];
            $menuDetailAll = MenuDetail::where('menu_id', $id);
            foreach($request->details as $key => $value){
                if(array_key_exists('id', $value)){
                    $menuDetails = [
                        $value['action'],
                        $request->user,
                        $value['deleted'],
                        $value['id']
                    ];

                    DB::select('CALL sp_menudetail_update(?, ?, ?, ?)', $menuDetails);

                    array_push($deletedId, $value['id']);
                }else{
                    $menuDetails = [
                        $id,
                        $value['action'],
                        $request->editedby
                    ];

                    $actionId = DB::select('CALL sp_menudetail_create(?, ?, ?)', $menuDetails)[0]->id;
                    array_push($deletedId, $actionId);
                }
            }

            $menuDetailAll->whereNotIn('id', $deletedId)->update([
                'deletedby' => $request->editedby,
                'deleteddate' => date('Y-m-d H:i:s'),
                'deleted' => 1
            ]);

            return response()->json(['success' => true, 'data' => $menu], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Menu', 'err' => $e->getMessage()]);
        }
    }

     /**
     * destroy
     *
     * @param  mixed $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id){
        $menuDetailsData = MenuDetail::where('menu_id', $id)->get();

        foreach ($menuDetailsData as $key => $value) {
            $menuDetails = [
                $request->deletedBy,
                $value['id']
            ];
            DB::select('CALL sp_menudetail_delete(?, ?)', $menuDetails);
        }

        $menu = [
            $request->deletedBy,
            $id
        ];
        DB::select('CALL sp_menu_delete(?, ?)', $menu);

        return response()->json(['success' => true, 'data' => $menuDetailsData], 200);
    }
}
