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
	public function getVersion() { return 4.00; }
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
			'use_email' => array(true, 'bool'),
			'show_adult' => array(true, 'bool'),
			'adult_age' => array('21', 'int', '12', '40'),
			'show_gender' => array(true, 'bool'),
			'mail_sender' => array(GWF_BOT_EMAIL, 'text', 0, 128),
			'demo_changetime' => array(GWF_Time::ONE_MONTH*3, 'time', 0, GWF_TIME::ONE_YEAR*2),
			'show_checkboxes' => array(true, 'bool'),
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
	
	public function sidebarContent($bar)
	{
		if ($bar === 'left')
		{
			return $this->accountSidebar();
		}
	}
	
	private function accountSidebar()
	{
		$this->onLoadLanguage();
		$tVars = array(
				'user' => GWF_User::getStaticOrGuest(),
				'href_settings' => GWF_WEB_ROOT.'index.php?mo=Avatar&amp;me=Upload',
		);
		return $this->template('account_sidebar.php', $tVars);
	}

}
