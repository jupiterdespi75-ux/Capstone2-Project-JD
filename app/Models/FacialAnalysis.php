<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacialAnalysis extends Model
{
    protected $table = 'facial_analysis';
    public $timestamps = false;

    protected $fillable = ['visitor_id', 'embedding'];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function matches()
    {
        return $this->hasMany(FaceMatch::class, 'facial_analysis_id');
    }
}
