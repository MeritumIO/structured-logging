<?php

namespace Meritum\StructuredLogging\Test\Factory;

use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Factory\ExceptionTranslatorFactory;
use Meritum\StructuredLogging\Translation\Translator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ExceptionTranslatorFactoryTest extends TestCase
{
    private function makeContainer(array $handlers = []): ContainerInterface
    {
        $tags = $this->createMock(TagRegistryInterface::class);
        $tags->method('getTagged')
            ->with('exception.translator.handlers')
            ->willReturn($handlers);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with(TagRegistryInterface::class)
            ->willReturn($tags);

        return $container;
    }

    #[Test]
    public function test_returns_exception_translator(): void
    {
        $factory = new ExceptionTranslatorFactory();

        $result = $factory($this->makeContainer());

        $this->assertInstanceOf(ExceptionTranslator::class, $result);
    }

    #[Test]
    public function test_returns_translator_instance(): void
    {
        $factory = new ExceptionTranslatorFactory();

        $result = $factory($this->makeContainer());

        $this->assertInstanceOf(Translator::class, $result);
    }
}
