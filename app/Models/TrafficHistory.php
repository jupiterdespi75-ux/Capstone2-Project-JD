<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficHistory extends Model
{
    protected $table = 'traffic_history';
    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'walk_in_id',
        'gate_id',
        'direction',
        'scan_status',
        'scanned_by',
        'scanned_at',
    ];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function walkIn()
    {
        return $this->belongsTo(WalkIn::class);
    }
}
