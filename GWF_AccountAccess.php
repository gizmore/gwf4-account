<?php
/**
 * @author gizmore
 */
final class GWF_AccountAccess extends GDO
{
	###########
	### GDO ###
	###########
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'acc_acc'; }
	public function getColumnDefines()
	{
		return array(
			'accacc_uid'  => array(GDO::UINT|GDO::PRIMARY_KEY, GDO::NOT_NULL),
			'accacc_ua'   => array(GDO::CHAR|GDO::BINARY|GDO::CASE_S|GDO::PRIMARY_KEY, GDO::NULL, 16),
			'accacc_ip'   => GWF_IP6::gdoDefine(GWF_IP_EXACT, GDO::NOT_NULL, GDO::PRIMARY_KEY),
			'accacc_isp'  => array(GDO::CHAR|GDO::BINARY|GDO::CASE_S, GDO::NULL, 16),
			'accacc_time' => array(GDO::TIME, GDO::NOT_NULL),

			'accacc_user' => array(GDO::JOIN, GDO::NULL, array('GWF_User', 'accacc_uid', 'user_id')),
		);
	}
	
	public function displayDate()
	{
		return GWF_Time::displayTimestamp($this->getVar('accacc_time'));
	}
	
	public function displayIP()
	{
		return GWF_IP6::displayIP($this->getVar('accacc_ip'), GWF_IP_EXACT);
	}
	
	public function displayHex($data)
	{
		$back = unpack('H*', $data);
		return array_pop($back);
	}
	
	public function displayUAHex()
	{
		return $this->displayHex($this->getVar('accacc_ua'));
	}
	
	public function displayISPHex()
	{
		return $this->displayHex($this->getVar('accacc_isp'));
	}

	private static function isphash()
	{
		if ($_SERVER['REMOTE_ADDR'] === ($isp = @gethostbyaddr($_SERVER['REMOTE_ADDR'])))
		{
			$isp = null;
		}
		return self::hash($isp);
	}
	
	private static function uahash()
	{
		return self::hash(preg_replace('/\d/', '', $_SERVER['HTTP_USER_AGENT']));
	}
	
	private static function hash_check($field, $hash, $quote='"')
	{
		return $hash === null ? $field.' IS NULL' : $field.'='.$quote.GDO::escape($hash).$quote;
	}
	
	private static function hash($value)
	{
		return $value === null ? null : md5($value, true);
	}
	
	public static function onAccess(Module_Account $module, GWF_User $user)
	{
		$table = self::table(__CLASS__);
		$query = '';
		
		# Check UA
		$ua = self::uahash();		
		if ($user->isOptionEnabled(GWF_User::ALERT_UAS))
		{
			$query .= " AND ".self::hash_check('accacc_ua',$ua);
		}
		
		# Check exact IP
		$ip = GWF_IP6::getIP(GWF_IP_EXACT);
		if ($user->isOptionEnabled(GWF_User::ALERT_IPS))
		{
			$query .= " AND accacc_ip='".$table->escape($ip)."'";
		}
		
		$isp = null;
		if ($user->isOptionEnabled(GWF_User::ALERT_ISPS))
		{
			$isp = self::isphash();
			$query .= ' AND '.self::hash_check('accacc_isp',$isp);
		}
		
		# Query alert
		if (!empty($query))
		{
			if (!$table->selectVar('1', "accacc_uid={$user->getID()} $query"))
			{
				self::sendAlertMail($module, $user, 'record_alert');

				$data = array(
						'accacc_uid' => $user->getID(),
						'accacc_ip' => $ip,
						'accacc_isp' => $isp,
						'accacc_ua' => $ua,
						'accacc_time' => time(),
				);
				$table->insertAssoc($data);
			}
		}
	}
	
	public static function sendAlertMail(Module_Account $module, GWF_User $user, $record_alert='record_alert')
	{
		if ($receive_mail = $user->getValidMail())
		{
			$module->onLoadLanguage();
			$mail = new GWF_Mail();
			$mail->setSender(GWF_BOT_EMAIL);
			$sig = $module->lang("mail_signature");
			$mail->setSenderName($sig);
			$mail->setReceiver($receive_mail);
			$mail->setSubject($module->lang("mails_$record_alert"));
			$url = Common::getAbsoluteURL($module->getMethodURL('Access'));
			$mail->setBody($module->lang("mailb_record_alert", array(
				$user->displayUsername(),
				$module->lang("mailv_$record_alert"),
				GWF_HTML::display($_SERVER['HTTP_USER_AGENT']),
				$_SERVER['REMOTE_ADDR'],
				gethostbyaddr($_SERVER['REMOTE_ADDR']),
				GWF_HTML::anchor($url, $url, 'Manage IP recording'),
				$sig
			)));
			$mail->sendToUser($user);
		}
	}
	
}
