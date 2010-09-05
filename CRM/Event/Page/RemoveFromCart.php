<?php

require_once 'CRM/Core/Page.php';

class CRM_Event_Page_RemoveFromCart extends CRM_Core_Page {
  function run( ) {
    $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this, true );
    require_once 'CRM/Event/BAO/Cart.php';
    $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $removed_event_in_cart = $cart->remove_event_in_cart( $this->_id );
    CRM_Core_Session::setStatus( ts("%1 has been removed from your cart.", array( 1 => $removed_event_in_cart->event->title ) ) );
    return CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/event/view_cart') );
  }
}

?>
