<?php

require_once drupal_get_path('module', 'civicrm') . '/modules/views/civicrm/civicrm_handler_field_event_link.inc';

class civicrm_mer_handler_field_event_link extends civicrm_handler_field_event_link {
  function render($values) {
    $data = check_plain($values->{$this->field_alias});
    if ($this->options['link_to_civicrm_event'] == 'registration') {
      if (user_access('register for events') && $data !== NULL && $data !== '') {
	return civicrm_views_href( $data, 'civicrm/event/add_to_cart', "reset=1&id={$values->{$this->aliases['id']}}" );
      }
    } else {
      parent::render($values);
    }
  }
}
