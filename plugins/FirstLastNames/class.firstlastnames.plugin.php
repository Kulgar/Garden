<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['FirstLastNames'] = array(
   'Description' => 'Adds required First Name and Last Name fields for users and allows them to be displayed in place of usernames',
   'Version' => '1.3.2',
   'Author' => "Jonathan Pautsch",
   'AuthorEmail' => 'themes@secondwindprojects.com',
   'AuthorUrl' => 'http://secondwindprojects.com/'
);

class FirstLastNames extends Gdn_Plugin
{
    public function Setup()
    {
		$this->Structure();
    }
	
	public function Structure()
    {
		if( C('Plugins.FirstLastNames.NickName') )
			Gdn::Structure()
			->Table('User')
			->Column('FirstName', 'varchar(50)')
			->Column('LastName', 'varchar(50)', NULL)
			->Set();
		else
			Gdn::Structure()
			->Table('User')
			->Column('FirstName', 'varchar(50)')
			->Column('LastName', 'varchar(50)')
			->Set();
    }
	
	public function EntryController_Render_Before($Sender,$Args)
	{
		if(strcasecmp($Sender->RequestMethod,'register')==0)
		{
			if(strcasecmp($Sender->View,'registerthanks')!=0 && strcasecmp($Sender->View,'registerclosed')!=0)
			{
				$RegistrationMethod = Gdn::Config('Garden.Registration.Method');
				$Sender->View = $this->GetView( 'register'.strtolower($RegistrationMethod).'.php');
			}
		}
    }
	
	public function UserInfoModule_OnBasicInfo_Handler($Sender)
	{
		if( C('Plugins.FirstLastNames.NickName') )
		{
			echo Wrap(T('Nickname'), 'dt');
			echo Wrap($Sender->User->FirstName, 'dd');
		}
		else
		{
			echo Wrap(T('First Name'), 'dt');
			echo Wrap($Sender->User->FirstName, 'dd');
			echo Wrap(T('Last Name'), 'dt');
			echo Wrap($Sender->User->LastName, 'dd');
		}
	}
	
	public function ProfileController_EditMyAccountAfter_Handler($Sender)
	{
		?>
		<li>
			<?php
			if( C('Plugins.FirstLastNames.NickName') )
				echo $Sender->Form->Label('Nickname', 'FirstName');
			else
				echo $Sender->Form->Label('First Name', 'FirstName');
				
			echo $Sender->Form->TextBox('FirstName');
			?>
		</li>
		<?php if( !C('Plugins.FirstLastNames.NickName') ) { ?>
		<li>
			<?php
			echo $Sender->Form->Label('Last Name', 'LastName');
			echo $Sender->Form->TextBox('LastName');
			?>
		</li>
		<?php }
	}
	
	public function UserController_AfterFormInputs_Handler($Sender)
	{
		?>
		<h3><?php echo T('FirstLastNames Options'); ?></h3>
		<ul>
			<li>
				<?php
				if( C('Plugins.FirstLastNames.NickName') )
					echo $Sender->Form->Label('Nickname', 'FirstName');
				else
					echo $Sender->Form->Label('First Name', 'FirstName');
					
				echo $Sender->Form->TextBox('FirstName');
				?>
			</li>
			<?php if( !C('Plugins.FirstLastNames.NickName') ) { ?>
			<li>
				<?php
				echo $Sender->Form->Label('Last Name', 'LastName');
				echo $Sender->Form->TextBox('LastName');
				?>
			</li>
			<?php } ?>
		</ul>
		<?php
	}
	
	public function UserController_Render_Before($Sender)
	{
		if(strcasecmp($Sender->RequestMethod,'add')==0)
		{
			$Sender->View = $this->GetView('add.php');
		}
	}
	
	public function DiscussionController_CommentInfo_Handler($Sender)
	{
		if( !C('Plugins.FirstLastNames.DisplayNames') || C('Plugins.FirstLastNames.HideRealUsername') )
			return;

		$Author = $Sender->EventArguments['Author'];
		echo '<span class="realusername"><a href="">@'.$Author->Name.'</a></span>';
	}
	
	public function PostController_CommentInfo_Handler($Sender)
	{
		if( !C('Plugins.FirstLastNames.DisplayNames') || C('Plugins.FirstLastNames.HideRealUsername') )
			return;

		$Author = $Sender->EventArguments['Author'];
		echo '<span class="realusername"><a href="">@'.$Author->Name.'</a></span>';
	}
	
	public function DiscussionController_Render_Before($Sender)
	{
		$this->PrepareController($Sender);
	}
   
	public function PostController_Render_Before($Sender)
	{
		$this->PrepareController($Sender);
	}
   
	protected function PrepareController($Sender)
	{
		$Sender->AddJsFile('firstlastnames.js', 'plugins/FirstLastNames');
	}
	
	public function Base_GetAppSettingsMenuItems_Handler($Sender)
	{
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddItem('Forum', T('Forum'));
		$Menu->AddLink('Forum', T('FirstLastNames'), 'settings/firstlastnames', 'Garden.Settings.Manage');
	}
	
	public function SettingsController_FirstLastNames_Create($Sender)
	{
		$Sender->Permission('Garden.Settings.Manage');
		$Sender->Title('FirstLastName Plugin Settings');
		$Sender->AddSideMenu('settings/firstlastnames');
		$Sender->Render('plugins/FirstLastNames/views/settings.php');
	}
	
	public function SettingsController_ToggleDisplayNames_Create($Sender)
	{
		$Sender->Permission('Garden.Settings.Manage');
		if (Gdn::Session()->ValidateTransientKey(GetValue(0, $Sender->RequestArgs)))
			SaveToConfig('Plugins.FirstLastNames.DisplayNames', C('Plugins.FirstLastNames.DisplayNames') ? FALSE : TRUE);
		 
		Redirect('settings/firstlastnames');
	}
	
	public function SettingsController_ToggleNickName_Create($Sender)
	{
		$Sender->Permission('Garden.Settings.Manage');
		if (Gdn::Session()->ValidateTransientKey(GetValue(0, $Sender->RequestArgs)))
			SaveToConfig('Plugins.FirstLastNames.NickName', C('Plugins.FirstLastNames.NickName') ? FALSE : TRUE);
			
		if( C('Plugins.FirstLastNames.NickName') )
			Gdn::Structure()
			->Table('User')
			->Column('FirstName', 'varchar(50)')
			->Column('LastName', 'varchar(50)', NULL)
			->Set();
		else
			Gdn::Structure()
			->Table('User')
			->Column('FirstName', 'varchar(50)')
			->Column('LastName', 'varchar(50)')
			->Set();
		 
		Redirect('settings/firstlastnames');
	}
	
	public function SettingsController_ToggleHideRealUsername_Create($Sender)
	{
		$Sender->Permission('Garden.Settings.Manage');
		if (Gdn::Session()->ValidateTransientKey(GetValue(0, $Sender->RequestArgs)))
			SaveToConfig('Plugins.FirstLastNames.HideRealUsername', C('Plugins.FirstLastNames.HideRealUsername') ? FALSE : TRUE);
		 
		Redirect('settings/firstlastnames');
	}
	
	public function OnDisable()
	{
		Gdn::Structure()
		->Table('User')
		->Column('FirstName', 'varchar(50)', NULL)
		->Column('LastName', 'varchar(50)', NULL)
		->Set();
	}
}

if (!function_exists('UserAnchor')) {
   function UserAnchor($User, $CssClass = '', $Options = NULL) {
      static $NameUnique = NULL;
      if ($NameUnique === NULL)
         $NameUnique = C('Garden.Registration.NameUnique');
      
      $Px = $Options;
      $Name = GetValue($Px.'Name', $User, T('Unknown'));
      $UserID = GetValue($Px.'UserID', $User, 0);
	  
	  if( C('Plugins.FirstLastNames.DisplayNames') )
	  {
		  $User = Gdn::SQL()->Select('FirstName, LastName')
			->From('User')
			->Where('UserID',$UserID)
			->Get()
			->FirstRow(DATASET_TYPE_ARRAY);
		  if ( !$User['FirstName'] || $User['FirstName'] == '' )
			$DisplayName = $Name;
		  else if( C('Plugins.FirstLastNames.NickName') )
			$DisplayName = $User['FirstName'];
		  else
			$DisplayName = $User['FirstName'] . " " . $User['LastName'];
	  }

      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';

      return '<a href="'.htmlspecialchars(Url('/profile/'.($NameUnique ? '' : "$UserID/").rawurlencode($Name))).'"'.$CssClass.'>'.htmlspecialchars($DisplayName ? $DisplayName : $Name).'</a>';
   }
}
	
?>