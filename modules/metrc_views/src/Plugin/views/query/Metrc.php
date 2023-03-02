<?php

namespace Drupal\metrc_views\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\metrc\MetrcUserKeyManager;
use Drupal\metrc\MetrcClient;
use Drupal\metrc_views\MetrcBaseTableEndpointInterface;
use Drupal\metrc_views\MetrcBaseTableEndpointPluginManager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * metrc views query plugin which wraps calls to the metrc API in order to
 * expose the results to views.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "metrc",
 *   title = @Translation("Metrc"),
 * 
 *   help = @Translation("Query against the Metrc API.")
 * )
 */
class Metrc extends QueryPluginBase {

  /**
   * metrc client.
   *
   * @var \Drupal\metrc\MetrcClient
   */
  protected $metrcClient;

  /**
   * metrc access token manager for loading access tokens from the database.
   *
   * @var \Drupal\metrc\MetrcUserKeyManager
   */
  protected $metricUserKeyManager;

  /**
   * metrc base table endpoint plugin manager.
   *
   * @var \Drupal\metrc_views\MetrcBaseTableEndpointPluginManager
   */
  protected $metrcBaseTableEndpointPluginManager;

  /**
   * Array of relationships. Each array entry should be a
   * metrcBaseTableEndpoint plugin_id.
   *
   * @var string[]
   */
  protected $relationships;

  /**
   * Collection of filter criteria.
   *
   * @var array
   */
  protected $where;

  /**
   * metrc constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param MetrcClient $metrc_client
   * @param MetrcUserKeyManager $metr
   * @param MetrcBaseTableEndpointPluginManager $metrc_base_table_endpoint_plugin_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MetrcClient $metrc_client, MetrcUserKeyManager $metrc_user_key_manager, MetrcBaseTableEndpointPluginManager $metrc_base_table_endpoint_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->metrcClient = $metrc_client;
    $this->metrcUserKeyManager = $metrc_user_key_manager;
    $this->metrcBaseTableEndpointPluginManager = $metrc_base_table_endpoint_plugin_manager;
    $this->relationships = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('metrc.client'),
      $container->get('metrc.user_key_manager'),
      $container->get('plugin.manager.metrc_base_table_endpoints')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    // Mostly modeled off of \Drupal\views\Plugin\views\query\Sql::build()

    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    // Fill up the $query array with properties that we will use in forming the
    // API request.
    $query = [];

    // Iterate over $this->where to gather up the filtering conditions to pass
    // along to the API. Note that views allows grouping of conditions, as well
    // as group operators. This does not apply to us, as the metrc API has no
    // such concept, nor do we support this concept for filtering connected
    // metrc Drupal users.
    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          // Remove dot from begining of the string.
          $field_name = ltrim($condition['field'], '.');
          $query[$field_name] = $condition['value'];
        }
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    // Set the units according to the setting on the view.
    if (!empty($this->options['accept_lang'])) {
      $this->metrcClient->setAcceptLang($this->options['accept_lang']);
    }

    // Grab data regarding conditions placed on the query.
    $query = $view->build_info['query'];

    // Need the data about the table to know which endpoint to use.
    $views_data = Views::viewsData();
    $base_table = $this->view->storage->get('base_table');
    $base_table_data = $views_data->get($base_table);

    // Here we combine the base_table_endpoint_id of the base table with any
    // added via relatiionships. Since all relationships defined by
    // metrc_views module are bi-directional, we use array_unique to ensure
    // that we only ever hit each endpoint once. Unlike a SQL relationship
    // there is no distinction between a relationship one vertex away and a
    // relationship n verticies away.
    $metrc_endpoint_ids = array_unique(array_merge([$base_table_data['table']['base']['metrc_base_table_endpoint_id']], $this->relationships));
    $index = 0;

    $row = [];
    foreach ($metrc_endpoint_ids as $metrc_endpoint_id) {
      /** @var MetrcBaseTableEndpointInterface $metrc_endpoint */
      $metrc_endpoint = $this->metrcBaseTableEndpointPluginManager->createInstance($metrc_endpoint_id);
      if ($data = $metrc_endpoint->getRowWithBasicAuth('sdfds')) {
        // The index key is very important. Views uses this to look up values
        // for each row. Without it, views won't show any of your result rows.
        $row = array_merge($row, $data);
      }
    }
    // If we got some data back from the API for this user, add defaults and
    // expose as a row to views.
    if (!empty($row)) {
      $row['index'] = $index++;
      $row['uid'] = $uid;
      $view->result[] = new ResultRow($row);
    }
  }

  /**
   * Adds a simple condition to the query. Collect data on the configured filter
   * criteria so that we can appropriately apply it in the query() and execute()
   * methods.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['accept_lang'] = array(
      'default' => NULL,
    );

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['accept_lang'] = [
      '#type' => 'select',
      '#options' => $this->metrcClient->getAcceptLangOptions(),
      '#title' => $this->t('Unit system'),
      '#default_value' => $this->options['accept_lang'],
      '#description' => $this->t('Set the unit system to use for metrc API requests.'),
    ];
  }

  /**
   * Add a relationship. For metrc views query backends, a relationship
   * corresponds to a metrcBaseTableEndpoint plugin_id, which will be used to
   * fetch rows from that endpoint in addition to the base table requested.
   *
   * @param string $endpoint_plugin_id
   *   Plugin id for a metrcBaseTableEndpoint plugin.
   */
  public function addRelationship($endpoint_plugin_id) {
    $this->relationships[] = $endpoint_plugin_id;
  }

  /**
   * The following methods replicate the interface of Views' default SQL query
   * plugin backend to simplify the Views integration of the metrc
   * API. It's necessary to define these, since many handlers assume they are
   * working against a SQL query plugin backend. There is an issue that details
   * this lack of an enforced contract as a bug
   * (https://www.drupal.org/node/2484565). Sigh.
   *
   * @see https://www.drupal.org/node/2484565
   */

  /**
   * Ensures a table exists in the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the metrc API. Since the metrc API has no
   * concept of "tables", this method implementation does nothing. If you are
   * writing metrc API-specific Views code, there is therefore no reason at all
   * to call this method.
   * See https://www.drupal.org/node/2484565 for more information.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * Adds a field to the table. In our case, the Fitibt API has no
   * notion of limiting the fields that come back, so tracking a list
   * of fields to fetch is irrellevant for us. Hence this function body is more
   * or less empty and it serves only to satisfy handlers that may assume an
   * addField method is present b/c they were written against Views' default SQL
   * backend.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the metrc API.
   *
   * @param string $table
   *   NULL in most cases, we could probably remove this altogether.
   * @param string $field
   *   The name of the metric/dimension/field to add.
   * @param string $alias
   *   Probably could get rid of this too.
   * @param array $params
   *   Probably could get rid of this too.
   *
   * @return string
   *   The name that this field can be referred to as.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * End of methods necessary to replicate the interface of Views' default SQL
   * query plugin backend to simplify the Views integration of the metrc API.
   */
}
