<?php

namespace Meritum\StructuredLogging\Context;

use Stringable;
use Meritum\StructuredLogging\ContextEnricher;

final class CorrelationIdEnricher implements ContextEnricher
{
    public function __construct(private readonly Stringable|string $correlationId) {}

    public function enrich(array $context): array
    {
        return $context + [
            'correlation_id' => (string) $this->correlationId,
        ];
    }
}
