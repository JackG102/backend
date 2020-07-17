<?php

namespace Drupal\batch_api\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form will include a number of batch operations 
 * that relate to dealing with Article nodes.
 */
class ArticleBatchOperationForm extends FormBase {
    
    protected $entity_type_manager;

    /**
     * The construct and create methods for dependency injection 
     * to bring in the services needed for the Batch operations.
     */
    public function __construct(EntityTypeManager $entity_type_manager) {
        $this->entity_type_manager = $entity_type_manager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Returns the machine name of the form.
     */
    public function getFormId() {
        return 'article_batch_form';
    }

    /**
     * Adds a submit button which will call the submit handler 
     * to run the batch operation.
     */
    public function buildForm(array $form, FormStateInterface $form_state){
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Execute Batch Operation'),
        ];  

        return $form;
    }

    /**
     * Runs the batch operation to add a tag to each article node.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        $article_ids = \Drupal::entityQuery('node')->condition('type','article')->execute();
        // Define the Operations variable
        $operations = [];

        // IMPORTANT - HEART AND SOUL OF BATCH API
        // Chunks the data into small batches 
        // and applies the method to each small chunk
        foreach(array_chunk($article_ids, 20) as $small_chunk) {
            $operations[] = ['\Drupal\batch_api\Form\ArticleBatchOperationForm::applyTags', [$small_chunk]];
        }
        // Defines the batch itself
        // Note: The operations variable references the method and the chunked data
        $batch = [
            'title' => $this->t('Applying tag to Article nodes'),
            'operations' => $operations,
            'finished' => 'my_finished_callback',
        ];

        // Initiaties the batch operation
        batch_set($batch);

    }

    // The logic applied to each chunk of data during the batching process
    public static function applyTags($article_ids, &$context) {
        $article_nodes[] = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($article_ids);
        //// Sets the title of each article field to a string
        // foreach($article_nodes[0] as $article_node) {
        //     $article_node->set('title','I love the Batch API')
        //     ->save();
        // }
        //// Deletes article nodes
        foreach($article_nodes[0] as $article_node) {
            $article_node->delete();
        }
    }
}