<?php

namespace Drupal\retirement_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RetirementConfigurationForm extends ConfigFormBase {

    const SETTINGS = 'retirement_calculator.settings';

    public function getFormId() {
        return 'retirement_calculator_configuration_form';
    }

    protected function getEditableConfigNames() {
        return [
            static::SETTINGS,
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['euro'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Convert USD to Euro'),
            '#description' => $this->t('Convert projected retirement to Euros'),
            '#default_value' => $this->configFactory()->getEditable(static::SETTINGS)->get('euro')
        ];

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->configFactory()->getEditable(static::SETTINGS)
        ->set('euro', $form_state->getValue('euro'))
        ->save();

        return parent::submitForm($form, $form_state);
    }

}