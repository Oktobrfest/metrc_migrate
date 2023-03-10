<?php

namespace Drupal\metrc_migrate;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base data parser implementation.
 *
 * @see \Drupal\metrc_migrate\Annotation\DataParser
 * @see \Drupal\metrc_migrate\DataParserPluginInterface
 * @see \Drupal\metrc_migrate\DataParserPluginManager
 * @see plugin_api
 */
abstract class DataParserPluginBase extends PluginBase implements DataParserPluginInterface {

  /**
   * List of source urls.
   *
   * @var string[]
   */
  protected $urls;

  /**
   * Index of the currently-open url.
   *
   * @var int
   */
  protected $activeUrl;

  /**
   * String indicating how to select an item's data from the source.
   *
   * @var string
   */
  protected $itemSelector;

  /**
   * Current item when iterating.
   *
   * @var mixed
   */
  protected $currentItem = NULL;

  /**
   * Value of the ID for the current item when iterating.
   *
   * @var string
   */
  protected $currentId = NULL;

  /**
   * The data retrieval client.
   *
   * @var \Drupal\metrc_migrate\DataFetcherPluginInterface
   */
  protected $dataFetcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->urls = $configuration['urls'];
    $this->itemSelector = $configuration['item_selector'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Returns the initialized data fetcher plugin. DT FIX-my Change to their original
   *
   * @return \Drupal\metrc_migrate\DataFetcherPluginInterface
   *   The data fetcher plugin.
   */
  public function getDataFetcherPlugin() {
    if (!isset($this->dataFetcher)) {
      $this->dataFetcher = \Drupal::service('plugin.manager.metrc_migrate.data_fetcher')->createInstance($this->configuration['data_fetcher_plugin'], $this->configuration);
    }
    return $this->dataFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->activeUrl = NULL;
    $this->next();
  }

  /**
   * Implementation of Iterator::next().
   */
  public function next() {
    $this->currentItem = $this->currentId = NULL;
    if (is_null($this->activeUrl)) {
      if (!$this->nextSource()) {
        // No data to import.
        return;
      }
    }
    // At this point, we have a valid open source url, try to fetch a row from
    // it.
    $this->fetchNextRow();
    // If there was no valid row there, try the next url (if any).
    if (is_null($this->currentItem)) {
      while ($this->nextSource()) {
        $this->fetchNextRow();
        if ($this->valid()) {
          break;
        }
      }
    }
    if ($this->valid()) {
      foreach ($this->configuration['ids'] as $id_field_name => $id_info) {
        $this->currentId[$id_field_name] = $this->currentItem[$id_field_name];
      }
    }
  }

  /**
   * Opens the specified URL.
   *
   * @param string $url
   *   URL to open.
   *
   * @return bool
   *   TRUE if the URL was successfully opened, FALSE otherwise.
   */
  abstract protected function openSourceUrl($url);

  /**
   * Retrieves the next row of data. populating currentItem.
   *
   * Retrieves from the open source URL.
   */
  abstract protected function fetchNextRow();

  /**
   * Advances the data parser to the next source url.
   *
   * @return bool
   *   TRUE if a valid source URL was opened
   */
  protected function nextSource() {
    if (empty($this->urls)) {
      return FALSE;
    }

    while ($this->activeUrl === NULL || (count($this->urls) - 1) > $this->activeUrl) {
      if (is_null($this->activeUrl)) {
        $this->activeUrl = 0;
      }
      else {
        // Increment the activeUrl so we try to load the next source.
        ++$this->activeUrl;
        if ($this->activeUrl >= count($this->urls)) {
          return FALSE;
        }
      }

      if ($this->openSourceUrl($this->urls[$this->activeUrl])) {
        // We have a valid source.
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->currentItem;
  }

  /**
   * {@inheritdoc}
   */
  public function currentUrl(): ?string {
    $index = $this->activeUrl ?: \array_key_first($this->urls);

    return $this->urls[$index] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->currentId;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return !empty($this->currentItem);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $count = 0;
    foreach ($this as $item) {
      $count++;
    }
    return $count;
  }

  /**
   * Return the selectors used to populate each configured field.
   *
   * @return string[]
   *   Array of selectors, keyed by field name.
   */
  protected function fieldSelectors() {
    $fields = [];
    foreach ($this->configuration['fields'] as $field_info) {
      if (isset($field_info['selector'])) {
        $fields[$field_info['name']] = $field_info['selector'];
      }
    }
    return $fields;
  }

}
