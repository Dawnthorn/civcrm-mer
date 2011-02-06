<?php

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
