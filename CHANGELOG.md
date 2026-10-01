# Changelog

All notable changes to `meritum/structured-logging` are documented here.

---

## [2.0.0] — 2026-10-01

2.0 migrates to `georgeff/kernel` ^2.0.

### Added
- `StructuredLoggingOption` enum — `StructuredLoggingOption::EnricherTag` (`log.context.enrichers`) and `StructuredLoggingOption::TranslatorTag` (`exception.translator.handlers`) hold the package's two tag names, so custom enrichers and translation handlers can be registered via `->tag(StructuredLoggingOption::EnricherTag->value)` instead of hardcoding the strings. The tag values themselves are unchanged, so existing string-based registrations keep working
- `CorrelationId` implements `Georgeff\Kernel\Contract\ResettableInterface`. `reset()` generates a fresh UUID v4, so a long-running worker that calls `$kernel->resetShared()` between units of work gives each one its own correlation ID instead of carrying the previous one over. The kernel tracks the shared `CorrelationId` automatically once it's resolved; no tag or extra wiring is needed. Processes that never call `resetShared()` (e.g. PHP-FPM) are unaffected

### Changed
- **Breaking:** migrated to `georgeff/kernel` ^2.0 — `StructuredLoggingModule` now implements `Georgeff\Kernel\Contract\ModuleInterface`, so it can only be added to a 2.0 kernel
- Replacing one of the module's services (`CorrelationId`, `CorrelationIdEnricher`, `ExceptionTranslator`, `ExceptionReporter`) now requires `override()`: kernel 2.0's `define()` throws `DefinitionException` for an id that's already defined
