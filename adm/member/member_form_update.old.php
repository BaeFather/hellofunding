<?
###############################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

$sub_menu = "200100";
include_once("./_common.php");
include_once(G5_LIB_PATH."/register.lib.php");

//check_demo();
auth_check($auth[$sub_menu], 'w');
check_admin_token();

$g5['title'] = "회원등록처리";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

while(list($k, $v)=each($_POST)) {
	if( !is_array(${$k}) ) ${$k} = trim($v);
}


if($mode=='new') {
	// 아이디 중복 체크
	if($mb_id) {
		$chk_mb_id_msg = exist_mb_id($mb_id);
		if($chk_mb_id_msg) { alert($chk_mb_id_msg); }
	}
}

if($mode=='edit') {

	$mb = sql_fetch("SELECT mb_id FROM ".$g5['member_table']." WHERE mb_no='$mb_no'");
	$mb_id = $mb['mb_id'];

}


/*
휴대폰 이메일 중복체크 주석처리함: 변경요청시 요청자에게 다음 동영상 보여주기 https://www.youtube.com/watch?v=3jLGmI_BGko
// 휴대폰번호 중복 체크
if($mb_hp) {
	$mb_hp = preg_replace('/(-| )/', '', $mb_hp);
	$chk_hp_msg = exist_mb_hp($mb_hp, $mb_id);
	if($chk_hp_msg) { alert($chk_hp_msg); }
}

// 이메일 중복 체크
if($mb_email) {
	$chk_email_msg = valid_mb_email($mb_email);								// 무결성 검사
	if($chk_email_msg) { alert($chk_email_msg); }

	$chk_email_msg = exist_mb_email($mb_email, $mb_id);				// 중복체크
	if($chk_email_msg) { alert($chk_email_msg); }
}
*/

if($mb_hp) {
	$mb_hp_enc = masterEncrypt($mb_hp, false);               // 전화번호 암호화
	$mb_hp_key = substr($mb_hp, -4);                         // 전화번호는 뒤4자리만 등록
}

// 투자자타입은 투자회원+개인회원일 경우에만 등록
if($member_group=='F') {
	$_member_investor_type = ($member_type=='1') ? "'".$member_investor_type."'" : "NULL";
}
else {
	$_member_investor_type = "NULL";
}

if(!$mb_mailling) $mb_mailling = 'N';
if(!$mb_sms) $mb_sms = 'N';

$is_creditor = ($is_creditor=='Y') ? $is_creditor : 'N';			// 대부업 플래그

$bank_name   = ($bank_code) ? $BANK[$bank_code] : '';		// 은행명

// 계좌번호 검사
$account_num = preg_replace('/(-| )/', '', $account_num);
if($account_num) {
	$account_num_enc = masterEncrypt($account_num, false);		// 계좌번호 암호화
	$account_num_key = substr($account_num, -4);		// 전화번호는 뒤4자리만 등록
}

// 인증된 주민번호가 있으면 생년월일, 성별 추출
if($member_type=='1' && $private_yn=='Y' && $regist_number) {

	$encJumin = masterEncrypt($regist_number, true);
	$md5Jumin = strtoupper(md5(masterEncrypt($regist_number, false)));

	$ARR = getBirthGender($regist_number);
	$birthdate = $ARR[0];
	$gender    = $ARR[1];

//} else if ($member_group=="L" && $regist_number) {
} else if ($regist_number) {

	$encJumin = masterEncrypt($regist_number, true);
	$md5Jumin = strtoupper(md5(masterEncrypt($regist_number, false)));

}


// 첨부파일 컨트롤 시작 ----------------------------------------------------------------
$org_business_license = $_POST['org_business_license'];
$org_bankbook         = $_POST['org_bankbook'];
$org_loan_co_license  = $_POST['org_loan_co_license'];


$upload_folder = G5_DATA_PATH."/member";
//사업자등록증
if ($del_business_license == "Y") {
	if ($org_business_license) {
		@unlink("$upload_folder/$org_business_license");
		$org_business_license = "";
	}
}
if ($_FILES["business_license"]['size'] > 0) {
	$new_file_name = "";
	list($usec, $sec) = explode(" ", microtime());
	$reg_time = $usec*1000000;
	$ext = substr(strrchr($_FILES["business_license"]['name'],"."),1);
	$ext = strtolower($ext);
	$new_file_name = time().$reg_time.".".$ext;

	$uploadimg_name_1 = UploadFile($upload_folder, "10", "business_license", "", $new_file_name);
	if (!$uploadimg_name_1) {
		alert("파일 업로드 오류를 체크하십시요.");
	}
}
else {
	$uploadimg_name_1  = $org_business_license;
}

//통장사본
if ($_POST['del_bankbook'] == "Y") {
	if ($org_bankbook) {
		@unlink("$upload_folder/$org_bankbook");
		$org_bankbook = "";
	}
}
if ($_FILES["bankbook"]['size'] > 0) {
	$new_file_name = "";
	list($usec, $sec) = explode(" ", microtime());
	$reg_time = $usec*1000000;
	$ext = substr(strrchr($_FILES["bankbook"]['name'],"."),1);
	$ext = strtolower($ext);
	$new_file_name = time().$reg_time.".".$ext;

	$uploadimg_name_2 = UploadFile($upload_folder, "10", "bankbook", "", $new_file_name);
	if (!$uploadimg_name_2) {
		alert("파일 업로드 오류를 체크하십시요.");
	}
}
else {
	$uploadimg_name_2  = $org_bankbook;
}

//대부업등록증사본
if ($_POST['del_loan_co_license'] == "Y") {
	if ($org_loan_co_license) {
		@unlink("$upload_folder/$org_loan_co_license");
		$org_loan_co_license = "";
	}
}
if ($_FILES["loan_co_license"]['size'] > 0) {
	$new_file_name = "";
	list($usec, $sec) = explode(" ", microtime());
	$reg_time = $usec*1000000;
	$ext = substr(strrchr($_FILES["loan_co_license"]['name'],"."),1);
	$ext = strtolower($ext);
	$new_file_name = time().$reg_time.".".$ext;

	$uploadimg_name_3 = UploadFile($upload_folder, "10", "loan_co_license", "", $new_file_name);
	if (!$uploadimg_name_3) {
		alert("파일 업로드 오류를 체크하십시요.");
	}
}
else {
	$uploadimg_name_3  = $org_loan_co_license;
}
// 첨부파일 컨트롤 끝 -----------------------------------------------------------------


/////////////////////////////
// 계좌정보수정쿼리 정리
/////////////////////////////
$sql0_add = "";
if($member_type=='1') {  // 개인회원 -> 승인 체크 체크
	// 주민번호 암호화 및 저장 필요
	if($private_yn=='Y') {
		$sql0_add.= ",
			bank_name = '$bank_name',
			bank_code = '$bank_code',
			bank_private_name = '$bank_private_name',
			bank_private_name_sub = '$bank_private_name_sub',
			account_num = '$account_num_enc',
			account_num_key = '$account_num_key',
			mb_birth = '$birthdate',
			mb_sex = '$gender'";
	}
}
else {
	$sql0_add.= ",
			bank_name = '$bank_name',
			bank_code = '$bank_code',
			bank_private_name = '$bank_private_name',
			bank_private_name_sub = '$bank_private_name_sub',
			account_num = '$account_num_enc',
			account_num_key = '$account_num_key'";
}


///////////////////////////////////////////////////////////////////////////////
// 신규회원 등록
///////////////////////////////////////////////////////////////////////////////
if($mode=="new") {
	$mb_password = get_encrypt_string2($mb_password);		// SHA256 적용

	$mb_co_reg_num = preg_replace('/(-| )/', '', $mb_co_reg_num);		// 사업자등록번호
	$corp_num      = preg_replace('/(-| )/', '', $corp_num);				// 법인등록번호
	$corp_phone    = preg_replace('/(-| )/', '', $corp_phone);			// 업체연락처
	$mb_memo       = sql_real_escape_string($mb_memo);

	if($corp_rdate && strlen($corp_rdate)==8) {
		$corp_rdate = substr($corp_rdate,0,4).'-'.substr($corp_rdate,4,2).'-'.substr($corp_rdate,6);
	}

	$mb_legal_num  = $corp_num;		// 차주테이블용 법인등록번호

	$sql0 = "
      mb_id ='$mb_id',
			mb_password = '$mb_password',
			mb_name = '$mb_name',
			mb_co_name = '$mb_co_name',
			mb_co_reg_num = '$mb_co_reg_num',
			mb_co_owner = '$mb_co_owner',
			corp_num = '$corp_num',
			corp_rdate = '$corp_rdate',
			corp_noneprofit = '$corp_noneprofit',
			mb_email ='$mb_email',
			mb_level ='1',
			mb_hp = '$mb_hp_enc',
			mb_hp_key = '$mb_hp_key',
			corp_phone = '$corp_phone',
			zip_num = '$zip_num',
			mb_addr1 = '$mb_addr1',
			mb_addr2 = '$mb_addr2',
			mb_addr3 = '$mb_addr3',
			mb_addr_jibeon = '$mb_addr_jibeon',
			mb_datetime = NOW(),
			mb_mailling = '$mb_mailling',
			mb_sms = '$mb_sms',
			member_group = '$member_group',
			member_type = '$member_type',
			member_investor_type = '$_member_investor_type',
			is_creditor = '$is_creditor',
			is_owner_operator = '$is_owner_operator',
			is_sbiz_owner = '$is_sbiz_owner',
			is_invest_manager = '$is_invest_manager',
			business_license = '$uploadimg_name_1',
			bankbook = '$uploadimg_name_2',
			loan_co_license = '$uploadimg_name_3',
			receive_method = '$receive_method',
			remit_fee = '$remit_fee',
			remit_fee_sdate = '$remit_fee_sdate',
			mb_memo = '$mb_memo',
			mb_10 = '$mb_10'";
	$sql0.= $sql0_add;


	$sqlx = "
		INSERT INTO
			{$g5['member_table']}
		SET
			$sql0";

	//echo "<pre style='font-size:11px'>".$sqlx."<pre>"; exit;

	if(sql_query($sqlx)) {
		$mb_no = sql_insert_id();
		$action_result = true;

		if($member_group == 'L') {  // 대출회원일 때
			// 차주 테이블 insert
			$chaju_sql = "
				INSERT INTO
					cf_chaju
				SET
					mb_no = '$mb_no',
					mb_legal_num = '$mb_legal_num',
					credit_score = '$credit_score',
					rating_cp = '$rating_cp',
					psnl_num1 = '$psnl_num1',
					psnl_num2 = '$psnl_num2',
					limit_amt = '$limit_amt'";

			sql_query($chaju_sql);
		}


		member_edit_log($mb_no);	// 회원정보변경기록

	}

}

///////////////////////////////////////////////////////////////////////////////
// 회원정보 수정
///////////////////////////////////////////////////////////////////////////////
if($mode=="edit") {

	$mb_co_reg_num = preg_replace('/(-| )/', '', $mb_co_reg_num);		// 사업자등록번호
	$corp_num      = preg_replace('/(-| )/', '', $corp_num);				// 법인등록번호
	$corp_phone    = preg_replace('/(-| )/', '', $corp_phone);			// 업체연락처
	$mb_memo       = sql_real_escape_string($mb_memo);

	if($corp_rdate && strlen($corp_rdate)==8) {
		$corp_rdate = substr($corp_rdate,0,4).'-'.substr($corp_rdate,4,2).'-'.substr($corp_rdate,6);
	}

	$mb_legal_num  = $corp_num;		// 차주테이블용 법인등록번호

	$sql0 = "
			mb_name = '$mb_name',
			mb_co_name = '$mb_co_name',
			mb_co_reg_num = '$mb_co_reg_num',
			mb_co_owner = '$mb_co_owner',
			corp_num = '$corp_num',
			corp_rdate = '$corp_rdate',
			corp_noneprofit = '$corp_noneprofit',
			mb_email ='$mb_email',
			mb_hp = '$mb_hp_enc',
			mb_hp_key = '$mb_hp_key',
			corp_phone = '$corp_phone',
			is_creditor = '$is_creditor',
			is_owner_operator = '$is_owner_operator',
			is_sbiz_owner = '$is_sbiz_owner',
			is_invest_manager = '$is_invest_manager',
			zip_num = '$zip_num',
			mb_addr1 = '$mb_addr1',
			mb_addr2 = '$mb_addr2',
			mb_addr3 = '$mb_addr3',
			mb_addr_jibeon = '$mb_addr_jibeon',
			mb_mailling = '$mb_mailling',
			mb_sms = '$mb_sms',
			member_group = '$member_group',
			member_type = '$member_type',
			member_investor_type = $_member_investor_type,
			business_license = '$uploadimg_name_1',
			bankbook = '$uploadimg_name_2',
			loan_co_license = '$uploadimg_name_3',
			receive_method = '$receive_method',
			remit_fee = '$remit_fee',
			remit_fee_sdate = '$remit_fee_sdate',
			edit_datetime = NOW(),
			mb_memo = '$mb_memo',
			mb_10 = '$mb_10'";

	// 비밀번호가 있을 경우 쿼리 추가
	if($mb_password) {
		$new_password = get_encrypt_string2($mb_password);			// SHA256 적용
		$sql0.= ", mb_password = '$new_password'";
	}

	$sql0.= $sql0_add;

	$sqlx = "
		UPDATE
			{$g5['member_table']}
		SET
			$sql0
		WHERE
			mb_no = '{$mb_no}'";

	//echo "<pre style='font-size:11px'>".$sqlx."<pre>"; exit;

	$sql = "SELECT count(A.mb_no) AS cnt FROM cf_chaju A where mb_no = '$mb_no'";
	$row = sql_fetch($sql);
	$count = $row['cnt'];  // count 값이 0이면 기존에 데이터가 없고 값이 있으면 기존 데이터가 있고..

	if($count == '0') {  // 차주 테이블에 데이터가 없으면 insert

		//echo $count; die();

		// 차주 테이블 insert
		$chaju_sql = "
			INSERT INTO
				cf_chaju
			SET
				mb_no        = '$mb_no',
				mb_legal_num = '$mb_legal_num',
				credit_score = '$credit_score',
				rating_cp    = '$rating_cp',
				psnl_num1    = '$psnl_num1',
				psnl_num2    = '$psnl_num2',
				limit_amt    = '$limit_amt'";

		sql_query($chaju_sql);

	} else {  // 차주 테이블에 데이터가 있으면 update

		//echo $count; die();

		// 차주 테이블 update
		$chaju_sql = "
			UPDATE
				cf_chaju
			SET
				mb_legal_num = '$mb_legal_num',
				credit_score = '$credit_score',
				rating_cp    = '$rating_cp',
				psnl_num1    = '$psnl_num1',
				psnl_num2    = '$psnl_num2',
				limit_amt    = '$limit_amt'
			WHERE
				mb_no = '{$mb_no}'";

		sql_query($chaju_sql);

	}


	if(sql_query($sqlx)) {
		$action_result = true;

		member_edit_log($mb_no);	// 회원정보변경기록

	}

}


///////////////////////////////////////////////////////////////////////////////
// 등록 및 수정 완료 후 처리
///////////////////////////////////////////////////////////////////////////////
if($action_result) {

	////////////////////////////////
	// 주민번호 암호화 및 저장
	////////////////////////////////
	if($regist_number) {

		$link2 = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2);

		// 기등록 데이터 체크
		$r = sql_fetch("SELECT COUNT(idx) AS cnt FROM member_private WHERE mb_no='$mb_no' ORDER BY idx DESC LIMIT 1", '', $link2);

		if($r['cnt']) {
			$private_query = "UPDATE member_private SET regist_number='$encJumin', 5dm='$md5Jumin' WHERE mb_no='$mb_no' ORDER BY idx DESC LIMIT 1";
		}
		else {
			$private_query = "INSERT INTO member_private (mb_no, regist_number, 5dm) VALUES ('$mb_no', '$encJumin', '$md5Jumin')";
		}

		$result = sql_query($private_query, '', $link2);
		sql_close($link2);

	}

	$msg = ($mode=="edit") ? '수정' : '등록';
	$msg.= '되었습니다.';

	echo "<script>alert('$msg'); location.replace('./member_form.php?{$qstr}&mb_id={$mb_id}');</script>";

}
else {

	$msg = ($mode=="edit") ? '수정' : '등록';
	$msg.= '처리에 오류가 발생하였습니다.';
	echo "<script>alert('$msg');</script>";

}

?>