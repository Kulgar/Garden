<?php if (!defined('APPLICATION')) exit(); ?>
<?php $Session = Gdn::Session(); ?>

<h1><?php echo T("Email is required to continue the SSO process in Vanilla") ?></h1>
<div class="Box">
   <?php
   // Make sure to force this form to post to the correct place in case the view is
   // rendered within another view (ie. /dashboard/entry/index/):
   echo $this->Form->Open(array('Action' => Url('/entry/ValidateEmailEntered'), 'id' => 'Form_Email_Request'));
   echo $this->Form->Errors(); 
   $msg=$this->Data('msg');
   ?>
   
 
   <ul>
      <li>
         <?php
            if ($msg) echo '&nbsp;&nbsp;<div class="Errors"><ul><li>'.$msg.'</li></ul></div>';
            echo $this->Form->Label('Enter your email address', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Continue');
           

         ?>
         
      </li>
   </ul>

   <?php echo $this->Form->Close(); ?>
</div>