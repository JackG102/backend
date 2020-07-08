<?php

namespace Drupal\additional_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *   id = "additional_fields_bird_observation_formatter",
 *   label = @Translation("Default Bird Observation Formatter"),
 *   field_types = {
 *     "additional_fields_bird_observation",
 *   }
 * )
 */


class BirdObservationFormatter extends FormatterBase {
    public function viewElements(FieldItemListInterface $items, $langcode){
        $elements = [];
        foreach($items as $delta => $item) {

            $species = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($item->bird_species)->label();
            $number = $item->number_of_birds;
            $elements[$delta] = [
                '#type' => 'markup',
                '#markup' => "<span>$species</span><br><span>$number</span>"
            ];
        }
        return $elements;
    }
}
