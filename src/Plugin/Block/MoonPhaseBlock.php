<?php

declare(strict_types=1);

namespace Drupal\moonphase\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\moonphase\MoonPhaseRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the current moon, refreshed on each uncached render.
 */
#[Block(
  id: 'moonphase_current',
  admin_label: new TranslatableMarkup('Current moon phase'),
  category: new TranslatableMarkup('Moon Phase'),
)]
final class MoonPhaseBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly MoonPhaseRenderer $renderer,
    private readonly TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('moonphase.renderer'), $container->get('datetime.time'));
  }

  public function build(): array {
    return $this->renderer->build($this->time->getRequestTime());
  }

  public function getCacheMaxAge(): int {
    return 0;
  }

}
