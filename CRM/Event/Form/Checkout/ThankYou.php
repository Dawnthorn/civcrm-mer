<?php

require_once 'CRM/Core/DAO/FinancialTrxn.php';
require_once 'CRM/Contact/BAO/Contact.php';
require_once 'CRM/Event/BAO/Participant.php';
require_once 'CRM/Event/Form/Registration/ThankYou.php';

class CheckoutParticipant
{
  public $contact = null;
  public $first_name = null;
  public $last_name = null;
  public $must_wait = false;
  public $participant = null;

  function __construct( $participant )
  {
	$this->participant = $participant;
	if ( $this->participant->status_id != 1 ) {
	  $this->must_wait = true;
	}
	$this->contact = new CRM_Contact_BAO_Contact( );
	$this->contact->id = $this->participant->contact_id;
	$this->contact->find( true );
	$this->first_name = $this->contact->first_name;
	$this->last_name = $this->contact->last_name;
  }
}

class CRM_Event_Form_Checkout_ThankYou extends CRM_Event_Form_Checkout
{
  public $line_items = null;

  function buildLineItems( )
  {
	$not_waiting_participants = array( );
	$waiting_participants = array( );
	$participant_ids = $this->get( 'participant_ids' );
	$participants = array( );
	$participant_query = new CRM_Event_BAO_Participant( );
	$participant_query->whereAdd( "id IN (" . implode( ", ", $participant_ids ) . ")" );
	$participant_query->find( );
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  $event_in_cart->load_location( );
	  $event_in_cart->participants = array( );
	}
	while ( $participant_query->fetch() ) {
	  $participant = clone( $participant_query );
	  foreach ( $this->cart->events_in_carts as $event_in_cart ) {
		if ( $event_in_cart->event->id == $participant->event_id ) {
		  $checkout_participant = new CheckoutParticipant( $participant );
		  $event_in_cart->add_participant( $checkout_participant );
		}
	  }
	} 
	$line_items = $this->get( 'line_items' );
	foreach ( $line_items as $line_item ) {
	  foreach ( $this->cart->events_in_carts as $event_in_cart ) {
		if ($line_item['event_id'] == $event_in_cart->event_id) {
		  $line_item['event'] = $event_in_cart->event;
		  $line_item['num_participants'] = $event_in_cart->num_not_waiting_participants();
		  $line_item['participants'] = $event_in_cart->not_waiting_participants();
		  $line_item['num_waiting_participants'] = $event_in_cart->num_waiting_participants();
		  $line_item['waiting_participants'] = $event_in_cart->waiting_participants();
		  $line_item['location'] = $event_in_cart->location;
		}
	  }
	  $this->line_items[] = $line_item;
	}
	$this->assign( 'line_items', $this->line_items );
  }

  function buildQuickForm( )
  {
    $defaults = array( );
    $ids = array( );
	$params = array( 'id' => $contributionID );
	$transaction = new CRM_Core_DAO_FinancialTrxn( );
	$transaction->id = $this->get( 'transaction_id' );
	$transaction->find( true );
	$template_params_to_copy = array
	(
	  'billing_name',
	  'billing_city',
	  'billing_country',
	  'billing_postal_code',
	  'billing_state',
	  'billing_street_address',
	  'credit_card_exp_date',
	  'credit_card_type',
	  'credit_card_number',
	);
	foreach ( $template_params_to_copy as $template_param_to_copy ) {
	  $this->assign( $template_param_to_copy, $this->get( $template_param_to_copy ) );
	}
	$this->buildLineItems( );
	$this->assign( 'discounts', $this->get( 'discounts' ) );
	$this->assign( 'events_in_carts', $this->cart->events_in_carts );
	$this->assign( 'transaction', $transaction );
	$this->assign( 'payment_required', $this->get( 'payment_required' ) );
	$this->assign( 'total', $this->get( 'total' ) );
  }

  function preProcess( )
  {
    $this->event_cart_id = $this->get( 'last_event_cart_id' );
    parent::preProcess( );
  }
}
