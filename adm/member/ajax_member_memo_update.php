<?
###############################################################################
## ajax_member_memo_update.php
## 회원정보 메모 등록
###############################################################################

include_once("./_common.php");

//auth_check($auth[$sub_menu], 'w');
//check_admin_token();

if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

while( list($k, $v) = each($_POST) ) { if(!is_array(${$k})) ${$k} = trim($v); }


$MB = sql_fetch("SELECT mb_no, mb_memo FROM g5_member WHERE mb_no = '".$mb_no."'");

if($MB['mb_no']) {

	//if($mb_memo) {

		$mb_memo = sql_real_escape_string($mb_memo);

		if($mb_memo != $MB['mb_memo']) {
			$sql = "UPDATE g5_member SET mb_memo = '".$mb_memo."' WHERE mb_no = '".$mb_no."'";
			if( sql_query($sql) ) {
				$ARR = array('result'=>'success', 'msg'=>'메모등록 완료');
			}
			else {
				$ARR = array('result'=>'error', 'msg'=>'데이터 등록 실패!');
			}
		}
		else {
			$ARR = array('result'=>'error', 'msg'=>'내용변동사항 없음!');
		}

	//}

}
else {

	$ARR = array('result'=>'error', 'msg'=>'회원정보데이터 없음!');

}


echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);


sql_close();
exit;

?>