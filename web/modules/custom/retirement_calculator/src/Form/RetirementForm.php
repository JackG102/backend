<?php

namespace Drupal\retirement_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\retirement_calculator\Mailer\MailRetirementResults;

class RetirementForm extends FormBase {

    protected $account;
    protected $path;
    protected $entity_manager;
    protected $logger;
    protected $retirement_mailer;

    /**
     * Dependency Injection:
     * Instantiates the service dependencies into the Retirement Form class
     */
    public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_manager, CurrentPathStack $path, LoggerChannelFactory $logger, MailRetirementResults $retirement_mailer) {
        $this->account = $account;
        $this->entityTypeManager = $entity_manager;
        $this->path = $path;
        $this->logger = $logger;
        $this->retirement_mailer = $retirement_mailer;
    }

    /**
     * Dependency Injection:
     * Creates the container that houses the service dependencies
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('current_user'),
            $container->get('entity_type.manager'),
            $container->get('path.current'),
            $container->get('logger.factory'),
            $container->get('retirement_calculator.mail_retirement_results')
        );
    }

    /**
     * Returns the Form's machine name
     */

    public function getFormId() {
        return "user_retirement_calculator_form";
    }

    /**
     * Gets the user ID from the Url, where the Retirement Calculator
     * is located on the User profile.
     *  
     * Note: This method does NOT get the ID from the currently logged in user.
     */

    public function getUserIdFromUrl() {
        $current_path = $this->path->getPath();
        $exploded_path = explode('/', $current_path);
        $user_id_from_url = $exploded_path[2];
        
        return $user_id_from_url;
    }

    /**
     * Loads the User object using the User ID from the getUserIdFromURL method
     */

    public function loadUserObjectFromUrl() {
        $user = $this->entityTypeManager->getStorage('user')->load($this->getUserIdFromUrl());
        
        return $user; 
    }

    /**
     * Builds the Retirement Calculator form fields
     * based on the permissions of the logged-in user
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form['#theme'] = 'retirement_form';
        
        // Fetches User ID From URL and stores it
        $user_id_from_url = $this->getUserIdFromUrl();

        // Stores value from Projected Retirement Savings field from current user
        $user = $this->loadUserObjectFromUrl();
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
                    '#type' => 'button',
                    '#value' => $this->t('Calculate'),
                    '#ajax' => [
                        'callback' => '::submitForm',
                        'wrapper' => 'retirement_summary'
                    ]
                ];

                $form['submit_and_email'] = [
                    '#type' => 'button',
                    '#value' => $this->t('Calculate & Email Results'),
                    '#ajax' => [
                        'callback' => '::submitFormAndEmail',
                        'wrapper' => 'retirement_summary'
                    ]
                ];
            }

            $form['projected_retirement_savings'] = [
                '#markup' => "<div id='retirement_summary'><br> <span><strong>Projected Retirement Savings:</strong> $" . $user_savings . "</span></div",
            ];
    
            return $form;

        } else {
            $form['form_deny'] = [
                '#markup' => "<span>Permission Denied</span>"
            ];
            
            return $form;
        }
    }

    /**
     * Calculates and returns projected retirement value
     * based on the form field values
     */ 

    public function calculateRetirementAmount(array &$form, FormStateInterface $form_state) {
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

    /**
     * Validates the form values where it currently ensures that
     * the retirement age is greater than a person's age.
     */

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $current_age = $form_state->getValue('current_age');
        $retirement_age = $form_state->getValue('retirement_age');

        if ($retirement_age < $current_age) {
            $form_state->setErrorByName('retirement_age', $this->t('Ensure that your retirement age is greater than your current age, silly.'));
        }
    }

    /**
     * Submits the form where it saves the projected retirement portfolio value
     * to a field on the User profile
     * 
     * Note: This method is currently getting called from an Ajax callback on
     * on the submit button. 
     * 
     * @TODO refactor duplicated code in submit form with specialized methods
     */

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $projected_retirement_value = $this->calculateRetirementAmount($form, $form_state);
        $user = $this->loadUserObjectFromUrl();
        $username = $user->get('name')->value;
        $user->set('field_projected_retirement_savin', $projected_retirement_value)->save();
        $form['projected_retirement_savings']['#markup'] = "<div id='retirement_summary'><br> <span><strong>Projected Retirement Savings:</strong> $" . $projected_retirement_value . "</span></div>";
        $this->logger->get('retirement_calculator')->info("$username is projected to save @result",['@result' => $projected_retirement_value]);

        
        return $form['projected_retirement_savings'];

    }  

    public function submitFormAndEmail(array &$form, FormStateInterface $form_state) {
        $projected_retirement_value = $this->calculateRetirementAmount($form, $form_state);
        $user = $this->loadUserObjectFromUrl();
        $user->set('field_projected_retirement_savin', $projected_retirement_value)->save();
        $form['projected_retirement_savings']['#markup'] = "<div id='retirement_summary'><br> <span><strong>Projected Retirement Savings:</strong> $" . $projected_retirement_value . "</span></div>";
        $username = $user->get('name')->value;
        $this->logger->get('retirement_calculator')->info("$username is projected to save @result",['@result' => $projected_retirement_value]);

        // Prepare data in an array that is passed to Retiremement Mailer service
        $retirement_mailer_info = [
            'retirement_results' => $projected_retirement_value,
            'user' => $username
        ];

        // Invoke Retirement Mailer service that has some values to help compose the email
        $this->retirement_mailer->sendResults($retirement_mailer_info);

        return $form['projected_retirement_savings'];
    }
}