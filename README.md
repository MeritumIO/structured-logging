# meritum/structured-logging

Structured exception logging for the Meritum ecosystem. Provides a domain exception model, a translation pipeline that converts arbitrary exceptions into structured domain exceptions, PSR-3 reporting, and correlation ID enrichment.

## Requirements

- PHP 8.4+
- [`georgeff/kernel`](https://github.com/MikeGeorgeff/kernel) ^2.0

## Installation

```bash
composer require meritum/structured-logging
```

A PSR-3 logger must be registered in your kernel before adding this module. [`meritum/logger`](https://github.com/MeritumIO/logger) is a zero-dependency option.

## Module registration

```php
use Meritum\StructuredLogging\StructuredLoggingModule;

$kernel->addModule(new StructuredLoggingModule());
```

The module registers the following services:

| Service | Notes |
|---|---|
| `CorrelationId` | Shared. Auto-generates a UUID v4 on first resolution, and a new one on `resetShared()`. |
| `CorrelationIdEnricher` | Tagged with `StructuredLoggingOption::EnricherTag` (`log.context.enrichers`). Adds `correlation_id` to every log entry. |
| `ExceptionTranslator` | Collects every service tagged with `StructuredLoggingOption::TranslatorTag` (`exception.translator.handlers`). |
| `ExceptionReporter` | Translates, then logs via the decorated `LoggerInterface`. |
| `LoggerInterface` | Decorated with `ContextEnrichingLogger` to apply registered enrichers. |

The `StructuredLoggingOption` enum holds both tag names, so you don't need to hardcode the strings when registering your own handlers and enrichers.

To replace one of these services with your own implementation, use `$kernel->override()`. Calling `define()` with an id the module already defines throws a `DefinitionException`.

## Domain exceptions

Define your own domain exceptions by extending `DomainException`:

```php
use Meritum\StructuredLogging\Exception\DomainException;
use Meritum\StructuredLogging\Severity;

final class OrderNotFoundException extends DomainException
{
    public function getErrorCode(): string
    {
        return 'ORDER_' . $this->generateErrorCodeSuffix(1);
    }
}

throw new OrderNotFoundException(
    message: 'Order not found',
    severity: Severity::Warning,
    context: ['order_id' => $orderId],
    retryable: false,
);
```

Every domain exception exposes a `structuredData` property containing the full structured payload, which lands in the PSR-3 log context:

```php
[
    'error_code'  => 'ORDER_0001',
    'message'     => 'Order not found',
    'severity'    => 'warning',
    'retryable'   => false,
    'occurred_at' => '2026-06-10T12:00:00+00:00',
    'detail'      => ['order_id' => 42],
    'metadata'    => ['file' => '...', 'line' => 42, 'class' => '...'],
]
```

The `detail` key carries the per-exception contextual data passed in `$context`. This is deliberately named to avoid a collision with the PSR-3 context array wrapper that some loggers produce.

## Translation pipeline

The translator converts arbitrary `Throwable` instances into domain exceptions. Handlers are registered as tagged kernel services, and the translator collects them automatically when it's first resolved.

### Defining a handler

Implement `TranslationHandler` and tag it with `StructuredLoggingOption::TranslatorTag` in your module:

```php
use Throwable;
use Meritum\StructuredLogging\Severity;
use Meritum\StructuredLogging\TranslationHandler;
use Meritum\StructuredLogging\Exception\DomainException;

final class DatabaseExceptionHandler implements TranslationHandler
{
    public function matches(Throwable $exception): bool
    {
        return $exception instanceof \PDOException;
    }

    public function handle(Throwable $exception): DomainException
    {
        return new DatabaseException(
            message: 'A database error occurred',
            severity: Severity::Error,
            context: ['pdo_code' => $exception->getCode()],
            previous: $exception,
        );
    }

    public function priority(): int
    {
        return 0;
    }
}
```

```php
use Meritum\StructuredLogging\StructuredLoggingOption;

// In your module's register() method:
$kernel->define(DatabaseExceptionHandler::class, fn() => new DatabaseExceptionHandler())
       ->tag(StructuredLoggingOption::TranslatorTag->value);
```

Higher `priority()` values win when multiple handlers match the same exception. If no handler matches, the translator wraps the exception in an `UnknownException` and logs it at `error` severity.

### Using the translator directly

```php
use Meritum\StructuredLogging\ExceptionTranslator;

$domain = $translator->translate($e);
```

If `$e` is already a `DomainException`, it is returned as-is.

## Reporting

`ExceptionReporter::report()` runs the full pipeline — translate, enrich, log — and returns the resulting `DomainException` for the caller to act on (render a response, rethrow, etc.):

```php
use Meritum\StructuredLogging\ExceptionReporter;

$domain = $reporter->report($e);
```

## Context enrichment

Enrichers add data to the PSR-3 log context on every log call through the decorated logger. `CorrelationIdEnricher` is registered automatically. Add your own by implementing `ContextEnricher` and tagging it:

```php
use Meritum\StructuredLogging\ContextEnricher;

final class AppVersionEnricher implements ContextEnricher
{
    public function enrich(array $context): array
    {
        return $context + ['app_version' => '1.4.2'];
    }
}
```

```php
use Meritum\StructuredLogging\StructuredLoggingOption;

$kernel->define(AppVersionEnricher::class, fn() => new AppVersionEnricher())
       ->tag(StructuredLoggingOption::EnricherTag->value);
```

Use the `+` operator rather than `array_merge` so that context values already set by the caller are not overwritten.

## Correlation ID

`CorrelationId` is a shared service that generates a UUID v4 when it's first resolved. It is automatically added to every log entry via `CorrelationIdEnricher`.

To overwrite the generated ID from an incoming HTTP request header (e.g. in a PSR-15 middleware):

```php
use Meritum\StructuredLogging\CorrelationId;

final class CorrelationIdMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CorrelationId $correlationId) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('X-Correlation-ID');

        $this->correlationId->set($header);

        return $handler->handle($request);
    }
}
```

`set()` validates the value as a UUID v4. Invalid or missing values are silently ignored and the auto-generated ID is preserved — a garbage header from a client is not an exceptional condition.

### Long-running processes

`CorrelationId` implements the kernel's `ResettableInterface`. In a long-running worker or daemon that handles many requests or jobs in one process, call `$kernel->resetShared()` between units of work. `reset()` generates a fresh UUID, so a request without a correlation header gets its own ID instead of inheriting the previous request's:

```php
while ($job = $queue->next()) {
    $handler->handle($job);

    $kernel->resetShared();
}
```

Middleware that calls `set()` runs at the start of each unit of work, after the reset, so client-supplied IDs keep working unchanged. A UUID passed to the constructor is not restored by `reset()`; the reset always generates a new one. In a traditional PHP-FPM setup each request is its own process, so no reset is needed.

## Severity levels

| Case | PSR-3 level |
|---|---|
| `Severity::Critical` | `critical` |
| `Severity::Error` | `error` |
| `Severity::Warning` | `warning` |
| `Severity::Info` | `info` |
| `Severity::Debug` | `debug` |
