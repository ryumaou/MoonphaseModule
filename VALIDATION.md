# Validation status

Performed during conversion:

- Confirmed all eight GIF files match the WordPress originals by SHA-256.
- Confirmed the full-moon reference timestamp against a UTC date conversion.
- Checked the module files for WordPress bootstrap, database, and URL dependencies.
- Reviewed service registration, block discovery metadata, render arrays, and
  content display integration against the Drupal API.
- The original WordPress checkout remains unchanged.

Not executed: PHP syntax checks, the standalone PHP regression script, or Drupal
installation/runtime checks. No PHP executable or Drupal test site was available
in the conversion environment. Compatibility declarations are implementation
targets rather than a claim of successful integration testing.

On a PHP-equipped machine, lint the PHP files and `moonphase.module`, then run
`php tests/calculator.php`. Follow the installation and site checks in README.md.
