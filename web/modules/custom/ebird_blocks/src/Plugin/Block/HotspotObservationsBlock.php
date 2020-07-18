<?php

namespace Drupal\ebird_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A block that will render notable bird observations 
 * within a distance of certain coordinates.
 * 
 * @Block(
 *   id = "hotspot_observations",
 *   admin_label = @Translation("Hotspot Observations")
 * )
 */

 class HotspotObservationsBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

    protected $client_factory;
    protected $json;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, Json $json, ClientFactory $client_factory) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        $this->json = $json;
        $this->client_factory = $client_factory;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('serialization.json'),
            $container->get('http_client_factory'),
          );
    }

    /**
     * Create the form that will provide the options for what data
     * we will render in our block
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);
        $form['location'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Location code for eBird Hotspot'),
            '#description' => $this->t("One can find the location code by visiting an eBird hotspot page, and copying the last part of the URL.  For example, https://ebird.org/hotspot/L159300 . One would type in 'L159300' for the Hotspot location of the Dyke Marsh."),
            '#default_value' => $this->t('L579773'),
            '#required' => TRUE,
        ];
        $form['max_results'] = [
            '#type' => 'number',
            '#title' => $this->t('Maximum number of results'),
            '#description' => $this->t("The number of bird observations rendered on the screen.  Probably shouldn't be more than 100."),
            '#default_value' => 25,
            '#min' => 1,
            '#max' => 150,
        ];
        $form['far_back'] = [
            '#type' => 'number',
            '#title' => $this->t('Far back'),
            '#description' => $this->t("The number of days back to fetch observations."),
            '#default_value' => 30,
            '#min' => 1,
            '#max' => 30,
        ];

        return $form;
    }

    public function blockSubmit($form, $form_state) {
        $this->setConfigurationValue('location', $form_state->getValue('location'));
        $this->setConfigurationValue('max_results', $form_state->getValue('max_results'));
        $this->setConfigurationValue('far_back', $form_state->getValue('far_back'));
    }

    public function build() {
        $build = [];
        $config_module_values = \Drupal::config('ebird_blocks.settings');
        $config_form_values = $this->getConfiguration();
        $api_key = $config_module_values->get('api_key');

        $client = $this->client_factory->fromOptions([
            'base_uri' => 'https://api.ebird.org/'
        ]);

        $response = $client->get('/v2/data/obs/US-VA-059/recent', [
            'query' => [
                'maxResults' => $config_form_values['max_results'],
                'r' => $config_form_values['location'],
                'back' => $config_form_values['far_back'],
            ],
            'headers' => [
               'X-eBirdApiToken' => $api_key, 
            ]
        ]);
        

        $bird_observations = $this->json->decode($response->getBody());

        foreach($bird_observations as $bird_observation_number => $observation ) {
            $build['observation'][$bird_observation_number]['comName'] = $observation['comName'];
            $build['observation'][$bird_observation_number]['locName'] = $observation['locName'];
            $build['observation'][$bird_observation_number]['obsDt'] = $observation['obsDt'];
            $build['observation'][$bird_observation_number]['howMany'] = $observation['howMany'];
        }

        $build['observation_table'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Bird'),
                $this->t('Location'),
                $this->t('Date & Time'),
                $this->t('Number Seen')
            ],
        ];

        $rows = [];
        
        foreach($build['observation'] as $observation_key => $observation_item) {

            $bird = $observation_item['comName'];
            $location = $observation_item['locName'];
            $date_and_time = $observation_item['obsDt'];
            $number_of_birds = $observation_item['howMany'];

            $rows[$observation_key]['comName'] = $bird;
            $rows[$observation_key]['locName'] = $location;
            $rows[$observation_key]['obsDt'] = $date_and_time;
            $rows[$observation_key]['howMany'] = $number_of_birds;

        }
        $build['observation_table']['#rows'] = $rows;

        return $build;
    }

 }