<?php

namespace Drupal\metrc_views;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * metrc base table endpoint plugin manager class. Each metrc endpoint has a
 * one-to-one mapping with a views base table. Each of which can have a base
 * table endpoint plugin associated with it, which is an object that has
 * specific domain knowledge about the metrc endpoint it interacts with. Each
 * plugin is resposibile for communicating with that endpoint and translating
 * the response's into \Drupal\views\ResultRow objects.
 */
class MetrcBaseTableEndpointPluginManager extends DefaultPluginManager {

  /**
   * MetrcBaseTableEndpointPluginManager constructor.
   *
   * @param \Traversable $namespaces
   * @param CacheBackendInterface $cache_backend
   * @param ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/MetrcBaseTableEndpoint', $namespaces, $module_handler, 'Drupal\metrc_views\MetrcBaseTableEndpointInterface', 'Drupal\metrc_views\Annotation\MetrcBaseTableEndpoint');

    $this->alterInfo('metrc_base_table_endpoints_info');
    $this->setCacheBackend($cache_backend, 'metrc_base_table_endpoints');
  }
}
