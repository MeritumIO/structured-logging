<?php

namespace Meritum\StructuredLogging\Factory;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Meritum\StructuredLogging\ExceptionReporter;
use Meritum\StructuredLogging\Reporting\Reporter;
use Meritum\StructuredLogging\ExceptionTranslator;

final class ExceptionReporterFactory
{
    public function __invoke(ContainerInterface $container): ExceptionReporter
    {
        return new Reporter(
            $container->get(ExceptionTranslator::class),
            $container->get(LoggerInterface::class)
        );
    }
}
