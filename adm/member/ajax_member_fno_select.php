<?

include_once("_common.php");

while( list($k, $v) = each($_POST) ) { if(!is_array(${$k})) ${$k} = trim($v); }

$reqType = ($_REQUEST['reqType']) ? $_REQUEST['reqType'] : 'list';
$member_type = ($_REQUEST['member_type']) ? $_REQUEST['member_type'] : '1';

###############################################################################
## 회원목록 출력
###############################################################################
if($reqType=='list') {

	$sql = "
		SELECT
			A.mb_f_no, A.mb_no, A.mb_id,
			IF(A.member_type='2', A.mb_co_name, A.mb_name) AS mb_title,
			(SELECT COUNT(mb_no) FROM g5_member WHERE mb_f_no=A.mb_f_no) AS mb_cnt
		FROM
			g5_member A
		WHERE 1
			AND A.member_group='L' AND A.member_type='".$member_type."'  AND A.mb_level='1'
			AND A.mb_no=A.mb_f_no
		ORDER BY
			mb_cnt DESC,
			A.edit_datetime DESC,
			A.mb_no DESC";
	//echo $sql;

	$res = sql_query($sql);
	$rows = $res->num_rows;

	$ARR = array();

	for($i=0; $i<$rows; $i++) {
		$LIST[$i] = sql_fetch_array($res);
	}

	$ARR = array("result"=>"SUCCESS", "message"=>"", "list"=>$LIST);

	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

}

###############################################################################
## 상세 정보 출력
## - 우선순위 : 가장 최근 KYC정보 입력일 역순 > 회원정보수정일 역순 > 회원번호 역순
###############################################################################
if($reqType == 'detail') {

	$res = sql_query("SELECT * FROM g5_member WHERE mb_f_no='".$mb_no."' AND mb_level IN('1','2','3','4','5') AND kyc_allow_yn!='N' ORDER BY kyc_reg_dd DESC, edit_datetime DESC, mb_no DESC");
	$rows = $res->num_rows;
	$same_mb_arr = '';
	for($i=0,$j=1; $i<$rows; $i++,$j++) {
		$row = sql_fetch_array($res);

		if($j==1) $MB = $row;

		$same_mb_arr.= $row['mb_no'];
		$same_mb_arr.= ($j < $rows) ? ',' : '';
	}


	if( is_array($MB) ) {

		$mb_co_reg_num = (strlen($MB['mb_co_reg_num'])>=10) ? substr($MB['mb_co_reg_num'],0,3) . '-' . substr($MB['mb_co_reg_num'],3,2) . '-' . substr($MB['mb_co_reg_num'],5) : "";
		$corp_num = (strlen($MB['corp_num'])>=13) ? substr($MB['corp_num'],0,6) . '-' . substr($MB['corp_num'],6) : "";

		$DATA['mb_no'] = $MB['mb_no'];
		$DATA['is_creditor'] = $MB['is_creditor'];
		$DATA['mb_name'] = $MB['mb_name'];
		$DATA['eng_first_nm'] = $MB['eng_first_nm'];
		$DATA['eng_last_nm'] = $MB['eng_last_nm'];
		$DATA['mb_co_name'] = $MB['mb_co_name'];
		$DATA['mb_co_name_eng'] = $MB['mb_co_name_eng'];
		$DATA['mb_co_reg_num'] = $mb_co_reg_num;
		$DATA['mb_co_owner'] = $MB['mb_co_owner'];
		$DATA['corp_officer_div'] = $MB['corp_officer_div'];
		$DATA['corp_num'] = $corp_num;
		$DATA['corp_rdate'] = $MB['corp_rdate'];
		$DATA['corp_noneprofit'] = $MB['corp_noneprofit'];
		$DATA['corp_forigner'] = $MB['corp_forigner'];

		if($MB['corp_phone'] || $MB['corp_phone_ineb']) {
			$DATA['corp_phone'] = ($MB['corp_phone_ineb']) ? DGuardDecrypt($MB['corp_phone_ineb']) : masterDecrypt($MB['corp_phone'], false);
		}

		$DATA['foreigner'] = $MB['foreigner'];
		$DATA['mb_email'] = $MB['mb_email'];

		if($MB['mb_hp'] || $MB['mb_hp_ineb']) {
			$DATA['mb_hp'] = ($MB['mb_hp_ineb']) ? DGuardDecrypt($MB['mb_hp_ineb']) : masterDecrypt($MB['mb_hp'], false);
		}

		$DATA['zip_num'] = $MB['zip_num'];
		$DATA['mb_addr1'] = $MB['mb_addr1'];
		$DATA['mb_addr2'] = $MB['mb_addr2'];
		$DATA['mb_addr3'] = $MB['mb_addr3'];
		$DATA['mb_addr_jibeon'] = $MB['mb_addr_jibeon'];
		$DATA['mb_mailling'] = $MB['mb_mailling'];
		$DATA['mb_sms'] = $MB['mb_sms'];
		$DATA['bank_name'] = $MB['bank_name'];
		$DATA['bank_code'] = $MB['bank_code'];

		if($MB['account_num'] || $MB['account_num_ineb']) {
			$DATA['account_num'] = ($MB['account_num_ineb']) ? DGuardDecrypt($MB['account_num_ineb']) : masterDecrypt($MB['account_num'], false);
		}

		$DATA['bank_private_name'] = $MB['bank_private_name'];
		$DATA['bank_private_name_sub'] = $MB['bank_private_name_sub'];
		$DATA['business_license'] = $MB['business_license'];
		$DATA['loan_co_license'] = $MB['loan_co_license'];
		$DATA['id_card'] = $MB['id_card'];
		$DATA['bankbook'] = $MB['bankbook'];
		$DATA['junior_doc1'] = $MB['junior_doc1'];
		$DATA['junior_doc2'] = $MB['junior_doc2'];
		$DATA['junior_doc3'] = $MB['junior_doc3'];
		$DATA['corp_deungibu_doc'] = $MB['corp_deungibu_doc'];
		$DATA['corp_owner_id_card_doc'] = $MB['corp_owner_id_card_doc'];
		$DATA['corp_owner_quest_doc'] = $MB['corp_owner_quest_doc'];
		$DATA['corp_stockholders_doc'] = $MB['corp_stockholders_doc'];
		$DATA['corp_ingam_doc'] = $MB['corp_ingam_doc'];
		$DATA['corp_noneprofit_policy_doc'] = $MB['corp_noneprofit_policy_doc'];
		$DATA['indi_etc_identfy_doc1'] = $MB['indi_etc_identfy_doc1'];
		$DATA['indi_etc_identfy_doc2'] = $MB['indi_etc_identfy_doc2'];
		$DATA['identify_zip_file'] = $MB['identify_zip_file'];
		$DATA['etcfile1'] = $MB['etcfile1'];
		$DATA['etcfile2'] = $MB['etcfile2'];
		$DATA['all_doc_check_yn'] = $MB['all_doc_check_yn'];
		$DATA['mb_ci'] = $MB['mb_ci'];
		$DATA['edit_datetime'] = $MB['edit_datetime'];
		$DATA['mb_10'] = $MB['mb_10'];
		$DATA['kyc_reg_dd'] = $MB['kyc_reg_dd'];

		$ARR = array("result"=>"success", "message"=>"");
		$ARR['minfo'] = $DATA;


		// 차주 신용정보 기록내역 읽어오기 (가장 최근 기록자료)
		$ARR['chajuinfo'] = array();
		if($same_mb_arr) {
			if( $CHAJU = sql_fetch("SELECT idx, mb_no, mb_legal_num, credit_score, rating_cp, limit_amt FROM cf_chaju WHERE mb_no IN(".$same_mb_arr.") AND (credit_score!='' OR rating_cp!='') ORDER BY idx DESC LIMIT 1") ) {
				$ARR['chajuinfo'] = $CHAJU;
			}
		}

		// 회원 AML정보 (가장 최근 기록자료)
		$AML_TABLE = ($member_type == '2') ? "g5_member_aml_corp" : "g5_member_aml_indi";
		if( $AML = sql_fetch("SELECT * FROM {$AML_TABLE} WHERE mb_no IN(".$same_mb_arr.") ORDER BY edit_dt DESC, reg_dt DESC LIMIT 1") ) {
			$ARR['aml'] = $AML;
		}

		//echo json_encode($ARR);
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	}
	else {

		$ARR = array("result"=>"fail", "message"=>"회원데이터가 존재하지 않습니다.");
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	}

}

sql_close();
exit;

?>