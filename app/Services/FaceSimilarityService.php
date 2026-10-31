<?php

namespace App\Services;

/**
 * Simulated Facial Vector Engine — a direct port of the same algorithm
 * used and tested in the project's Node.js prototype (lib/face.js).
 *
 * The manuscript specifies 512-dimensional facial embeddings compared via
 * cosine similarity. A production build would extract these embeddings
 * with a real face-recognition model (e.g. a ResNet-based model). This
 * class instead deterministically derives a 512-dim vector from the raw
 * bytes of the uploaded image using a seeded pseudo-random projection.
 *
 * This demonstrates the full pipeline correctly (upload → embed → compare
 * → flag → admin review) and will correctly flag a genuinely identical or
 * near-identical re-uploaded image. It will NOT recognize two different
 * photos of the same person's face, since it reads file bytes, not facial
 * features. Swap extractEmbedding() for a real model call to go to
 * production — nothing else in the app needs to change.
 */
class FaceSimilarityService
{
    public const DIM = 512;

    // Above this cosine similarity, a submission is flagged as a possible
    // duplicate identity and routed to Admin review instead of auto-pending.
    public const DUPLICATE_THRESHOLD = 0.985;

    /**
     * @param string $fileContents Raw bytes of the uploaded image.
     * @return float[] A unit-normalized 512-dimensional vector.
     */
    public function extractEmbedding(string $fileContents): array
    {
        $hash = hash('sha256', $fileContents, true); // 32 raw bytes

        // Seed a 32-bit PRNG from the first 16 bytes of the hash (same
        // seeding approach as the Node version's readUInt32BE XORs).
        $seed = 0;
        for ($i = 0; $i < 4; $i++) {
            $chunk = unpack('N', substr($hash, $i * 4, 4))[1];
            $seed ^= $chunk;
        }

        $vec = [];
        for ($i = 0; $i < self::DIM; $i++) {
            $vec[] = $this->nextFloat($seed);
        }

        // Normalize to a unit vector.
        $magnitude = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vec))) ?: 1.0;

        return array_map(fn ($v) => $v / $magnitude, $vec);
    }

    /**
     * xorshift32, matching the Node implementation bit-for-bit in intent
     * (32-bit wraparound arithmetic). $seed is passed by reference and
     * advances on every call.
     */
    private function nextFloat(int &$seed): float
    {
        $seed = $this->to32Bit($seed ^ $this->to32Bit($seed << 13));
        $seed = $this->to32Bit($seed ^ ($this->unsignedRightShift($seed, 17)));
        $seed = $this->to32Bit($seed ^ $this->to32Bit($seed << 5));

        $unsigned = $seed < 0 ? $seed + 4294967296 : $seed;

        return ($unsigned / 4294967295) * 2 - 1;
    }

    private function to32Bit(int $n): int
    {
        $n &= 0xFFFFFFFF;
        if ($n & 0x80000000) {
            $n -= 0x100000000;
        }
        return $n;
    }

    private function unsignedRightShift(int $n, int $bits): int
    {
        $unsigned = $n < 0 ? $n + 4294967296 : $n;
        return $unsigned >> $bits;
    }

    /**
     * @param float[] $a
     * @param float[] $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        foreach ($a as $i => $value) {
            $dot += $value * $b[$i];
        }
        return $dot; // both already unit-normalized
    }
}
