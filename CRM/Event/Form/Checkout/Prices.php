<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Cart.php';
require_once 'CRM/Event/Form/Checkout.php';
require_once 'CRM/Price/BAO/Set.php';

class CRM_Event_Form_Checkout_Prices extends CRM_Event_Form_Checkout
{
  public $price_fields_for_event;

  function buildQuickForm( )
  {
    $this->price_fields_for_event = array();
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
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
	  $field_name = "{$base_field_name}_{$index}";
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
  }
}
