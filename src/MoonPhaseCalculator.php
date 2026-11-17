<?php

declare(strict_types=1);

namespace Drupal\moonphase;

/**
 * Estimates lunar phases from the geocentric longitudes of the Sun and Moon.
 */
final class MoonPhaseCalculator {

  public const PERIOD_DAYS = 29.53058867;
  public const DAY_SECONDS = 86400;
  // Retained for callers of the original calculator.
  public const REFERENCE_FULL_MOON = 1030055340;
  private const PHASE_WINDOW = 0.026;

  public const NAMES = [
    'New Moon', 'Waxing Crescent', 'First Quarter Moon', 'Waxing Gibbous',
    'Full Moon', 'Waning Gibbous', 'Third Quarter Moon', 'Waning Crescent',
  ];

  public const IMAGES = [
    'new.gif', 'waxingcresent.gif', '1quarter.gif', 'waxinggibbous.gif',
    'full.gif', 'waninggibbous.gif', 'lastquarter.gif', 'waningcresent.gif',
  ];

  /**
   * Returns the angular fraction of the cycle, with zero at full moon.
   */
  public function getPositionInCycle(int $timestamp): float {
    // Orbital elements and lunar longitude perturbations from Paul Schlyter.
    // https://www.stjarnhimlen.se/comp/ppcomp.html
    // The model's epoch is December 31, 1999 at 00:00 UTC.
    $days = ($timestamp - 946598400) / self::DAY_SECONDS;
    $sunAnomaly = deg2rad(fmod(356.0470 + 0.9856002585 * $days, 360));
    $sunPerihelion = deg2rad(282.9404 + 0.0000470935 * $days);
    $sunMeanLongitude = $sunAnomaly + $sunPerihelion;
    $sunEccentricity = 0.016709 - 0.000000001151 * $days;
    $sunLongitude = self::trueAnomaly($sunAnomaly, $sunEccentricity) + $sunPerihelion;

    $moonNode = deg2rad(fmod(125.1228 - 0.0529538083 * $days, 360));
    $moonPerigee = deg2rad(fmod(318.0634 + 0.1643573223 * $days, 360));
    $moonAnomaly = deg2rad(fmod(115.3654 + 13.0649929509 * $days, 360));
    $moonMeanLongitude = $moonNode + $moonPerigee + $moonAnomaly;
    $argumentOfLatitude = $moonPerigee + self::trueAnomaly($moonAnomaly, 0.054900);
    $moonLongitude = $moonNode + atan2(
      cos(deg2rad(5.1454)) * sin($argumentOfLatitude),
      cos($argumentOfLatitude),
    );

    $elongation = $moonMeanLongitude - $sunMeanLongitude;
    $meanArgumentOfLatitude = $moonMeanLongitude - $moonNode;
    $correction = -1.274 * sin($moonAnomaly - 2 * $elongation)
      + 0.658 * sin(2 * $elongation)
      - 0.186 * sin($sunAnomaly)
      - 0.059 * sin(2 * $moonAnomaly - 2 * $elongation)
      - 0.057 * sin($moonAnomaly - 2 * $elongation + $sunAnomaly)
      + 0.053 * sin($moonAnomaly + 2 * $elongation)
      + 0.046 * sin(2 * $elongation - $sunAnomaly)
      + 0.041 * sin($moonAnomaly - $sunAnomaly)
      - 0.035 * sin($elongation)
      - 0.031 * sin($moonAnomaly + $sunAnomaly)
      - 0.015 * sin(2 * $meanArgumentOfLatitude - 2 * $elongation)
      + 0.011 * sin($moonAnomaly - 4 * $elongation);

    return self::wrapCycle((rad2deg($moonLongitude - $sunLongitude) + $correction - 180) / 360);
  }

  private static function trueAnomaly(float $meanAnomaly, float $eccentricity): float {
    $eccentricAnomaly = $meanAnomaly;
    for ($iteration = 0; $iteration < 3; $iteration++) {
      $eccentricAnomaly -= ($eccentricAnomaly - $eccentricity * sin($eccentricAnomaly) - $meanAnomaly)
        / (1 - $eccentricity * cos($eccentricAnomaly));
    }
    return atan2(
      sqrt(1 - $eccentricity ** 2) * sin($eccentricAnomaly),
      cos($eccentricAnomaly) - $eccentricity,
    );
  }

  private static function wrapCycle(float $position): float {
    return $position - floor($position);
  }

  /**
   * Unwraps the angular change using elapsed time to distinguish lunations.
   */
  private function cycleProgress(int $timestamp, int $elapsed, float $startPosition): float {
    $change = $this->getPositionInCycle($timestamp + $elapsed) - $startPosition;
    return $change + round($elapsed / (self::PERIOD_DAYS * self::DAY_SECONDS) - $change);
  }

  /**
   * Finds the first whole second at or after the upcoming phase crossing.
   */
  private function daysUntilPhase(int $timestamp, float $position, float $target): float {
    $remaining = self::wrapCycle($target - $position);
    $remaining = $remaining > 0 ? $remaining : 1;
    $lower = 0;
    $upper = 35 * self::DAY_SECONDS;
    while ($upper - $lower > 1) {
      $middle = intdiv($lower + $upper, 2);
      if ($this->cycleProgress($timestamp, $middle, $position) < $remaining) {
        $lower = $middle;
      }
      else {
        $upper = $middle;
      }
    }
    return $upper / self::DAY_SECONDS;
  }

  /**
   * Calculates phase, illumination, and days until the next principal phases.
   *
   * At an exact phase instant, "next" means the following lunar cycle.
   */
  public function calculate(int $timestamp): array {
    $position = $this->getPositionInCycle($timestamp);
    // Give each principal phase the same window on either side of its peak.
    $window = self::PHASE_WINDOW;
    $id = match (TRUE) {
      $position <= $window || $position >= 1 - $window => 4,
      $position < 0.25 - $window => 5,
      $position <= 0.25 + $window => 6,
      $position < 0.5 - $window => 7,
      $position <= 0.5 + $window => 0,
      $position < 0.75 - $window => 1,
      $position <= 0.75 + $window => 2,
      default => 3,
    };
    return [
      'timestamp' => $timestamp,
      'phase_id' => $id,
      'phase_name' => self::NAMES[$id],
      'image' => self::IMAGES[$id],
      'position' => $position,
      'illumination' => round((1 + cos(2 * M_PI * $position)) * 50, 1),
      'days_until_full_moon' => round($this->daysUntilPhase($timestamp, $position, 0.0), 2),
      'days_until_new_moon' => round($this->daysUntilPhase($timestamp, $position, 0.5), 1),
      'days_until_first_quarter' => round($this->daysUntilPhase($timestamp, $position, 0.75), 1),
      'days_until_third_quarter' => round($this->daysUntilPhase($timestamp, $position, 0.25), 1),
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
