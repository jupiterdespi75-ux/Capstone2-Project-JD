<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Maps to the `facial_analysis_matches` table. Named FaceMatch (not
// "Match") to avoid confusion with PHP's match expression.
class FaceMatch extends Model
{
    protected $table = 'facial_analysis_matches';
    public $timestamps = false;

    protected $fillable = [
        'facial_analysis_id',
        'matched_visitor_id',
        'matched_blacklist_id',
        'similarity',
        'created_at',
    ];

    public function facialAnalysis()
    {
        return $this->belongsTo(FacialAnalysis::class, 'facial_analysis_id');
    }

    public function matchedVisitor()
    {
        return $this->belongsTo(Visitor::class, 'matched_visitor_id');
    }

    public function matchedBlacklist()
    {
        return $this->belongsTo(Blacklist::class, 'matched_blacklist_id');
    }
}
