<?php

include_once('/home/crowdfund/public_html/data/office_ipconfig.php');
include_once('/home/crowdfund/public_html/data/dbconfig.php');

/////////////////////////////////////////////////
// 아이넵 DGuard 암호화
/////////////////////////////////////////////////
function DGuardEncrypt($text)
{
	$text = trim($text);
	$enc_data = '';
	if( strlen($text) ) {
		$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('Encryptor Connect Error!!');
		if( !@is_object($linkX) ) return false;

		$sql = "SELECT DGUARD.ENCRYPT('TBL','ENC', '".$text."') AS change_str";
		$enc_data = sql_fetch($sql, "", $linkX)['change_str'];
		sql_close($linkX);
	}
	return $enc_data;
}


/////////////////////////////////////////////////
// 아이넵 DGuard 복호화
/////////////////////////////////////////////////
function DGuardDecrypt($encText)
{
	$encText = trim($encText);
	$dec_data = '';
	if( strlen($encText) ) {
		$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('Encryptor Connect Error!!');
		if( !@is_object($linkX) ) return false;

		$sql = "SELECT DGUARD.DECRYPT('TBL','ENC', '".$encText."') AS change_str";
		$dec_data = sql_fetch($sql, "", $linkX)['change_str'];
		sql_close($linkX);
	}
	return $dec_data;
}



// ** 암호화키를 추출하여 암호화 ** //
// 최종 암호화 값을 base64 엔코딩 후 반환.
// 암호화방식 = ($isRSA==1) ? RSA(비대칭암호화) : AES256(대칭암호화);
function masterEncrypt($text, $isRSA=0)
{
	global $CONF;

	if(trim($text)=='') {
		return false;
	}
	else {
		if( $isRSA ) {
			$pbkey = LoadKey("pb", $CONF['LoadKeyPwd']);	// 암호화키 추출

			$pubkey_decoded = openssl_pkey_get_public($pbkey);
			if($pubkey_decoded === false) return false;

			$ciphertext = false;
			$status = @openssl_public_encrypt($text, $ciphertext, $pubkey_decoded);
			if(!$status || $ciphertext === false) return false;

			return base64_encode($ciphertext);
		}
		else {
			$pvkey = LoadKey("pv", $CONF['LoadKeyPwd']);	// 암호화키 추출

			$key = hash('MD5', $pvkey, true);
			return base64_encode(openssl_encrypt($text, "aes-256-cbc", $key, true, str_repeat(chr(0), 16)));
		}
	}
}


// ** 복호화키를 추출하여 복호화 ** //
// 평문 반환
function masterDecrypt($ciphertext, $isRSA=0)
{
	global $CONF;

	if(trim($ciphertext)=='') {
		return false;
	}
	else {
		$pvkey = LoadKey("pv", $CONF['LoadKeyPwd']);	// 복호화키 추출

		if( $isRSA ) {
			$password = NULL;

			$ciphertext = base64_decode($ciphertext, true);
			if($ciphertext === false) return false;

			$privkey_decoded = openssl_pkey_get_private($pvkey, $password);
			if($privkey_decoded === false) return false;

			$plaintext = false;
			$status = openssl_private_decrypt($ciphertext, $plaintext, $privkey_decoded);
			@openssl_pkey_free($privkey_decoded);
			if(!$status || $plaintext === false) return false;

			return $plaintext;
		}
		else {
			$key = hash('MD5', $pvkey, true);
			$plaintext = openssl_decrypt(base64_decode($ciphertext), "aes-256-cbc", $key, true, str_repeat(chr(0), 16));
			return $plaintext;
		}
	}
}


// ** 외부DB서버에 암호화 된 암.복호화키를 평문으로 추출 ** //
function LoadKey($key_type, $password) {
	if($key_type && in_array($key_type, array('pv','pb'))) {
		if( defined('G5_MYSQL_HOST2') && defined('G5_MYSQL_USER2') && defined('G5_MYSQL_PASSWORD2') && defined('G5_MYSQL_DB2') ) {

			$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('DB2 Connect Error!!!');

			$sql = "
				SELECT
					AES_DECRYPT(UNHEX(".$key_type."),'".get_encrypt_string2($password)."') AS ".$key_type."
				FROM
					secure_key
				ORDER BY
					rdate DESC LIMIT 1";
			$SECURE_KEY = sql_fetch($sql, "", $linkX);

			return $SECURE_KEY[$key_type];

			sql_close($linkX);

		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}

?>
