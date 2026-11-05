# Moon Phase for Drupal

A Drupal 10.3 / 11 port of `WP_Moonphase_plugin` by J. K. Hoffman,
with the original eight GIF images and the lunar calculation credited to
Dan Sheeler and Kumar McMillan. Distributed under the original GPL license;
see `LICENSE`.

## Install

1. Copy this entire directory to `web/modules/custom/moonphase` (or
   `modules/custom/moonphase` when your Drupal document root has no `web`).
   The module machine name is **moonphase**, regardless of this repository's
   `MoonphaseModule` directory name.
2. Enable **Moon Phase** on Extend (`/admin/modules`), or run
   `drush en moonphase -y` followed by `drush cr`.
3. For a sidebar widget, visit Structure → Block layout (`/admin/structure/block`),
   place **Current moon phase** in the desired region, and save.
4. For post dates, visit Structure → Content types → your content type →
   Manage display. Move **Moon phase on content date** out of Disabled and save.
   Repeat for each desired view mode, such as Full content and Teaser.

The content component uses Drupal's **Authored on** timestamp, including edits
to that date. It calculates the phase when rendering; no extra database field,
manual post updates, external API, WordPress installation, or cron job is needed.
The image, phase name, illumination, and days to the next full moon are visible
as text, with alternative text and an image tooltip.

## Calculation and caching

This preserves the original approximate 29.53058867-day cycle anchored at
August 22, 2002, 22:29 UTC. It fixes PHP's integer modulo truncation by using
`fmod()` and closes an uncovered interval in the legacy phase thresholds.
Results may drift from astronomical ephemerides; use this for decorative or
informational display. The images retain their original orientation.

Content-date output uses normal node/display caching. The current-moon block
has cache max-age zero so Drupal's render cache does not freeze its date.
Drupal's anonymous Internal Page Cache and external page caches can still serve
cached pages: configure their page lifetime or exclusions if timely moon
updates matter. Placing this block can reduce render-cache effectiveness.

## Developer API

```php
$phase = \Drupal::service('moonphase.calculator')->calculate($unix_timestamp);
$week = \Drupal::service('moonphase.calculator')->getUpcomingWeek($unix_timestamp);
$render_array = \Drupal::service('moonphase.renderer')->build($unix_timestamp);
```

Inject these services in classes. Calculation returns a phase ID (0–7), English
phase name, GIF filename, full-moon-relative cycle position, illumination percent,
and days until the next full, new, first-quarter, and third-quarter moons.
An exact phase instant counts the following cycle as the next occurrence.
The weekly helper uses seven fixed 24-hour steps rather than local calendar days.

## Verification

Run the standalone numerical regression checks with PHP 8.1 or later:

```sh
php tests/calculator.php
```

Before deployment, enable the module on a Drupal site and check block discovery,
image URLs (including subdirectory installations), translated labels, content
display modes, edited Authored on dates, and anonymous page-cache behavior.
