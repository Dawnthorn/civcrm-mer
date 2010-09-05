<?php

require_once 'CRM/Core/Page.php';

class CRM_Event_Page_ViewCart extends CRM_Core_Page 
{
  function run( ) {
    require_once 'CRM/Event/BAO/Cart.php';
    $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $cart->load_associations();
    $this->assign_by_ref('events_in_carts', $cart->events_in_carts);
    return parent::run();
  }
}

?>