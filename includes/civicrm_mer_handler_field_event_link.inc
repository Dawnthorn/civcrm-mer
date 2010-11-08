<?php

require_once drupal_get_path('module', 'civicrm') . '/modules/views/civicrm/civicrm_handler_field_event_link.inc';
require_once drupal_get_path('module', 'civicrm_mer') .'/CRM/Event/BAO/Cart.php';

class civicrm_mer_handler_field_event_link extends civicrm_handler_field_event_link {
  function render($values) {
    $data = check_plain($values->{$this->field_alias});
    if ($this->options['link_to_civicrm_event'] == 'registration') {
      $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
      $cart->load_associations( );
      $in_cart = false;
	  foreach ( $cart->events_in_carts as $event_in_cart ) {
	    if ($values->{$this->aliases['id']} == $event_in_cart->event->id)  {
    	  $in_cart = true;
    	  break;
    	}
      }
      if (user_access('register for events') && $data !== NULL && $data !== '') {
      	if ($in_cart) {
      		return civicrm_views_href( $data, 'civicrm/event/remove_from_cart', "reset=1&id={$values->{$this->aliases['id']}}" );
      	} else {
			return civicrm_views_href( $data, 'civicrm/event/add_to_cart', "reset=1&id={$values->{$this->aliases['id']}}" );
		}
      }
    } else {
      parent::render($values);
    }
  }
}
