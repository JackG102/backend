<?php

namespace Drupal\additional_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
Use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldWidget(
 *   id = "additional_fields_bird_observation_widget",
 *   label = @Translation("Bird Observation Widget"),
 *   description = @Translation("Bird Observation Widget"),
 *   field_types = {
 *     "additional_fields_bird_observation",
 *   }
 * )
 */

class BirdObservationWidget extends WidgetBase implements WidgetInterface {

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

        $element['bird_species'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Bird Species'),
            '#target_type' => 'taxonomy_term',
            '#selection_handler' => 'default',
            '#selection_settings' => [
               'target_bundles' => 'bird_species', 
            ],
            '#attributes' => [
                'style' => 'max-width: 200px;'
            ],
            '#default_value' => isset($items[$delta]->bird_species) ? \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($items[$delta]->bird_species ) : '',
        ];

        $element['number_of_birds'] = [
            '#type' => 'number',
            '#title' => $this->t('Number'),
            '#min' => 0,
            '#max' => 1000,
            '#default_value' => isset($items[$delta]->number_of_birds) ? $items[$delta]->number_of_birds : '',
        ];
        return $element;
    }
}