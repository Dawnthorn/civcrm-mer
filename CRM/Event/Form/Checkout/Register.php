<?php

require_once 'CRM/Event/Form/Registration/Register.php';

class CRM_Event_Form_Checkout_Register extends CRM_Event_Form_Registration_Register
{
  public $cart;
  public $event_in_cart;
  public $event_in_cart_id;

  function preProcess( )
  {
    require_once 'CRM/Event/BAO/Cart.php';
    $page_name = $this->getAttribute('name');
    if ( preg_match( '/Register_(\d+)/', $page_name, $matches ) > 0 ) {
      $this->event_in_cart_id = $matches[1];
    } else {
      CRM_Core_Error::fatal( ts( 'The name of this page is \'%1\' which doesn\'t match the pattern Register_(\d+)', $page_name ) );
    }
    $this->cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $this->cart->load_associations();
    $this->event_in_cart = CRM_Event_BAO_EventInCart::find_by_id( $this->event_in_cart_id );
    $this->event_in_cart->load_associations();
    $this->set( 'id', $this->event_in_cart->event->id );
    parent::preProcess( );
  }

  function postProcess( )
  {
    parent::postProcess( );
  }
}
