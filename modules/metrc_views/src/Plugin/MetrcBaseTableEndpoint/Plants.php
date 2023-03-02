<?php

namespace Drupal\metrc_views\Plugin\MetrcBaseTableEndpoint;

use Drupal\metrc_views\MetrcBaseTableEndpointBase;

/**
 * Metrc Plants Endpoint
 *
 * @MetrcBaseTableEndpoint(
 *   id = "plants",
 *   name = @Translation("Metrc Plants"),
 *   description = @Translation("Receive a list of plants from Metrc"),
 *   response_key = "Id"
 * )
 */
class Plants extends MetrcBaseTableEndpointBase
{

  /**
   * {@inheritdoc}
   */
  public function getRowWithBasicAuth(string $encodedKey)
  {
    if ($data = file_get_contents('https://www.your_website.com/sites/default/files/js/plants.json')) {
      $contents = utf8_encode($data);
      $results = json_decode($contents); 
      return $this->filterArrayByPath($results, array_keys($this->getFields()));
    }
  }

   /**
   * {@inheritdoc}
   */
  public function getFields()
  {
    return [
      'Id' => [
        'title' => $this->t('ID'),
        'field' => $this->integer,
      ],
      'Label' => [
        'title' => $this->t('Label'),
        'field' => $this->standard,
      ],
      'State' => [
        'title' => $this->t('State'),
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
        'field' => $this->standard,
        'relationship' => [
          'title' => 'Strains',
          'help' => 'Strains grown in the harvest',
          'label' => 'Strains',
          'id' => 'metrc',
          'base' => 'metrc_strains',
        ],
      ],
      'LocationId' => [
        'title' => $this->t('Location ID'),
        'field' => $this->integer,
      ],
      'HarvestId' => [
        'title' => $this->t('Harvest ID'),
        'field' => $this->integer,
        'relationship' => [
          'title' => 'Harvest',
          'help' => 'Harvest Info',
          'label' => 'Harvest',
          'id' => 'metrc',
          'base' => 'metrc_harvest',
        ],
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
      ]
    ];
  }
}
