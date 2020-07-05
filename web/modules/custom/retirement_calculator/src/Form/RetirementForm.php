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

    public function getUserIdFromUrl() {
        // Get user ID from URL
        $current_path = $this->path->getPath();
        $exploded_path = explode('/', $current_path);
        $user_id_from_url = $exploded_path[2];
        return $user_id_from_url;
    }

    public function loadUserObject() {
        // Loads the user object from the User's ID in URL
        $user = $this->entityTypeManager->getStorage('user')->load($this->getUserIdFromUrl());
        return $user; 
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        // Fetches User ID From URL and stores it
        $user_id_from_url = $this->getUserIdFromUrl();

        // Stores value from Projected Retirement Savings field from current user
        $user = $this->loadUserObject();
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
    
            $form['current_savings'] = [
                '#type' => 'number',
                '#title' => $this->t('How much have you currently saved?'),
                '#min' => 0,
                '#description' => 'Take into account various forms of savings and investments',
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

    // Calculates and returns projected retirement value of portfolio
    public function getRetirementAmount(array &$form, FormStateInterface $form_state) {
        $current_age = $form_state->getValue('current_age');
        $retirement_age = $form_state->getValue('retirement_age');
        $currently_invested = $form_state->getValue('current_savings');
        $investment_interval = $form_state->getValue('rate_of_investment');
        $investment_interval_amount = $form_state->getValue('investment_number');
        $annual_percentage_return = .01 * $form_state->getValue('rate_of_return');

        // Calculate yearly investment
        $yearly_investment = $currently_invested + ($investment_interval * $investment_interval_amount);
                
        // Calculate years of investment
        $years_to_invest = $retirement_age - $current_age;

        // Formula to calculate projected retirement savings with annual compound interest

        for ($i = 0; $i < $years_to_invest; $i++) {
            if ($i == 0 ) {

                // Investment already has initial contribution from lines that declare $yearly_investment
                $yearly_investment = ($yearly_investment + ($yearly_investment * $annual_percentage_return)); 
            } else {

                // Adds in additional contributions and frequency after the first year, 
                // Then does percentage earned on principal after contributions
                $yearly_investment = $yearly_investment + ($investment_interval * $investment_interval_amount); 
                $yearly_investment = round($yearly_investment + ($yearly_investment * $annual_percentage_return), 2); 
            }
        }
        $projected_retirement_value = $yearly_investment;
        
        // Returns final retirement value
        return $projected_retirement_value;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $current_age = $form_state->getValue('current_age');
        $retirement_age = $form_state->getValue('retirement_age');

        if ($retirement_age < $current_age) {
            $form_state->setErrorByName('retirement_age', $this->t('Ensure that your retirement age is greater than your current age, silly.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        // Saves the projected retirement portfolio value to user profile 
        $projected_retirement_value = $this->getRetirementAmount($form, $form_state);
        $user = $this->loadUserObject();
        $user->set('field_projected_retirement_savin', $projected_retirement_value)->save();
        return $form;
    }
    
}