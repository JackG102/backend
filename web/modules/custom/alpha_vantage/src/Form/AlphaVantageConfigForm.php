<?php

namespace Drupal\alpha_vantage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the Alpha Vantage module
 */
class AlphaVantageConfigForm extends ConfigFormBase {

    const SETTINGS = 'alpha_vantage.settings';

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'alpha_vantage_configuration_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            static::SETTINGS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config(static::SETTINGS);

        $form['api_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Alpha Vantage API key'),
        '#description' => $this->t('Enter the Alpha Vantage API key that the site will use.'),
        '#default_value' => $config->get('api_key'),
        '#size' => 60,
      ];  

      return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->configFactory()->getEditable(static::SETTINGS)
        ->set('api_key', $form_state->getValue('api_key'))
        ->save();

        return parent::submitForm($form, $form_state);
    }
}