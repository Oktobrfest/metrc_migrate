<?php

namespace Drupal\metrc_migrate\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class MetrcControllerAlterSubscriber.
 */
class MetrcControllerAlterSubscriber implements EventSubscriberInterface {

  /**
   * Alters the controller output.
   */
  public function onView(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    if ($route == 'entity.migration.overview_process') {
      $build = $event->getControllerResult();
      if (is_array($build)) {
        // alter controller build array
        $build['process']['historical'] = [
			'#type' => 'link',
			'#title' => $this->t('Run Historical'),
			'#url' => Url::fromRoute('entity.migration.process.historical', ['migration_group' => $migration_group->id(), 'migration' => $migration->id()]),
		  ];

        $event->setControllerResult($build);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // priority > 0 so that it runs before the controller output
    // is rendered by \Drupal\Core\EventSubscriber\MainContentViewSubscriber
    $events[KernelEvents::VIEW][] = ['onView', 50];
    return $events;
  }

}