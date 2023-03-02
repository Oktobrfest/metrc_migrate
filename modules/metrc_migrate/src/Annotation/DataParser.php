<?php

namespace Drupal\metrc_migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data parser annotation object.
 *
 * Plugin namespace: Plugin\metrc_migrate\data_parser.
 *
 * @see \Drupal\metrc_migrate\DataParserPluginBase
 * @see \Drupal\metrc_migrate\DataParserPluginInterface
 * @see \Drupal\metrc_migrate\DataParserPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DataParser extends Plugin {

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
