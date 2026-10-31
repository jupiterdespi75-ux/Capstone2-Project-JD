<?php

namespace App\Services;

use App\Models\Blacklist;
use App\Models\FaceMatch;
use App\Models\FacialAnalysis;
use App\Models\Visitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Facial Duplicate Detection module.
 *
 * Embeddings come from a real face-recognition model (InsightFace
 * `buffalo_l`, ArcFace, 512-dim) running in the separate Python FastAPI
 * service in /ai_service. This class sends the stored image to that service
 * over HTTP, compares vectors with cosine similarity, records matches in
 * `facial_analysis_matches`, and flags the visitor for Admin review.
 */
class FacialVerificationService
{
    public const DIM = 512;

    // Cosine similarity at or above this value counts as a match (ArcFace).
    // Override with QRODE_FACE_THRESHOLD in .env (see config/services.php).
    public const DEFAULT_THRESHOLD = 0.70;

    // Python-service error codes caused by the PHOTO (the visitor can retake
    // it), as opposed to the service itself being unavailable.
    private const USER_FAULT_CODES = [
        'no_face_detected',
        'multiple_faces_detected',
        'invalid_image',
        'empty_file',
        'file_too_large',
    ];

    public function threshold(): float
    {
        return (float) config('services.face.threshold', self::DEFAULT_THRESHOLD);
    }

    private function serviceUrl(): string
    {
        return rtrim((string) config('services.face.url', 'http://127.0.0.1:5000'), '/');
    }

    /**
     * Send the stored image to the Python service and return its 512-float
     * embedding, or null if no vector could be produced (no face, several
     * faces, invalid image, or the service is down).
     *
     * @param string $imageRelativePath Path on the `public` disk, e.g. "selfies/abc.jpg"
     * @return float[]|null
     */
    public function extract512DimVector(string $imageRelativePath): ?array
    {
        return $this->requestEmbedding($imageRelativePath)['vector'];
    }

    /**
     * Same as extract512DimVector() but also reports WHY it failed.
     *
     * @return array{vector: float[]|null, error: string|null, message: string|null, user_fault: bool}
     */
    public function requestEmbedding(string $imageRelativePath): array
    {
        $absolutePath = Storage::disk('public')->path($imageRelativePath);

        if (!is_file($absolutePath)) {
            Log::warning('Facial analysis: image not found', ['path' => $imageRelativePath]);

            return $this->failure('image_missing', 'The image file could not be found.', false);
        }

        try {
            $response = Http::timeout((int) config('services.face.timeout', 30))
                ->acceptJson()
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
                ->post($this->serviceUrl() . '/extract-vector');
        } catch (ConnectionException $e) {
            Log::error('Facial analysis: AI service unreachable', ['error' => $e->getMessage()]);

            return $this->failure('service_unavailable', 'The face verification service is unavailable.', false);
        }

        if ($response->failed()) {
            $code = (string) $response->json('error', 'service_error');
            $message = (string) $response->json('message', 'The face verification service returned an error.');
            $userFault = in_array($code, self::USER_FAULT_CODES, true);

            Log::info('Facial analysis: no vector returned', [
                'status' => $response->status(),
                'code' => $code,
            ]);

            return $this->failure($userFault ? $code : 'service_unavailable', $message, $userFault);
        }

        $vector = $response->json('embedding');

        if (!is_array($vector) || count($vector) !== self::DIM) {
            Log::error('Facial analysis: malformed response from AI service');

            return $this->failure('service_unavailable', 'The face verification service returned an invalid response.', false);
        }

        return [
            'vector' => array_map('floatval', $vector),
            'error' => null,
            'message' => null,
            'user_fault' => false,
        ];
    }

    /**
     * @return array{vector: null, error: string, message: string, user_fault: bool}
     */
    private function failure(string $error, string $message, bool $userFault): array
    {
        return ['vector' => null, 'error' => $error, 'message' => $message, 'user_fault' => $userFault];
    }

    /**
     * Cosine similarity of two equal-length vectors (works for unit vectors
     * like ArcFace output as well as non-normalized ones).
     *
     * @param float[] $vecA
     * @param float[] $vecB
     */
    public function calculateCosineSimilarity(array $vecA, array $vecB): float
    {
        if (count($vecA) !== count($vecB)) {
            throw new \InvalidArgumentException('Vectors must have the same dimension.');
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vecA as $i => $a) {
            $a = (float) $a;
            $b = (float) $vecB[$i];
            $dot += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        $denominator = sqrt($normA) * sqrt($normB);

        return $denominator > 0 ? $dot / $denominator : 0.0;
    }

    /**
     * Extract the selfie's vector and compare it against every other stored
     * visitor vector and every active blacklist reference photo. Matches at
     * or above the threshold are recorded in facial_analysis_matches and the
     * visitor is set to `flagged` for Admin review.
     *
     * If the photo is unusable (no face / several faces / not an image) the
     * result has user_fault = true and nothing is stored — the caller should
     * ask the visitor to retake it. If the AI service itself is down, the
     * visitor is flagged for manual review (fail-safe: never silently skip
     * the duplicate check).
     *
     * @return array{flagged: bool, matches: array<int, array{source: string, id: int, similarity: float}>, error: string|null, message: string|null, user_fault: bool}
     */
    public function processSelfieAndCheckDuplicate(Visitor $visitor, string $imageRelativePath): array
    {
        $extraction = $this->requestEmbedding($imageRelativePath);

        if ($extraction['vector'] === null) {
            if ($extraction['user_fault']) {
                return [
                    'flagged' => false,
                    'matches' => [],
                    'error' => $extraction['error'],
                    'message' => $extraction['message'],
                    'user_fault' => true,
                ];
            }

            $visitor->update([
                'status' => 'flagged',
                'status_reason' => 'Facial verification was unavailable — manual Admin review required.',
            ]);

            return [
                'flagged' => true,
                'matches' => [],
                'error' => $extraction['error'],
                'message' => $extraction['message'],
                'user_fault' => false,
            ];
        }

        $vector = $extraction['vector'];
        $threshold = $this->threshold();

        $analysis = FacialAnalysis::updateOrCreate(
            ['visitor_id' => $visitor->id],
            ['embedding' => json_encode($vector), 'created_at' => now()]
        );

        // Re-running for the same visitor must not stack up old matches.
        FaceMatch::where('facial_analysis_id', $analysis->id)->delete();

        $matches = [];

        // 1) Other visitors' stored vectors.
        foreach (FacialAnalysis::where('visitor_id', '!=', $visitor->id)->get() as $other) {
            $otherVector = json_decode($other->embedding, true);
            if (!is_array($otherVector) || count($otherVector) !== self::DIM) {
                continue;
            }

            $similarity = $this->calculateCosineSimilarity($vector, $otherVector);

            if ($similarity >= $threshold) {
                FaceMatch::create([
                    'facial_analysis_id' => $analysis->id,
                    'matched_visitor_id' => $other->visitor_id,
                    'similarity' => $similarity,
                    'created_at' => now(),
                ]);
                $matches[] = ['source' => 'visitor', 'id' => (int) $other->visitor_id, 'similarity' => $similarity];
            }
        }

        // 2) Active blacklist entries that have a reference photo.
        $blacklisted = Blacklist::where('is_active', true)
            ->whereNotNull('reference_photo')
            ->where(function ($q) use ($visitor) {
                $q->whereNull('visitor_id')->orWhere('visitor_id', '!=', $visitor->id);
            })
            ->get();

        foreach ($blacklisted as $entry) {
            $entryVector = $this->blacklistVector($entry);
            if ($entryVector === null) {
                continue;
            }

            $similarity = $this->calculateCosineSimilarity($vector, $entryVector);

            if ($similarity >= $threshold) {
                FaceMatch::create([
                    'facial_analysis_id' => $analysis->id,
                    'matched_blacklist_id' => $entry->id,
                    'similarity' => $similarity,
                    'created_at' => now(),
                ]);
                $matches[] = ['source' => 'blacklist', 'id' => (int) $entry->id, 'similarity' => $similarity];
            }
        }

        $flagged = count($matches) > 0;

        if ($flagged) {
            $visitor->update([
                'status' => 'flagged',
                'status_reason' => 'Selfie matched an existing visitor or blacklist record — pending Admin review.',
            ]);
        }

        return ['flagged' => $flagged, 'matches' => $matches, 'error' => null, 'message' => null, 'user_fault' => false];
    }

    /**
     * A blacklist entry's vector is computed once from its reference photo
     * and cached in blacklist.embedding, so later registrations don't have to
     * call the AI service again for every blacklisted person.
     *
     * @return float[]|null
     */
    private function blacklistVector(Blacklist $entry): ?array
    {
        if ($entry->embedding) {
            $cached = json_decode($entry->embedding, true);
            if (is_array($cached) && count($cached) === self::DIM) {
                return $cached;
            }
        }

        $vector = $this->extract512DimVector($entry->reference_photo);
        if ($vector !== null) {
            $entry->update(['embedding' => json_encode($vector)]);
        }

        return $vector;
    }
}
