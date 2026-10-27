<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(string $event, ?Model $auditable = null, array $oldValues = [], array $newValues = [], ?User $user = null): void
    {
        $user ??= Auth::user();

        AuditLog::query()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    private function redact(array $values): array
    {
        $sensitive = ['password', 'mfa_secret', 'consumer_secret', 'passkey', 'secret_key', 'api_key'];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }
}
