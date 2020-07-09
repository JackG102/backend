<?php

namespace Drupal\retirement_calculator\Event;

use Symfony\Component\EventDispatcher\Event;

class RetirementCalculatorSubmitEvent extends Event {

    const EVENT = 'retirement_calculator.submit_calculator_form';
    
    // Returns projected retirement value from Retirement Calculator Event
    public function getValue() {
        $this->money;
    }

    public function setValue($money) {
        $this->money = $money;
    }
}