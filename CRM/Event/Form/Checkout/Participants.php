<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Event/BAO/Cart.php';
require_once 'CRM/Event/BAO/MerParticipant.php';
require_once 'CRM/Event/Form/Checkout.php';

class CRM_Event_Form_Checkout_Participants extends CRM_Event_Form_Checkout
{
  function buildQuickForm( )
  {
    foreach ( $this->participants as $participant ) {
      $participant->load_fields( $this );
    }
    $this->assign( 'participants', $this->participants );

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
