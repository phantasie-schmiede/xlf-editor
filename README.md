# Xlf Editor

Backend module which allows to edit XLF files

## Installation

```bash
composer require psbits/xlf-editor
```

## Development

```bash
composer install
composer qa
```

`composer qa` fixes everything that can be fixed automatically and afterwards runs all linters and the static
analysis. The same checks run in the `Quality` GitHub Actions workflow and in the `pre-commit` hook that is
installed by `composer install`.

The workflow also runs a support matrix that checks the supported TYPO3 and PHP combinations still resolve their
dependencies, and publishes the extension to TER when a semver tag is pushed. Publishing requires the
`TYPO3_API_TOKEN` repository secret.
