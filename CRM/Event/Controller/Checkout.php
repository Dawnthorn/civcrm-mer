<?php

require_once 'CRM/Event/Controller/Registration.php';

class CRM_Event_Controller_Checkout extends CRM_Core_Controller 
{
  function __construct( $title = null, $action = CRM_Core_Action::NONE, $modal = true ) 
  {
    parent::__construct( $title, $modal );

    require_once 'CRM/Event/StateMachine/Checkout.php';

    $this->_stateMachine = new CRM_Event_StateMachine_Checkout( $this, $action );
    $this->addPages( $this->_stateMachine, $action );
    $config = CRM_Core_Config::singleton( );

    //changes for custom data type File
    $uploadNames = $this->get( 'uploadNames' );
    if ( is_array( $uploadNames ) && ! empty ( $uploadNames ) ) {
      $this->addActions( $config->customFileUploadDir, $uploadNames );
    } else {
      // add all the actions
      $this->addActions( );
    }
  }
}
