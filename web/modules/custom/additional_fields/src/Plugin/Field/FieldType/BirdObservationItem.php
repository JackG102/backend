<?php

namespace Drupal\additional_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

 /**
 * @FieldType(
 *   id = "additional_fields_bird_observation",
 *   label = @Translation("Bird Observations"),
 *   description = @Translation("Birds species and their numbmers seen while watching them!"),
 *   category = @Translation("Birding"),
 *   default_widget = "additional_fields_bird_observation_widget",
 *   default_formatter = "additional_fields_bird_observation_formatter"
 * )
*/

class BirdObservationItem extends FieldItemBase implements FieldItemInterface {

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        return [
            'columns' => [
                'bird_species' => [
                    'type' => 'int',
                    'not null' => TRUE,
                    'unsigned' => TRUE,
                ],

                'number_of_birds' => [
                    'type' => 'int',
                    'not null' => TRUE,
                    'unsigned' => TRUE
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $value1 = $this->get('bird_species')->getValue();
        $value2 = $this->get('number_of_birds')->getValue();
        return empty($value1) && empty($value2);
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['bird_species'] = DataDefinition::create('integer')
            ->setLabel('Bird Species')
            ->setDescription(t('The species of bird seen'));
        $properties['number_of_birds'] = DataDefinition::create('integer')
            ->setLabel('Number of Birds')
            ->setDescription(t('The number of birds seen of that species'));
        
        return $properties;
    }
}