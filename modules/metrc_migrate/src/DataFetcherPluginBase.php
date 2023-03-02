<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base data fetcher implementation.
 *
 * @see \Drupal\metrc_migrate\Annotation\DataFetcher
 * @see \Drupal\metrc_migrate\DataFetcherPluginInterface
 * @see \Drupal\metrc_migrate\DataFetcherPluginManager
 * @see plugin_api
 */
abstract class DataFetcherPluginBase extends PluginBase implements DataFetcherPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
