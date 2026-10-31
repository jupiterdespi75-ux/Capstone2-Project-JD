<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $table = 'blacklist';
    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'walk_in_id',
        'name',
        'reason',
        'reference_photo',
        'embedding',
        'is_active',
        'created_by',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function walkIn()
    {
        return $this->belongsTo(WalkIn::class);
    }
}
