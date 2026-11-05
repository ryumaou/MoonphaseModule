<?php

declare(strict_types=1);

// Standalone regression checks; no Drupal bootstrap or PHPUnit required.
require dirname(__DIR__) . '/src/MoonPhaseCalculator.php';

use Drupal\moonphase\MoonPhaseCalculator;

$calculator = new MoonPhaseCalculator();
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$reference = (new DateTimeImmutable('2002-08-22 22:29:00 UTC'))->getTimestamp();
$check($reference === MoonPhaseCalculator::REFERENCE_FULL_MOON, 'UTC reference date');
$period = MoonPhaseCalculator::PERIOD_DAYS * 86400;
$check($calculator->calculate($reference)['phase_id'] === 4, 'Reference full moon');
foreach ([[0.125, 5], [0.25, 6], [0.4, 7], [0.47395, 7], [0.5, 0], [0.6, 1], [0.75, 2], [0.9, 3]] as [$position, $id]) {
  $phase = $calculator->calculate($reference + (int) round($position * $period));
  $check($phase['phase_id'] === $id, 'Phase at ' . $position);
}
$check($calculator->calculate($reference)['illumination'] === 100.0, 'Full illumination');
$check($calculator->calculate($reference + (int) round($period / 2))['illumination'] === 0.0, 'New illumination');
foreach ([-2208988800, -1, 0, $reference - 1, $reference, 1793880000, 4102444800] as $timestamp) {
  $phase = $calculator->calculate($timestamp);
  $check($phase['position'] >= 0 && $phase['position'] < 1, 'Normalized cycle');
  $check($phase['phase_id'] >= 0 && $phase['phase_id'] <= 7, 'Valid phase');
  foreach (['full_moon', 'new_moon', 'first_quarter', 'third_quarter'] as $event) {
    $check($phase['days_until_' . $event] >= 0 && $phase['days_until_' . $event] <= 29.6, 'Next phase range');
  }
  $check(is_file(dirname(__DIR__) . '/moonimg/' . $phase['image']), 'Image exists');
}
$week = $calculator->getUpcomingWeek(0);
$check(count($week) === 7 && array_keys($week) === [0, 86400, 172800, 259200, 345600, 432000, 518400], 'Week timestamps');
echo "Moon phase calculator checks passed.\n";
