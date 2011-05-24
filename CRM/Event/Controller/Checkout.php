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
