<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for data fetchers.
 *
 * @see \Drupal\metrc_migrate\Annotation\DataFetcher
 * @see \Drupal\metrc_migrate\DataFetcherPluginBase
 * @see \Drupal\metrc_migrate\DataFetcherPluginInterface
 * @see plugin_api
 */
class DataFetcherPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new DataFetcherPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/metrc_migrate/data_fetcher', $namespaces, $module_handler, 'Drupal\metrc_migrate\DataFetcherPluginInterface', 'Drupal\metrc_migrate\Annotation\DataFetcher');

    $this->alterInfo('metrc_migrate_plugin');
    $this->setCacheBackend($cache_backend, 'metrc_migrate_plugins_data_fetcher');
  }

}
