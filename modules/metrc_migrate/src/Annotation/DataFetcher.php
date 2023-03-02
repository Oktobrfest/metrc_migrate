<?php

namespace Drupal\metrc_migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data fetcher annotation object.
 *
 * Plugin namespace: Plugin\metrc_migrate\data_fetcher.
 *
 * @see \Drupal\metrc_migrate\DataFetcherPluginBase
 * @see \Drupal\metrc_migrate\DataFetcherPluginInterface
 * @see \Drupal\metrc_migrate\DataFetcherPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DataFetcher extends Plugin {

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
