<?php

namespace Drupal\metrc_migrate\Plugin\migrate\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * To do custom value transformations use the following:
 *
 * @code
 * field_text:
 *   plugin: concat
 *     source:
 *       - foo
 *       - bar
 * @endcode
 *
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "concat2",
 *   handle_multiples = TRUE
 * )
 */
class concat2 extends ProcessPluginBase implements MigrateProcessInterface
{
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $delimiter = $this->configuration['delimiter'] ?? '';
      return implode($delimiter, $value);
    }
    else {
      throw new MigrateException(sprintf('%s is not an array', var_export($value, TRUE)));
    }
  }

}