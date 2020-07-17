<?php

namespace Drupal\ebird_views\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A configuration form to store the eBird API key
 * and link to information about how to get one if
 * need be.
 */

 class EbirdConfigurationForm extends ConfigFormBase {

    const SETTINGS = 'ebird_views.settings';

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ebird_views_configuration_form';
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
            '#title' => $this->t('eBird API Key'),
            '#description' => $this->t('Enter in your eBird API key.  For the module to function properly, you will need add your API key here'),
            '#default_value' => $config->get('api_key'),
        ];
        $form['help'] = [
            '#markup' => "<span>If you do not have an eBird API key, you'll need to sign into your eBird account and sign up to receive one: <a href='https://ebird.org/api/keygen'>https://ebird.org/api/keygen</a></span>",
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