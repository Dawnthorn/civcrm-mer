<?php

function civicrm_mer_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'civicrm_mer') . '/includes',
    ),
    'handlers' => array(
      'civicrm_mer_handler_field_event_link' => array(
	'parent' => 'views_handler_field'
      ),
    ),
  );
}

function civicrm_mer_views_data_alter(&$data) {
  $data['civicrm_event']['registration_link_text']['field']['handler'] = 'civicrm_mer_handler_field_event_link';
}
