<?php

namespace Drupal\metrc_migrate\Plugin\metrc_migrate\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\metrc_migrate\AuthenticationPluginBase;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @Authentication(
 *   id = "metrc_basic",
 *   title = @Translation("Metrc Basic")
 * )
 */
class MetrcBasic extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions() {
    // Get configs
    $siteConfig = \Drupal::config('metrc.application_settings');
    $vendor_key = $siteConfig->get('vendor_key');
    $user_key = $siteConfig->get('user_key');

    return [
      'auth' => [
        $user_key,
        $vendor_key,
      ],
    ];
  }

}
