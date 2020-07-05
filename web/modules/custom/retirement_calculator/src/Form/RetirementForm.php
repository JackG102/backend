<?php

namespace Drupal\retirement_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;

class RetirementForm extends FormBase {

    protected $account;
    protected $path;
    protected $entity_manager;

    public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_manager, CurrentPathStack $path) {
        $this->account = $account;
        $this->entityTypeManager = $entity_manager;
        $this->path = $path;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('current_user'),
            $container->get('entity_type.manager'),
            $container->get('path.current')
        );
    }

    public function getFormId() {
        return "user_retirement_calculator_form";
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get user ID from URL
        $current_path = $this->path->getPath();
        $exploded_path = explode('/', $current_path);
        $user_id_from_url = $exploded_path[2];

        // Stores value from Projected Retirement Savings field from current user
        $user = $this->entityTypeManager->getStorage('user')->load($user_id_from_url);
        $user_savings = $user->get('field_projected_retirement_savin')->value;

        //Checks to see if user is viewing personal retirement calculator
        $isPersonalCalculator = $user_id_from_url == $this->account->id();

        // If user has permission to view all retirement calculators 
        // or the calculator is the user's personal one, render the form
        if ($this->account->hasPermission('ViewAllCalculators') || $isPersonalCalculator) {
            
            $form['current_age'] = [
                '#type' => 'number',
                '#title' => $this->t('What is your current age?'),
                '#min' => 0,
            ];
    
            $form['retirement_age'] = [
                '#type' => 'number',
                '#title' => $this->t('What is your desired retirement age?'),
                '#min' => 1,
                '#max' => 100
            ];
    
            $form['check_savings'] = [
                '#type' => 'select',
                '#title' => $this->t('Have you invested any money for retirement?'),
                '#description' => $this->t("It's ok if you haven't. We have to start somewhere"),
                '#options' => [
                    '0' => $this->t("No"),
                    '1' => $this->t("Yes")
                ],
                '#default_value' => '0', 
    
                // Add this line for easy referencing if field has a value  
                '#attributes' => [
                    'name' => 'savings_check'
                ] 
            ];
    
            $form['current_savings'] = [
                '#type' => 'number',
                '#title' => $this->t('How much have you currently saved?'),
                '#min' => 0,
                '#description' => 'Take into account various forms of savings and investments',
    
                // Checks the value of "check savings" field. If the person has
                // saved money it makes this field visible to fill out.
                '#states' => [
                    'visible' => [
                        ':input[name="savings_check"]' => [
                            'value' => '1'
                        ],
                    ],
                ]
            ];
    
            $form['rate_of_investment'] = [
                '#type' => 'select',
                '#title' => $this->t('At what interval would you like to invest?'),
                '#description' => $this->t('Making a good habit of at least monthly contributions is recommended.'),
                '#options' => [
                    '1' => $this->t("Once a Year"),
                    '12' => $this->t("Monthly"),
                    '26' => $this->t("Twice a Month"),
                    '52' => $this->t("Weekly"),
                ],
                '#default_value' => '12',   
            ];
    
            $form['investment_number'] = [
                '#type' => 'number',
                '#title' => $this->t('Amount to invest per contribution'),
                '#min' => 0,
                '#default_value' => 100,
            ];
    
            $form['rate_of_return'] = [
                '#type' => 'number',
                '#title' => $this->t('Expected yearly rate of return'),
                '#description' => $this->t('A rate of return would average between 6% to 12% depending on the year'),
                '#min' => 0,
                '#max' => 200,
                '#default_value' => 6,
            ];
            
            // Render Submit button if user has sufficient permissions
            if ($this->account->hasPermission('EditAllCalculators') || $isPersonalCalculator) {
                $form['submit'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Submit'),
                ];
            }

            $form['projected_retirment_savings'] = [
                '#markup' => "<br> <span><strong>Projected Retirement Savings:</strong> $" . $user_savings . "</span>"
            ];
    
            return $form;

        } else {
            $form['form_deny'] = [
                '#markup' => "<span>Permission Denied</span>"
            ];
            return $form;
        }
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state){

    }
    
}