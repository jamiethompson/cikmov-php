# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-02-13

### Added
- Initial public release of `cikmov`.
- Deterministic UK postcode analysis entrypoint:
  - `Cikmov::analyse(string $input, int $minConfidenceToApply = 85): Result`
- Immutable `Result` value object with invariant enforcement.
- Constraint-based grammar engine covering:
  - outward patterns (`A9`, `A9A`, `A99`, `AA9`, `AA9A`, `AA99`)
  - inward pattern (`digit letter letter`)
  - CIKMOV inward exclusion rule
  - mandatory postcode area enforcement
  - AA9A London-specific restrictions
  - special handling for `GIR 0AA`
- Deterministic candidate generation and scoring with:
  - positional substitutions
  - outward-vs-inward weighted penalties
  - ambiguity-aware confidence reduction
  - threshold-gated correction application
  - alternative candidate reporting
- Comprehensive PHPUnit suite covering:
  - grammar/normalization/correction
  - ambiguity and alternatives
  - AA9A valid/invalid rules
  - area enforcement and invalid areas
  - special/non-geographic cases
  - idempotency and invariant misuse
- CI workflow for PHPUnit on PHP 8.2.
- MIT license and package distribution hygiene files.

[Unreleased]: https://github.com/jamiethompson/cikmov-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/jamiethompson/cikmov-php/releases/tag/v0.1.0
