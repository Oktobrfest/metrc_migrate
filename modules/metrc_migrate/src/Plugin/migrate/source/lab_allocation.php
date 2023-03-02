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
use Drupal\mysql\Driver\Database\mysql\Schema;

/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "lab_allocation",
 *   source_module = "metrc_migrate"
 * )  
 */

class lab_allocation extends SourcePluginExtension
{

  /**
   * Data obtained from the source plugin configuration.
   *
   * @var array[]
   *   Array of data rows, each one an array of values keyed by field names.
   */
  protected $dataRows = [];

  /**
   * Description of the unique ID fields for this source.
   *
   * @var array[]
   *   Each array member is keyed by a field name, with a value that is an
   *   array with a single member with key 'type' and value a column type such
   *   as 'integer'.
   */
  protected $ids = [];

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
    $this->configuration = $configuration;
    // set the id and datatype from the configuration. tid & Integer in this case
    $this->ids = $configuration['ids'];
    $schema1 = new Schema(\Drupal::database());
    // first get a list of all the harvest tid numbers. And add that to this->ids.
    if ($schema1->tableExists('taxonomy_term_data') && ($schema1->tableExists('migrate_map_lab_test'))) {
      $results = \Drupal::database()->select('taxonomy_term_data', 'ttd')
        ->fields('ttd', ['tid'])
        ->condition('ttd.vid', 'harvests', '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $delta => $result) {
          // loop thru all the harvests gathering the lab test paragraphs for each harvest
          $labResults = \Drupal::database()->select('paragraph_revision__field_harvest', 'prfh')
            ->fields('prfh', ['entity_id', 'revision_id'])
            ->condition('prfh.field_harvest_target_id',  $result->tid, '=')
            ->execute()
            ->fetchAll();
          if (!empty($labResults)) {
              // put THC stuff here
              $THCResults = \Drupal::database()->select('paragraph_revision__field_harvest', 'prfh')
              ->fields('prfh', ['entity_id', 'revision_id'])
              ->condition('prfh.field_harvest_target_id',  $result->tid, '=');
          $THCResults->leftJoin('paragraph_revision__field_test_type_name', 'typ', 'prfh.entity_id = typ.entity_id');
          $THCResults->condition('typ.field_test_type_name_target_id',  1, '=');
          $THCResults->leftJoin('paragraph_revision__field_test_result_level', 'lvl', 'typ.entity_id = lvl.entity_id');
          $THCResults->fields('lvl', ['field_test_result_level_value']);
          $THCResults->leftJoin('paragraph_revision__field_lab_test_result_id', 'id', 'lvl.entity_id = id.entity_id');
          $THCResults->fields('id', ['field_lab_test_result_id_value']);
          $THC = $THCResults->execute()
              ->fetchAll();
          if (!empty($THC)) {
              // do a lookup using the same lab result ID from THC on THCa for the same harvest.
              $THCpercent = [];
                // loop thru each THC test and grab THCa as well
              foreach ($THC as $delta1 => $THCTest) {
              $THCaResults = \Drupal::database()->select('paragraph_revision__field_lab_test_result_id', 'id')
                  ->fields('id', ['entity_id'])
                  ->condition('id.field_lab_test_result_id_value', $THC[$delta1]->field_lab_test_result_id_value, '=');
              $THCaResults->leftJoin('paragraph_revision__field_test_type_name', 'typ', 'id.entity_id = typ.entity_id');
              $THCaResults->condition('typ.field_test_type_name_target_id',  2, '=');
              $THCaResults->leftJoin('paragraph_revision__field_test_result_level', 'lvl', 'typ.entity_id = lvl.entity_id');
              $THCaResults->fields('lvl', ['field_test_result_level_value']);
              $THCa = $THCaResults->execute()
                  ->fetchAll();
              if (!empty($THCa)) {
                  // calc THC percentage and add it to array
                      $THC_lvl = $THC[$delta1]->field_test_result_level_value;
                      $THCa_lvl = $THCa[0]->field_test_result_level_value;
                      $THCpercent[] = ($THCa_lvl * .877) + $THC_lvl;
                  };
              };
                  // sort by descending values
                  rsort($THCpercent);
                  $this->dataRows[$delta]['thc'] = $THCpercent[0];
              };
            // put into dataRows: make an array of harvests each containing a nested array of lab tests with target_id & target_revision_id for each paragraph/labtest
            $this->dataRows[$delta]['tid'] = $result->tid;
            foreach ($labResults as $lab) {
              $this->dataRows[$delta]['field_lab_results'][] = [
                'target_id' => $lab->entity_id,
                'target_revision_id' => $lab->revision_id,
              ];
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields()
  {
    if ($this->count() > 0) {
      $first_row = reset($this->dataRows);
      $field_names = array_keys($first_row);
      return array_combine($field_names, $field_names);
    } else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator()
  {
    return new \ArrayIterator($this->dataRows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString()
  {
    return 'lab_allocation';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds()
  {
    return $this->ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE)
  {
    // We do not want this source plugin to have a cacheable count.
    // @see \Drupal\migrate_cache_counts_test\Plugin\migrate\source\CacheableEmbeddedDataSource
    return count($this->dataRows);
  }
}
