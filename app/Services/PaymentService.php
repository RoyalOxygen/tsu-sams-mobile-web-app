<?php
declare(strict_types=1);

namespace App\Services;

use App\Audit;
use App\Database;

final class PaymentService
{
    public static function paymentsEnabled(): bool
    {
        return self::enrollmentFeeEnabled() || self::faceUpdateFeeEnabled();
    }

    public static function enrollmentFeeEnabled(): bool
    {
        $v = setting('enrollment_fee_enabled', '');
        if ($v === '') {
            return setting('payments_enabled', '0') === '1';
        }
        return $v === '1';
    }

    public static function faceUpdateFeeEnabled(): bool
    {
        $v = setting('face_update_fee_enabled', '');
        if ($v === '') {
            return setting('payments_enabled', '0') === '1';
        }
        return $v === '1';
    }

    public static function enrollmentFee(): float
    {
        return (float)setting('enrollment_fee', '0');
    }

    public static function faceUpdateFee(): float
    {
        return (float)setting('face_update_fee', '0');
    }

    public static function enrollmentRequired(): bool
    {
        return self::enrollmentFeeEnabled() && self::enrollmentFee() > 0;
    }

    public static function faceUpdateRequired(): bool
    {
        return self::faceUpdateFeeEnabled() && self::faceUpdateFee() > 0;
    }

    public static function create(int $studentId, string $type, float $amount): array
    {
        $ref = 'TSU' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
        $id = Database::insert('payments', [
            'student_id' => $studentId,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'NGN',
            'status' => 'pending',
            'reference' => $ref,
            'created_at' => now(),
        ]);
        Database::insert('transactions', [
            'payment_id' => $id,
            'channel' => 'demo_gateway',
            'amount' => $amount,
            'status' => 'initiated',
            'created_at' => now(),
        ]);
        Audit::log('payment.create', 'payments', (string)$id, ['type' => $type, 'amount' => $amount]);
        return Database::one('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public static function markPaid(int $paymentId): void
    {
        Database::update('payments', [
            'status' => 'paid',
            'paid_at' => now(),
        ], 'id = ?', [$paymentId]);
        Database::insert('transactions', [
            'payment_id' => $paymentId,
            'channel' => 'demo_gateway',
            'provider_ref' => 'SIM-' . strtoupper(bin2hex(random_bytes(3))),
            'amount' => 0,
            'status' => 'success',
            'created_at' => now(),
        ]);
        $p = Database::one('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        if ($p) {
            Database::update('transactions', ['amount' => $p['amount']], 'payment_id = ? AND status = ?', [$paymentId, 'success']);
        }
        Audit::log('payment.paid', 'payments', (string)$paymentId);
    }

    public static function hasPaidEnrollment(int $studentId): bool
    {
        if (!self::enrollmentRequired()) {
            return true;
        }
        $row = Database::one(
            "SELECT id FROM payments WHERE student_id = ? AND type = 'enrollment' AND status = 'paid' LIMIT 1",
            [$studentId]
        );
        return $row !== null;
    }

    public static function hasPaidFaceUpdate(int $studentId): bool
    {
        if (!self::faceUpdateRequired()) {
            return true;
        }
        $profile = Database::one('SELECT updated_at, enrolled_at FROM face_profiles WHERE student_id = ?', [$studentId]);
        $since = '1970-01-01 00:00:00';
        if (is_array($profile)) {
            $since = (string)($profile['updated_at'] ?: $profile['enrolled_at'] ?: $since);
        }
        $row = Database::one(
            "SELECT id FROM payments WHERE student_id = ? AND type = 'face_update' AND status = 'paid' AND COALESCE(paid_at, created_at) > ? ORDER BY id DESC LIMIT 1",
            [$studentId, $since]
        );
        return $row !== null;
    }
}
