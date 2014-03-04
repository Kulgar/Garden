<?php if (!defined('APPLICATION')) exit();

/**
 * BLTIConnect Plugin
 * 
 * Enables SingleSignOn (SSO) from BLTI compliant forums 
 *
 * @author Carlos Ors
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 * @since 1.0
 */
// Define the plugin:
$PluginInfo['BLTIConnect'] = array(
   'Name' => 'Vanilla BLTIConnectPlugin',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => '/dashboard/authentication/blti',   
   'Description' => 'BLTI Provider plugin.',
   'Version' => '1.1',
   'Author' => "Carlos Ors",
   'AuthorEmail' => 'corsg@uoc.edu',
   'AuthorUrl' => 'http://learningtechnologies.uoc.edu/carles-ors/'   
   
);

$Configuration['Garden']['Authenticator']['DefaultScheme'] = 'blti';

$lticonfiguration=NULL;

if (!class_exists("bltiUocWrapper")) {
	require_once dirname(__FILE__).'/IMSBasicLTI/uoc-blti/bltiUocWrapper.php';
	require_once dirname(__FILE__).'/IMSBasicLTI/ims-blti/blti_util.php';
	require_once dirname(__FILE__).'/IMSBasicLTI/utils/UtilsPropertiesBLTI.php';
}

define (GUEST_PREFIX,'guest_');
define (MODERATOR_PREFIX,'moderator_');
define (MEMBER_PREFIX,'member_');


class BLTIConnectPlugin extends Gdn_Plugin {
   
   public function __construct() {
      parent::__construct();
      
      // 2.0.18+
      // Ensure that when BLTIConnect is turned on, we always have its SearchPath indexed
      try {
         $BLTIConnectSearchPathName = 'BLTIConnect RIMs';
         $CustomSearchPaths = Gdn::PluginManager()->SearchPaths(TRUE);

         if (!in_array($BLTIConnectSearchPathName, $CustomSearchPaths)) {
            $InternalPluginFolder = $this->GetResource('internal');
            Gdn::PluginManager()->AddSearchPath($InternalPluginFolder, 'BLTIConnect RIMs');
         }
      } catch (Exception $e) {}
   }



   public function SettingsController_BLTIConnect_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('BLTI Connect SSO');
		  $Sender->Form = new Gdn_Form();
      
      $this->Provider = $this->LoadProviderData($Sender);
 		  $this->EnableSlicing($Sender);
		  $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
   *  Handle request for configure URL
   * 
   * When the user loads Dashboard/Authentication, the list of currently enabled authenticators is polled for 
   * each of their configuration URLs. This handles that polling request and responds with the subcontroller
   * URL that loads BLTIConnect's config window.
   * 
   * @param mixed $Sender
   */
   public function AuthenticationController_AuthenticatorConfigurationBLTI_Handler($Sender) {
      $Sender->AuthenticatorConfigure = '/dashboard/settings/blticonnect';
   }
   
   
   /*
   * guarda la configuración que hay en la pantalla de configuracion del BLTIAutenticatior en disco */
  /* 
   public function Controller_Index($Sender) {
   	
     $key=$Sender->Form->GetValue('lti_key', '');
     $secret=$Sender->Form->GetValue('lti_secret', '');
     $wrapp=new bltiUocWrapper(false, false);
     $conf=$wrapp->configuration;
     $wrapp->configuration_file;
     //$defPropFile=(dirname(__FILE__).'/../configuration/authorizedConsumersKey2.cfg'
     //$propsToUpdate=new UtilsPropertiesBLTI();
     $conf->setProperty('consumer_key.'.$key.'.secret',$secret);

     $conf->store($wrapp->configuration_file);
   	 $Sender->Render('blticonnect','','plugins/BLTIConnect');
   }
   */
   /*
   public function DiscussionsController_Index($Sender) {
   	echo "hola";
   	 EntryController_SignIn_Handler($Sender);
   }
   */
   ///
   public function LoadProviderData($Sender) {
      $Authenticator = Gdn::Authenticator()->GetAuthenticator('blti');
      $Provider = $Authenticator->GetProvider();
      
      if (!$Provider) {
         $Provider = $this->CreateProviderModel();
      }
      
      $Sender->SetData('Provider', $Provider);
      return ($Provider) ? $Provider : NULL;
   }
   

   public function EntryController_SignIn_Handler($Sender) {
      if (!Gdn::Authenticator()->IsPrimary('blti')) return;
      //$AllowCallout = !Gdn::Request()->GetValue('Landing', FALSE);

      $context = new bltiUocWrapper(false, false);

      if ( $context->valid ) {
       	// Force user to be logged out of Vanilla
 
          $Authenticator = Gdn::Authenticator()->GetAuthenticator('blti');
      	  $Authenticator->SetIdentity(NULL);
          $this->SigninLoopback($Sender,TRUE,$context);          
          //Gdn::Session()->SetPreference("prefs",array('bltiinfo'=>$context->info ));
          // Forget that this happened (user can start fresh)
          //$Authenticator->DeleteCookie();
          //cogemos como target el resource_lintk_title
          //$foroUrl=$foroUrl.'&Target='.'discussion%2F2%2Fpaso-de-informacion';
       }
       else
       {
       	$Message = T('Invalid BLTI request. Use <a href="./password">/entry/password</a> to log in manually<br>');
       	Gdn::Locale()->SetTranslation('PermissionErrorMessage',$Message);
       	throw new Exception($Message,401);
 
       }
      
   }
    
   
   protected function SigninLoopback($Sender, $AllowCallout = TRUE, $context) {
      
      //if (!Gdn::Authenticator()->IsPrimary('blti')) return;

      $Authenticator = Gdn::Authenticator()->GetAuthenticator('blti');
      //$RealUserID=-1;

      //$AuthResponse = $Authenticator->Authenticate();
       $foroUrl=Url(Gdn::Request()->GetValue('Target'),TRUE);
       $goToCategory=Gdn::Request()->GetValue('custom_goToCategory',0);
       //$foroUrl=$foroUrl.'&Target='.'discussion%2F2%2Fpaso-de-informacion';
      //Redirect(Url(Gdn::Request()->GetValue('Target'),TRUE),302);

       //$lticonfiguration=$context->configuration;    

      if ($RealUserID == -1) {
      	  echo 'Unknown error<br>';
      } else {
         if ($RealUserID) {
            // The user is already signed in. Send them to the default page.
            //no debería entrar por aquí
         	Redirect($foroUrl, 302);
         } else {
  
            
            //$UserName=$this->blti_get_username($context);//SSO_PREFIX.'user12';
            
            $UserKey=$context->getUserKey();
         	$Email = $context->getUserEmail();//'user12@local.com';
      
            //$user=Gdn::Authenticator()->GetUserModel()->GetByUsername($Email);         
          
            //create or update the 3 roles
            
            $UserID = $Authenticator->Authenticate($UserKey,'',TRUE);
            if ($UserID>0) 
            {

            	// create category if necessary
            	//echo 'Create the category<br>';
            	$categId=$this->createCategoryAndRolesIfNecessary($context);
            	//actualiza los roles
            	$this->updateUserInformation($UserID,$context);
            	$this->updateUserRolesIfNecessary($UserID, $context);
            	
                $goToCategory=$context->info['custom_gotocategory'];
            	if ($goToCategory)
                {
                  $categUrlCode=$this->getCourseCodeFromBLTIContext($context); 	
                  $foroUrl='/categories/'.$categUrlCode;
                }
                else
                {
            	//cogemos como target el resource_link_title
            	$entryTitle=$context->getResourceTitle();
   	            $givenEntryId=$context->info['resource_link_id'];
                $discussId=$this->createDiscussionIfNecessary($UserKey,$categId,$entryTitle,$givenEntryId);
                //$foroUrl=$foroUrl.'&Target='.$entryTitle;
                //$foroUrl=$foroUrl.'&Target='.'discussion%2F2%2Fpaso-de-informacion';
                //$foroUrl=$foroUrl.'&p=/discussion/'.$discussId.'/'.Gdn_Format::Url($entryTitle);
                $foroUrl='/discussion/'.$discussId.'/'.Gdn_Format::Url($entryTitle);
                	
                }
                Redirect($foroUrl, 302);
            }
            else
            {              	
            	//create the user
            	//echo 'Create the user<br>';
                $this->createCategoryAndRolesIfNecessary($context); 
              
                if ($Email)
                {
	            	$UserID=$this->createUser($context);
	            	if ($UserID){
	            	   $this->SigninLoopback($Sender, $AllowCallout, $context);
	            	}
	            	else
	            	{
	            	  //Redirect('/index.php?p=/entry/password',302);
	
	       	          echo 'SSO could not create automatically the user in Vanilla. Reasons:<br>';
	            	  var_dump(Gdn::Authenticator()->GetUserModel()->Validation->Results());
	            	  //$Sender->Render('/views/errorPage.php');
	
	            	}
                	
                }
                else
                {
                    // We ask for the email
	                $Sender->View="../../../../plugins/BLTIConnect/views/entry/emailRequest";
                    session_start();
	                $_SESSION['blticontext']=serialize($context);
	                $Sender->render();
	                //$Sender->Render('emailRequest', '', 'plugins/BLTIConnect');
	                
                }
            }

         }
      }
      exit();
   }


   public function EntryController_ValidateEmailEntered_Create(&$Sender) {
   	  // Validate email entered

   	  $um=new UserModel();
   	  $email=$Sender->Form->GetValue('Email', '');
   	  $user=$um->GetByEmail($email);
      if ($user)
      {
   	  //if email used previosly, show the form again      	
   	    $Sender->View="../../../../plugins/BLTIConnect/views/entry/emailRequest";
	    $msgEmailExist='The email you entered is in use by another member.';
   	    $Sender->SetData('msg', $msgEmailExist);
	    
   	    //$Sender->InformMessage($msgEmailExist, 'Dismissable HasIcon'); // Send to the screen
	    //$Sender->InformMessage('<span class="Errors"></span> This is a test!', 'Dismissable HasSprite');
   	    $Sender->render();      	
      }
      else
      {
   	    //if email is not used previosly, continue SSO registration process
      	session_start();
   	    //echo $email;
   	    
      	$context=unserialize($_SESSION['blticontext']);
      	$context->info['lis_person_contact_emailprimary']=$email;
      	$_SESSION['blticontext']=serialize($context);
      	//$context=unserialize($_SESSION['blticontext']);
      	$UserKey=$context->getUserKey();
        $UserID=$this->createUser($context);
        if ($UserID){
           $this->SigninLoopback($Sender, TRUE, $context);
        }
        else
        {
          echo 'SSO could not create automatically the user in Vanilla. Reasons:<br>';
          var_dump(Gdn::Authenticator()->GetUserModel()->Validation->Results());
              //$Sender->Render('/views/errorPage.php');

            }
      	//var_dump($context);
      	
      }	  
	  
   	  //var_dump($context);

 
   }
   
   
   
   function blti_get_username($context) {
	
	if ($context->info['custom_username']) {
	  $username = $context->info['custom_username'];
	  $username = $context->info['tool_consumer_instance_guid'].'_'.$username;
	
	} else {	
	  $username = $context->getUserKey();
	}
	
	 $username = str_replace(':','_',$username);  // TO make it past sanitize_user	
	return $username;
	
	}
	   

   function getCourseCodeFromBLTIContext($bltiContext)
   {
   	return str_replace(' ','-',$bltiContext->getCourseName());
   }
	
   
   function createCategoryAndRolesIfNecessary($bltiContext)
   {	
   	  $catCode=$this->getCourseCodeFromBLTIContext($bltiContext);
      if (!$catCode)
      {
      	//ob_start();

      	
      	//echo 'LTI context not available but needed. Proceess aborted.<br>';
      	$launch_presentation_return_url=$bltiContext->info['launch_presentation_return_url'];
      	if ($launch_presentation_return_url)
      	{
      	 header("Refresh: 4;URL=".$launch_presentation_return_url);
      	}
         $Message = T('LTI context not available but needed. Process aborted.<br>');
         Gdn::Locale()->SetTranslation('PermissionErrorMessage',$Message);
         throw new Exception($Message,401);
      	exit();
      }
   	  $CategoryModel = new CategoryModel();
   	  $categ=$CategoryModel->GetByCode($catCode);
   	  if (!$categ)
      {
   	  	$CategData['CategoryID']=$categ->CategoryID;
	   	$CategData['CodeIsDefined']="0";
	   	$CategData['Name']=$bltiContext->getCourseName();
	   	  //$CategData['UrlCode']=str_replace(' ','-',$CategData['Name']);
	   	$CategData['UrlCode']=$catCode;
	   	$CategData['Description']=$CategData['Name'];
	   	$CategData['CustomPermissions']="1";
	   	$CategData['Save']="Save";
	   	$CategData['AllowDiscussions']="1";
	   	$CategData['InsertUserID']="1";
	   	$CategData['UpdateUserID']="1"; 	  
	   	$CategData['Permission']=array();
      	$CategID=$CategoryModel->Save($CategData);
   	    
      	//role is created the first time when category did not exist
        $role_guest_categ_id=$this->createRole(GUEST_PREFIX,$CategData['UrlCode'],$CategID);
        $role_member_categ_id=$this->createRole(MEMBER_PREFIX,$CategData['UrlCode'],$CategID);
        $role_moderator_categ_id=$this->createRole(MODERATOR_PREFIX,$CategData['UrlCode'],$CategID);  
        return $CategID;
      }
   	  else
   	  {
   	  	return $categ->CategoryID;
   	  }
   	  
   }

   
   function createDiscussionIfNecessary($username,$categid,$entryTitle,$givenEntryId)
   {
   	  
   	  $userModel=new UserModel();
   	  //$username=$this->blti_get_username($bltiContext);
   	  $userData=$userModel->GetByUsername($username);

   	  if (!$entryTitle)
   	  {
   	  	$entryTitle=$givenEntryId;
   	  }
   	  
   	  
   	  $discussionModel=new DiscussionModel();
   	  //$discussionModel->GetID()
      $olddiscussId=$discussionModel->getDiscussionIdFromPlatformDiscussionID($givenEntryId);
      
      if ($olddiscussId)
      {
      	$discuss['DiscussionID']=$olddiscussId;
      }
      else
      {
        $discuss['InsertUserID']=$userData->UserID;	
      }
      
      
   	  $discuss['DraftID']=0;
   	  
   	  $discuss['UpdateUserID']=$userData->UserID;
   	  $discuss['Name']=$entryTitle;
   	  //$discuss['Name']='valorhardcoded';
   	  $discuss['Body']=$entryTitle;
   	  $discuss['CategoryID']=$categid;
   	  $discuss['Post_Discussion']='Post Discussion';
   	  //$discuss['Announce']=false;
   	  //$discuss['closed']=false;
   	  //$discuss['foreignID']=$givenEntryId;
   	  $discussId=$discussionModel->Save($discuss);

   	  if (!$olddiscussId)
   	  {
	    $discussionModel->SetProperty($discussId,'PlatformDiscussionID',$givenEntryId); 	
   	  }  
   	  return $discussId;
   	     	
   }   
   
   

   
   function createRole($rolePrefix,$categCode,$categID)
   {
   	  $i=0;
   	  $roleModel=new RoleModel();
   	  
   	  $roleData['Name']=$rolePrefix.$categCode;
   	  $roleData['Description']=$rolePrefix.$categCode;
   	  $roleData['Save']='Save';

   	  //if guest

   	  $roleData['Permission'][$i++]='Garden.Activity.View';
   	  $roleData['Permission'][$i++]='Garden.Profiles.View';
  	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/-1//Vanilla.Discussions.View';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.View';   	  

   	  //if member
   	  if ($rolePrefix === MEMBER_PREFIX || $rolePrefix === MODERATOR_PREFIX) {
   	  $roleData['Permission'][$i++]='Garden.Profiles.Edit';
   	  $roleData['Permission'][$i++]='Garden.SignIn.Allow';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/-1//Vanilla.Comments.Add';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/-1//Vanilla.Discussions.Add';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Comments.Add';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Add';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.View';
   	  }
   	  //if moderator	 
   	  if ($rolePrefix === MODERATOR_PREFIX) { 
 	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Comments.Delete';
   	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Comments.Edit';
	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Announce';
	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Close';
	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Delete';
	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Edit';
	  $roleData['Permission'][$i++]='Category/PermissionCategoryID/'.$categID.'//Vanilla.Discussions.Sink';
   	  }
  	   	 
   	  $roleId=$roleModel->Save($roleData);

   	  return $roleId;   	
   }
   
   function updateUserInformation($userid,$bltiContext)
   {
   	   $UserData['UserID']=$userid;
	   $UserData['Name'] = $bltiContext->getUserKey();
	   $UserData['Password'] = rand();  //se requiere password para crear el usuario
	   $UserData['Email'] = $bltiContext->getUserEmail();

	   $tmpGender=strtolower($bltiContext->info['custom_user_gender']);
	   $UserData['Gender'] = $tmpGender?$tmpGender:"m";
	   $UserData['HourOffset'] = "0";
	   $UserData['DateOfBirth'] = "";
	   $UserData['CountNotifications'] = "0";	   

       $UserData['Photo']=$bltiContext->getUserImage();
	   $UserData['RoleID'] = C('Garden.Registration.DefaultRoles');
	   
	   $categCode=$this->getCourseCodeFromBLTIContext($bltiContext);   	
	   
	   $UserModel = Gdn::Authenticator()->GetUserModel();//new UserModel();
	   $UserID = $UserModel->Save($UserData, array('ActivityType' => 'Join', 'CheckExisting' => TRUE, 'NoConfirmEmail' => TRUE, 'SaveRoles'=>FALSE));	   
   }
   
   
   function updateUserRolesIfNecessary($userid,$bltiContext)
   {
   	$UserModel = Gdn::Authenticator()->GetUserModel();
   	
    $roleModel=new RoleModel();
    $categCode=$this->getCourseCodeFromBLTIContext($bltiContext);
	 //$rolesDataSet=$UserModel->GetRoles($userid);
	 //$roles=$rolesDataSet->_Result;  
	   if ($this->isAdmin($bltiContext))
	   {
        $roleName=MODERATOR_PREFIX.$categCode;
	   	$role = $roleModel->getRoleIdFromCode($roleName); 
	   }elseif ($this->isTeacher($bltiContext))
	   {
        $roleName=MODERATOR_PREFIX.$categCode;
	   	$role = $roleModel->getRoleIdFromCode($roleName); 
	   	
	   }elseif ($this->isStudent($bltiContext))
	   {
        $roleName=MEMBER_PREFIX.$categCode;
	   	$role = $roleModel->getRoleIdFromCode($roleName); 
	   	
	   }else  // guest or other
	   {
        $roleName=GUEST_PREFIX.$categCode;
	   	$role = $roleModel->getRoleIdFromCode($roleName); 
	   }
	   
	   //obtener los roles actuales del usuario
	   $currentUserRoles=$roleModel->getRolesFromUser($userid);
	   foreach ($currentUserRoles as $roleObj) 
	   {
	   	 $rolesUpdated[]=$roleObj->RoleID;
	   	 
	   }
	   
	   if (!in_array($role,$rolesUpdated))
	   {
	   
		   // añadir el nuevo
		   $rolesUpdated[]=$role;
	   
		   //quitar repetidos
		   //$rolesUpdated = array_unique($rolesUpdated);
		   //var_dump($rolesUpdated);
		   //actualizar roles del usuario
		   $UserModel->SaveRoles($userid, $rolesUpdated);
		   //echo 'es nuevo rol';
	   }
	   
             
	   // Save the user.
        //$UserModel->SaveRoles($userid,$roles);
	   //$UserID = $UserModel->Save($UserData, array('ActivityType' => 'Join', 'CheckExisting' => TRUE, 'NoConfirmEmail' => TRUE, 'SaveRoles'=>TRUE));   	

   }
   
   function createUser($bltiContext)
   {
	   $UserData['Name'] = $bltiContext->getUserKey();
	   $UserData['Password'] = rand();  //se requiere password para crear el usuario
	   $UserData['Email'] = $bltiContext->getUserEmail();
	   $tmpGender=strtolower($bltiContext->info['custom_user_gender']);
	   $UserData['Gender'] = $tmpGender?$tmpGender:"m";
	   $UserData['HourOffset'] = "0";
	   $UserData['DateOfBirth'] = "";
	   $UserData['CountNotifications'] = "0";	   

       $UserData['Photo']=$bltiContext->getUserImage();
	   $UserData['RoleID'] = C('Garden.Registration.DefaultRoles');
	   
	   $categCode=$this->getCourseCodeFromBLTIContext($bltiContext);
	  
	   // roles management
	   
	   $roleModel=new RoleModel();
	   
	   if ($this->isAdmin($bltiContext))
	   {
        $roleName=MODERATOR_PREFIX.$categCode;
	   	$UserData['RoleID'][] = $roleModel->getRoleIdFromCode($roleName); 
	   }elseif ($this->isTeacher($bltiContext))
	   {
        $roleName=MODERATOR_PREFIX.$categCode;
	   	$UserData['RoleID'][] = $roleModel->getRoleIdFromCode($roleName); 
	   	
	   }elseif ($this->isStudent($bltiContext))
	   {
        $roleName=MEMBER_PREFIX.$categCode;
	   	$UserData['RoleID'][] = $roleModel->getRoleIdFromCode($roleName); 
	   	
	   }else  // guest or other
	   {
        $roleName=GUEST_PREFIX.$categCode;
	   	$UserData['RoleID'][] = $roleModel->getRoleIdFromCode($roleName); 
	   }
	   
             
	   //$UserData['Attributes'] = Gdn_Format::Serialize($Attributes);
	   // Save the user.
	   $UserModel = Gdn::Authenticator()->GetUserModel();//new UserModel();
	   $UserID = $UserModel->Save($UserData, array('ActivityType' => 'Join', 'CheckExisting' => TRUE, 'NoConfirmEmail' => TRUE, 'SaveRoles'=>TRUE));
	
	   
	   // Add the user to the default role(s).
	   /*
	   if ($UserID) {
	   	//crear group
	     $UserModel->SaveRoles($UserID, C('Garden.Registration.DefaultRoles'));
	   }*/
	   return $UserID;
   }
   

   function isAdmin($bltictx)
   {
   	return $this->isTeacher($bltictx);
   }
   
   function isTeacher($bltictx)
   {
   	 return $bltictx->isInstructor();
   }

   function isStudent($bltictx)
   {
        $roles = $bltictx->info['roles'];
        $roles = strtolower($roles);
        if ( ! ( strpos($roles,"learner") === false ) ) return true;
        return false;   	 
   }

   
   
   public function Setup() {
   	    Gdn::Structure()
        ->Table('Discussion')
        ->Column('PlatformDiscussionID','varchar(30)',NULL)
        ->Set();
     /*
   	 $NumLookupMethods = 0;
		
		if (function_exists('fsockopen')) $NumLookupMethods++;
		if (function_exists('curl_init')) $NumLookupMethods++;

		if (!$NumLookupMethods)
		   throw new Exception(T("Unable to initialize plugin: required connectivity libraries not found, need either 'fsockopen' or 'curl'."));
      */
      $this->_Enable(TRUE);
   }
   
   public function OnDisable() {
		$this->_Disable();
		
		Gdn::Authenticator()->DisableAuthenticationScheme('blti');
		
		RemoveFromConfig('Garden.Authenticators.blti.Name');
      RemoveFromConfig('Garden.Authenticators.blti.CookieName');
   }

   
   public function CreateProviderModel() {
      $Key = 'k'.sha1(implode('.',array(
         'blticonnect',
         'key',
         microtime(true),
         RandomString(16),
         Gdn::Session()->User->Name
      )));
      
      $Secret = 's'.sha1(implode('.',array(
         'blticonnect',
         'secret',
         md5(microtime(true)),
         RandomString(16),
         Gdn::Session()->User->Name
      )));
      
      $ProviderModel = new Gdn_AuthenticationProviderModel();
      $Inserted = $ProviderModel->Insert($Provider = array(
         'AuthenticationKey'           => $Key,
         'AuthenticationSchemeAlias'   => 'blti',
         'AssociationSecret'           => $Secret,
         'AssociationHashMethod'       => 'HMAC-SHA1'
      ));
      
      return ($Inserted !== FALSE) ? $Provider : FALSE;
   }
   
   public function AuthenticationController_DisableAuthenticatorBLTI_Handler(&$Sender) {
      $this->_Disable();
   }
   
   private function _Disable() {
      RemoveFromConfig('Plugins.BLTIConnect.Enabled');
		
		$WasEnabled = Gdn::Authenticator()->UnsetDefaultAuthenticator('blti');
      if ($WasEnabled)
         RemoveFromConfig('Garden.SignIn.Popup');
         
      $InternalPluginFolder = $this->GetResource('internal');
      // 2.0.18+
      try {
         Gdn::PluginManager()->RemoveSearchPath($InternalPluginFolder);
      } catch (Exception $e) {}
   }
	
   public function AuthenticationController_EnableAuthenticatorBLTI_Handler(&$Sender) {
      $this->_Enable();
   }
	
	private function _Enable($FullEnable = TRUE) {
	  SaveToConfig('Garden.Authenticators.blti.Name', 'BLTIConnect');
      SaveToConfig('Garden.Authenticators.blti.CookieName', 'VanillaBLTI');
      
      $InternalPluginFolder = $this->GetResource('internal');
      // 2.0.18+
      try {
         Gdn::PluginManager()->AddSearchPath($InternalPluginFolder, 'BLTIConnect RIMs');
      } catch (Exception $e) {echo 'error en enable';}
      
      if ($FullEnable) {
         SaveToConfig('Garden.SignIn.Popup', FALSE);
         SaveToConfig('Plugins.BLTIConnect.Enabled', TRUE);
      }
      Gdn::Authenticator()->EnableAuthenticationScheme('blti', $FullEnable);
      
      // Create a provider key/secret pair if needed
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', 'blti')
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$Provider)
         $this->CreateProviderModel();
	}  
	
	

	public function UserModel_ValidateCredentialsNoPasswd_Create($Sender) {

	  $Email= $Sender->EventArguments[0];
	   
	  $Sender->EventArguments['Credentials'] = array('Email'=>$Email, 'ID'=>$ID, 'Password'=>$Password);
      $Sender->FireEvent('BeforeValidateCredentials');

      if (!$Email && !$ID)
         throw new Exception('The email or id is required');

		try {
			$Sender->SQL->Select('UserID, Name, Attributes, Admin, Password, HashMethod, Deleted, Banned')
				->From('User');
	
			if ($ID) {
				$Sender->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$Sender->SQL->Where('Email', $Email);
				} else {
					$Sender->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $Sender->SQL->Get();
		} catch(Exception $Ex) {
         $Sender->SQL->Reset();
         
			// Try getting the user information without the new fields.
			$Sender->SQL->Select('UserID, Name, Attributes, Admin, Password')
				->From('User');
	
			if ($ID) {
				$Sender->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$Sender->SQL->Where('Email', $Email);
				} else {
					$Sender->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		}
		
      if ($DataSet->NumRows() < 1)
         return FALSE;

      $UserData = $DataSet->FirstRow();
		// Check for a deleted user.
		if(GetValue('Deleted', $UserData))
			return FALSE;
		
		
      $UserData->Attributes = Gdn_Format::Unserialize($UserData->Attributes);
      return $UserData;
	   
	   
	   exit();
	}
	
   /**
    * Obtain el roleId from the name	
    * @param $sender
    * @return $RoleId
    */
   public function RoleModel_getRoleIdFromCode_create($sender)
   {
   	 $roleCode= $sender->EventArguments[0];
   	 
   	 $dataset=$sender->SQL->Select('RoleID')
         ->From('Role')
         ->Where('Name', $roleCode)
         ->Get()->FirstRow();
   	 return $dataset->RoleID;
   	 //$dataset=GetValue('Deleted', $UserData
   }

   /**
    * Obtain the roles list from a user
    * @param $sender
    * @return array of Std class
    */
   public function RoleModel_getRolesFromUser_create($sender)
   {
   	  $userid=$sender->EventArguments[0];
   	      $UserRoles = Gdn::SQL()
         ->Select('RoleID')
         ->From('UserRole')
         ->Where('UserID', $userid)
         ->Get()->Result();
         return $UserRoles;
   }
   
   
   /**
    * Obtain the DiscussionId from the name
    * @param $sender
    * @return $DiscussionId
    */
   public function DiscussionModel_getDiscussionIdFromName_create($sender)
   {
   	  $discussName=$sender->EventArguments[0];
   	  $dataset=$sender->SQL->Select('DiscussionID')
         ->From('Discussion')
         ->Where('Name', $discussName)
         ->Get()->FirstRow();
   	 return $dataset->DiscussionID;
   	   	  /*
   	        // Get CategoryID of this discussion
            $Data = $this->SQL
               ->Select('CategoryID')
               ->From('Discussion')
               ->Where('DiscussionID', $DiscussionID)
               ->Get();
               */
   }
   
   /*
    * Obtain the DiscussionId from the foreignId 
    * @param $sender
    * @return DiscussionId
    */
   public function DiscussionModel_getDiscussionIdFromPlatformDiscussionID_create($sender)
   {
   	  $foreignId=$sender->EventArguments[0];
   	  $dataset=$sender->SQL->Select('DiscussionID')
         ->From('Discussion')
         ->Where('PlatformDiscussionID', $foreignId)
         ->Get()->FirstRow();
   	 return $dataset->DiscussionID;
   }
	
}
