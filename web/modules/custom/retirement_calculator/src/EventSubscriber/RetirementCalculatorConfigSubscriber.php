<?php

namespace Drupal\retirement_calculator\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * This is a trial run of creating an event listener.
 * All it does is that when configuration is save,
 * it prints out some hard coded text.  It also injects 
 * a messenger service here following the proper dependency
 * injection method.
 */


class RetirementCalculatorConfigSubscriber implements EventSubscriberInterface {

    protected $messenger;

    public function __construct($messenger) {
        $this->messenger = $messenger;
    }

    public static function getSubscribedEvents() {
        return [
            ConfigEvents::SAVE => 'configSaved'
        ];
    }

    public function configSaved(ConfigCrudEvent $event) {
        $this->messenger->addStatus('Saved config! Great job!');
    }

}