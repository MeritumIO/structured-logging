<?php

namespace Meritum\StructuredLogging;

enum StructuredLoggingOption: string
{
    case EnricherTag   = 'log.context.enrichers';
    case TranslatorTag = 'exception.translator.handlers';
}
