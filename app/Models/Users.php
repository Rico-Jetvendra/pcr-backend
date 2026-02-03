<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Users extends Authenticatable implements JWTSubject{
    use HasFactory, Notifiable;
    protected $primaryKey = "id";
    protected $table = 'tbl_user';
    public $timestamps = false;
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'wanumber',
        'roleid',
        'pwd_reset_token',
        'status',
        'address',
        'registerkey',
        'registerdate',
        'registerip',
        'confirmdate',
        'createdby',
        'createddate',
        'editedby',
        'editeddate',
        'deletedby',
        'deleteddate',
        'deleted',
    ];

    // Metode JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'deleted' => $this->deleted
        ];
    }
}
