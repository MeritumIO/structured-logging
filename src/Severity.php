<?php

namespace Meritum\StructuredLogging;

enum Severity: string
{
    case Critical = 'critical';
    case Error    = 'error';
    case Warning  = 'warning';
    case Info     = 'info';
    case Debug    = 'debug';
}
