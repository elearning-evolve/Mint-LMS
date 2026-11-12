# Mint LMS tests

## Unit and architecture tests

Run the default CI suite (no WordPress bootstrap required):

```bash
composer check
```

This runs architecture boundary tests, PHPStan, PHPCS, and unit tests.

## Integration tests

Integration tests live in `tests/Integration/` and require a WordPress test environment with `$wpdb` available. They self-skip when WordPress is not bootstrapped:

```bash
composer test:integ
```

To run integration tests locally, use the [WordPress PHPUnit test scaffold](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) and point `phpunit.xml.dist` bootstrap at your WP test install, or run inside a CI job that loads WordPress before PHPUnit.
