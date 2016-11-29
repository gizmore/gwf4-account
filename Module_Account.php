<?php
/**
 * Member Account Changes.
 * @author gizmore
 * @license MIT
 */
final class Module_Account extends GWF_Module
{
	##################
	### GWF_Module ###
	##################
	public function getVersion() { return 4.01; }
	public function onLoadLanguage() { return $this->loadLanguage('lang/account'); }
	public function onCronjob() { require_once 'GWF_AccountCronjob.php'; GWF_AccountCronjob::onCronjob($this); }
	public function getClasses() { return array('GWF_AccountChange', 'GWF_AccountDelete', 'GWF_AccountAccess'); }
	public function getDescription() { return 'Change account settings. Delete Account'; }
	public function getDefaultAutoLoad() { return true; }

	###############
	### Install ###
	###############
	public function onInstall($dropTable)
	{
		return GWF_ModuleLoader::installVars($this, array(
			'use_email' => array('1', 'bool'),
			'show_adult' => array('1', 'bool'),
			'adult_age' => array('21', 'int', '12', '40'),
			'show_gender' => array('1', 'bool'),
			'mail_sender' => array(GWF_BOT_EMAIL, 'text', 0, 128),
			'demo_changetime' => array(GWF_Time::ONE_MONTH*3, 'time', 0, GWF_TIME::ONE_YEAR*2),
			'show_checkboxes' => array('1', 'bool'),
			'account_guest_settings' => array('1', 'bool'),
		));
	}

	##################
	### Convinient ###
	##################
	public function cfgUseEmail() { return $this->getModuleVarBool('use_email', '1'); }
	public function cfgShowAdult() { return $this->getModuleVarBool('show_adult', '1'); }
	public function cfgShowGender() { return $this->getModuleVarBool('show_gender', '1'); }
	public function cfgChangeTime() { return $this->getModuleVarInt('demo_changetime', 2592000*3); }
	public function cfgMailSender() { return $this->getModuleVar('mail_sender', GWF_BOT_EMAIL); }
	public function cfgAdultAge() { return $this->getModuleVarInt('adult_age', 21); }
	public function cfgShowCheckboxes() { return $this->getModuleVarBool('show_checkboxes', '1'); }
	public function cfgGuestSettings() { return $this->getModuleVarBool('account_guest_settings', '1') && GWF_Session::hasSession(); }
	
	###############
	### Startup ###
	###############
	public function onStartup()
	{
		if ($user = GWF_Session::getUser())
		{
			if ($user->isOptionEnabled(GWF_User::RECORD_IPS))
			{
				$this->includeClass('GWF_AccountAccess');
				GWF_AccountAccess::onAccess($this, $user);
			}
		}
	}
	
	###############
	### Sidebar ###
	###############
	public function sidebarContent($bar)
	{
		if ($bar === 'left')
		{
			if (($this->cfgGuestSettings()) || (GWF_Session::getUser()))
			{
				return $this->accountSidebar();
			}
		}
	}
	
	private function accountSidebar()
	{
		$this->onLoadLanguage();
		$tVars = array(
			'user' => GWF_User::getStaticOrGuest(),
			'info_text' => $this->accountSidebarInfotext(), 
			'href_settings' => GWF_WEB_ROOT.'account',
		);
		return $this->template('account_sidebar.php', $tVars);
	}
	
	private function accountSidebarInfotext()
	{
		$user = GWF_User::getStaticOrGuest();
		return GWF_User::isGuestS() ?
			$this->lang('side_info_guest', array($user->getName())) :
			$this->lang('side_info_member', array($user->getName()));
	}

}
