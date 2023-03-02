<?php

namespace Drupal\metrc_migrate\Plugin\migrate\source;

use DateTime;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\SourcePluginExtension;
use Drupal\migrate\Row;
use Drupal\mysql\Driver\Database\mysql\Schema;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query;
use \Drupal\node\Entity\Node;
use ArrayIterator;
use ArrayObject;

/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "metrc_url2"
 * )  
 */
class MetrcPackageHarvUrl extends SourcePluginExtension
{

  /**
   * The database connection for the map/message tables on the destination.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The source URLs to retrieve.
   *
   * @var array
   */
  protected $sourceUrls = [];

  /**
   * @var array
   */
  protected $configuration = [];

  /**
   * @var MigrationInterface
   */
  protected $migration;

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected $dataParserPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration)
  {
    $this->migration = $migration;
    // Get configs
    $siteConfig = \Drupal::config('metrc.application_settings');
    $configuration['license'] = $siteConfig->get('license_numbers');
    $configuration['base_url'] = $siteConfig->get('base_url');
    $configuration['urls'] = [$configuration['base_url'] .
      $configuration['path']];
    $this->configuration = $configuration;
    $urls = [];
    if (array_key_exists('by_type', $configuration)) {
      switch ($configuration['by_type']) {
        case 'lab_test':
          $urls = $this->labTest();
          break;
        case 'date':
          $start = $this->getStartDate();
          $urls = $this->addByDate($start);
          break;
        case 'default':
          $urls = $configuration['urls'];
        case 'custom':
          $this->setExistingUnPublished();
          $start = new DateTime();
          $start->setDate(2021, 1, 1);
          $urls = $this->addByDate($start);
          break;
        case 'wholesale':
          $start = new DateTime();
          $start->setDate(2021, 1, 1);
          $urls = $this->wholesale($start);
          break;
      }
    }

    if (array_key_exists('statuses', $configuration) && $configuration['statuses']) {
      $urls = $this->addStatuses($urls);
    };

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->migration->setTrackLastImported(True);
    $urls = $this->addLicense($urls);
    $this->sourceUrls = $urls;
    $this->configuration['urls'] = $urls;

    if (!isset($this->database)) {
      $this->database = \Drupal::database();
    }
  }

  public function wholesale($start) {
    $schema1 = new Schema(\Drupal::database());
    // first get a list of all the delivery Ids in last (timeperiod var). And add that to this->ids.
    if ($schema1->tableExists('node')) {
        $deliveries = \Drupal::database()->select('node', 'd')
        ->fields('d', ['nid'])
        ->condition('d.type',  "delivery", '=')
        ->execute()
        ->fetchAll();
    };

    foreach ($deliveries as $delivery) {
      if (isset($delivery)) {
        $urls[] = $this->configuration['base_url'] . $this->configuration['path'] . '/' .$delivery->nid . "/packages/wholesale";
      };
    }
    return $urls;
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
        $urlArray = $urlParts;
        $urlArray[0] .= "/$status";
        $statusUrls[] = implode("?", $urlArray);
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
  protected function getStartDate(): DateTime
  {
    // Get last run time
    $start_unix = \Drupal::keyValue('migrate_last_imported')
      ->get($this->migration->id(), NULL);
    $start_unix = microtime(TRUE);
    $twoDaysAgo = new DateTime('now');
    $twoDaysAgo->modify('-2 days');
    $startTime = date("Y-m-d", $start_unix);
    $twoDaysAgo->format("Y-m-d");
    // use last runtime if greater than 2 days ago
    if ($startTime == null || ($startTime < $twoDaysAgo)) {
      $start = $twoDaysAgo;
    } else {
      $start = $startTime;
    };

    return $start;
  }

  /**
   * Build an array of URLs to query
   * 
   * @return array of urls
   */
  protected function addByDate(DateTime $start): array
  {
    $urls = [];
    // set end time (the end of current day)
    $end = new DateTime();
    $end->format("Y-m-d");
    // Daylight savings fix for metrc systems 24hr limit requirement
    $daylightsav = [];
    $daylightsav[0] = date_create("2020-11-01");
    $daylightsav[0]->format("Y-m-d");
    $daylightsav[1] = date_create("2021-11-07");
    $daylightsav[1]->format("Y-m-d");
    $daylightsav[2] = date_create("2022-11-06");
    $daylightsav[2]->format("Y-m-d");
    $daylightsav[3] = date_create("2023-11-05");
    $daylightsav[3]->format("Y-m-d");
    $daylightsav[4] = date_create("2024-11-03");
    $daylightsav[4]->format("Y-m-d");
    $daylightsav[5] = date_create("2024-11-02");
    $daylightsav[5]->format("Y-m-d");

    // Create the url dates
    for ($start->format("Y-m-d"); $start < $end; $start->modify('+1 day')) {
      $start_copy = clone $start;
      $stop = $start_copy->modify('+1 day');
      // check daylight savings
      $current_timeless = clone $start;
      $current_timeless = $start->setTime(
        $hour = 0,
        $minute = 0,
        $second = 0,
        $microsecond = 0
      );
      for ($yr = 0; $yr < 6; $yr++) {
        if ($current_timeless == $daylightsav[$yr]) {
          $stop->modify('-2 hours');
        };
      };
      // set URLs
      $urls[] = $this->configuration['base_url'] . $this->configuration['path'] .
        '?lastModifiedStart=' . $start->format("Y-m-d") .
        '&lastModifiedEnd=' . $stop->format("Y-m-d");
    };
    return $urls;
  }

  /**
   *   Note it only works on Active packages. Returns lab test urls.
   * 
   * @return array
   */
  protected function labTest(): array
  {
    $packages = [];
  
    $schema1 = new Schema(\Drupal::database());
    // first get a list of all the harvest tid numbers. And add that to this->ids.
    if ($schema1->tableExists('taxonomy_term_revision__field_harvest_state') && ($schema1->tableExists('migrate_map_lab_test'))) {
      $packages = [];
      $harvests = \Drupal::database()->select('taxonomy_term_revision__field_harvest_state', 'hs')
        ->fields('hs', ['entity_id'])
        ->condition('hs.field_harvest_state_value', '1', '=')
        ->execute()
        ->fetchAll();
      if (!empty($harvests)) {
        foreach ($harvests as $delta => $harvest) {
          $harv_pkg =  \Drupal::entityTypeManager()->getStorage('node')->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', 'package')
            ->condition('field_sourceharvestnames', $harvest->entity_id, '=')
            ->condition('field_package_state', 'active', '=')
            ->condition('status', Node::PUBLISHED)
            ->latestRevision()
            ->range(0, 1)
            ->execute();

          if (isset($harv_pkg)) {
            $pkg_array = new ArrayObject($harv_pkg);
            $pkg_itterator = $pkg_array->GetIterator();
            $packages[] = $pkg_itterator->current();
          };
        };
      }
    };

    foreach ($packages as $packageId) {
      if (isset($packageId)) {
        $urls[] = $this->configuration['base_url'] . $this->configuration['path'] .
          "?packageId=$packageId";
      };
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
