<?php

namespace Drupal\retirement_calculator\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;

class CalculatorRoute implements ContainerInjectionInterface {
    
    protected $current_user;
    protected $entity_manager;

    public function __construct(AccountInterface $user, EntityTypeManagerInterface $entity_manager) {
        $this->user = $user;
        $this->entityTypeManager = $entity_manager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('current_user'),
            $container->get('entity_type.manager')
        );
    }

    public function routes() {
        $routes = [];
        $routes['retirement_calculator.user_retirement_form'] = new Route(
            '/user/{user}/retirement_calculator',
            
            [
                '_form' => '\Drupal\retirement_calculator\Form\RetirementForm',
                '_title' => 'User Retirement Form'
            ],
            [
                '_permission'  => "access content",
            ]
        );
        
        return $routes;
    }
}