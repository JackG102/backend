<?php

namespace Drupal\zany_text_filters\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;


/**
 * @Filter(
 *   id = "exclamation_filter",
 *   title = @Translation("Exclamation Filter"),
 *   description = @Translation("Changes every period into an '!'!"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */

class ExclamationFilter extends FilterBase {

    public function process($text, $langcode) {
        
        // Fetches in the configuration form on the text filter 
        // to apply double "!!" or not
        $doubleExclamationMarks = $this->settings['exclamation_mark_double'];
        if ($doubleExclamationMarks) {
            $text = str_replace(".", "!!", $text); 
        } else {
            $text = str_replace(".", "!", $text);
        }
        return new FilterProcessResult($text);
    }

    public function settingsForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $form['exclamation_mark_double'] = [
            "#type" => "checkbox",
            "#title" =>"Double the fun?",
            "#description" => $this->t('If selected, add double exclamation marks rather one.')
        ];
        return $form;
    }
}