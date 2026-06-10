<?php

namespace Meritum\StructuredLogging;

use Stringable;

final class CorrelationId implements Stringable
{
    public private(set) string $uuid;

    public function __construct(?string $uuid = null)
    {
        $this->uuid = (null !== $uuid && $this->isValidUuid($uuid)) ? $uuid : $this->makeUuid();
    }

    public function set(string $uuid): void
    {
        if ($this->isValidUuid($uuid)) {
            $this->uuid = $uuid;
        }
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    private function makeUuid(): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40) . random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80) . random_bytes(1)),
            bin2hex(random_bytes(6))
        );
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}
