# php-coverage-tools

[![CI](https://github.com/magician79/php-coverage-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/magician79/php-coverage-tools/actions/workflows/ci.yml)

Small, explicit CLI utilities for normalizing PHP coverage reports.

---

## Why

Cobertura and similar coverage formats correctly include **interfaces and pure
contracts** in their reports. These elements contain no executable code.

Many coverage summary tools, however, treat these entries as **0% covered files**,
which pollutes coverage metrics and causes misleading regressions.

This package enforces a simple rule:

> **Non-executable code must not affect coverage health.**

---

## Tooling

### `filter-cobertura`

Filters **non-executable packages** from a Cobertura XML report.

A package is considered *non-executable* when all of the following are true:

- `line-rate` is `0`
- `complexity` is `0`
- It contains **no executable `<line>` elements**
- It represents an empty `<classes/>` structure (typical for interfaces)

This logic matches Cobertura output observed in real-world reports and is
validated by unit tests.

Executable packages and *mixed packages* (for example, partially covered code)
are preserved defensively.

---

## Usage

```bash
filter-cobertura input.xml output.xml
```

The resulting file contains **only executable coverage data** and can safely be
used for coverage summaries, CI thresholds, and badges.

If a non-executable package leaks through after filtering, the tool fails fast
with a clear error instead of silently producing incorrect metrics.

---

## Guarantees

- Executable packages are **never removed**
- Mixed or ambiguous packages are **preserved**
- Pure non-executable packages are **excluded**
- Behavior is enforced by **unit tests**

---

## Installation

Install as a development dependency:

```bash
composer require magician79/php-coverage-tools
```

> This package is intended for CI and development workflows and should not be installed in production dependencies.

---

## Typical CI usage

```bash
vendor/bin/filter-cobertura \
  coverage/cobertura.xml \
  coverage/cobertura.filtered.xml
```

Use the filtered report for coverage summaries and badges.

---

## License

MIT