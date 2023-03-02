<?php

namespace Drupal\metrc_migrate;

/**
 * Defines an interface for data fetchers.
 *
 * @see \Drupal\metrc_migrate\Annotation\Authentication
 * @see \Drupal\metrc_migrate\AuthenticationPluginBase
 * @see \Drupal\metrc_migrate\AuthenticationPluginManager
 * @see plugin_api
 */
interface AuthenticationPluginInterface {

  /**
   * Performs authentication, returning any options to be added to the request.
   *
   * @return array
   *   Options (such as Authentication headers) to be added to the request.
   *
   * @link http://docs.guzzlephp.org/en/latest/request-options.html
   */
  public function getAuthenticationOptions();

}
