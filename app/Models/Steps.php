<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\FuncCall;

class Steps extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'step',
        'name',
        'email',
        'password',
        'confirmation_code'
    ];


    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }
}
