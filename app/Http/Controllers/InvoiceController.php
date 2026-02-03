<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceEmail;
use App\Mail\ChangeStatus;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ClaimController;
use Illuminate\Support\Facades\Redirect;

class InvoiceController extends Controller{
    /**
     * index
     *
     * @return void
     */
    // public function index() {
    //     return response()->json(['success' => true, 'data' => $request], 200);
    // }

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
        $total = DB::select('CALL sp_invoice_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_invoice_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    }

    /**
     * index
     *
     * @return void
     */
    public function show($params) {
        $param = json_decode($params);

        $data = [
            $param->page,
            $param->limit,
            $param->id,
        ];

        $data = DB::select('CALL sp_invoice_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

        return response()->json(['success' => true, 'data' => [], 'message' => 'Success To Get Record Invoice'], 200);
    }

    /**
     * details
     *
     * @return void
     */
    public function details($id) {
        $data = DB::select('CALL sp_invoicedetail_read(?)', [$id]);

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
            'invoice_no' => 'required',
            'vehno' => 'required',
            // 'vehnm' => 'required',
            'oddo' => 'required',
            'invoice_date' => 'required',
            'brand' => 'required',
            'type' => 'required',
            'year' => 'required',
            'user' => 'required',
            'vhimage' => 'required',
            'oddoimage' => 'required',
            'ivimage' => 'required',
        ]);

        try {
            $vhimage = $this->createImageFile('vehicle', $request->vhimage, $request->user_id);
            $oddoimage = $this->createImageFile('oddo', $request->oddoimage, $request->user_id);
            $ivimage = $this->createImageFile('invoice', $request->ivimage, $request->user_id);

            $data = [
                $request->user_id,
                $request->invoice_no,
                $request->vehno,
                $request->vehnm,
                $request->oddo,
                $request->shopcd,
                date("Y-m-d", strtotime($request->invoice_date)),
                $vhimage,
                $oddoimage,
                $ivimage,
                $request->brand,
                $request->type,
                $request->year,
                $request->user
            ];

            $inv = DB::select('CALL sp_invoice_create('.implode(', ', array_fill(0, count($data), "?")).')', $data);
            if($inv[0]->status == 0){
                return response()->json(['false' => true, 'message' => $inv[0]->message]);
            }

            foreach($request->details as $key => $value){
                $invoiceDetailsData = [
                    $inv[0]->id,
                    array_key_exists('serialno', $value) ? $value['serialno']: '',
                    $value['itemcd'],
                    $request->user
                ];
                DB::select('CALL sp_invoicedetail_create('.implode(', ', array_fill(0, count($invoiceDetailsData), "?")).')', $invoiceDetailsData);
            }

            $email = DB::select('CALL sp_get_retailer_email(?)', [$request->shopcd]);

            $dataEmail = [
                'subject' => 'New Invoice',
                'data' => $data,
                'details' => $request->details,
                'invoice_id' => $inv[0]->id,
                'files' => [
                    public_path('/storage/img/invoice/'.$request->user_id.'/'.$vhimage),
                    public_path('/storage/img/invoice/'.$request->user_id.'/'.$oddoimage),
                    public_path('/storage/img/invoice/'.$request->user_id.'/'.$ivimage)
                ]
            ];

            Mail::to($email[0]->email)->send(new InvoiceEmail($dataEmail));

            return response()->json(['success' => true, 'id' => $inv[0]->id, 'message' => 'Success To Saved Data Invoice'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Invoice', 'err' => $e->getMessage()]);
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
            'shopcd' => 'required',
            'invoice_no' => 'required',
            'vehno' => 'required',
            // 'vehnm' => 'required'
        ]);

        try{
            $invoice = Invoice::findOrFail($id);
            $vhimage = $this->createImageFile('vehicle', $request->vhimage, $request->user_id);
            $oddoimage = $this->createImageFile('oddo', $request->oddoimage, $request->user_id);
            $ivimage = $this->createImageFile('invoice', $request->ivimage, $request->user_id);

            $invoiceData = [
                $request->user_id,
                $request->invoice_no,
                $request->vehno,
                $request->vehnm,
                $request->oddo,
                $request->shopcd,
                date("Y-m-d", strtotime($request->invoice_date)),
                $vhimage,
                $oddoimage,
                $ivimage,
                $request->status,
                $request->brand,
                $request->type,
                $request->year,
                $request->user,
                $id
            ];
            DB::select('CALL sp_invoice_update('.implode(', ', array_fill(0, count($invoiceData), "?")).')', $invoiceData);

            $deletedId = [];
            $invoiceDetailAll = InvoiceDetail::where('vehicle_id', $id);
            foreach($request->details as $key => $value){
                if(array_key_exists('id', $value)){
                    $invoiceDetails = [
                        $value['serialno'],
                        $value['itemcd'],
                        $request->user,
                        $value['deleted'],
                        $value['id']
                    ];

                    DB::select('CALL sp_invoicedetail_update('.implode(', ', array_fill(0, count($invoiceDetails), "?")).')', $invoiceDetails);

                    array_push($deletedId, $value['id']);
                }else{
                    $invoiceDetailsData = [
                        $id,
                        $value['serialno'],
                        $value['itemcd'],
                        $request->user
                    ];

                    $inv = DB::select('CALL sp_invoicedetail_create('.implode(', ', array_fill(0, count($invoiceDetailsData), "?")).')', $invoiceDetailsData)[0]->id;

                    array_push($deletedId, $inv);
                }
            }

            $invoiceDetailAll->whereNotIn('id', $deletedId)->update([
                'deletedby' => $request->user,
                'deleteddate' => date('Y-m-d H:i:s'),
                'deleted' => 1
            ]);

            if($request->status_old == '4'){
                $email = DB::select('CALL sp_get_retailer_email(?)', [$request->shopcd]);

                $dataEmail = [
                    'subject' => 'Reclaim Invoice',
                    'data' => $invoiceData,
                    'details' => $request->details,
                    'invoice_id' => $id,
                    'files' => [
                        public_path('/storage/img/invoice/'.$request->user_id.'/'.$vhimage),
                        public_path('/storage/img/invoice/'.$request->user_id.'/'.$oddoimage),
                        public_path('/storage/img/invoice/'.$request->user_id.'/'.$ivimage)
                    ]
                ];

                Mail::to($email[0]->email)->send(new InvoiceEmail($dataEmail));
            }

            return response()->json(['success' => true, 'data' => $invoice, 'message' => 'Success Saved Data Invoice'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Invoice', 'err' => $e->getMessage()]);
        }
    }

     /**
     * destroy
     *
     * @param  mixed $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id){
        $invoiceDetailsData = InvoiceDetail::where('vehicle_id', $id)->get();

        foreach ($invoiceDetailsData as $key => $value) {
            $invoiceDetails = [
                $request->deletedBy,
                $value['id']
            ];
            DB::select('CALL sp_invoicedetail_delete(?, ?)', $invoiceDetails);
        }

        $invoice = [
            $id,
            $request->deletedBy
        ];
        DB::select('CALL sp_invoice_delete(?, ?)', $invoice);

        return response()->json(['success' => true, 'data' => $invoiceDetailsData, 'message' => 'Success Delete Data Invoice'], 200);
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

            DB::select('CALL sp_invoice_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Invoice ('.$request->invoice_no.')',
                'status' => 'Progress',
                'status_text' => 'is currently in ',
                'user' => $request->user,
                'invoice_no' => $request->invoice_no
            ];

            Mail::to($user[0]->email)->send(new ChangeStatus($dataEmail));

            return response()->json(['success' => true, 'data' => $data, 'message' => 'Success To Update Status Invoice'], 200);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed To Update Invoice Status', 'err' => $e->getMessage()]);
        }
    }

    public function approve($id){
        try {
            $invoice = DB::table('rgtrhdr')->where('id', $id)->first();

            if($invoice->status != 1){
                $status = $invoice->status == '3' ? 'Approve': 'Rejected';
                return response()->json(['success' => false, 'message' => 'Invoice already been '.$status]);
            }

            if($invoice->deleted != 0){
                return response()->json(['success' => false, 'message' => 'Invoice already been cancelled']);
            }

            $data = [
                $id,
                3,
                $invoice->shopcd,
                ''
            ];

            $user = DB::select('CALL sp_get_user_email(?)', [$invoice->user_id]);

            $response = DB::select('CALL sp_invoice_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Invoice ('.$invoice->invoice_no.')',
                'status' => 'Approved',
                'status_text' => 'has been ',
                'user' => $invoice->createdby,
                'invoice_no' => $invoice->invoice_no
            ];

            Mail::to($user[0]->email)->send(new ChangeStatus($dataEmail));

            // return Redirect::to(env('APP_FRONT_URL')."/status/approve/".$id);
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Success To Update Status Invoice'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed To Update Invoice Status', 'err' => $e->getMessage()]);
        }
    }

    public function reject($id, Request $request){
        try {
            $invoice = DB::table('rgtrhdr')->where('id', $id)->first();

            if($invoice->status != 1){
                $status = $invoice->status == '3' ? 'Approve': 'Rejected';
                return response()->json(['success' => false, 'message' => 'Invoice already been '.$status]);
            }

            if($invoice->deleted != 0){
                return response()->json(['success' => false, 'message' => 'Invoice already been cancelled']);
            }

            $data = [
                $id,
                4,
                $invoice->shopcd,
                $request->notes
            ];

            $user = DB::select('CALL sp_get_user_email(?)', [$invoice->user_id]);

            DB::select('CALL sp_invoice_change_status('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            $dataEmail = [
                'subject' => 'Update on Invoice ('.$invoice->invoice_no.')',
                'status' => 'Rejected',
                'status_text' => 'has been ',
                'user' => $invoice->createdby,
                'invoice_no' => $invoice->invoice_no
            ];

            Mail::to($user[0]->email)->send(new ChangeStatus($dataEmail));

            // return Redirect::to(env('APP_FRONT_URL')."/status/reject");
            return response()->json(['success' => true, 'data' => $invoice, 'message' => 'Success To Update Status Invoice'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed To Update Invoice Status', 'err' => $e->getMessage()]);
        }
    }

    public function getImages($id, $filename){
        if(Storage::disk('public')->exists('img/invoice/'.$id.'/'.$filename)){
            return storage_path('app/public/img/invoice/'.$id.'/'.$filename);
            // return Storage::url('img/invoice/'.$id.'/'.$filename);
        }
        return $id;
    }

    public function status($id){
        return DB::table('rgtrhdr')->where('id', $id)->first();
    }

    protected function createImageFile($type, $image, $id, $folder = 'invoice'){
        $dir = 'app/public/img/'.$folder.'/';
        if(substr($image, 0, 5) != 'data:'){
            return $image;
        }

        $randomName = $type.'_'.strtotime(date('d-m-Y H:i:s')).'.jpg';
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));

        if (!file_exists(storage_path($dir).$id)) {
            mkdir(storage_path($dir).$id, 0777, true);
        }

        Storage::disk('public')->put('/img/'.$folder.'/'.$id.'/'.$randomName, $data);
        // file_put_contents(storage_path('app/public/img/invoice/').$id.'/'.$randomName, $data);

        return $randomName;
    }
}
