<?
###############################################################################
## WLF 인증과정
##  1. KYC 등록완료 (심사전)
##  2. KYC정보를 이용한 WLF 데이터 추출
##  3. WLF실행
##     g5_member_wlf_log 테이블 생성
##     정상 코드인 경우
##       개인 : 신분증 심사 및 1원인증 통과자 -> KYC 자동승인
##              신분증 및 환급계좌정보를 첨부파일등으로 업로드 -> 심사중 -> 관리자가 직접 승인
##       법인 : KYC 심사중 -> 관리자가 직접 승인
##			비정상 코드인 경우
##				g5_member 에 필드 추가 (wlf_st : 0 실패,1 성공, wlf_log_idx : wlf 로그번호 등록)
##
##	※ 폼 참조 : http://10.22.162.37:8080/view/AML/common/include/HELLOPP/interface/wlf_test.jsp
###############################################################################

include_once('/home/crowdfund/public_html/AML/aml.config.php');

function WLFSend($mb_no, $title='WLF 요청자료 전송', $headers='', $returnType='')
{

	if(!$mb_no) return false;

	global $g5;
	global $_CONF;
	global $AMLCF;

	$ret = array();

	if(!$headers) {
		$headers = ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'];
	}

	$stimeStamp = time();

	$WLFDATA = makeWLFData($mb_no);
	//print_rr($WLFDATA, 'color:red');

	$make_success_yn = $make_error_msg = '';

	if($WLFDATA['SUCCESS_YN'] == 'Y') {

		$data = $WLFDATA['DATA'];

		$detailUrl = "/view/AML/common/include/HELLOPP/interface/wlf.jsp";
		$url = $AMLCF['report_domain'] . $detailUrl;
		$print_url = $AMLCF['report_domain'] . ':' . $AMLCF['report_port'] . $detailUrl;

		$data_str = http_build_query($data, '', '&');
		if( $headers == 'application/json' ) $data_str = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

		$sendJson = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

		$logSql = "
			INSERT INTO
				g5_member_wlf_log
			SET
				rdt      = NOW(),
				title    = '".$title."',
				mb_no    = '".$mb_no."',
				toUrl    = '".$print_url."',
				sendJson = '".$sendJson."',
				ip       = '".$_SERVER['REMOTE_ADDR']."'";
		sql_query($logSql);
		$log_id = sql_insert_id();		// 로그ID

	}
	else {

		$data = $WLFDATA;

		$sendJson = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

		$make_success_yn = $WLFDATA['SUCCESS_YN'];
		$make_error_msg  = $WLFDATA['ERROR_MSG'];

		$logSql = "
			INSERT INTO
				g5_member_wlf_log
			SET
				rdt        = NOW(),
				title      = '".$title."',
				mb_no      = '".$mb_no."',
				SUCCESS_YN = '".$make_success_yn."',
				ERROR_MSG  = '".$make_error_msg."',
				toUrl      = '".$print_url."',
				recvJson   = '".$sendJson."',
				ip         = '".$_SERVER['REMOTE_ADDR']."'";
		sql_query($logSql);

		return $data;

	}


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_PORT, $AMLCF['report_port']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if($method=="PUT") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	else if($method=="DELETE") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	else if($method=="GET") {
		$url = $AMLCF['report_domain'];
		$url.= ':' . $AMLCF['report_port'];
		if($detailUrl) $url.= $detailUrl;
		$url.= '?' . $data_str;
	}
	else {		// DEFAULT POST
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_PORT, $AMLCF['report_port']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header      = substr($result, 0, $header_size);
	$body        = substr($result, $header_size);

	curl_close($ch);


	$ret = array(
		'http_code' => $http_code
		,'head'     => $header
		,'body'     => json_decode($body, true)
		,'req_url'  => $url
	);

	//print_rr($ret); return;

	//if($returnType=='json') $ret = json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);		// 결과값을 json 으로 받기 원할 경우


	//////////////////////
	// 로그 기록 마무리
	//////////////////////
	$result_json = json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);


	$thrSec = time() - $stimeStamp;

	$logSql = "
		UPDATE
			g5_member_wlf_log
		SET
			SUCCESS_YN = '".$ret['body']['SUCCESS_YN']."',
			CODE       = '".$ret['body']['CODE']."',
			ERROR_MSG  = '".$ret['body']['ERROR_MSG']."',
			recvJson   = '" . json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT) . "',
			thrSec     = '".$thrSec."'
		WHERE
			idx = '".$log_id."'";
	sql_query($logSql);

	return $ret['body'];

}


// 회원별 WML 전송데이터 메이킹
function makeWLFData($mb_no='') {

	global $g5;
	global $_CONF;

	$RETARR = array(
		'SUCCESS_YN' => ''
		,'DATA'      => ''
		,'ERROR_MSG' => ''
	);

	if(!$mb_no) {
		$RETARR['SUCCESS_YN'] = 'N';
		$RETARR['ERROR_MSG']  = '회원번호 확인 요망!';
		return $RETARR;
	}

	$MB = sql_fetch("SELECT * FROM g5_member WHERE mb_no = '".$mb_no."' AND mb_level IN('1','2','3','4','5')");
	if(!$MB) {
		$RETARR['SUCCESS_YN'] = 'N';
		$RETARR['ERROR_MSG'] = '회원정보없음!';
		return $RETARR;
	}

	// 법인/개인/개인사업자 구분 : 02:법인, 01:개인, 03:개인사업자
	$customer_div = sprintf("%02d", $MB['member_type']);
	if($MB['member_type']=='1' && $MB['is_owner_operator']=='1') $customer_div = '03';

	if($customer_div) {
		if( in_array($customer_div, array('01','03')) ) {
			$AMLDATA = sql_fetch("SELECT * FROM  g5_member_aml_indi WHERE mb_no = '".$MB['mb_no']."' ORDER BY reg_dt DESC LIMIT 1");
		}
		else {
			$AMLDATA = sql_fetch("SELECT * FROM  g5_member_aml_corp WHERE mb_no = '".$MB['mb_no']."' ORDER BY reg_dt DESC LIMIT 1");
		}
	}

	if(!$AMLDATA) {
		$RETARR['SUCCESS_YN'] = 'N';
		$RETARR['ERROR_MSG']  = 'KYC자료없음!';
		return $RETARR;
	}

	$REQ_DATA['SYS_SYSTEM_DIV']  = 'HELLOPP';
	$REQ_DATA['REQ_USER_ID']     = '5';
	$REQ_DATA['CUSTOMER_NO']     = $MB['mb_no'];
	$REQ_DATA['CUSTOMER_DIV']    = $customer_div;

	$REQ_DATA['CUSTOMER_NM']     = $MB['mb_name'];
	$REQ_DATA['CUSTOMER_ENG_NM'] = ($MB['eng_last_nm'] && $MB['eng_first_nm']) ? $MB['eng_last_nm'].' '.$MB['eng_first_nm'] : $AMLDATA['CUSTOMER_ENG_NM'];
	$REQ_DATA['BIRTH_DD']        = $AMLDATA['BIRTH_DD'];
	$REQ_DATA['COUNTRY_CD']      = $AMLDATA['COUNTRY_CD'];
	$REQ_DATA['LIVE_COUNTRY_CD'] = $AMLDATA['LIVE_COUNTRY_CD'];

	if($AMLDATA['AGENT_YN'] == 'Y') {

		// $AMLDATA['AGENT_mb_id'] 로 에이전트로 등록된 회원정보 가져오기
		$AGENT_AMLDATA = $AMLDATA = sql_fetch("SELECT * FROM  g5_member_aml_indi WHERE mb_no = '".$AMLDATA['AGENT_mb_id']."' ORDER BY reg_dt DESC LIMIT 1");

		$REQ_DATA['AGENT_YN']              = $AMLDATA['AGENT_YN'];								// 대리인 여부 Y,N
		$REQ_DATA['AGENT_NM']              = $AGENT_AMLDATA['CUSTOMER_NM'];				// 대리인명
		$REQ_DATA['AGENT_ENG_NM']          = $AGENT_AMLDATA['CUSTOMER_ENG_NM'];		// 대리인 영문명
		$REQ_DATA['AGENT_BIRTH_DD']        = $AGENT_AMLDATA['BIRTH_DD'];					// 대리인 출생일자
		$REQ_DATA['AGENT_COUNTRY_CD']      = $AGENT_AMLDATA['COUNTRY_CD'];				// 대리인 국적코드
		$REQ_DATA['AGENT_LIVE_COUNTRY_CD'] = $AGENT_AMLDATA['LIVE_COUNTRY_CD'];		// 대리인 거주국가코드

	}


	if($customer_div == '02') {

		// 대표자
		$REQ_DATA['CEO_YN']              = ($MB['corp_officer_div']) ? 'Y' : 'N';			// 대표자 여부 Y,N
		$REQ_DATA['CEO_NM']              = $AMLDATA['CEO_NM'];												// 대표자명
		$REQ_DATA['CEO_ENG_NM']          = $AMLDATA['CEO_ENG_NM'];										// 대표자 영문명
		$REQ_DATA['CEO_BIRTH_DD']        = $AMLDATA['CEO_BIRTH_DD'];									// 대표자 출생일자
		$REQ_DATA['CEO_COUNTRY_CD']      = $AMLDATA['CEO_COUNTRY_CD'];								// 대표자 국적
		$REQ_DATA['CEO_LIVE_COUNTRY_CD'] = $AMLDATA['CEO_ADDR_COUNTRY_CD'];						// 대표자 거주국가코드


		$is_owner = ( in_array($AMLDATA['REAL_OWNR_CHK_CD'], array('32','40')) ) ? 'Y' : 'N';

		$REQ_DATA['REAL_OWNR_YN'] = $is_owner;		// 실소유자 여부 Y,N
		if($is_owner=='Y') {
			$REQ_DATA['REAL_OWNR_NM']              = $AMLDATA['CEO_NM'];									// 실소유자명
			$REQ_DATA['REAL_OWNR_ENG_NM']          = $AMLDATA['CEO_ENG_NM'];							// 실소유자 영문명
			$REQ_DATA['REAL_OWNR_BIRTH_DD']        = $AMLDATA['CEO_BIRTH_DD'];;						// 실소유자 출생일자
			$REQ_DATA['REAL_OWNR_COUNTRY_CD']      = $AMLDATA['CEO_COUNTRY_CD'];					// 실소유자 국적
			$REQ_DATA['REAL_OWNR_LIVE_COUNTRY_CD'] = $AMLDATA['CEO_ADDR_COUNTRY_CD'];;		// 실소유자 거주국가
		}

	}

	$RETARR['SUCCESS_YN'] = 'Y';
	$RETARR['DATA']       = $REQ_DATA;
	$RETARR['ERROR_MSG']  = '';

	return $RETARR;

}

?>