<?php

namespace Meritum\StructuredLogging;

use Throwable;
use Meritum\StructuredLogging\Exception\DomainException;

interface ExceptionTranslator
{
    public function translate(Throwable $exception): DomainException;
}
