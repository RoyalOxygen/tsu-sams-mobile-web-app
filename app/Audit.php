<?php
declare(strict_types=1);

namespace App;

final class Audit
{
    public static function log(string $action, ?string $entity = null, ?string $entityId = null, array $meta = []): void
    {
        Database::insert('audit_logs', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'ip_address' => client_ip(),
            'user_agent' => user_agent(),
            'meta' => $meta ? json_encode($meta) : null,
            'created_at' => now(),
        ]);
        Database::insert('activity_logs', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'event' => $action,
            'details' => $entity ? ($entity . '#' . $entityId) : null,
            'ip_address' => client_ip(),
            'created_at' => now(),
        ]);
    }
}
