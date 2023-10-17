<?
###############################################################################
## 관리자 해제 / 탈퇴
###############################################################################

$sub_menu = '200800';
include_once("./_common.php");

check_demo();

auth_check($auth[$sub_menu], "d");


$target_mb_no = trim($_REQUEST['mb_no']);

if(!$target_mb_no) {

	alert("잘못된 접근입니다..","./subadmin_list.php");

}
else {

	$sql = "SELECT * FROM g5_sub_admin WHERE mb_no = '".$target_mb_no."'";
	$ROW = sql_fetch($sql);

	$REP['mb_level'] = '200';


	$sql = "
		UPDATE
			g5_member
		SET
			mb_level        = '".$REP['mb_level']."',
			mb_hp           = '',
			mb_password     = '',
			mb_5            = '',
			mb_leave_date   = CURDATE(),
			mb_leave_reason = '관리자권한해제',
			edit_datetime   = NOW()
		WHERE
			mb_no = '".$target_mb_no."'";

	if( sql_query($sql) ) {

		$sql2 = "
			UPDATE
				g5_sub_admin
			SET
				is_inspecter         = '',
				is_editor            = '',
				auth_info            = '',
				privacy_auth         = '',
				hp_auth              = '',
				account_view_auth    = '',
				member_control_auth  = '',
				product_control_auth = '',
				account_auth         = '',
				allow_location       = '',
				regdate              = '',
				withdrawal           = '1',
				withdrawal_date      = NOW(),
				edit_mb_no           = '".$member['mb_no']."',
				edit_datetime        = NOW()
			WHERE
				mb_no='".$target_mb_no."'";

		if( sql_query($sql2) ) {

			$sql3 = "SELECT * FROM g5_sub_admin WHERE mb_no = '".$target_mb_no."'";
			$UPDATEDATA = sql_fetch($sql3);

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
					withdrawal           = '".$UPDATEDATA['withdrawal']."',
					withdrawal_date      = '".$UPDATEDATA['withdrawal_date']."',
					edit_mb_no           = '".$member['mb_no']."',
					edit_datetime        = NOW()";
			sql_query($log_sql);

			alert("관리자 해제 및 탈퇴 처리완료","./subadmin_list.php");

		}

	}

}

?>
