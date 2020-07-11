<?php

namespace Drupal\retirement_calculator\Mailer;

use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailRetirementResults {

    protected $mail;

    public function __construct(MailManagerInterface $mail) {
        $this->mail = $mail;
    }    

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('plugin.manager.mail')
        );
    }

    public function sendResults(array $retirement_results) {
        $mailManager = $this->mail;
        $module = 'retirement_calculator';
        $key = 'retirement_results';
        $to = 'duderino102@gmail.com';
        $params['retirement_amount'] = $retirement_results['retirement_results'];
        $params['retirement_user'] = $retirement_results['user'];
        $params['message'] = "Hi " . $params['retirement_user'] . ", your projected retirement result is $" . $params['retirement_amount'] . ".";
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = TRUE;

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }
}