<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Processing extends Model
{
    use HasFactory;

  //  public $table = 'carts';
    protected $fillable = [
        'client_name',
        'client_address',
        'client_order_details',
        'amount',
        'currency',
        'created_at',
        'updated_at'

    ];
}
