<?php

namespace Drupal\metrc_views\Plugin\MetrcBaseTableEndpoint;

use Drupal\metrc_views\MetrcBaseTableEndpointBase;

/**
 * metrc strains endpoint.
 *
 * @MetrcBaseTableEndpoint(
 *   id = "strains",
 *   name = @Translation("Metrc Strains"),
 *   description = @Translation("Returns strains."),
 *   response_key = "id"
 * )
 */
class Strains extends MetrcBaseTableEndpointBase
{

  public function getRowWithBasicAuth(string $encodedKey)
  {
    if ($data = file_get_contents('http://your_website.com/sites/default/files/js/strain.json')) {
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
        'field' => $this->standard,
      ],
      'Name' => [
        'title' => $this->t('Name'),
        'field' => $this->standard,
      ],
      'TestingStatus' => [
        'title' => $this->t('Testing Status'),
        'field' => $this->standard,
      ],
      'ThcLevel' => [
        'title' => $this->t('THC Levels'),
        'field' => $this->float,
      ],
      'CbdLevel' => [
        'title' => $this->t('CBD Levels'),
        'field' => $this->float,
      ],
      'IndicaPercentage' => [
        'title' => $this->t('Indica %'),
        'field' => $this->float,
      ],
      'SativaPercentage' => [
        'title' => $this->t('Sativa %'),
        'field' => $this->float,
      ],
      'IsUsed' => [
        'title' => $this->t('Is used'),
        'field' => $this->boolean,
      ],
      'Genetics' => [
        'title' => $this->t('Genetics'),
        'field' => $this->standard,
      ],
    ];
  }
}
