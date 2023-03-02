<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base data fetcher implementation.
 *
 * @see \Drupal\metrc_migrate\Annotation\Authentication
 * @see \Drupal\metrc_migrate\AuthenticationPluginInterface
 * @see \Drupal\metrc_migrate\AuthenticationPluginManager
 * @see plugin_api
 */
abstract class AuthenticationPluginBase extends PluginBase implements AuthenticationPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
