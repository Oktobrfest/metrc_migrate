<?php

namespace Drupal\metrc_migrate\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_plus\Entity\MigrationGroupInterface;
use Drupal\migrate_plus\Entity\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for migrate_tools migration view routes.
 */
class MigrationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new MigrationController object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager, CurrentRouteMatch $currentRouteMatch) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->currentRouteMatch = $currentRouteMatch;
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
   * Run a historical migration.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. Else no return value.
   */
  public function historical(MigrationGroupInterface $migration_group, MigrationInterface $migration) {
    $migrateMessage = new MigrateMessage();
    $options = [];

    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
    $executable = new MigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
    $executable->batchImport();

    $route_parameters = [
      'migration_group' => $migration_group,
      'migration' => $migration->id(),
    ];
    $blahj  = batch_process(Url::fromRoute('entity.migration.process', $route_parameters));
  }
}
