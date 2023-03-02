<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for data parsers.
 *
 * @see \Drupal\metrc_migrate\Annotation\DataParser
 * @see \Drupal\metrc_migrate\DataParserPluginBase
 * @see \Drupal\metrc_migrate\DataParserPluginInterface
 * @see plugin_api
 */
class DataParserPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new DataParserPluginManager.
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
    parent::__construct('Plugin/metrc_migrate/data_parser', $namespaces, $module_handler, 'Drupal\metrc_migrate\DataParserPluginInterface', 'Drupal\metrc_migrate\Annotation\DataParser');

    $this->alterInfo('data_parser_info');
    $this->setCacheBackend($cache_backend, 'metrc_migrate_plugins_data_parser');
  }

}
