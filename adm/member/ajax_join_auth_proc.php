<?
include_once("./_common.php");

if(!$_POST['mode'] || $_POST['mode'] == '') exit;

//print_r($_POST);


if($_POST['mode'] == 'instant_auth') {

	$MEMBER = sql_fetch("SELECT mb_no, mb_level FROM g5_member WHERE mb_no='".$_POST['mb_no']."' AND mb_memo NOT REGEXP '[0-9]+ 삭제함'");

	if($MEMBER['mb_no']=='') echo '존재하지 않는 회원입니다.';
	if($MEMBER['mb_level']>=1 && $MEMBER['mb_level']<=10) echo '이미 승인된 회원입니다.';

	$mb_level = ($_POST['auth']=='Y') ? '1' : '100';  //mb_level=100 ->승인거절자

	$sql = "UPDATE g5_member SET mb_level='$mb_level' WHERE mb_no='".$_POST['mb_no']."'";
	if($res = sql_query($sql)) {
		$change_txt = ($_POST['auth']=='Y') ? "승인" : "거절";
		echo $change_txt . " 처리 되었습니다.";
	}
	else {
		echo "시스템 에러: 잠시 후 다시 시도하십시요.";
	}

}

?>
