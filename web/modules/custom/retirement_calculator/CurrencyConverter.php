<?php

namespace Drupal\retirement_calculator;

class CurrencyConverter {

    public function convertUsdtoEuro($usd) {
        $euro = $usd * .88;
        return $euro;
    }
    
}