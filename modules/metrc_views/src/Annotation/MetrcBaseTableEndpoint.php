<?php

namespace Drupal\metrc_views\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a metrc base table endpoint annotation object.
 *
 * Plugin namespace: Plugin\metrc\MetrcBaseTableEndpoint
 *
 * @see \Drupal\metrc\MetrcBaseTableEndpointPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class MetrcBaseTableEndpoint extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the endpoint.
   *
   * @var string
   */
  public $name;

  /**
   * Short description of the endpoint.
   *
   * @var string
   */
  public $description;

  /**
   * Key name of data that is always returned on the response and can be used
   * as a default, representative value from an API response. Key name should
   * have path parts delimited by colons denoting which element of $array is
   * desired.
   *
   * @var string
   */
  public $response_key;
}
