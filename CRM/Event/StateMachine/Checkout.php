<?php

/*
Copyright (C) 2011 Giant Rabbit LLC

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class CRM_Event_StateMachine_Checkout extends CRM_Core_StateMachine
{
  function __construct( $controller, $action = CRM_Core_Action::NONE )
  {
    parent::__construct( $controller, $action );

    require_once 'CRM/Event/BAO/Cart.php';
    $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $cart->load_associations();
    if ( $cart->is_empty() ) {
      CRM_Core_Error::statusBounce( ts( "You don't have any events in you cart. Please add some events." ), CRM_Utils_System::url( 'civicrm/event' ) );
    }

    $pages = array( );
	$is_monetary = false;
	$is_conference = false;
	foreach ($cart->events_in_carts as $event_in_cart) {
	  $event_in_cart->load_associations();
	  if ($event_in_cart->event->is_monetary) {
		$is_monetary = true;
	  }
	  if ( $event_in_cart->event->event_type_id == 1 ) {
		$is_conference = true;
	  }
	}
	$pages["CRM_Event_Form_Checkout_ParticipantsAndPrices"] = null;
	if ($is_conference) {
	  foreach ($cart->events_in_carts as $event_in_cart) {
		$conference_participant_indexes = $controller->get( 'conference_participant_indexes' );
		if ( $conference_participant_indexes != null ) {
		  foreach ( $conference_participant_indexes as $i ) {
			$pages["CRM_Event_Form_Checkout_ConferenceEvents_{$i}"] = array
			(
			  'className' => 'CRM_Event_Form_Checkout_ConferenceEvents',
			  'title' => "Select Conference Events For Participant {$i}",
			);
		  }
		}
	  }
	}
	if ($is_monetary) {
	  $pages["CRM_Event_Form_Checkout_Payment"] = null;
    }
	$pages["CRM_Event_Form_Checkout_ThankYou"] = null;
    $this->addSequentialPages( $pages, $action );
  }
}

?>
