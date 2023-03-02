<?php

namespace Drupal\metrc_views\Plugin\MetrcBaseTableEndpoint;

use Drupal\metrc_views\MetrcBaseTableEndpointBase;

/**
 * metrc profile endpoint.
 *
 * @MetrcBaseTableEndpoint(
 *   id = "harvest",
 *   name = @Translation("Metrc Harvest"),
 *   description = @Translation("Returns a harvest"),
 *   response_key = "Id"
 * )
 */
class Harvest extends MetrcBaseTableEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function getRowWithBasicAuth(string $encodedKey) {
    if ($data = $this->metrcClient->getResourceOwner($access_token)) {
      $data = $data->toArray();
      $data = $this->filterArrayByPath($data, array_keys($this->getFields()));

      // Adjust avatar and avatar150
      $data['avatar'] = [
        'avatar' => $data['avatar'],
        'avatar150' => $data['avatar150'],
      ];
      unset($data['avatar150']);

      // Change memberSince to timestamp
      $data['memberSince'] = strtotime($data['memberSince']);

      return $data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      'Id' => [
        'title' => $this->t('ID'),
        'field' => $this->integer,
      ],
      'Name' => [
        'title' => $this->t('Name'),
        'field' => $this->standard,
      ],
      'HarvestType' => [
        'title' => $this->t('Harvest Type'),
        'field' => $this->standard,
      ],
      'GrowthPhase' => [
        'title' => $this->t('Growth Phase'),
        'field' => $this->standard,
      ],
      'PlantBatchId' => [
        'title' => $this->t('CBD Levels'),
        'field' => $this->integer,
      ],
      'StrainId' => [
        'title' => $this->t('Strain Id'),
        'field' => $this->integer,
      ],
      'LocationId' => [
        'title' => $this->t('Location ID'),
        'field' => $this->integer,
      ],
      'HarvestId' => [
        'title' => $this->t('Harvest ID'),
        'field' => $this->integer,
      ],
      'IsOnHold' => [
        'title' => $this->t('Is On Hold'),
        'field' => $this->boolean,
      ],
      'PlantedDate' => [
        'title' => $this->t('Planted Date'),
        'field' => $this->standard,
      ],
      'VegetativeDate' => [
        'title' => $this->t('Planted Date'),
        'field' => $this->standard,
      ],
      'FloweringDate' => [
        'title' => $this->t('Planted Date'),
        'field' => $this->standard,
      ],
      'HarbestDate' => [
        'title' => $this->t('Planted Date'),
        'field' => $this->standard,
      ],
      'DestroyedDate' => [
        'title' => $this->t('Destroyed Date'),
        'field' => $this->standard,
      ],
      'DestroyedNote' => [
        'title' => $this->t('Destroyed Note'),
        'field' => $this->standard,
      ],
      'LastModified' => [
        'title' => $this->t('Last Modified Date'),
        'field' => $this->standard,
      ],
    ];
  }
}