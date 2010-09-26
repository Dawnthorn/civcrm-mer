<?php

class CRM_Event_Page_CheckoutAJAX
{
  function add_participant_to_cart( )
  {
    require_once 'CRM/Event/BAO/MerParticipant.php';
    require_once 'CRM/Event/Form/Checkout/Participants.php';

    $index = $_GET['index'];
    $session = CRM_Core_Session::singleton( );
    $template = CRM_Core_Smarty::singleton ();
    $participant = new CRM_Event_BAO_MerParticipant( );
    $participant->index = $index;
    $form = new CRM_Core_Form( );
    $participant->load_fields( $form );
    $renderer = $form->getRenderer();
    $form->accept($renderer);
    $template->assign( 'form', $renderer->toArray() );
    $template->assign( 'participant', $participant );
    $output = $template->fetch( "CRM/Event/Form/Checkout/Participant.tpl" );
    echo $output;
    CRM_Utils_System::civiExit( );
  }
}

