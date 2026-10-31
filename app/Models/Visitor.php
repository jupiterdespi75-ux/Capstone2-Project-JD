<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    public $timestamps = false; // we manage created_at manually via useCurrent()

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'selfie_path',
        'id_card_path',
        'purpose_of_visit',
        'status',
        'status_reason',
        'registration_type',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $hidden = ['password'];

    public function facialAnalyses()
    {
        return $this->hasMany(FacialAnalysis::class);
    }

    public function gatePasses()
    {
        return $this->hasMany(GatePass::class);
    }

    public function activeGatePass()
    {
        return $this->hasOne(GatePass::class)->where('is_active', true)->latestOfMany();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
