<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmEmail;
use App\Http\Resources\UsersResource;
use App\Mail\ForgotPassword;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UsersController extends Controller{

    /**
     * index
     *
     * @return void
     */
    public function index() {
        $data = DB::select('CALL sp_users_read()');

        return response()->json(['success' => true, 'data' => $data, 'message' => 'Saved Data Users'], 200);
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
        $total = DB::select('CALL sp_users_total_read('.implode(', ', array_fill(0, count($data), "?")).')', $data)[0];
        $data = DB::select('CALL sp_users_read('.implode(', ', array_fill(0, count($data), "?")).')', $data);

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
            'password' => 'required|min:5'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            if($request->password == $request->confirm_password){
                $data = array(
                    $request->nama,
                    $request->email,
                    bcrypt($request->password),
                    $request->wanumber,
                    $request->roleid,
                    0,
                    $request->address,
                    $request->createdBy,
                    $request->shopid
                );

                $users = DB::select('CALL sp_users_create('.implode(', ', array_fill(0, count($data), "?")).')', $data);

                if($users[0]->status == 1){
                    $send = [
                        'subject' => 'Confirm your  email address',
                        'title' => 'Confirmation Mail',
                        'messages' => $users[0]->reset_token,
                        'email' => $request->email
                    ];

                    $this->send_email($send);

                    // return new UsersResource(true, 'Saved Data Users', $users);
                    return response()->json(['success' => true, 'user' => $users, 'message' => 'Saved Data Users'], 200);
                }else{
                    return response()->json(['false' => true, 'message' => 'There is already data with the same name and email.']);
                }
            }else{
                return response()->json(['false' => true, 'message' => 'Please confirm if the password is the same.']);
            }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }


    /**
     * store
     *
     * @param  mixed $request
     * @return json
     */
    public function register(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'birth_date' => 'required',
            'gender' => 'required',
            'job' => 'required',
            'field' => 'required',
            'email' => 'required',
            'password' => 'required|min:5'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(["messages" => $validator->errors()], 422);
        }

        try {
            if($request->password == $request->confirm_password){
                $data = array(
                    $request->nama,
                    date("Y-m-d", strtotime($request->birth_date)),
                    $request->gender,
                    $request->job,
                    $request->field,
                    $request->email,
                    bcrypt($request->password),
                    $request->wanumber,
                    $request->roleid,
                    0,
                    $request->address,
                    $request->createdBy,
                    $request->shopid
                );

                $users = DB::select('CALL sp_users_create('.implode(', ', array_fill(0, count($data), "?")).')', $data);

                if($users[0]->status == 1){
                    if($request->type == 'email'){
                        $send = [
                            'subject' => 'Confirm your email address',
                            'title' => 'Confirmation Mail',
                            'messages' => $users[0]->reset_token,
                            'email' => $request->email
                        ];

                        $this->send_email($send);
                    }else{
                        $send = [
                            'token' => $users[0]->reset_token,
                            'phone_number' => $request->wanumber
                        ];

                        $this->send_wa($send);
                    }

                    return response()->json(['success' => true, 'user' => $users, 'message' => 'Saved Data Users'], 200);
                }else{
                    return response()->json(['false' => true, 'message' => 'There is already data with the same name and email.']);
                }
            }else{
                return response()->json(['false' => true, 'message' => 'Please confirm if the password is the same.']);
            }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return UsersResource
     */
    public function update(Request $request, $id){
        $request->validate([
            'nama' => 'required',
            'email' => 'required'
        ]);

        try{
            $users = Users::findOrFail($id);
            $pass = $request->password;

            if($pass){
                $pass = bcrypt($pass);
            }else{
                $pass = $users->pwd_hash;
            }
            $data = array(
                $request->nama,
                $request->email,
                $pass,
                $request->wanumber,
                $request->roleid,
                $request->address,
                $request->user,
                $id
            );

            $users = DB::select('CALL sp_users_update('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            return response()->json(['success' => true, 'message' => 'Updated Data Users'], 201);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()], 409);
        }
    }

    /**
    * destroy
    *
    * @param  mixed $id
    * @return UsersResource
    */
    public function destroy(Request $request, $id){
        $users = DB::select('CALL sp_users_delete(?, ?)', array($request->deletedBy, $id));
        return response()->json(['success' => true, 'message' => 'Successfully deleted users'], 201);
    }

    public function send_email($response){
        $data = [
            'subject' => $response['subject'],
            'title' => $response['title'],
            'body' => $response['messages']
        ];

        try {
            Mail::to($response['email'])->send(new ConfirmEmail($data));

            return response()->json(['success' => true, 'message' => 'Successfully send email'], 201);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed send email', 'err' => $e->getMessage()], 409);
        }
    }

    public function send_wa($response){
        $_token = '123456';
        // $_token = 'xk1b4m3LmLj6YuR6LGRS';
        $_target = preg_replace('/\D+/', '', $response['phone_number']);

        $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => 'http://localhost:3000/api',
        //     // CURLOPT_URL => 'https://api.fonnte.com/send',
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => '',
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 0,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => 'POST',
        //     CURLOPT_POSTFIELDS => array(
        //         // 'target' => $_target,
        //         // 'message' => $response['token'].' adalah kode verifikasi Anda. Demi keamanan Anda, jangan bagikan kode ini kepada siapapun. \n PT. Veron Indonesia',
        //         // 'delay' => '5-10',
        //         // 'countryCode' => '62', //optional
        //     ),
        //     CURLOPT_HTTPHEADER => array(
        //         'Content-Type: application/json'
        //     ),
        // ));

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://localhost:3000/api',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS =>'{
                "nohp": "'.$_target.'",
                "pesan": "'.$response["token"].' adalah kode verifikasi Anda. Demi keamanan Anda, jangan bagikan kode ini kepada siapapun. \n\nPT. Veron Indonesia",
                "token": "'.$_token.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);

        if (isset($error_msg)) {
            echo $error_msg;
        }

        $data = array(
            'whatsapp confirm',
            json_encode($response)
        );

        DB::select('CALL sp_insert_log('.implode(', ', array_fill(0, count($data), "?")).')', $data);
        // echo $response;
    }

    public function confirm(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'email' => 'required',
            'reset_token' => 'required|min:6|max:6'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try{
            $data = array(
                $request->nama,
                $request->email,
                $request->reset_token,
            );

            $users = DB::select('CALL sp_users_confirm('.implode(', ', array_fill(0, count($data), "?")).')', $data);

            if($users[0]->status == 1){
                return response()->json(['success' => true, 'user' => $users, 'message' => $users[0]->messages], 200);
            }else{
                return response()->json(['success' => false, 'message' => $users[0]->messages]);
            }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }

    public function login(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = request(['email', 'password']);

        try{
            // if (!$token = auth('api')->attempt($credentials)) {
            //     return response()->json(['error' => 'Unauthorized'], 401);
            // }

            $users = DB::select('CALL sp_login(?)', [$request->email]);
            // $users = Users::select(DB::raw('tbl_user.*, th_role.role_name'))->leftJoin('th_role', 'tbl_user.roleid', '=', 'th_role.id')->where('tbl_user.deleted', '=', '0')->where('tbl_user.email', '=', $request->email)->first();

            if($users == null){
                return response()->json(['success' => false, 'message' => 'There`s no user with that email.'], 200);
            }

            if(!Hash::check($request->password, $users['password'])){
                return response()->json(['messages' => 'Unauthorized', 'password' => $users['password']], 401);
            }

            $token = JWTAuth::fromUser($users);
            $roles = DB::select('CALL sp_get_roles(?)', [$users['roleid']]);

            if($users['status'] == 0){
                return response()->json(['success' => false, 'message' => 'Login Successful'], 200);
            }

            return response()->json(
                [
                    'success' => true,
                    'user' => $users,
                    'message' => 'Successfully Login',
                    'roles' => $roles,
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ], 200
            );
        } catch (JWTException $e) {
            return response()->json(['false' => true, 'message' => 'Failed Login', 'err' => $e->getMessage()]);
        }
    }

    public function forgot(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try{
            $response = DB::select('CALL sp_send_forgot_password_request(?)', [$request->email]);

            if($response == null){
                return response()->json(['success' => false, 'message' => 'There`s no user with that email.'], 200);
            }

            $data = [
                'subject' => 'Password Change Request',
                'title' => 'Password Reset',
                'body' => $response[0]->token
            ];

            Mail::to($request->email)->send(new ForgotPassword($data));

            return response()->json(['success' => true, 'message' => 'Successfully send email', 'id' => $response[0]->id], 201);
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }

    public function resetConfirm(Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'reset_token' => 'required|min:6|max:6'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try{
            $data = array(
                null,
                $request->email,
                $request->reset_token,
            );

            $users = DB::select('CALL sp_users_confirm(?, ?, ?)', $data);

            if($users[0]->status == 1){
                return response()->json(['success' => true, 'user' => $users, 'message' => $users[0]->messages], 200);
            }else{
                return response()->json(['success' => false, 'message' => $users[0]->messages]);
            }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }

    public function reset($id, Request $request){
        //set validation
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'confirm_password' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try{
            if($request->password === $request->confirm_password){
                $data = array(
                    $id,
                    bcrypt($request->password),
                    'Forgot'
                );

                $users = DB::select('CALL sp_reset_password(?, ?, ?)', $data);

                return response()->json(['success' => true, 'user' => $users, 'message' => 'Reset password successful'], 200);
            }else{
                return response()->json(['false' => true, 'message' => 'Please confirm if the password is the same.']);
            }
        } catch (\Exception $e) {
            return response()->json(['false' => true, 'message' => 'Failed Saved Data Users', 'err' => $e->getMessage()]);
        }
    }

    public function verifyCaptcha(Request $request){
        if (captcha_check($request->captcha)) {
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 422);
    }
}
