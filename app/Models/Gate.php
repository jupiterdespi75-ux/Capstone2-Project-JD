<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gate extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'is_active'];
}
