<?php

require_once 'CRM/Contribute/BAO/Contribution.php';
require_once 'CRM/Event/Form/Registration/ThankYou.php';

class CRM_Event_Form_Checkout_ThankYou extends CRM_Event_Form_Checkout
{
  function buildQuickForm( )
  {
    $contributionID = $this->get ('contributionID');
    $defaults = array( );
    $ids = array( );
    $params = array( 'id' => $contributionID );
    $contribution = CRM_Contribute_BAO_Contribution::retrieve( $params, $data, $ids );
    $this->assign( 'events_in_carts', $this->cart->events_in_carts );
    $this->assign( 'contribution', $contribution );
    $this->assign( 'participants', $this->participants );
  }
}
