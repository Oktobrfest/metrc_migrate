<?php

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form is specifically for configuring process pipelines.
 */
class MigrationDynamicExecuteForm extends FormBase {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MigrationExecuteForm object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager, RouteMatchInterface $route_match) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migration_execute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $form ?: [];

    /** @var \Drupal\migrate_plus\Entity\MigrationInterface $migration */
    $migration = $this->getRouteMatch()->getParameter('migration');
    $form['#title'] = $this->t('Execute migration %label', ['%label' => $migration->label()]);

    $form = $this->buildFormOperations($form, $form_state);
    $form = $this->buildFormOptions($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
    ];

    return $form;
  }

  /**
   * Build the operation form field.
   *
   * @param array $form
   *   The execution form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The execution form updated with the operations.
   */
  protected function buildFormOperations(array $form, FormStateInterface $form_state) {
    // Build the migration execution form.
    $options = [
      'lab_test' => $this->t('Get labs for packages'),
    ];

    $form['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#description' => $this->t('Choose an operation to run.'),
      '#options' => $options,
      '#default_value' => 'import',
      '#required' => TRUE,
      'lab_test' => [
        '#description' => $this->t('Imports all lab results for each package'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $operation = $form_state->getValue('operation');

    if ($form_state->getValue('limit')) {
      $limit = $form_state->getValue('limit');
    }
    else {
      $limit = 0;
    }

    if ($form_state->getValue('update')) {
      $update = $form_state->getValue('update');
    }
    else {
      $update = 0;
    }
    if ($form_state->getValue('force')) {
      $force = $form_state->getValue('force');
    }
    else {
      $force = 0;
    }

    $migration = $this->getRouteMatch()->getParameter('migration');
    if ($migration) {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration_plugin */
      $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
      $migrateMessage = new MigrateMessage();

      switch ($operation) {
        case 'import':

          $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
          ];

          $executable = new MigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
          $executable->batchImport();

          break;

        case 'rollback':

          $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
          ];

          $executable = new MigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
          $executable->rollback();

          break;

        case 'stop':

          $migration_plugin->interruptMigration(MigrationInterface::RESULT_STOPPED);

          break;

        case 'reset':

          $migration_plugin->setStatus(MigrationInterface::STATUS_IDLE);

          break;

      }
    }
  }

}
