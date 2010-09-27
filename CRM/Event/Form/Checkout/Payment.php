<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Event/BAO/Cart.php';

class CRM_Event_Form_Checkout_Payment extends CRM_Event_Form_Checkout
{
  public $_fields = array();
  public $_paymentProcessor;

  function buildPaymentFields( )
  {

    $payment_processor_id = null;
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      if ( $payment_processor_id == null ) {
	$payment_processor_id = $event_in_cart->event->payment_processor_id;
      } else {
	if ( $event_in_cart->event->payment_processor_id != NULL && $event_in_cart->event->payment_processor_id != $payment_processor_id ) {
	  CRM_Core_Error::statusBounce( ts( 'When registering for multiple events all events must use the same payment processor. ') );
	}
      }
    }

    if ( $payment_processor_id == null ) {
      CRM_Core_Error::statusBounce( ts( 'A payment processor must be selected for this event registration page, or the event must be configured to give users the option to pay later (contact the site administrator for assistance).' ) );
    }

    require_once 'CRM/Core/BAO/PaymentProcessor.php';
    $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $payment_processor_id, $this->_mode );
    $this->assign( 'paymentProcessor', $this->_paymentProcessor );

    require_once 'CRM/Core/Payment/Form.php';
    CRM_Core_Payment_Form::setCreditCardFields( $this );
    CRM_Core_Payment_Form::buildCreditCard( $this );
  }


  function buildQuickForm( )
  {
    $line_items = array();
    $total = 0;
    $price_values = $this->getValuesForPage( 'Prices' );
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
      if ( $price_set_id === false ) {
	CRM_Core_OptionGroup::getAssoc( "civicrm_event.amount.{$event_in_cart->event_id}", $fee_data, true );
	$cost = $fee_data[$price_values["event_{$event_in_cart->event_id}_amount"]]['value'];
      } else {
	$price_sets = CRM_Price_BAO_Set::getSetDetail( $price_set_id, true );
	$price_set = $price_sets[$price_set_id];
	$price_set_amount = array( );
	CRM_Price_BAO_Set::processAmount( $price_set['fields'], $price_values, $price_set_amount );
	$cost = $price_values['amount'];
      }
      $num_participants = count( $this->participants );
      $amount = $cost * $num_participants;
      $line_items[] = array( 
	'event' => $event_in_cart->event,
	'num_participants' => $num_participants, 
	'cost' => $cost,
	'amount' => $amount,
      );
      $total += $amount;
    }

    $this->buildPaymentFields( );

    $this->assign( 'line_items', $line_items );
    $this->assign( 'total', $total );
    $buttons = array( );
    $buttons[] = array(
      'name' => ts('<< Go Back'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp',
      'type' => 'back',
    );
    $buttons[] = array(
      'isDefault' => true,
      'name' => ts('Continue >>'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'type' => 'next',
    );
    $this->addButtons( $buttons );
  }
}
