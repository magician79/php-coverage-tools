# php-coverage-tools

[![CI](https://github.com/magician79/php-coverage-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/magician79/php-coverage-tools/actions/workflows/ci.yml)

Small CLI utilities for normalizing PHP coverage reports.

## Why

Coverage tools like Cobertura correctly report interfaces and pure abstract
contracts as having zero executable lines. However, many summary generators
treat these files as 0% covered, producing misleading health metrics.

This repository contains small, explicit tools that enforce the rule:

> Non-executable files must not affect coverage health.

## Tools

### filter-cobertura

Removes files with `lines-valid="0"` from a Cobertura XML report.

```bash
filter-cobertura input.xml output.xml
```

This ensures that coverage summaries and badges reflect real executable code.