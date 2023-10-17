<?
include_once('_common.php');

// 복호화
if($_REQUEST['mode']=='d') {

	$MEMBER = array(
		array('id'=>'beauttylee', 'idx'=>'10675'),
		array('id'=>'hy1216', 'idx'=>'34558'),
		array('id'=>'lemonadesgv@naver.com', 'idx'=>'8961')
	);
	$MEMBER_KEYS = array_keys($MEMBER);

	for($i=0; $i<count($MEMBER); $i++) {
		echo $MEMBER[$MEMBER_KEYS[$i]]['id'].": ". getJumin($MEMBER[$MEMBER_KEYS[$i]]['idx'], true) ."<br>\n";
	}

}


// 암호화
if($_REQUEST['mode']=='e') {

	//$INPUT = array('mb_no'=>'76', 'jumin'=>'8907071042325');
	//$INPUT = array('mb_no'=>'2247', 'jumin'=>'7001046102092');
	//$INPUT = array('mb_no'=>'2711', 'jumin'=>'4302252069318');
	//$INPUT = array('mb_no'=>'2510', 'jumin'=>'6702051122117');
	//$INPUT = array('mb_no'=>'4004', 'jumin'=>'8510231108519');
	//$INPUT = array('mb_no'=>'6037', 'jumin'=>'7810125300393');
	$INPUT = array('mb_no'=>'6160', 'jumin'=>'6705035780023');

	$encJumin = masterEncrypt($INPUT['jumin'], true);
	$md5Jumin = strtoupper(md5(masterEncrypt($INPUT['jumin'], false)));

	echo "INSERT INTO member_private (mb_no, regist_number, 5dm) VALUES ('".$INPUT['mb_no']."', '$encJumin', '$md5Jumin');";

}

?>