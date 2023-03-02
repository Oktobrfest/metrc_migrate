<?php

namespace Drupal\metrc_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class Badges
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("metrc_badges")
 */
class Badges extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw_badges = $this->getValue($values);
    if ($raw_badges) {
      foreach($raw_badges as $raw_badge) {
        $badges[] = [
          '#theme' => 'metrc_badge',
          '#raw_badge' => $raw_badge,
          '#image' => $raw_badge['image100px'],
        ];
      }
      return [
        '#theme' => 'item_list',
        '#items' => $badges,
      ];
    }
  }
}
