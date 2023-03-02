<?php

namespace Drupal\metrc_migrate;

/**
 * Defines an interface for data parsers.
 *
 * @see \Drupal\metrc_migrate\Annotation\DataParser
 * @see \Drupal\metrc_migrate\DataParserPluginBase
 * @see \Drupal\metrc_migrate\DataParserPluginManager
 * @see plugin_api
 */
interface DataParserPluginInterface extends \Iterator, \Countable {

  /**
   * Returns current source URL.
   *
   * @return string|null
   *   The URL currently parsed on success, otherwise NULL.
   */
  public function currentUrl(): ?string;

}
