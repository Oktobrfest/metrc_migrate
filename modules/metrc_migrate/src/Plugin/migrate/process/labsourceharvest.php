<?php

namespace Drupal\metrc_migrate\Plugin\migrate\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "labsourceharvest",
 *   handle_multiples = TRUE
 * )
 */
class labsourceharvest extends ProcessPluginBase implements MigrateProcessInterface
{
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
      return $value;
   
  }

}