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

class CRM_Event_Form_Checkout extends CRM_Core_Form
{
  public $_action;
  public $cart;
  public $contact;
  public $event_cart_id = null;
  public $_mode;
  public $participants;

  function checkWaitingList( )
  {
	require_once 'CRM/Event/BAO/Participant.php';
	foreach ( $this->cart->events_in_carts as $event_in_cart )
	{
	  $empty_seats = CRM_Event_BAO_Participant::eventFull( $event_in_cart->event_id, true );
	  if ( !is_numeric( $empty_seats ) ) {
		$empty_seats = 0;
	  }
	  foreach ( $event_in_cart->participants as $participant ) {
		if ( $empty_seats <= 0 ) {
		  $participant->must_wait = true;
		}
		$empty_seats--;
	  }
	}
  }

  function getContactID( )
  {
	$tempID = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this );

	// force to ignore the authenticated user
	if ( $tempID === '0' ) {
	  return;
	}

	//check if this is a checksum authentication
	$userChecksum = CRM_Utils_Request::retrieve( 'cs', 'String', $this );
	if ( $userChecksum ) {
	  //check for anonymous user.
	  require_once 'CRM/Contact/BAO/Contact/Utils.php';
	  $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum( $tempID, $userChecksum );
	  if ( $validUser ) return  $tempID;
	}

	// check if the user is registered and we have a contact ID
	$session = CRM_Core_Session::singleton( );
	return $session->get( 'userID' );
  }

  function getValuesForPage( $page_name )
  {
	$container = $this->controller->container( );
	return $container['values'][$page_name];
  }

  function loadCart( )
  {
	if ( $this->event_cart_id == null ) {
	  $this->cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
	} else {
	  $this->cart = CRM_Event_BAO_Cart::find_by_id( $this->event_cart_id );
	}
	$this->cart->load_associations( );
  }

  function loadParticipants( )
  {
	require_once 'CRM/Event/BAO/MerParticipant.php';
	$event_in_cart_id = $this->cart->events_in_carts[0]->id;
	if ( array_key_exists( "event_in_cart_{$event_in_cart_id}_participant_0_email", $this->_submitValues ) ) {
	  $participants_data = $this->_submitValues;
	} else {
	  $participants_data = $this->getValuesForPage( 'ParticipantsAndPrices' );
	}
	foreach ( $participants_data as $key => $value ) {
	  $matches = array();
	  if ( preg_match( "/event_in_cart_(\d+)_participant_(\d+)_email/", $key, $matches ) ) {
		$event_in_cart_id = $matches[1];
		$participant_index = $matches[2];
		$event_in_cart = $this->cart->get_event_in_cart_by_id( $event_in_cart_id );
		$participant = new CRM_Event_BAO_MerParticipant();
		$participant->index = $participant_index;
		$participant->load_values( $participants_data, $event_in_cart );
		$event_in_cart->add_participant( $participant );
	  }
	}
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  if ( empty($event_in_cart->participants) ) {
		$participant = new CRM_Event_BAO_MerParticipant();
		$participant->index = 0;
		$participant->contact_id = $this->getContactID( );
		$event_in_cart->add_participant( $participant );
	  }
	}
  }

  function preProcess( )
  {
	$this->_action = CRM_Utils_Request::retrieve( 'action', 'String', $this, false );
	$this->_mode = 'live';
	$this->loadCart( );
	$this->loadParticipants( );
	$this->checkWaitingList( );
  }

  function setDefaultValues( )
  {
	require_once 'CRM/Contact/BAO/Contact.php';
	$defaults = array( );
	$params = array( 'id' => $this->getContactID() );
	$contact = CRM_Contact_BAO_Contact::retrieve( $params, $defaults );
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  $defaults[CRM_Event_BAO_MerParticipant::full_field_name( $event_in_cart, 0, 'email' )] = CRM_Event_BAO_MerParticipant::primary_email_from_contact( $contact );
	  $defaults[CRM_Event_BAO_MerParticipant::full_field_name( $event_in_cart, 0, 'first_name' )] = $contact->first_name;
	  $defaults[CRM_Event_BAO_MerParticipant::full_field_name( $event_in_cart, 0, 'last_name' )] = $contact->last_name;
	}
	return $defaults;
  }
}
