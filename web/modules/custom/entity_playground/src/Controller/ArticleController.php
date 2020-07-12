<?php

namespace Drupal\entity_playground\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArticleController extends ControllerBase {

    protected $entity_type_manager;
    protected $ids;

    public function __construct(EntityTypeManager $entity_type_manager) {
        $this->entity_type_manager = $entity_type_manager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    public function content() {
        $build = [];
        $build['sample_content'] = [
            '#markup' => '<p>Sample content here.</p>'
        ];

        //// Adds the build object's render array to the Controller's render array
        // $build['node_2'] = $this->renderNode(2);
        // $build['node_3'] = $this->renderNode(4);

        //// Adds the array of node build objects to the Controller's render array
        //$build['many_nodes'] = $this->renderAllNodes();

        // Seperated out functionality to fetch ids, build the node objects,
        // and then build the view objects for the nodes.  Whereas above, I 
        // did everything in the same method to test out the concepts
        $ids = $this->fetchRecentNodeIds(6);
        $objects = $this->loadMultipleNodeObjects($ids);
        $build['recent_articles'] = $this->buildMultipleNodeRenderObjects($objects);

        return $build;
    }

    // Renders a node object based on node id
    public function renderNode($nid) {
        // Loads the node via the injected entity type manager
        $node = $this->entity_type_manager->getStorage('node')->load($nid);
        
        // Loads the View Builder for the node entity type
        $node_builder = $this->entity_type_manager->getViewBuilder('node');
        
        // Use the View Builder's view method to return
        // the build object's render array
        // Note: one can pass in a second parameter to specify the display mode
        $build_object = $node_builder->view($node, 'teaser');

        return $build_object;
    }
    // Render nodes 1 through 4 -- all logic inside the method
    public function renderAllNodes() {
        $ids = [1,2,3,4];
        $all_nodes = $this->entity_type_manager->getStorage('node')->loadMultiple($ids);
        $node_builder = $this->entity_type_manager->getViewBuilder('node');
        // Constructs an array of build objects for nodes 1 through 4
        foreach($all_nodes as $node) {
            $build_object[] = $node_builder->view($node, 'teaser');
        }
        return $build_object;
    }

    public function fetchRecentNodeIds($number) {

        $query = $this->entity_type_manager->getStorage('node')->getQuery();
        $query
            ->condition('type','article')
            ->condition('status', TRUE)
            ->range(0, $number)
            ->sort('created', 'DESC');
        $ids = $query->execute();

        return $ids;
    }

    public function loadMultipleNodeObjects(array $ids) {
        $node_objects = $this->entity_type_manager->getStorage('node')->loadMultiple($ids);

        return $node_objects;
    }

    public function buildMultipleNodeRenderObjects(array $node_objects) {
        $node_builder = $this->entity_type_manager->getViewBuilder('node');
        foreach($node_objects as $node_object) {
            $build_view_node_array[] = $node_builder->view($node_object, 'teaser');
        };

        return $build_view_node_array;
    }




}