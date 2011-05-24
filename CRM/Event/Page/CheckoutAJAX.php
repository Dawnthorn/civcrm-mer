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

class CRM_Event_Page_CheckoutAJAX
{
  function add_participant_to_cart( )
  {
    require_once 'CRM/Event/BAO/EventInCart.php';
    require_once 'CRM/Event/BAO/MerParticipant.php';
    require_once 'CRM/Event/Form/Checkout/ParticipantsAndPrices.php';

    $index = $_GET['index'];
    $event_in_cart_id = $_GET['event_in_cart_id'];
    $session = CRM_Core_Session::singleton( );
    $template = CRM_Core_Smarty::singleton ();
    $event_in_cart = CRM_Event_BAO_EventInCart::find_by_id( $event_in_cart_id );

    $participant = new CRM_Event_BAO_MerParticipant( );
    $participant->index = $index;
    $form = new CRM_Core_Form( );
    $participant->load_fields( $form, $event_in_cart );
    $renderer = $form->getRenderer();
    $form->accept($renderer);
    $template->assign( 'event_in_cart', $event_in_cart );
    $template->assign( 'form', $renderer->toArray() );
    $template->assign( 'participant', $participant );
    $output = $template->fetch( "CRM/Event/Form/Checkout/Participant.tpl" );
    echo $output;
    CRM_Utils_System::civiExit( );
  }
}

