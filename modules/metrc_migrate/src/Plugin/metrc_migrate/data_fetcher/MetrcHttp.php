<?php

namespace Drupal\metrc_migrate\Plugin\metrc_migrate\data_fetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\metrc_migrate\DataFetcherPluginBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\metrc_migrate\Plugin\metrc_migrate\authentication\MetrcBasic;
use Drupal\metrc_migrate\Plugin\metrc_migrate\authentication;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url 
 *   data_fetcher_plugin: metrc_http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "metrc_http",
 *   title = @Translation("Metrc HTTP")
 * )
 */
class MetrcHttp extends DataFetcherPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The request headers.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * The data retrieval client.
   *
   * @var \Drupal\metrc_migrate\AuthenticationPluginInterface
   */
  protected $authenticationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = \Drupal::httpClient();

    // Ensure there is a 'headers' key in the configuration.
    $configuration += ['headers' => []];
    $this->setRequestHeaders($configuration['headers']);
  }

  /**
   * Returns the initialized authentication plugin.
   *
   * @return \Drupal\metrc_migrate\AuthenticationPluginInterface
   *   The authentication plugin.
   */
  public function getAuthenticationPlugin() {
    if (!isset($this->authenticationPlugin)) {
      $this->authenticationPlugin = \Drupal::service('plugin.manager.metrc_migrate.authentication')->createInstance($this->configuration['authentication']['plugin'], $this->configuration['authentication']);
    }
    return $this->authenticationPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestHeaders(array $headers) {
    $this->headers = $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestHeaders() {
    return !empty($this->headers) ? $this->headers : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url) {
    try {
      $options = ['headers' => $this->getRequestHeaders()];
      if (!empty($this->configuration['authentication'])) {
        $options = array_merge($options, $this->getAuthenticationPlugin()->getAuthenticationOptions());
      }
      $response = $this->httpClient->get($url, $options);
      if (empty($response)) {
        throw new MigrateException('No response at ' . $url . '.');
      }
    }
    catch (RequestException $e) {
      throw new MigrateException('Error message: ' . $e->getMessage() . ' at ' . $url . '.');
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent($url) {
    $response = $this->getResponse($url);
    return $response->getBody();
  }

}
