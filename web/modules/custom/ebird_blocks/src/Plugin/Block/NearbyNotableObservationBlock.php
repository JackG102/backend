<?php

namespace Drupal\ebird_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Serialization\Json;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A block that will render notable bird observations 
 * within a distance of certain coordinates.
 * 
 * @Block(
 *   id = "nearby_notable_observations",
 *   admin_label = @Translation("Local notable bird observations")
 * )
 */

 class NearbyNotableObservationBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    public function build() {
        $build = [];
        $config = \Drupal::config('ebird_blocks.settings');
        $api_key = $config->get('api_key');

        $client = $this->client_factory->fromOptions([
            'base_uri' => 'https://api.ebird.org/'
        ]);

        $response = $client->get('/v2/data/obs/geo/recent/notable', [
            'query' => [
                'back' => 30,
                'dist' => 10,
                'lat' => 38.94,
                'lng' => -77.34
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