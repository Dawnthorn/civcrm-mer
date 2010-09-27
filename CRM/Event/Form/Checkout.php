<?php

class CRM_Event_Form_Checkout extends CRM_Core_Form
{
  public $_action;
  public $cart;
  public $contact;
  public $_mode;
  public $participants;

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
    $this->cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $this->cart->load_associations( );
  }

  function loadParticipants( )
  {
    $this->participants = array ( );
    if ( array_key_exists( "participant_0_email", $this->_submitValues ) ) {
      $participants_data = $this->_submitValues;
    } else {
      $participants_data = $this->getValuesForPage( 'Participants' );
    }
    foreach ( $participants_data as $key => $value ) {
      $matches = array();
      if ( preg_match( "/participant_(\d+)_email/", $key, $matches ) ) {
	if ( trim( $value ) == "" ) {
	  continue;
	}
	$participant = new CRM_Event_BAO_MerParticipant();
	$participant->index = $matches[1];
	$participant->load_values( $participants_data );
	$this->participants[] = $participant;
      }
    }
    if ( empty( $this->participants ) ) {
      $participant = new CRM_Event_BAO_MerParticipant();
      $participant->index = 0;
      $participant->contact_id = $this->getContactID( );
      $this->participants[] = $participant;
    }
  }

  function preProcess( )
  {
    $this->_action = CRM_Utils_Request::retrieve( 'action', 'String', $this, false );
    #    $this->_mode = ( $this->_action == 1024 ) ? 'test' : 'live';
    $this->_mode = 'test';
    $this->loadCart( );
    $this->loadParticipants( );
  }

  function setDefaultValues( )
  {
    require 'CRM/Contact/BAO/Contact.php';
    $defaults = array( );
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails( $this->getContactID() );
    $defaults["participant_0_email"] = $contact_details[1];
    return $defaults;
  }
}
