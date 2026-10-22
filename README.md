# Xlf Editor

Backend module which allows to edit XLF files

## Usage

The module lists the language files of all installed extensions and allows to edit the labels of every
trans-unit. After saving, the localization cache is flushed, so the edited labels are displayed immediately.

The module is registered below the PSBits Foundation main module, after the `Analyze LocalLang` module.

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
