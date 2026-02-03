<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Claim;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClaimEmail;
use App\Mail\ChangeStatusClaim;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller{
    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_claim_read()');

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

        $total = DB::select('CALL sp_claim_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_claim_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }

    /**
     * index
     *
     * @return void
     */
    public function show($id) {
        $data = DB::select('CALL sp_claim_read(?)', [$id]);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * details
     *
     * @return void
     */
    public function details($id) {
        $data = DB::select('CALL sp_claimdetail_read(?)', [$id]);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function serialnumber($id) {
        $data = DB::select('CALL sp_serial_number_read(?)', [$id]);

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
            // 'shopcd' => 'required',
            'serialnumber' => 'required',
            'oddo' => 'required',
        ]);

        try {
            $vhimage = $this->createImageFile('vehicle', $request->vhimage, $request->user_id);
            $oddoimage = $this->createImageFile('oddo', $request->oddoimage, $request->user_id);
            $serialimage = $this->createImageFile('serial', $request->serialimage, $request->user_id);
            $item1image = $this->createImageFile('item1', $request->item1image, $request->user_id);
            $item2image = $this->createImageFile('item2', $request->item2image, $request->user_id);

            $claimData = [
                $request->user_id,
                $request->serialnumber,
                $request->shopcd,
                $request->note1,
                $request->oddo,
                $vhimage,
                $oddoimage,
                $item1image,
                $item2image,
                $serialimage,
                $request->user
            ];

            $claim = DB::select('CALL sp_claim_create('.implode(', ', array_fill(0, count($claimData), "?")).')', $claimData);

            $email = DB::select('CALL sp_get_retailer_email(?)', [$request->shopcd]);

            $data = [
                'subject' => 'New Claim',
                'serial_number' => $request->serialnumber,
                'shop' => $request->shop_display,
                'user' => $request->user,
                'data' => $claimData,
                'claim_id' => $claim[0]->id,
                'files' => [
                    public_path('/storage/img/claim/'.$request->user_id.'/'.$vhimage),
                    public_path('/storage/img/claim/'.$request->user_id.'/'.$oddoimage),
                    public_path('/storage/img/claim/'.$request->user_id.'/'.$item1image),
                    public_path('/storage/img/claim/'.$request->user_id.'/'.$item2image),
                    public_path('/storage/img/claim/'.$request->user_id.'/'.$serialimage)
                ]
            ];

            Mail::to($email[0]->email)->send(new ClaimEmail($data));

            return response()->json(['success' => true, 'data' => []], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Claim', 'err' => $e->getMessage()]);
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
            // 'shopcd' => 'required',
            'serialnumber' => 'required',
        ]);

        try{
            $vhimage = $this->createImageFile('vehicle', $request->vhimage, $request->user_id);
            $oddoimage = $this->createImageFile('oddo', $request->oddoimage, $request->user_id);
            $serialimage = $this->createImageFile('serial', $request->serialimage, $request->user_id);
            $item1image = $this->createImageFile('item1', $request->item1image, $request->user_id);
            $item2image = $this->createImageFile('item2', $request->item2image, $request->user_id);

            $claimData = [
                $id,
                $request->serialnumber,
                $request->shopcd,
                $request->note1,
                $request->oddo,
                $vhimage,
                $oddoimage,
                $item1image,
                $item2image,
                $serialimage,
                $request->user,
                (int)$request->status,
                // $request->status != '4' ? $request->status : 1
            ];

            DB::select('CALL sp_claim_update('.implode(', ', array_fill(0, count($claimData), "?")).')', $claimData);
            $claim = DB::select('CALL sp_invoicedetail_read(?)', [$request->vehicle_id]);
            $email = DB::select('CALL sp_get_retailer_email(?)', [$request->shopcd]);

            if($request->status_claim == '4'){
                $data = [
                    'subject' => 'Reclaim',
                    'serial_number' => $request->serialnumber,
                    'shop' => $request->shop_display,
                    'user' => $request->user,
                    'data' => $claimData,
                    'claim_id' => $id,
                    'files' => [
                        public_path('/storage/img/claim/'.$request->user_id.'/'.$vhimage),
                        public_path('/storage/img/claim/'.$request->user_id.'/'.$oddoimage),
                        public_path('/storage/img/claim/'.$request->user_id.'/'.$item1image),
                        public_path('/storage/img/claim/'.$request->user_id.'/'.$item2image),
                        public_path('/storage/img/claim/'.$request->user_id.'/'.$serialimage)
                    ]
                ];
                Mail::to($email[0]->email)->send(new ClaimEmail($data));
            }

            return response()->json(['success' => true, 'data' => $claimData], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Claim', 'err' => $e->getMessage()]);
        }
    }

     /**
     * destroy
     *
     * @param  mixed $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id){
        $claim = [
            $id,
            $request->deletedBy
        ];
        DB::select('CALL sp_claim_delete('.implode(', ', array_fill(0, count($claim), "?")).')', $claim);

        return response()->json(['success' => true, 'id' => $id], 200);
    }

    public function progress($id, Request $request){
        $request->validate([
            'user' => 'required'
        ]);

        try {
            $data = [
                $id,
                2,
                $request->user,
                ''
            ];

            $user = DB::select('CALL sp_get_user_email(?)', [$request->user_id]);

            DB::select('CALL sp_claim_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Serial Number ('.$request->serialnumber.')',
                'status' => 'Progress',
                'status_text' => 'is currently in ',
                'user' => $request->user,
                'serialnumber' => $request->serialnumber
            ];

            Mail::to($user[0]->email)->send(new ChangeStatusClaim($dataEmail));

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Update Claim Status', 'err' => $e->getMessage()]);
        }
    }

    public function approve($id, Request $request){
        try {
            $claim = DB::table('rgtrclaim')->select('rgtrclaim.*', 'rgtrdtl.serialno')->join('rgtrdtl', 'rgtrdtl.id', '=', 'rgtrclaim.serialnumber')->where('rgtrclaim.id', $id)->first();

            if($claim->status != 1){
                $status = $claim->status == '3' ? 'Approve': 'Rejected';
                return response()->json(['success' => false, 'message' => 'Item already been '.$status]);
            }

            if($claim->deleted != 0){
                return response()->json(['success' => false, 'message' => 'Item already been cancelled']);
            }

            $data = [
                $id,
                3,
                $claim->createdby,
                ''
            ];

            $user = DB::select('CALL sp_get_user_email(?)', [$claim->user_id]);

            DB::select('CALL sp_claim_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Serial Number ('.$claim->serialno.')',
                'status' => 'Approved',
                'status_text' => 'has been ',
                'user' => $claim->createdby,
                'serialnumber' => $claim->serialno
            ];

            Mail::to($user[0]->email)->send(new ChangeStatusClaim($dataEmail));

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed To Update Claim Status', 'err' => $e->getMessage()]);
        }
    }

    public function reject($id, Request $request){
        try {
            $claim = DB::table('rgtrclaim')->select('rgtrclaim.*', 'rgtrdtl.serialno')->join('rgtrdtl', 'rgtrdtl.id', '=', 'rgtrclaim.serialnumber')->where('rgtrclaim.id', $id)->first();

            if($claim->status != 1){
                $status = $claim->status == '3' ? 'Approve': 'Rejected';
                return response()->json(['success' => false, 'message' => 'Item already been '.$status]);
            }

            if($claim->deleted != 0){
                return response()->json(['success' => false, 'message' => 'Item already been cancelled']);
            }

            $data = [
                $id,
                4,
                $claim->createdby,
                $request->notes
            ];

            $user = DB::select('CALL sp_get_user_email(?)', [$claim->user_id]);

            DB::select('CALL sp_claim_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Serial Number ('.$claim->serialno.')',
                'status' => 'Rejected',
                'status_text' => 'has been ',
                'user' => $claim->createdby,
                'serialnumber' => $claim->serialno
            ];

            Mail::to($user[0]->email)->send(new ChangeStatusClaim($dataEmail));

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'message' => 'Failed To Update Claim Status', 'err' => $e->getMessage()]);
        }
    }

    public function status($id){
        $claim = DB::table('rgtrclaim')->select('rgtrclaim.*', 'rgtrdtl.serialno', 'rgtrhdr.invoice_no', 'rgtrhdr.invoicedate')->join('rgtrdtl', 'rgtrdtl.id', '=', 'rgtrclaim.serialnumber')->join('rgtrhdr', 'rgtrhdr.id', '=', 'rgtrdtl.vehicle_id')->where('rgtrclaim.id', $id)->first();

        return $claim;
    }

    protected function createImageFile($type, $image, $id){
        if(substr($image, 0, 5) != 'data:'){
            return $image;
        }

        $randomName = $type.'_'.strtotime(date('d-m-Y H:i:s')).'.jpg';
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));

        if (!file_exists(storage_path('app/public/img/claim/').$id)) {
            mkdir(storage_path('app/public/img/claim/').$id, 0777, true);
        }

        Storage::disk('public')->put('/img/claim/'.$id.'/'.$randomName, $data);
        // file_put_contents(storage_path('app/public/img/claim/').$id.'/'.$randomName, $data);

        return $randomName;
    }
}
