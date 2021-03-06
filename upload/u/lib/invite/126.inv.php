<?php
!function_exists('readover') && exit('Forbidden');
require_once R_P . 'u/lib/invite/base.inv.php';

/**
 * 126邮箱登录，导出联系人列表
 * @author papa
 * 2010-04-22
 */

class INV_126 extends INV_Base {
	var $loginUrl = "https://reg.163.com/logins.jsp?type=1&product=mail126&url=http://entry.mail.126.com/cgi/ntesdoor?hid%3D10010102%26lightweight%3D1%26verifycookie%3D1%26language%3D0%26style%3D-1";
	var $listUrl = "http://entry.mail.126.com/cgi/ntesdoor?hid=10010102&lightweight=1&verifycookie=1&language=0&style=-1&username=";

	/**
	 * 根据用户名密码获得 126 邮箱 联系人email地址列表
	 * @param string $username
	 * @param string $password
	 * @return array
	 */
	function getEmailAddressList($username, $password) {
		$this->username = $username;
		$this->password = $password;
		if (!$this->_validateUserAndPasswd()) {
			return 0;
		}
		if (!$this->_login()) {
			return 0;
		}
		$this->headurl = $this->listUrl . $this->username . "@126.com";
		$this->_setHeader();
		$this->_getEmailAddressList();
		$this->_deleteCookieFile();
		return $this->addressList;
	}

	/**
	 * 获得好友列表
	 */
	function _getEmailAddressList() {
		if (!$this->header['sid']) {
			return 0;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://" . $this->header['host'] . "/a/s?sid=" . $this->header['sid'] . "&func=global:sequential");
		curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEJAR3);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
		$str = "<?xml version=\"1.0\"?><object><array name=\"items\"><object><string name=\"func\">pab:searchContacts</string><object name=\"var\"><array name=\"order\"><object><string name=\"field\">FN</string><boolean name=\"ignoreCase\">true</boolean></object></array></object></object><object><string name=\"func\">user:getSignatures</string></object><object><string name=\"func\">pab:getAllGroups</string></object></array></object>";
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
		curl_setopt($ch, CURLOPT_TIMEOUT, TIMEOUT);
		
		ob_start();
		curl_exec($ch);
		$contents = ob_get_contents();
		ob_end_clean();
		curl_close($ch);
		
		$contents = pwConvert($contents, 'GBK', 'UTF-8');
		preg_match_all("/<string\s*name=\"EMAIL;PREF\">(.*)<\/string>/Umsi", $contents, $mails);
		preg_match_all("/<string\s*name=\"FN\">(.*)<\/string>/Umsi", $contents, $names);
		foreach ($names[1] as $k => $user) {
			$this->addressList[$mails[1][$k]] = $user;
		}
	}

	/**
	 * 用户登录邮箱
	 * @param string $username
	 * @param string $password
	 */
	function _login() {
		$postfields = "username=" . $this->username . "@126.com&password=" . $this->password;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $this->loginUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEJAR1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, TIMEOUT);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$str = curl_exec($ch);
		curl_close($ch);
		
		preg_match("/replace\(\"(.*?)\"\)\;/", $str, $mtitle);
		$_url1 = $mtitle[1];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $_url1);
		curl_setopt($ch, CURLOPT_TIMEOUT, TIMEOUT);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEJAR1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEJAR2);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$str2 = curl_exec($ch);
		curl_close($ch);
		if (strpos($str2, "安全退出") !== false) {
			return 0;
		}
		return 1;
	}

}
?>