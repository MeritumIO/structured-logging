<?php

namespace Meritum\StructuredLogging;

use Throwable;
use Meritum\StructuredLogging\Exception\DomainException;

interface ExceptionReporter
{
    public function report(Throwable $exception): DomainException;
}
