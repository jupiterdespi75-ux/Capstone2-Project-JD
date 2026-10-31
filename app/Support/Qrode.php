<?php

namespace App\Support;

use App\Models\SystemLog;

class Qrode
{
    /**
     * "Encrypted QR hash token" per spec: HMAC over the visitor id + a
     * random salt, so the token can't be forged or guessed but is cheap
     * to verify against — same scheme as the tested Node prototype.
     */
    public static function generatePassToken(int $visitorId): string
    {
        $salt = bin2hex(random_bytes(16));
        $secret = env('QRODE_SECRET', 'qrode-dev-secret-change-me');
        $hmac = hash_hmac('sha256', "{$visitorId}:{$salt}", $secret);

        return "QR-{$visitorId}-{$salt}-" . substr($hmac, 0, 24);
    }

    /**
     * @param int|null $userId
     * @param string $action
     * @param array|string $details
     */
    public static function log(?int $userId, string $action, $details = []): void
    {
        SystemLog::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => is_string($details) ? $details : json_encode($details),
        ]);
    }
}
