<?php

namespace App\Console\Commands;

use App\Models\Blacklist;
use App\Models\FaceMatch;
use App\Models\FacialAnalysis;
use App\Models\Visitor;
use App\Services\FacialVerificationService;
use Illuminate\Console\Command;

class ReembedFaces extends Command
{
    protected $signature = 'qrode:reembed-faces';

    protected $description = 'Recompute face vectors for existing visitors/blacklist photos with the real AI model (replaces the old file-hash simulation vectors)';

    public function handle(FacialVerificationService $faces): int
    {
        $ok = 0;
        $skipped = 0;

        foreach (Visitor::whereNotNull('selfie_path')->cursor() as $visitor) {
            $vector = $faces->extract512DimVector($visitor->selfie_path);

            if ($vector === null) {
                // Remove stale simulation vectors so they can't pretend to be real.
                $stale = FacialAnalysis::where('visitor_id', $visitor->id)->first();
                if ($stale) {
                    FaceMatch::where('facial_analysis_id', $stale->id)->delete();
                    $stale->delete();
                }
                $this->warn("Visitor #{$visitor->id} ({$visitor->name}): no usable face, vector removed.");
                $skipped++;
                continue;
            }

            FacialAnalysis::updateOrCreate(
                ['visitor_id' => $visitor->id],
                ['embedding' => json_encode($vector), 'created_at' => now()]
            );
            $ok++;
        }

        Blacklist::query()->update(['embedding' => null]); // recomputed lazily on next registration

        $this->info("Done. Re-embedded {$ok} visitor(s); {$skipped} had no usable face.");

        return self::SUCCESS;
    }
}
