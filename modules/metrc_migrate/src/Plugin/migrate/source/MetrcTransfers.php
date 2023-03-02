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
use Drupal\Core\Database\Driver\mysql\Schema;


/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "metrc_transfers"
 * )  
 */

class MetrcTransfers extends SourcePluginExtension
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
      $this->migration = $migration;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    // Get configs
    $siteConfig = \Drupal::config('metrc.application_settings');
    $configuration['license'] = $siteConfig->get('license_numbers');
    $configuration['base_url'] = $siteConfig->get('base_url');
    $configuration['urls'] = [$configuration['base_url'] .
      $configuration['path']];
    $this->configuration = $configuration;

    $urls = [];
    $urls = $this->addByDate();

    if (array_key_exists('statuses', $configuration) && $configuration['statuses']) {
      $urls = $this->addStatuses($urls);
    };

    $urls = $this->addLicense($urls);
    $this->sourceUrls = $urls;
    $this->configuration['urls'] = $urls;

    if (!isset($this->database)) {
      $this->database = \Drupal::database();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row)
  {
    return parent::prepareRow($row);
  }

  /**
   * Add license number to the urls
   * 
   * @param array $urls
   * 
   * @return array
   */
  protected function addLicense(array $urls): array
  {
    foreach ($urls as $key => $url) {
      $delemeter = '&';
      if (!str_contains($url, '?')) {
        $delemeter = '?';
      }
      $urls[$key] .= $delemeter . 'licenseNumber=' .
        $this->configuration['license'];
    }

    return $urls;
  }

  protected function addStatuses(array $urls): array
  {
    $statusUrls = [];
    foreach ($urls as $url) {
      $urlParts = explode("?", $url);
      foreach ($this->configuration['statuses'] as $status) {
        $urlParts[0] .= "/$status";
        $statusUrls[] = implode("?", $urlParts);
      }
    }

    return $statusUrls;
  }

  /**
   * Get the date to start querying 
   * 
   * @param int|null $startTime
   * @param array $configuration
   * 
   * @return DateTime
   */
  protected function getStartDate(?int $startTime): DateTime
  {
      $start = new DateTime('now');
    return $start->modify('-2 days'); 
    }

  /**
   * Build an array of URLs to query
   * 
   * @return array of urls
   */
  protected function addByDate(): array
  {
    $urls = [];

    // Get last run time
    $startTime = \Drupal::keyValue('migrate_last_imported')
      ->get($this->migration->id(), NULL);

    // Get dates to iterate through
    $start = $this->getStartDate($startTime);
    $current = clone $start;
    $current->modify('+1 day');
    $end = new DateTime();
    $end->modify('+1 day');
    $daylightsav = date_create("2021-11-07");
    $daylightsav->format("Y-m-d");
    $daylightsav2020 = date_create("2020-11-01");
    $daylightsav2020->format("Y-m-d");
    $compstartdate = $start;
    $compstartdate->setTime(0, 0, 0, 0);
    $compstartdate->format("Y-m-d");
    // Create the urls 
    for ($current; $current <= $end; $current->modify('+1 day')) {
      if ($compstartdate == $daylightsav) {
        $urls[] = $this->configuration['base_url'] . $this->configuration['path'] .
          '?lastModifiedStart=' . $start->format("Y-m-d") .
          '&lastModifiedEnd=2021-11-07T06:30:00Z';
      } else if ($compstartdate == $daylightsav2020) {
        $urls[] = $this->configuration['base_url'] . $this->configuration['path'] .
          '?lastModifiedStart=' . $start->format("Y-m-d") .
          '&lastModifiedEnd=2020-11-01T06:30:00Z';
      } else {
        $urls[] = $this->configuration['base_url'] . $this->configuration['path'] .
          '?lastModifiedStart=' . $start->format("Y-m-d") .
          '&lastModifiedEnd=' . $current->format("Y-m-d");
      };
      $start->modify('+1 day');
    }
    return $urls;
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
   * @return \Drupal\migrate_plus\DataParserPluginInterface
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
