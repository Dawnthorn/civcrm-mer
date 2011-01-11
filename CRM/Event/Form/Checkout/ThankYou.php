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

  function buildQuickForm( )
  {
    $defaults = array( );
    $ids = array( );
	$params = array( 'id' => $contributionID );
	$transaction = new CRM_Core_DAO_FinancialTrxn( );
	$transaction->id = $this->get( 'transaction_id' );
	$transaction->find( true );
	$this->assign( 'events_in_carts', $this->cart->events_in_carts );
	$this->assign( 'transaction', $transaction );
	$not_waiting_participants = array( );
	$waiting_participants = array( );
	$participant_ids = $this->get( 'participant_ids' );
	$participants = array( );
	$participant_query = new CRM_Event_BAO_Participant( );
	$participant_query->whereAdd( "id IN (" . implode( ", ", $participant_ids ) . ")" );
	$participant_query->find( );
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
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
  }

  function preProcess( )
  {
    $this->event_cart_id = $this->get( 'last_event_cart_id' );
    parent::preProcess( );
  }
}
