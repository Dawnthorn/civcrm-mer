<?php

class CRM_Event_Form_Checkout extends CRM_Core_Form
{
  public $_action;
  public $cart;
  public $participants;

  function loadCart ( )
  {
    $this->cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $this->cart->load_associations( );
  }

  function loadParticipants( )
  {
    $this->participants = array ( );
    $index = 0;
    while (1) {
      if ( !array_key_exists( "participant_{$index}_email", $this->_submitValues ) ) {
	break;
      }
      $participant = new CRM_Event_BAO_MerParticipant();
      $participant->index = $index;
      $this->participants[] = $participant;
      $index += 1;
    }
    if ( empty($this->participants) ) {
      $participant = new CRM_Event_BAO_MerParticipant();
      $participant->index = 0;
      $this->participants[] = $participant;
    }
  }

  function preProcess( )
  {
    $this->_action = CRM_Utils_Request::retrieve( 'action', 'String', $this, false );
    $this->loadCart( );
    $this->loadParticipants( );
  }
}
