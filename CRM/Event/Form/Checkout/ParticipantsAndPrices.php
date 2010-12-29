<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Cart.php';
require_once 'CRM/Event/Form/Checkout.php';
require_once 'CRM/Price/BAO/Set.php';

class CRM_Event_Form_Checkout_ParticipantsAndPrices extends CRM_Event_Form_Checkout
{
  public $price_fields_for_event;

  function buildQuickForm( )
  {
    $this->price_fields_for_event = array();
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  foreach ( $event_in_cart->participants as $participant ) {
		$participant->load_fields( $this, $event_in_cart );
	  }

	  $this->price_fields_for_event[$event_in_cart->event_id] = array();
	  $base_field_name = "event_{$event_in_cart->event_id}_amount";
	  $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
	  if ( $price_set_id === false ) {
		require_once 'CRM/Utils/Money.php';
		CRM_Core_OptionGroup::getAssoc( "civicrm_event.amount.{$event_in_cart->event_id}", $fee_data, true );
		$choices = array();
		foreach ( $fee_data as $fee ) {
		  if ( is_array( $fee ) ) {
			$choices[] = $this->createElement( 'radio', null, '', CRM_Utils_Money::format( $fee['value']) . ' ' . $fee['label'], $fee['amount_id'] );
		  }
		}
		$this->addGroup( $choices, $base_field_name, "");
		$this->price_fields_for_event[$event_in_cart->event_id][] = $base_field_name;
	  } else {
		$price_sets = CRM_Price_BAO_Set::getSetDetail( $price_set_id, true );
		$price_set = $price_sets[$price_set_id];
		$index = -1;
		foreach ( $price_set['fields'] as $field ) {
		  $index++;
		  $field_name = "event_{$event_in_cart->event_id}_price_{$field['id']}";
		  CRM_Price_BAO_Field::addQuickFormElement( $this, $field_name, $field['id'], false, true );
		  $this->price_fields_for_event[$event_in_cart->event_id][] = $field_name;
		}
	  }
	}
	$this->assign( 'events_in_carts', $this->cart->events_in_carts );
	$this->assign( 'price_fields_for_event', $this->price_fields_for_event );
	$this->addButtons( 
	  array ( 
		array ( 'type' => 'upload',
		'name' => ts('Continue >>'),
		'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
		'isDefault' => true )
	  )
	);
	$this->addFormRule( array( 'CRM_Event_Form_Checkout_ParticipantsAndPrices', 'formRule' ), $this );
  }

  static function formRule( $fields, $fields, $self )
  {
	$errors = array();
	foreach ( $self->cart->events_in_carts as $event_in_cart ) {
	  foreach ( $event_in_cart->participants as $mer_participant ) {
		$contact = CRM_Contact_BAO_Contact::matchContactOnEmail( $mer_participant->email );
		if ($contact != null) {
		  $participant = new CRM_Event_BAO_Participant();
		  $participant->event_id = $event_in_cart->event_id;
		  $participant->contact_id = $contact->id;
		  $num_found = $participant->find();
		  if ($num_found > 0) {
			$errors[$mer_participant->email_field_name( $event_in_cart )] = "The participant {$mer_participant->email} is already registered for {$event_in_cart->event->title} ({$event_in_cart->event->start_date}).";
		  }
		}
	  }
	}
	return empty( $errors ) ? true : $errors;
  }

  function preProcess( )
  {
	$user_id = $this->getContactID( );
	if ( $user_id === NULL ) {
	  CRM_Core_Session::setStatus( ts( "You must log in or create an account to register for events." ) );
	  return CRM_Utils_System::redirect( "/user?destination=civicrm/event/cart_checkout&reset=1" );
	}
	else {
	  return parent::preProcess( );
	}
  }
}
