<?
###############################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

$sub_menu = '200400';
include_once('./_common.php');

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

auth_check($auth[$sub_menu], "w");

$html_title = "개인투자자 승인";
$g5['title'] = $html_title.' 처리';
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

//include_once (G5_ADMIN_PATH.'/admin.head.php');


$sql = "
	SELECT
		A.*,
		B.mb_id, B.mb_name, B.mb_hp, B.mb_email, LEFT(B.mb_datetime, 10) AS mb_datetime
	FROM
		investor_type_change_request A
	LEFT JOIN
		g5_member B
	ON
		A.mb_no=B.mb_no
	WHERE
		idx='$idx'";
$DATA = sql_fetch($sql);
if(!$DATA) { alert('잘못된 경로 입니다.'); }
$DATA['mb_hp'] = masterDecrypt($DATA['mb_hp'], false);

if($DATA['allow']!=$allow || $DATA['rights_start_date']!=$rights_start_date || $DATA['rights_end_date']!=$rights_end_date || $DATA['judge_memo']!=$judge_memo) {
	$change_data = true;
	$judge_memo = sql_real_escape_string($judge_memo);
}

if($change_data) {

	$sql = "UPDATE investor_type_change_request SET ";
	$sql.= " allow = '$allow', ";
	if($allow=='Y') {
		$sql.= " allow_date = NOW(), ";
		$sql.= " rights_start_date = '$rights_start_date', ";
		$sql.= " rights_end_date =  '$rights_end_date', ";
	}
	else {
		$sql.= " allow_date = NULL, ";
		$sql.= " rights_start_date = NULL, ";
		$sql.= " rights_end_date =  NULL, ";
	}
	$sql.= " mkind='".$mkind."', ";
	$sql.= ($judge_memo) ? " judge_memo = '$judge_memo', " : "";
	$sql.= " appr_mb_no = '".$member['mb_no']."', ";
	$sql.= " last_edit_date = NOW() ";
	$sql.= " WHERE idx='$idx'";
	sql_query($sql);
	if($_COOKIE['debug_mode']) echo $sql."<br>";

	if($allow=='Y') {
		// 기존 신청 자동 거부처리
		sql_query("UPDATE investor_type_change_request SET allow='N' WHERE mb_no='".$DATA['mb_no']."' AND allow='wait'");

		// 회원 정보테이블 업데이트
		$sql2 = "
			UPDATE
				g5_member
			SET
				member_investor_type = '".$DATA['order_type']."',
				investor_judge_idx   = '".$DATA['idx']."'
			WHERE
				mb_no = '".$DATA['mb_no']."'";
		sql_query($sql2);
		if($_COOKIE['debug_mode']) echo $sql2."<br>";
	}

	echo "<script>location.replace('/adm/member/investor_type_req.php?$qstr');</script>";

}



?>