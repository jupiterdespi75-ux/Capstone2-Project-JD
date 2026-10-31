<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalkIn extends Model
{
    protected $table = 'walk_ins';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'id_card_path',
        'photo_path',
        'purpose_of_visit',
        'gate_id',
        'status',
        'status_reason',
        'encoded_by',
        'reviewed_by',
        'reviewed_at',
    ];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }
}
