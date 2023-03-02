<?php

/**
 * @file
 *
 * Views related hooks.
 */

/**
 * Implements hook_views_data().
 */
function metrc_views_views_data()
{
  $data = [];

  /** @var \Drupal\metrc_views\MetrcBaseTableEndpointPluginManager $metrc_base_table_endpoint_manager */
  $metrc_base_table_endpoint_manager = \Drupal::service('plugin.manager.metrc_base_table_endpoints');
  $plugin_definitions = $metrc_base_table_endpoint_manager->getDefinitions();

  foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
    /** @var \Drupal\metrc_views\MetrcBaseTableEndpointInterface $base_table_endpoint */
    $base_table_endpoint = $metrc_base_table_endpoint_manager->createInstance($plugin_id);
    $data['metrc_' . $plugin_id]['table']['group'] = $base_table_endpoint->getName();
    $data['metrc_' . $plugin_id]['table']['base'] = [
      'title' => $base_table_endpoint->getName(),
      'query_id' => 'metrc',
      'metrc_base_table_endpoint_id' => $plugin_id,
      'help' => $base_table_endpoint->getDescription(),
      'defaults' => [
        'field' => $base_table_endpoint->getResponseKey(),
      ],
    ];

    // Tack on all the field definitions.
    foreach ($base_table_endpoint->getFields() as $key => $field) {
      if ($field) {
        $data['metrc_' . $plugin_id][$key] = $field;
      }
    }
  }

  return $data;
}
