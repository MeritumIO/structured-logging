<?php

namespace Meritum\StructuredLogging;

interface ContextEnricher
{
    /**
     * @param mixed[] $context
     *
     * @return mixed[]
     */
    public function enrich(array $context): array;
}
