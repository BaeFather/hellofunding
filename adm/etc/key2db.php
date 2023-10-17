<?

if (!preg_match("/(220\.117\.134|211\.248\.149\.68)/", $_SERVER["REMOTE_ADDR"])) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

exit;

include_once("_common.php");

$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('DB2 Connect Error!!!');;

$private_key = file_get_contents(G5_PATH . '/syndicate/finnq/keys/hello_rsa_pri.20180627.pem');  // 개인
$public_key  = file_get_contents(G5_PATH . '/syndicate/finnq/keys/hello_rsa_pub.20180627.pem');	 // 공개

$password = get_encrypt_string2('hellofintech');


$ROW = sql_fetch("SELECT COUNT(*) AS cnt FROM secure_key", "", $linkX);
if($ROW['cnt']) {
	sql_query("TRUNCATE TABLE secure_key", "", $linkX);
}

$sql = "
	INSERT INTO
		secure_key
	SET
		pv = HEX(AES_ENCRYPT('".$private_key."', '".$password."')),
		pb = HEX(AES_ENCRYPT('".$public_key."', '".$password."')),
		rdate = NOW()";
echo $sql . "<br/><br/>\n";
sql_query($sql, "", $linkX);


$sql = "
	SELECT
		AES_DECRYPT(UNHEX(pv), '".$password."') AS pv,
		AES_DECRYPT(UNHEX(pb), '".$password."') AS pb
	FROM
		secure_key ORDER BY ";
echo $sql . "<br/><br/>\n";
$SECURE_KEY = sql_fetch($sql, "", $linkX);
print_rr($SECURE_KEY);


?>