# Contributing

Contributions are welcome! Bug reports and feature requests go to the
[issue tracker](https://github.com/silarhi/caf-parser/issues), changes to a pull request against `main`.

## Setup

```bash
composer install
pnpm install # Git hooks: lint-staged formats the staged files on commit
```

## Quality checks

Please make sure your changes pass all quality checks before submitting a pull request. The CI runs PHPUnit on every
supported PHP version, with the lowest and the latest dependencies, and each other check once, except Rector: run it
locally.

```bash
# Tests
vendor/bin/phpunit

# Static analysis (level: max)
vendor/bin/phpstan analyse

# Code style (drop --dry-run --diff to fix)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Code modernization (drop --dry-run to apply)
vendor/bin/rector process --dry-run

# composer.json
composer validate --strict
composer normalize --dry-run --diff

# Documentation examples
php docs/scripts/render-examples.php --check
```

The usual trio before pushing:

```bash
vendor/bin/phpunit && vendor/bin/phpstan analyse && vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Documentation

The documentation site, [caf-parser.silarhi.dev](https://caf-parser.silarhi.dev), is built from the [`docs/`](docs)
folder with [Starlight](https://starlight.astro.build). Document a change in the same pull request:

```bash
cd docs
pnpm install
pnpm dev # http://localhost:4321
```

The examples of the site are real parser output: each `docs/examples/<name>.txt` file is parsed by
`docs/scripts/render-examples.php`, which writes what the parser returns to `docs/src/data/examples/<name>.txt`. After
changing the parser or an example, regenerate them and commit the result; the CI fails when they are out of date:

```bash
php docs/scripts/render-examples.php
```

The example files hold made-up data only: never commit a real CAF slip.

Pull requests touching `docs/` build the site; it is published once merged into `main`.

## License

By contributing, you agree that your contributions are released under the [MIT License](LICENSE).
