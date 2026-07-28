<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeRedirectUrl implements ValidationRule
{
    /**
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail('La URL original no es valida.');

            return;
        }

        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            $fail('La URL original debe usar HTTP o HTTPS.');

            return;
        }

        if ($this->isBlockedHost($host)) {
            $fail('La URL original no puede apuntar a hosts internos o privados.');
        }
    }

    private function isBlockedHost(string $host): bool
    {
        $host = trim($host, '[]');

        if (in_array($host, ['localhost', '0.0.0.0', '::1'], true)) {
            return true;
        }

        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return true;
        }

        if ($host === '169.254.169.254') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
