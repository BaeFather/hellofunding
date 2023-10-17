<?

//phpinfo(); exit;

include_once('_common.php');

// 복호화
if($_REQUEST['mode']=='d') {

	echo getPrivate('3930', 'hp') . "\n";
	echo getPrivate('3930', 'acct') . "\n";
	echo getPrivate('3930', 'jumin');

}


// 암호화
if($_REQUEST['mode']=='e') {

	$text = "01064063972";

	echo DGuardEncrypt($text);

}

?>