# Upgrading from 1.x to 2.0

2.0 migrates `meritum/structured-logging` onto `georgeff/kernel` ^2.0. The package's own API (domain exceptions, the translation pipeline, `ExceptionReporter`, context enrichers, `CorrelationId`) is unchanged; what affects you here comes from upgrading the base kernel. **Read [`georgeff/kernel`'s own `UPGRADE-2.0.md`](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md) first**; this guide only covers what's specific to `meritum/structured-logging`.

See `CHANGELOG.md` for the full list of changes.

## Requirements

- [ ] **`georgeff/kernel` ^2.0.** `composer.json` now requires `"georgeff/kernel": "^2.0"`. `StructuredLoggingModule` implements kernel 2.0's `Contract\ModuleInterface`, so it can't be added to a 1.x kernel.
- [ ] **A kernel-2.0-compatible PSR-3 logger.** The module still decorates `LoggerInterface`, so something must define it. If you use [`meritum/logger`](https://github.com/MeritumIO/logger), upgrade it to ^2.0 as well.

## 1. Replacing a module service needs `override()`

Kernel 2.0's `define()` throws `DefinitionException` when an id is already defined. `StructuredLoggingModule` defines `CorrelationId`, `CorrelationIdEnricher`, `ExceptionTranslator` and `ExceptionReporter`, so defining any of them again yourself now fails instead of silently winning.

- [ ] If you replace one of these services, switch to `override()`:

  ```php
  // Before
  $kernel->define(ExceptionReporter::class, fn($c) => new MyReporter(...));

  // After
  $kernel->override(ExceptionReporter::class, fn($c) => new MyReporter(...));
  ```

- [ ] Custom enrichers and translation handlers registered under their own class ids are unaffected, so no change is needed.

## Not required, but worth adopting

- **`StructuredLoggingOption`** — holds the two tag names. The string values are unchanged, so existing `->tag('log.context.enrichers')` / `->tag('exception.translator.handlers')` calls keep working; the enum just saves hardcoding them:

  ```php
  use Meritum\StructuredLogging\StructuredLoggingOption;

  $kernel->define(AppVersionEnricher::class, fn() => new AppVersionEnricher())
         ->tag(StructuredLoggingOption::EnricherTag->value);
  ```

- **Resetting the correlation ID in long-running processes** — `CorrelationId` now implements the kernel's `ResettableInterface`. If you run a worker or daemon that handles many requests or jobs in one process, call `$kernel->resetShared()` between units of work so each one gets a fresh correlation ID. Nothing changes for PHP-FPM.

## Verifying the upgrade

- [ ] `composer test` — full suite passes
- [ ] `composer analyze` — PHPStan clean at `level: max`
- [ ] Grep your own codebase for `define(CorrelationId::class`, `define(CorrelationIdEnricher::class`, `define(ExceptionTranslator::class`, and `define(ExceptionReporter::class` — any match needs section 1.
- [ ] Also run through [`georgeff/kernel`'s own verification checklist](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md#verifying-the-upgrade) for base-kernel-level changes.
