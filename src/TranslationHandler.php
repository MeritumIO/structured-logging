<?php

namespace Meritum\StructuredLogging;

use Throwable;
use Meritum\StructuredLogging\Exception\DomainException;

interface TranslationHandler
{
    public function matches(Throwable $exception): bool;

    public function handle(Throwable $exception): DomainException;

    public function priority(): int;
}
