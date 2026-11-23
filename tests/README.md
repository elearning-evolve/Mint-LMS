# Mint LMS tests

## What GitHub CI runs

GitHub Actions (`.github/workflows/check.yml`) runs **only**:

- `composer arch` — architecture boundary tests
- `composer test:unit` — unit tests

Same default locally:

```bash
composer test:ci
# or
./vendor/bin/phpunit
```

(`phpunit.xml.dist` `defaultTestSuite` is `ci` = Architecture + Unit.)

Full local quality pass (still **no** integration):

```bash
composer check
```

That runs architecture, PHPStan, PHPCS, and unit tests.

## Integration tests — not in CI

`tests/Integration/` needs a real WordPress PHPUnit scaffold (`ABSPATH`, `$wpdb`, `wp_insert_post`, etc.).  
They are **intentionally excluded** from GitHub CI so PRs do not depend on a WordPress DB bootstrap.

Opt-in only:

```bash
composer test:integ
```

Without a WP test install, every integration test skips with an explicit message (see `IntegrationTestCase`).

To actually run them, use the [WordPress PHPUnit test scaffold](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) and point PHPUnit at a bootstrap that loads WordPress before Mint LMS (replace or wrap `tests/bootstrap.php` for that job only).

## Debug leftovers

Production paths under `assets/src`, `src`, `views`, and `templates` must stay free of `console.log`, `var_dump`, and `print_r`. Prefer `MintLMS\Logger` / `WP_DEBUG`.
