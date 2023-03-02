<?php

namespace Drupal\metrc;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory to build the client
 */
class MetrcClientFactory {

  /**
   * Create an instance of MetrcClient.
   *
   * @param ConfigFactoryInterface $config_factory
   *
   * @return MetrcClient
   */
  public static function create(ConfigFactoryInterface $config_factory) {
		$config = $config_factory->get('metrc.application_settings');
		
		$options = [
			'vendorKey' => $config->get('vendor_key'),
			'userKey' => $config->get('user_key')
		];
		
		return new MetrcClient($options);
	}
}

