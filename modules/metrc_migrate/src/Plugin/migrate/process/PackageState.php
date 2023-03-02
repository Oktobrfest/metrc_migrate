<?php

namespace Drupal\metrc_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Gives us a chance to set per field defaults.
 *
 * @MigrateProcessPlugin(
 *   id = "package_state"
 * )
 */
class PackageState extends ProcessPluginBase
{

    /**
     * {@inheritdoc}
     */
    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
    {
       $statuses = $row->getSourceProperty('statuses');
        foreach ($statuses as $status){
            if (in_array($value, $statuses)){
                return $value;
                }
            }
            // if the status provided isn't one of the status options set in the config YML file then skip it!
        throw new MigrateSkipRowException(" Skipped unsupported status! ");
    }
}
