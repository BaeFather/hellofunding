<?
///////////////////////
// 회원번호 리턴
///////////////////////

include_once("_common.php");

$my_mb_id = trim($_REQUEST['my_mb_id']);
$agent_mb_id = trim($_REQUEST['agent_mb_id']);

if($agent_mb_id=='') {
	$ARR = array('result'=>'FAIL', 'msg'=>'ID 미전송');
	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
}

if($my_mb_id == $agent_mb_id) {
	$ARR = array('result'=>'FAIL', 'msg'=>'본회원ID를 대리인ID로 등록할 수 없음!!');
	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
}

$MB = sql_fetch("SELECT mb_no FROM g5_member WHERE mb_id='".$agent_mb_id."' AND mb_level IN('1','2','3','4','5')");
if($MB['mb_no']) {
	$ARR = array('result'=>'SUCCESS', 'msg'=>$MB['mb_no']);
	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
}
else {
	$MB = sql_fetch("SELECT mb_no FROM g5_member_drop WHERE mb_id='".$agent_mb_id."' AND mb_level=0");
	if($MB['mb_no']) {
		$ARR = array('result'=>'FAIL', 'msg'=>'탈퇴ID 입니다.');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
	}
	else {
		$ARR = array('result'=>'FAIL', 'msg'=>'존재하지 않는 ID 입니다.');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
	}
}


@sql_close();
exit;

?>