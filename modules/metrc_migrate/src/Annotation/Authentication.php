<?php

namespace Drupal\metrc_migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data fetcher annotation object.
 *
 * Plugin namespace: Plugin\metrc_migrate\authentication.
 *
 * @see \Drupal\metrc_migrate\AuthenticationPluginBase
 * @see \Drupal\metrc_migrate\AuthenticationPluginInterface
 * @see \Drupal\metrc_migrate\AuthenticationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Authentication extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
