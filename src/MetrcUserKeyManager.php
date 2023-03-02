<?php

namespace Drupal\metrc;

use Drupal\Core\Database\Connection;

/**
 * CRUD operations for the metrc_user_userKeys table.
 */
class MetrcUserKeyManager {

  const KEY_TABLE = 'metrc_user_keys';

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * metrc client.
   *
   * @var \Drupal\metrc\MetrcClient
   */
  protected $metrcClient;

  /**
   * metrcAccessTokenManager constructor.
   *
   * @param Connection $connection
   * @param metrcClient $metrc_client
   */
  public function __construct(Connection $connection, MetrcClient $metrc_client) {
    $this->connection = $connection;
    $this->metrcClient = $metrc_client;
  }

  /**
   * Load a single user key
   *
   * @param int $uid
   *   Drupal user id.
   *
   * @return UserKey|null
   */
  public function loadUserKey($uid) {
    $userKeys = $this->loadMultipleUserKeys([$uid]);
    return isset($userKeys[$uid]) ? $userKeys[$uid] : NULL;
  }

  /**
   * Get the user key by Drupal uid. Take care for refreshing
   * the token if necessary.
   *
   * @param int[]|NULL $uids
   *   User id's for which to load user keys. Pass NULL to load all access
   *   tokens.
   *
   * @return array $encodedKeys
   *   Array of user keys, keyed by uid.
   */
  public function loadMultipleUserKeys($uids = NULL) {
    $userKeys = [];

    $encodedKeys = [];
    if ($userKeys = $this->loadMultiple($uids)) {

      foreach ($userKeys as $userKey) {
        if ($userKey === "") {
          continue;
        }
        
        $encodedKeys[$userKey->getUid()] = [
          'encodedKey' => base64_encode($userKey->getUserKey() . ":" . $vendorKey),
          'metrcUid' => $userKey->getMetrcUid()
        ];
      }
    }

    return $encodedKeys;
  }

  /**
   * Load an user key by uid.
   *
   * @param int $uid
   *   User id for which to look up an user key.
   * @return array|null
   *   Returns an associative array of the user key details for the given
   *   uid if they exist, otherwise NULL.
   */
  public function load($uid) {
    $userKeys = $this->loadMultiple([$uid]);
    return isset($userKeys[$uid]) ? $userKeys[$uid] : NULL;
  }

  /**
   * Loads one or more user keys.
   *
   * @param array|NULL $uids
   *  An array of uids, or NULL to load all user keys.
   */
  public function loadMultiple($uids = NULL) {
    $query = $this->connection->select(self::KEY_TABLE, 'f')
      ->fields('f');
    if (!empty($uids)) {
      $query->condition('uid', $uids, 'IN');
    }
    return $query->execute()
      ->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
  }

  /**
   * Save user key details for the given uid.
   *
   * @param int $uid
   *   User id for which to save user key details.
   * @param array $data
   *   Associative array of user key details.
   */
  public function save($uid, $data) {
    $this->connection->merge(self::KEY_TABLE)
      ->key(['uid' => $uid])
      ->fields($data)
      ->execute();
  }

  /**
   * Delete user key details for the given uid.
   *
   * @param int $uid
   *   User id for which to delete user key details.
   */
  public function delete($uid) {
    $this->connection->delete(self::KEY_TABLE)
      ->condition('uid', $uid)
      ->execute();
  }
}
