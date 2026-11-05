<?php

declare(strict_types=1);

namespace Drupal\moonphase;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Builds escaped, translatable Drupal render arrays for a given date.
 */
final class MoonPhaseRenderer {

  use StringTranslationTrait;

  public function __construct(
    private readonly MoonPhaseCalculator $calculator,
    private readonly ModuleExtensionList $moduleList,
    TranslationInterface $translation,
  ) {
    $this->stringTranslation = $translation;
  }

  public function build(int $timestamp): array {
    $phase = $this->calculator->calculate($timestamp);
    // Literal strings allow Drupal's translation extractor to discover names.
    $names = [
      $this->t('New Moon'), $this->t('Waxing Crescent'),
      $this->t('First Quarter Moon'), $this->t('Waxing Gibbous'),
      $this->t('Full Moon'), $this->t('Waning Gibbous'),
      $this->t('Third Quarter Moon'), $this->t('Waning Crescent'),
    ];
    $name = $names[$phase['phase_id']];
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['moonphase']],
      'image' => [
        '#theme' => 'image',
        '#uri' => $this->moduleList->getPath('moonphase') . '/moonimg/' . $phase['image'],
        '#alt' => $this->t('Moon phase: @phase', ['@phase' => $name]),
        '#title' => $this->t('@phase; @percent% illuminated; next full moon in @days days.', [
          '@phase' => $name,
          '@percent' => $phase['illumination'],
          '@days' => $phase['days_until_full_moon'],
        ]),
      ],
      'phase' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $name],
      'details' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('@percent% illuminated. Next full moon in @days days.', [
          '@percent' => $phase['illumination'],
          '@days' => $phase['days_until_full_moon'],
        ]),
      ],
    ];
  }

}
