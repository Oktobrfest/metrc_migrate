<?php

namespace Drupal\metrc\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\metrc\MetrcAccessTokenManager;
use Drupal\metrc\MetrcClient;
use Drupal\user\PrivateTempStoreFactory;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Authorization extends ControllerBase
{

  /**
   * metrc client.
   *
   * @var \Drupal\metrc\MetrcClient
   */
  protected $metrcClient;

  /**
   * metrc Access Token Manager.
   *
   * @var \Drupal\metrc\MetrcAccessTokenManager
   */
  protected $metrcAccessTokenManager;

  /**
   * Session storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Authorization constructor.
   *
   * @param metrcClient $metrc_client
   * @param metrcAccessTokenManager $metrc_access_token_manager
   * @param PrivateTempStoreFactory $private_temp_store_factory
   * @param Request $request
   * @param AccountInterface $current_user
   */
  public function __construct(metrcClient $metrc_client, metrcAccessTokenManager $metrc_access_token_manager, PrivateTempStoreFactory $private_temp_store_factory, Request $request, AccountInterface $current_user)
  {
    $this->metrcClient = $metrc_client;
    $this->metrcAccessTokenManager = $metrc_access_token_manager;
    $this->tempStore = $private_temp_store_factory->get('metrc');
    $this->request = $request;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('metrc.client'),
      $container->get('metrc.access_token_manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
    );
  }

  /**
   * Receive the authorization code from a Fitibit Authorization Code Flow
   * redirect, and request an access token from metrc.
   */
  public function authorize()
  {

    try {
      // Try to get an access token using the authorization code grant.
      $access_token = $this->metrcClient->getAccessToken(
        'authorization_code',
        [
          'code' => $this->request->get('code')
        ]
      );

      // Save access token details.
      $this->metrcAccessTokenManager->save($this->currentUser->id(), [
        'access_token' => $access_token->getToken(),
        'expires' => $access_token->getExpires(),
        'refresh_token' => $access_token->getRefreshToken(),
        'user_id' => $access_token->getResourceOwnerId(),
      ]);

      drupal_set_message('You\'re metrc account is now connected.');

      return new RedirectResponse(Url::fromRoute('metrc.user_settings', ['user' => $this->currentUser->id()])->toString());
    } catch (IdentityProviderException $e) {
      watchdog_exception('metrc', $e);
    }
  }

  /**
   * Check the state key from metrc to protect against CSRF.
   */
  public function checkAccess()
  {
    return AccessResult::allowedIf($this->tempStore->get('state') == $this->request->get('state'));
  }
}
