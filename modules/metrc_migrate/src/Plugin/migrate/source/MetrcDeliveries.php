<?php

namespace Drupal\metrc_migrate\Plugin\migrate\source;

use DateTime;
use Drupal\Component\Datetime\Time;
use Drupal\dblog\Plugin\views\wizard\Watchdog;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\SourcePluginExtension;
use Drupal\migrate\Row;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\mysql\Driver\Database\mysql\Schema as MysqlSchema;


/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "metrc_deliveries"
 * )  
 */

class MetrcDeliveries extends SourcePluginExtension
{
  /**
   * @var array
   */
  protected $configuration = [];

  /**
   * @var MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->migration = $migration;
    // Get configs
    $siteConfig = \Drupal::config('metrc.application_settings');
    $configuration['license'] = $siteConfig->get('license_numbers');
    $configuration['base_url'] = $siteConfig->get('base_url');
    $configuration['urls'] = [$configuration['base_url'] .
      $configuration['path']];
    $this->configuration = $configuration;

    $urls = [];
    if (array_key_exists('appends', $configuration)) {
      switch ($configuration['appends'][0]) {
        case 'deliveries':
          $transfers = $this->deliveriesTransfers();
          break;
        case 'packages':
          $transfers = $this->deliveredPackages();
          break;
      }
    }
    // make URL from transferIDs
    foreach ($transfers as $transfer) {
      $urls[] =  $configuration['urls'][0] . '/' . $transfer . '/';
    }

    if (array_key_exists('appends', $configuration) && $configuration['appends']) {
      foreach ($urls as $delta => $url) {
        $urls[$delta] .= $this->configuration['appends'][0];
      }
    };

    $this->sourceUrls = $urls;
    $this->configuration['urls'] = $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row)
  {
    return parent::prepareRow($row);
  }

  /**
   * Add a related transfer to the urls (for deliveries)
   * 
   * @return array
   */
  protected function deliveriesTransfers(): array
  {
    $transfers = [];
    $schema1 = new MysqlSchema(\Drupal::database());
    // first get a list of all the transfer NID numbers. And add that to this->TransferNIDs
    if ($schema1->tableExists('node') && ($schema1->tableExists('migrate_map_outgoing_transfers'))) {
      $results = \Drupal::database()->select('node', 'n')
        ->fields('n', ['nid'])
        ->condition('n.type', 'transfer', '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $transfers[] = $result->nid;
        }
      }
    }
    return $transfers;
  }


  /**
   * Add a related transfer to the urls (for deliveries)
   * 
   * @return array
   */
  protected function deliveredPackages(): array
  {
    $transfers = [];
    $schema1 = new MysqlSchema(\Drupal::database());
    // first get a list of all the transfer NID numbers. And add that to this->TransferNIDs
    if ($schema1->tableExists('node') && ($schema1->tableExists('migrate_map_delivery'))) {
      $results = \Drupal::database()->select('node', 'n')
        ->fields('n', ['nid'])
        ->condition('n.type', 'delivery', '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $transfers[] = $result->nid;
        }
      }
    }
    return $transfers;
  }


  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString()
  {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', $this->sourceUrls);
    return $urls;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   * @return \Drupal\metrc_migrate\DataParserPluginInterface
   *   The data parser plugin.
   */
  public function getDataParserPlugin()
  {
    if (!isset($this->dataParserPlugin)) {
      $this->dataParserPlugin = \Drupal::service('plugin.manager.metrc_migrate.data_parser')->createInstance($this->configuration['data_parser_plugin'], $this->configuration);
    }
    return $this->dataParserPlugin;
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   * @return \Iterator
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator()
  {
    return $this->getDataParserPlugin();
  }
}
