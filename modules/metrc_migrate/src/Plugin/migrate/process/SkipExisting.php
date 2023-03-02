<?php

namespace Drupal\metrc_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateSkipRowException;

/**
 * This plugin checks if a given entity exists.
 *
 * Example usage with configuration:
 * @code
 *   field_tags:
 *     plugin: skip_existing
 *     source: tid
 *     entity_type: taxonomy_term
 * @endcode
 *
 * @MigrateProcessPlugin(
 *  id = "skip_existing"
 * )
 */
class SkipExisting extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * EntityExists constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param $storage
   *   The entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage($configuration['entity_type'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $value = reset($value);
    }

    $entity = $this->storage->load($value);
    if ($entity instanceof EntityInterface) {
        throw new MigrateSkipRowException(" Skipped Existing ");
            }
    return FALSE;
  }

}
