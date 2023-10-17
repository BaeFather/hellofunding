<?
###############################################################################
## 법인회원 등록
###############################################################################

$sub_menu = "200100";
include_once("./_common.php");
include_once(G5_LIB_PATH."/register.lib.php");

auth_check($auth[$sub_menu], 'w');
check_admin_token();

if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록


while( list($k, $v) = each($_POST) ) { if(!is_array(${$k})) ${$k} = trim($v); }


$MB_TABLE = "g5_member";		// 테스트니깐.....

if( in_array($mode, array('new','edit')) ) {

	if($member_group=='L') $ARR['mb_f_no'] = $mb_f_no;	// 회원고유그룹번호
	$ARR['member_group'] = $member_group;
	$ARR['member_type']  = $member_type;


	if($mode == 'new') {
		// 아이디 중복 체크
		if($mb_id) {
			$chk_mb_id_msg = exist_mb_id($mb_id);
			if($chk_mb_id_msg) { alert($chk_mb_id_msg); exit; }
			$ARR['mb_id'] = sql_real_escape_string($mb_id);
		}
		$ARR['mb_password'] = get_encrypt_string2($mb_password);
	}

	if($mode == 'edit') {
		$MB = sql_fetch("SELECT mb_no, mb_id, mb_password FROM {$MB_TABLE} WHERE mb_no = '".$mb_no."'");
		if(!$MB['mb_no']) { alert('등록된 회원정보가 없습니다.'); exit; }

		$mb_id = $MB['mb_id'];

		if( $mb_password && (get_encrypt_string2($mb_password) != $MB['mb_password']) ) {
			$ARR['mb_password'] = get_encrypt_string2($mb_password);
		}
	}


	$mb_co_reg_num = preg_replace('/(-| )/', '', $mb_co_reg_num);					// 사업자등록번호
	$corp_num      = preg_replace('/(-| )/', '', $corp_num);							// 법인등록번호

	$corp_phone    = preg_replace('/(-| )/', '', $corp_phone);						// 업체연락처
	$corp_phone_enc  = masterEncrypt($corp_phone, false);
	$corp_phone_ineb = DGuardEncrypt($corp_phone);

	$ARR['mb_co_name']      = sql_real_escape_string($mb_co_name);				// 법인명
	$ARR['mb_co_name_eng']  = sql_real_escape_string($mb_co_name_eng);		// 법인영문명
	$ARR['corp_noneprofit'] = $corp_noneprofit;		// 비영리법인
	$ARR['mb_co_reg_num']   = $mb_co_reg_num;			// 사업자등록번호
	$ARR['mb_co_owner']     = sql_real_escape_string($mb_co_owner);				// 대표자명
	$ARR['corp_num']        = $corp_num;					// 법인등록번호
	$ARR['corp_rdate']      = $corp_rdate;				// 설립일
	$ARR['corp_forigner']   = $corp_forigner;			// 해외법인
	$ARR['corp_phone']      = $corp_phone_enc;				// 법인연락처
	$ARR['corp_phone_ineb'] = $corp_phone_ineb;				// 법인연락처
	$ARR['zip_num']         = $zip_num;						// 사업장 우편번호
	$ARR['mb_addr1']        = $mb_addr1;					// 도로명주소
	$ARR['mb_addr_jibeon']  = $mb_addr_jibeon;		// 지번주소
	$ARR['mb_addr2']        = $mb_addr2;					// 이하상세주소
	$ARR['mb_addr3']        = $mb_addr3;					// 주소메모
	$ARR['is_creditor']     = ($is_creditor=='Y') ? $is_creditor : 'N';		// 대부업 플래그

	$ARR['mb_name']          = sql_real_escape_string($mb_name);					// 담당자명
	$ARR['corp_officer_div'] = $corp_officer_div;													// 법인과의 관계 (1:대표자,2:직원)
	$ARR['mb_email']         = sql_real_escape_string($mb_email);

	if($mb_hp) {
		$mb_hp      = preg_replace("/-/", "", $mb_hp);											// 담당자 전화번호
		$mb_hp_enc  = masterEncrypt($mb_hp, false);													// 전화번호 암호화
		$mb_hp_ineb = DGuardEncrypt($mb_hp);																// 전화번호 암호화

		$ARR['mb_hp']     = $mb_hp_enc;
		$ARR['mb_hp_ineb']= $mb_hp_ineb;
	}

	$ARR['mb_10']             = $mb_10;																		// 헬로소속
	$ARR['is_invest_manager'] = $is_invest_manager;												// 자산운용사
	$ARR['is_sbiz_owner']     = $is_sbiz_owner;														// 자동투자선순위대상자

	if($member_group == 'F') {
		$ARR['remit_fee']         = $remit_fee;																// 플랫폼수수료면제
		$ARR['remit_fee_sdate']   = ($remit_fee) ? $remit_fee_sdate : '';			// 수수료면제적용일
	}

	$ARR['mb_mailling']       = ($mb_mailling) ? '1' : '0';								// 이메일수신동의
	$ARR['mb_sms']            = ($mb_sms) ? '1' : '0';										// SMS수신동의

	if($member_group == 'F') {
		$ARR['invested_mailling'] = ($invested_mailling) ? '1' : '0';					// 투자설명서발급동의
	}

	if( $account_num = preg_replace('/(-| )/', '', $account_num) ) {
		$account_num_enc  = masterEncrypt($account_num, false);							// 계좌번호 암호화
		$account_num_ineb = DGuardEncrypt($account_num);										// 계좌번호 암호화
	}

	if( $bank_code && $account_num && $bank_private_name ) {
		$ARR['bank_name']             = $BANK[$bank_code];
		$ARR['bank_code']             = $bank_code;
		$ARR['account_num']           = $account_num_enc;
		$ARR['account_num_ineb']      = $account_num_ineb;
		$ARR['bank_private_name']     = sql_real_escape_string($bank_private_name);
		$ARR['bank_private_name_sub'] = sql_real_escape_string($bank_private_name_sub);
	}
	else {
		$ARR['bank_name'] = $ARR['bank_code'] = $ARR['account_num'] = $ARR['account_num_key'] = $ARR['account_num_ineb'] = $ARR['bank_private_name'] = $ARR['bank_private_name_sub'] = '';
	}

	$ARR['receive_method'] = ($member_group=='F') ? '2' : '';



	// 첨부파일 컨트롤 시작 ----------------------------------------------------------------

	// 업로드 함수 : memberFileUpload('문서제목', '파일명저장필드명', '기존파일삭제여부', '파일저장디렉토리', '새파일개체')
	function mbFileSave($title, $fldNm, $oFileDelYN, $oFileNm, $fileDirNm, $nFileObj='') {

		$rtnVal  = '';
		$saveDir = G5_DATA_PATH . '/member/' . $fileDirNm;

		if($oFileDelYN == 'Y') {
			if($oFileNm) {
				$oFilePath = $saveDir . '/' . $oFileNm;
				if(is_file($oFilePath)) {
					@unlink($oFilePath);
				}
				$rtnVal = 'deleteSuccess';
			}
		}

		if(@$nFileObj['size'] > 0) {
			list($usec, $sec) = explode(" ", microtime());
			$regTime = $usec * 1000000;
			$ext      = strtolower(substr(strrchr($nFileObj['name'],"."),1));
			$nfileNm  = time() . $regTime . "." . $ext;

			$saveFileNm = UploadFile($saveDir, '100', $fldNm, "", $nfileNm);
			if($saveFileNm) {
				$rtnVal = $saveFileNm;
			}
			else {
				$rtnVal = 'saveFail';
			}
		}

		return $rtnVal;

	}


	$FLFIELD = array(
		array('title'=>'사업자등록증',           'fldNm'=>'business_license'),
	//array('title'=>'주민등록증',             'fldNm'=>'id_card'),
	//array('title'=>'법정대리인동의서',       'fldNm'=>'junior_doc1'),
	//array('title'=>'가족관계증명서',         'fldNm'=>'junior_doc2'),
	//array('title'=>'법정대리인신분증사본',   'fldNm'=>'junior_doc3'),
		array('title'=>'통장사본',               'fldNm'=>'bankbook'),
		array('title'=>'법인등기부등본',         'fldNm'=>'corp_deungibu_doc'),
		array('title'=>'대표자신분증',           'fldNm'=>'corp_owner_id_card_doc'),
		array('title'=>'실소유자정보양식',       'fldNm'=>'corp_owner_quest_doc'),
		array('title'=>'주주명부',               'fldNm'=>'corp_stockholders_doc'),
		array('title'=>'법인인감증명서',         'fldNm'=>'corp_ingam_doc'),
		array('title'=>'비영리단체정관',         'fldNm'=>'corp_noneprofit_policy_doc'),
		array('title'=>'기타사용자첨부압축파일', 'fldNm'=>'identify_zip_file'),
		array('title'=>'대부업등록증',           'fldNm'=>'loan_co_license')
	);


	for($i=0; $i<count($FLFIELD); $i++) {

		$oFileDelYN = ${"del_" . $FLFIELD[$i]['fldNm']};
		$oFileNm    = ${"org_" . $FLFIELD[$i]['fldNm']};
		$nFileSize  = @$_FILES[$FLFIELD[$i]['fldNm']]['size'];

		//echo "($i) title:" . $FLFIELD[$i]['title'] . " oFileDelYN:" . $oFileDelYN . " oFileNm:" . $oFileNm . " fileSize: " . $_FILES[$FLFIELD[$i]['fldNm']]['size'] . "<br>\n";

		if($oFileDelYN || $nFileSize) {

			$FL['title']      = $FLFIELD[$i]['title'];
			$FL['fldNm']      = $FLFIELD[$i]['fldNm'];
			$FL['fileDirNm']  = $FLFIELD[$i]['fldNm'];
			$FL['oFileDelYN'] = $oFileDelYN;
			$FL['oFileNm']    = $oFileNm;
			$FL['newFileObj'] = ($nFileSize > 0) ? $_FILES[$FLFIELD[$i]['fldNm']] : '';

			$makedFileNm = mbFileSave($FL['title'], $FL['fldNm'], $FL['oFileDelYN'], $FL['oFileNm'], $FL['fileDirNm'], $FL['newFileObj']);

			//echo "mbFileSave({$FL['title']}, {$FL['fldNm']}, {$FL['oFileDelYN']}, {$FL['oFileNm']}, {$FL['fileDirNm']}, {$FL['newFileObj']});<br>\n";
			//if($makedFileNm) { echo $FLFIELD[$i]['title'] . ": "; echo $makedFileNm; echo "<br/>\n"; }

			if($makedFileNm=='saveFail') { alert("파일업로드 오류 : " . $FL['title']); }
			else if($makedFileNm=='deleteSuccess') { $ARR[$FL['fldNm']] = ''; }
			else { $ARR[$FL['fldNm']] = $makedFileNm; }

		}

		$oFileDelYN = $oFileNm = $nFileSize = NULL;

	}
	// 첨부파일 컨트롤 끝 -----------------------------------------------------------------

	$ARR['all_doc_check_yn'] = (!$all_doc_check_yn) ? 'N' : $all_doc_check_yn;		// 제출서류검수완료
	if($mb_level) $ARR['mb_level'] = $mb_level;


	/////////////////////////////////////
	// DB처리
	/////////////////////////////////////
	$arr_count = count($ARR);
	$ARRKEY = array_keys($ARR);

	if($mode == 'new') {

		////////////////////
		// 신규회원 등록
		////////////////////

		$sqlx = "INSERT INTO {$MB_TABLE} SET ";
		for($k=0,$n=1; $k<$arr_count; $k++,$n++) {
			$sqlx.= $ARRKEY[$k]."='".$ARR[$ARRKEY[$k]]."'";
			$sqlx.= ($n < $arr_count) ? ', ' : '';
			$sqlx.= "\n";
		}

		if(sql_query($sqlx)) {
			$mb_no = sql_insert_id();
			$action_result = true;

			if($mb_f_no) {
				sql_query("UPDATE g5_member SET mb_f_no = '$mb_f_no', mb_datetime = NOW() WHERE mb_no = '$mb_no'");
			}
			else {
				sql_query("UPDATE g5_member SET mb_f_no = mb_no, mb_datetime = NOW() WHERE mb_no = '$mb_no'");
			}

			member_edit_log($mb_no);	// 회원정보변경기록

		}

	}
	else if($mode == 'edit') {

		////////////////////
		// 회원정보 수정
		////////////////////

		$sqlx = "UPDATE {$MB_TABLE} SET\n";
		for($k=0,$n=1; $k<$arr_count; $k++,$n++) {
			$sqlx.= $ARRKEY[$k]."='".$ARR[$ARRKEY[$k]]."'";
			$sqlx.= ($n < $arr_count) ? ', ' : '';
			$sqlx.= "\n";
		}
		$sqlx.= ", edit_datetime = NOW()";
		$sqlx.= " WHERE mb_no='".$mb_no."'";

		if(sql_query($sqlx)) {
			$action_result = true;
			member_edit_log($mb_no);
		}

	}

	//print_rr($sqlx,'font-size:12px;line-height:14px;'); exit;


	///////////////////////////////////////////////
	// 차주 신용정보관련필드 기록
	///////////////////////////////////////////////
	if($member_group=='L') {



	//$psnl_num1 = substr($regist_number, 0, 6);
	//$psnl_num2 = substr($regist_number, 6, 1);

		$CHAJU = sql_fetch("SELECT idx FROM cf_chaju WHERE mb_no = '$mb_no'");

		if($CHAJU['idx']) {
			$chaju_sql = "
				UPDATE
					cf_chaju
				SET
					mb_legal_num = '$corp_num',
					credit_score = '$credit_score',
					rating_cp    = '$rating_cp',
					psnl_num1    = '$psnl_num1',
					psnl_num2    = '$psnl_num2',
					limit_amt    = '$limit_amt'
				WHERE
					idx = '".$CHAJU['idx']."'";
		}
		else {
			$chaju_sql = "
				INSERT INTO
					cf_chaju
				SET
					mb_no        = '$mb_no',
					mb_legal_num = '$corp_num',
					credit_score = '$credit_score',
					rating_cp    = '$rating_cp',
					psnl_num1    = '$psnl_num1',
					psnl_num2    = '$psnl_num2',
					limit_amt    = '$limit_amt'";
		}

		if($chaju_sql) sql_query($chaju_sql);

	}


	///////////////////////////////////////////////
	// 등록 및 수정 완료 후 처리
	///////////////////////////////////////////////
	if($action_result) {

		////////////////////////////////
		// 주민번호 암호화 및 저장
		////////////////////////////////
		if($regist_number) {

			$encJumin  = masterEncrypt($regist_number, true);
			$inebJumin = DGuardEncrypt($regist_number);
			$md5Jumin  = md5($regist_number);

			$link2 = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2);

			// 기등록 데이터 체크
			$r = sql_fetch("SELECT COUNT(idx) AS cnt FROM member_private WHERE mb_no='".$mb_no."' ORDER BY idx DESC LIMIT 1", '', $link2);

			if($r['cnt']) {
				$private_query = "
					UPDATE
						member_private
					SET
						regist_number = '".$encJumin."',
						regist_number_ineb = '".$inebJumin."',
						5dm = '".$md5Jumin."'
					WHERE
						mb_no = '".$mb_no."'";
			}
			else {
				$private_query = "
					INSERT INTO
						member_private
					SET
						mb_no = '".$mb_no."',
						regist_number = '".$encJumin."',
						regist_number_ineb = '".$inebJumin."',
						5dm = '".$md5Jumin."'";
			}

			$result = sql_query($private_query, '', $link2);
			sql_close($link2);

		}

		$msg = ($mode=="edit") ? '수정' : '등록';
		$msg.= '되었습니다.';

		if($mode=="edit") {
			msg_reload($msg, "window.top");
		}
		else {
			msg_replace($msg, "member_form.php?{$qstr}&mb_id={$mb_id}", "window.top");
		}

	}
	else {

		$msg = ($mode=="edit") ? '수정' : '등록';
		$msg.= '처리에 오류가 발생하였습니다.';
		alert($msg); exit;

	}

}		// end if( in_array($mode, array('new','edit')) )



sql_close();
exit;

?>