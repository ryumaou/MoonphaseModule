<?php

declare(strict_types=1);

namespace Drupal\moonphase;

/**
 * Approximate lunar cycle adapted from the original MoonPhase WP plugin.
 *
 * Original calculation: Dan Sheeler, Kumar McMillan, and J. K. Hoffman.
 * This mean-period model is intended for display, not astronomical prediction.
 */
final class MoonPhaseCalculator {

  public const PERIOD_DAYS = 29.53058867;
  public const DAY_SECONDS = 86400;
  // August 22, 2002, 22:29 UTC, the original full-moon reference.
  public const REFERENCE_FULL_MOON = 1030055340;

  public const NAMES = [
    'New Moon', 'Waxing Crescent', 'First Quarter Moon', 'Waxing Gibbous',
    'Full Moon', 'Waning Gibbous', 'Third Quarter Moon', 'Waning Crescent',
  ];

  public const IMAGES = [
    'new.gif', 'waxingcresent.gif', '1quarter.gif', 'waxinggibbous.gif',
    'full.gif', 'waninggibbous.gif', 'lastquarter.gif', 'waningcresent.gif',
  ];

  /**
   * Returns cycle position with zero at full moon, including pre-2002 dates.
   */
  public function getPositionInCycle(int $timestamp): float {
    $period = self::PERIOD_DAYS * self::DAY_SECONDS;
    $position = fmod($timestamp - self::REFERENCE_FULL_MOON, $period) / $period;
    return $position < 0 ? $position + 1 : $position;
  }

  /**
   * Calculates phase, illumination, and days until the next principal phases.
   *
   * At an exact phase instant, "next" means the following lunar cycle.
   */
  public function calculate(int $timestamp): array {
    $position = $this->getPositionInCycle($timestamp);
    // Preserve the original thresholds while closing its 0.4739-0.474 gap.
    $id = match (TRUE) {
      $position >= 0.474 && $position <= 0.53 => 0,
      $position > 0.53 && $position <= 0.724 => 1,
      $position > 0.724 && $position <= 0.776 => 2,
      $position > 0.776 && $position < 0.974 => 3,
      $position >= 0.974 || $position <= 0.026 => 4,
      $position <= 0.234 => 5,
      $position <= 0.295 => 6,
      default => 7,
    };
    $days = static function (float $target) use ($position): float {
      $difference = $target - $position;
      return ($difference > 0 ? $difference : $difference + 1) * self::PERIOD_DAYS;
    };
    return [
      'timestamp' => $timestamp,
      'phase_id' => $id,
      'phase_name' => self::NAMES[$id],
      'image' => self::IMAGES[$id],
      'position' => $position,
      'illumination' => round((1 + cos(2 * M_PI * $position)) * 50, 1),
      'days_until_full_moon' => round($days(0.0), 2),
      'days_until_new_moon' => round($days(0.5), 1),
      'days_until_first_quarter' => round($days(0.75), 1),
      'days_until_third_quarter' => round($days(0.25), 1),
    ];
  }

  /**
   * Returns seven daily calculations, keyed by Unix timestamp (24-hour steps).
   */
  public function getUpcomingWeek(int $timestamp): array {
    $week = [];
    for ($day = 0; $day < 7; $day++) {
      $date = $timestamp + $day * self::DAY_SECONDS;
      $week[$date] = $this->calculate($date);
    }
    return $week;
  }

}
