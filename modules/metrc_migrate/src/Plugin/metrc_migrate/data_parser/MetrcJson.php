<?php

namespace Drupal\metrc_migrate\Plugin\metrc_migrate\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\metrc_migrate\DataParserPluginBase;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "metrc_json",
 *   title = @Translation("Metrc JSON")
 * )
 */
class MetrcJson extends DataParserPluginBase implements ContainerFactoryPluginInterface
{

  /**
   * Iterator over the JSON data.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   *
   * @return array
   *   The selected data to be iterated.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData($url)
  {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    // Convert objects to associative arrays.
    $source_data = json_decode($response, TRUE);

    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($source_data)) {
      $utf8response = utf8_encode($response);
      $source_data = json_decode($utf8response, TRUE);
    }

    // Backwards-compatibility for depth selection.
    if (is_int($this->itemSelector)) {
      return $this->selectByDepth($source_data);
    }

    // injecting transfer or delivery IDs into URL
    // first see whether its packages or deliveries
    // transfers 4=transfers ; 5=v1 & doesn't have deliveries on 5.
    $urlParts = explode('/', $url);

    // packages Source Variables Set here from URL text // includes package & package revisions migrations
    if (str_contains($url, 'packages') && (!str_contains($url, 'delivery'))) {
      $urlPartsLicense =  explode('=', $urlParts[5]);
      // set license number temp variable
      $UrlLicenseNumber = $urlPartsLicense[3];

      // get Package State- Active / Inactive 
      $urlPartsState =  explode('?', $urlParts[5]);
      // set status temp variable
      $PackageState = $urlPartsState[0];

      // Set Active or Inactive to Published or Not
      if ($urlPartsState[0] == 'active') {
        $published = TRUE;
      } else {
        $published = FALSE;
      };

      // set the above variables into the Source Attributes
      foreach ($source_data as $delta => $data_row) {
        $source_data[$delta]["Published"] = $published;
        $source_data[$delta]["PackageState"] = $PackageState;
        $source_data[$delta]["UrlLicenseNumber"] = $UrlLicenseNumber;
      }
    }; // end of packages active & inactive migration stuff

    // Deliveries or Package Deliveries
    if (str_contains($url, '/transfers/v1/')) {
      // Delivery Packages
      if ($urlParts[5] == 'delivery') {
        $urlDelta = 6; // delivery packages
        // set published or not
        foreach ($source_data as $delta => $data_row) {
          if ($data_row["ShipmentPackageState"] == 'Accepted') {
            $source_data[$delta]["Published"] = FALSE;
          } else {
            $source_data[$delta]["Published"] = TRUE;
          };
          if ($data_row["ShipmentPackageState"] == 'Rejected' ||$data_row["ShipmentPackageState"] == 'Returned' ) {
            $source_data[$delta]["Published"] = TRUE;
          };
        }
      }
     // It's an Outgoing Transfer and this variable has no use. Just set delta to whatever exists.
      else {
      $urlDelta = 5; // random. it wont effect things cuz TransferId isn't used in outgoing transfers migration
      };
      
 // set the ID value onto the source variable "TransferID" The actual transferID is the ID field in that migration.
      foreach ($source_data as $delta => $data_row) {
        $source_data[$delta]["TransferId"] = $urlParts[$urlDelta]; // deliveries packages
      }
    };

    // Wholesale package delivery info
    if (str_contains($url, 'wholesale')) {
      foreach ($source_data as $delta => $data_row) {
        $urlDelta = 6; // delivery packages
        $source_data[$delta]["DeliveryId"] = $urlParts[$urlDelta]; // deliveries packages
      }

    };

    // HARVEST Source Variables Set here from URL text
    if (str_contains($url, 'harvests')) {
      $urlPartsLicense =  explode('=', $urlParts[5]);
      // set license number temp variable
      $UrlLicenseNumber = $urlPartsLicense[3];

      // get HARVEST State- Active / Inactive 
      $urlPartsState =  explode('?', $urlParts[5]);
      // set Harvest State/Status boolean variable
      if ($urlPartsState[0] == 'active') {
        $HarvestState = TRUE;
      } else {
        $HarvestState = FALSE;
      };
   
      // set the above variables into the Source Attributes
      foreach ($source_data as $delta => $data_row) {
        $source_data[$delta]["HarvestState"] = $HarvestState;
        $source_data[$delta]["UrlLicenseNumber"] = $UrlLicenseNumber;
      }
    }

    // Strains
   if (str_contains($url, '/strains/v1/')) {
        $licStartPos = strpos($url, "403");
        $UrlLicenseNumber = substr($url, $licStartPos);
        foreach ($source_data as $delta => $data_row) {
         $source_data[$delta]["UrlLicenseNumber"] = $UrlLicenseNumber;
        }
      };

    // Otherwise, we're using xpath-like selectors.
    $selectors = explode('/', trim($this->itemSelector, '/'));
    foreach ($selectors as $selector) {
      if (!empty($selector) || $selector === '0') {
        $source_data = $source_data[$selector];
      }
    }
    return $source_data;
  }

  /**
   * Get the source data for reading.
   *
   * @param array $raw_data
   *   Raw data from the JSON feed.
   *
   * @return array
   *   Selected items at the requested depth of the JSON feed.
   */
  protected function selectByDepth(array $raw_data)
  {
    // Return the results in a recursive iterator that can traverse
    // multidimensional arrays.
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($raw_data),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    $items = [];
    // Backwards-compatibility - an integer item_selector is interpreted as a
    // depth. When there is an array of items at the expected depth, pull that
    // array out as a distinct item.
    $identifierDepth = $this->itemSelector;
    $iterator->rewind();
    while ($iterator->valid()) {
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() == $identifierDepth) {
        $items[] = $item;
      }
      $iterator->next();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url)
  {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow()
  {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $field_data = $current;
        $field_selectors = explode('/', trim($selector, '/'));
        foreach ($field_selectors as $field_selector) {
          if (is_array($field_data) && array_key_exists($field_selector, $field_data)) {
            $field_data = $field_data[$field_selector];
          } else {
            $field_data = '';
          }
        }
        $this->currentItem[$field_name] = $field_data;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }
}
