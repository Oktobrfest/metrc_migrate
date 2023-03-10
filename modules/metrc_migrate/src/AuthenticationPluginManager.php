<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for data fetchers.
 *
 * @see \Drupal\metrc_migrate\Annotation\Authentication
 * @see \Drupal\metrc_migrate\AuthenticationPluginBase
 * @see \Drupal\metrc_migrate\AuthenticationPluginInterface
 * @see plugin_api
 */
class AuthenticationPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new AuthenticationPluginManager.
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
    parent::__construct('Plugin/metrc_migrate/authentication', $namespaces, $module_handler, 'Drupal\metrc_migrate\AuthenticationPluginInterface', 'Drupal\metrc_migrate\Annotation\Authentication');

    $this->alterInfo('authentication_info');
    $this->setCacheBackend($cache_backend, 'metrc_migrate_plugins_authentication');
  }

}
