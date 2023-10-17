<?
###############################################################################
## 관리자 등록 / 수정
###############################################################################

$sub_menu = '200800';
include_once("./_common.php");

$g5['title'] = "관리자처리";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 관리자 접속로그 등록

include_once(G5_LIB_PATH."/register.lib.php");

//check_demo();
auth_check($auth[$sub_menu], "d");

//foreach($_REQUEST as $k=>$v) { $$_POST[$k] = trim($v); }
while( list($k, $v) = each($_POST) ) { if( !is_array($_POST[$k]) ) ${$k} = trim($v); }

if(!$mode) { alert("잘못된 접근입니다..","subadmin_list.php"); exit; }


// 수정 모드 일때
if($mode == 'modi') {

	if(!$mb_no) {
		alert("잘못된 접근입니다..","subadmin_list.php");
		exit;
	}
	else {

		$DATA = sql_fetch("SELECT * FROM g5_sub_admin WHERE mb_no='".$mb_no."'");
		$DATA['mb_hp']      = masterDecrypt($DATA['mb_hp']);
		$DATA['mb_hp_ineb'] = DGuardDecrypt($DATA['mb_hp_ineb']);

		$name = '';
		if($work_part) $name.= $work_part . '-';
		$name.= $mb_name;

		$mb_hp = preg_replace('/(-| )/', '', $mb_hp);
		if($mb_hp) {
			$mb_hp_enc  = masterEncrypt($mb_hp, false);
			$mb_hp_ineb = DGuardEncrypt($mb_hp);
		}

		$auth_info = '';
		if(count($auth) > 0) {
			foreach($auth as $auth_list) {
				$auth_info .= $auth_list.',';
			}
			$auth_info = substr($auth_info,0,-1);
		}

		// 비밀번호가 있을 경우 업데이트문 추가
		if($mb_password != '') {
			$passwd_update = " ,mb_password = '".get_encrypt_string($mb_password)."'";
		}

		// 변경하려는 데이터와 기존 데이터간 아무런 차이가 없을경우 돌려보냄 (메뉴사용권한은 비교에서 제외함)
		if(
			$is_inspecter==$DATA['is_inspecter'] && $is_editor==$DATA['is_editor'] && $mb_name==$DATA['mb_name'] && ($mb_password=='' || $mb_password==$DATA['mb_password']) && $mb_hp==$DATA['mb_hp'] &&  $mb_hp_ineb==$DATA['mb_hp_ineb'] &&
			$privacy_auth==$DATA['privacy_auth'] && $hp_auth==$DATA['hp_auth'] && $account_view_auth==$DATA['account_view_auth'] && $member_control_auth==$DATA['member_control_auth'] &&
			$product_control_auth==$DATA['product_control_auth'] && $account_auth==$DATA['account_auth']
		) {
			msg_go("변경된 정보가 없습니다.", "subadmin_list.php");
		}


		$sql = "
			UPDATE
				g5_member
			SET
				mb_hp       = '".$mb_hp_enc."',
				mb_hp_ineb  = '".$mb_hp_ineb."',
				mb_name     = '".$name."'
				$passwd_update
			WHERE
				mb_no = '".$mb_no."'";
		sql_query($sql);

		// 관리자 테이블 업데이트
		$sql_sub = "
			UPDATE
				g5_sub_admin
			SET
				is_inspecter         = '".$is_inspecter."',
				is_editor            = '".$is_editor."',
				auth_info            = '".$auth_info."',
				privacy_auth         = '".$privacy_auth."',
				hp_auth              = '".$hp_auth."',
				account_view_auth    = '".$account_view_auth."',
				member_control_auth  = '".$member_control_auth."',
				product_control_auth = '".$product_control_auth."',
				account_auth         = '".$account_auth."',
				allow_location       = '".$allow_location."',
				edit_mb_no           = '".$member['mb_no']."',
				edit_datetime        = NOW(),
				approve_mb_no        = NULL,
				approve_datetime     = NULL
			WHERE
				mb_no = '".$mb_no."'";
		if( sql_query($sql_sub) ) {

			$UPDATEDATA = sql_fetch("SELECT * FROM g5_sub_admin WHERE mb_no = '".$mb_no."'");

			// 관리자정보 변경로그 등록
			$log_sql = "
				INSERT INTO
					g5_sub_admin_log
				SET
					mb_no                = '".$UPDATEDATA['mb_no']."',
					is_inspecter         = '".$UPDATEDATA['is_inspecter']."',
					is_editor            = '".$UPDATEDATA['is_editor']."',
					auth_info            = '".$UPDATEDATA['auth_info']."',
					privacy_auth         = '".$UPDATEDATA['privacy_auth']."',
					hp_auth              = '".$UPDATEDATA['hp_auth']."',
					account_view_auth    = '".$UPDATEDATA['account_view_auth']."',
					member_control_auth  = '".$UPDATEDATA['member_control_auth']."',
					product_control_auth = '".$UPDATEDATA['product_control_auth']."',
					account_auth         = '".$UPDATEDATA['account_auth']."',
					allow_location       = '".$UPDATEDATA['allow_location']."',
					edit_mb_no           = '".$UPDATEDATA['edit_mb_no']."',
					edit_datetime        = '".$UPDATEDATA['edit_datetime']."'";
			sql_query($log_sql);

			msg_replace("수정되었습니다.", "./subadmin_form.php?mb_no={$mb_no}&mode=modi");

		}

	}
}
else {			// 등록모드 일때

	/* 회원정보 테이블 insert */

	$name = '';
	if($work_part) $name.= $work_part . '-';
	$name.= $mb_name;

	$mb_hp = preg_replace('/(-| )/', '', $mb_hp);
	if($mb_hp) {
		$mb_hp_enc  = masterEncrypt($mb_hp, false);
		$mb_hp_ineb = DGuardEncrypt($mb_hp);
	}

	$mb = get_member($mb_id);
	if($mb['mb_id']) {
		alert('이미 존재하는 회원아이디입니다.\\nＩＤ : '.$mb['mb_id'].'\\n이름 : '.$mb['mb_name']);
	}


	$sql = "
		INSERT INTO
			g5_member
		SET
			mb_id            = '".$mb_id."',
			mb_password      = '".get_encrypt_string($mb_password)."',
			mb_datetime      = '".G5_TIME_YMDHIS."',
			mb_ip            = '".$_SERVER['REMOTE_ADDR']."',
			mb_email_certify = '".G5_TIME_YMDHIS."',
			mb_name          = '".$name."',
			mb_hp            = '".$mb_hp_enc."',
			mb_hp_ineb       = '".$mb_hp_ineb."',
			mb_level         = '9'";

	sql_query($sql);
	//print_rr($sql);

	$mb = get_member($mb_id);
	$new_mb_no = $mb['mb_no'];


	/*관리자 테이블 INSERT */
	$auth_info = '';
	if(count($auth) > 0) {
		foreach($auth as $auth_list) {
			$auth_info.= $auth_list.',';
		}
		$auth_info = substr($auth_info, 0, -1);
	}

	$sql_sub = "
		INSERT INTO
			g5_sub_admin
		SET
			mb_no                = '".$new_mb_no."',
			mb_id                = '".$mb_id."',
			is_inspecter         = '".$is_inspecter."',
			is_editor            = '".$is_editor."',
			auth_info            = '".$auth_info."',
			privacy_auth         = '".$privacy_auth."',
			hp_auth              = '".$hp_auth."',
			account_view_auth    = '".$account_view_auth."',
			product_control_auth = '".$product_control_auth."',
			account_auth         = '".$account_auth."',
			allow_location       = '".$allow_location."',
			regdate              = NOW(),
			edit_mb_no           = '".$member['mb_no']."',
			edit_datetime        = NOW()";
	sql_query($sql_sub);
	//print_rr($sql_sub);

	msg_replace("관리자 정보가 등록되었습니다.", "subadmin_list.php");

	exit;

}
?>