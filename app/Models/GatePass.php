<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    public $timestamps = false;

    protected $fillable = ['visitor_id', 'pass_token', 'issued_at', 'is_active'];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
