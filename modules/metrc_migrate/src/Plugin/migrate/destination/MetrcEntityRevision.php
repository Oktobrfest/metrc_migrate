<?php

namespace Drupal\metrc_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "metrc_entity_revision",
 *   deriver = "Drupal\metrc_migrate\Plugin\Derivative\MetrcMigrateEntityRevision"
 *  * )
 */
class MetrcEntityRevision extends MetrcEntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, AccountSwitcherInterface $account_switcher) {
    $plugin_definition += [
      'label' => new TranslatableMarkup('@entity_type revisions', ['@entity_type' => $storage->getEntityType()->getSingularLabel()]),
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_field_manager, $field_type_manager, $account_switcher);
  }

  /**
   * Gets the entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity or false if it can not be created.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $revision_id = $old_destination_id_values ?
      reset($old_destination_id_values) :
      $row->getDestinationProperty($this->getKey('revision'));
    if (!empty($revision_id) && ($entity = $this->storage->loadRevision($revision_id))) {
      $entity->setNewRevision(FALSE);
    }
    else {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      $entity = $this->storage->load($entity_id);

      // If we fail to load the original entity something is wrong and we need
      // to return immediately.
      if (!$entity) {
        return FALSE;
      }

      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(TRUE);
    }
    // We need to update the entity, so that the destination row IDs are
    // correct.
    $entity = $this->updateEntity($entity, $row);
    $entity->isDefaultRevision(TRUE);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    $entity->save();
    return [$entity->getRevisionId()];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];

    $revision_key = $this->getKey('revision');
    if (!$revision_key) {
      throw new MigrateException(sprintf('The "%s" entity type does not support revisions.', $this->storage->getEntityTypeId()));
    }
    $ids[$revision_key] = $this->getDefinitionFromEntity($revision_key);

    if ($this->isTranslationDestination()) {
      $langcode_key = $this->getKey('langcode');
      if (!$langcode_key) {
        throw new MigrateException(sprintf('The "%s" entity type does not support translations.', $this->storage->getEntityTypeId()));
      }
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestId() {
    $values = $this->storage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->sort($this->getKey('revision'), 'DESC')
      ->range(0, 1)
      ->execute();
    // The array keys are the revision IDs.
    // The array contains only one entry, so we can use key().
    return (int) key($values);
  }

}
