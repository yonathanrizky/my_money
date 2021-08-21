<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'status', 'amount', 'description',
    ];

    public function balance()
    {
        return $this->hasOne(Balance::class, 'user_id', 'user_id');
    }
}
