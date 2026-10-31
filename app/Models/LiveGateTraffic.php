<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveGateTraffic extends Model
{
    protected $table = 'live_gate_traffic';
    public $timestamps = false;

    protected $fillable = ['visitor_id', 'walk_in_id', 'gate_id', 'time_in', 'time_out'];

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
