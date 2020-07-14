<?php

namespace Drupal\alpha_vantage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * A block to render entity content that should properly implement
 * the caching API
 * 
 * @Block(
 *   id = "simple_block",
 *   admin_label = @Translation("A simple block"),
 *   description = @Translation("A simple block to render content"),
 * )
 */
class SimpleBlock extends BlockBase implements ContainerFactoryPluginInterface {

    protected $entity_manager;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->entity_manager = $entity_manager;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Builds or returns the render array of Simple Block
     */
    public function build() {
        $build = [];

        $node = $this->entity_manager->getStorage('node')->load(2);
        $node_description = $node->get('body')->value;
        //// This approach with the getViewBuilder method actually caches correctly
        //// I wonder if its because you are rendering the node object build
        //// which has the cache tags located inside it.
        // $build_node = $this->entity_manager->getViewBuilder('node');
        // $actual_build_object = $build_node->view($node);
        // $build['node'] = $actual_build_object;
        $build['#markup'] = "<div> $node_description </div>";

        //// Example of hardcoding the node id for the cache tag
        // $build['#cache'] = [
        //     'tags' => ['node:2']
        // ];

        //// Example of fetching the cache tags from the node
        // If I were to add max-age or context I would do it here
        // in the render array
        $node_cache_tags = $node->getCacheTags();
        $build['#cache'] = [
            'tags' => $node_cache_tags,
        ];

        return $build;

    }

}