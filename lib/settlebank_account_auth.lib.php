<?
###############################################################################\
## 세틀뱅크 계좌점유인증 API 활용 함수
###############################################################################\
/*
API 서버 URL
	테스트베드	https://tbnpay.settlebank.co.kr
	상용 환경		https://npay.settlebank.co.kr

인증 서비스
	예금주성명조회Ⅰ				https://npay.settlebank.co.kr/v1/api/auth/acnt/ownercheck1
	계좌점유인증						https://npay.settlebank.co.kr/v1/api/auth/acnt/ownership/req
	계좌점유인증확인				https://npay.settlebank.co.kr/v1/api/auth/ownership/check

조회 서비스
	계좌점유인증내역조회		https://npay.settlebank.co.kr/v1/api/auth/ownership/translist
	은행점검조회						https://npay.settlebank.co.kr/v1/api/bank/timecheck/detail

개인정보암호화키 :
	테스트용 : SETTLEBANKISGOODSETTLEBANKISGOOD (32byte)
	상용 : 서비스 이행시 별도 통보
	개인정보 암호화 알고리즘: AES-256/ECB/PKCS5Padding 인코딩 base64


위변조 방지 알고리즘
	요청 데이터의 위변조 여부를 검증하기 위해 추가적으로 해쉬키 검증을 수행하며, 해쉬키 생성 알고리즘은 다음과 같습니다.
	SHA-256 Hex Encoding

해쉬생성 인증키
	ST190808090913247723
	테스트용 : ST190808090913247723 (20byte)


제공하는 API 는 REST 를 지향하나, REST 의 규격 전체를 만족하지 않습니다. (대부분의 트랜잭션 요청은 POST Method 만 사용)
요청은 POST method 만을 사용합니다.
변수값에 공통적으로 :,&,?,’,new line, <, > 기호는 전달 불가합니다.
데이터 송수신시 개인정보/중요정보 필드에 대해서는 다음과 같은 암복호화를 수행해야 합니다. (개인정보 AES-256/ECB/PKCS5Padding, 인코딩 Base64)

API의 응답 타임아웃 처리는 30초를 적용 합니다.
*/


//$stbk_mode = 'TEST';	// 테스트모드 (config.php 설정 참조)

///////////////////////////////////////////////////////////////////////////////
// 기본 통신 함수
///////////////////////////////////////////////////////////////////////////////
function stbkCurl($apiGubun, $data=array(), $method) {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;


	$STBK = $CONF['STLBANK'][$stbk_mode];

	// 환경설정값 추가
	$STBK_API = array(
		'ACNT_OWNER_CHECK' => array('title'=>'예금주성명조회',       'detailUrl'=>'/v1/api/auth/acnt/ownercheck1'),
		'AUTH_REQUEST'     => array('title'=>'계좌점유인증요청',     'detailUrl'=>'/v1/api/auth/ownership/req'),
		'AUTH_CHECK'       => array('title'=>'계좌점유인증확인',     'detailUrl'=>'/v1/api/auth/ownership/check'),
		'AUTH_TRANSLIST'   => array('title'=>'계좌점유인증내역조회', 'detailUrl'=>'/v1/api/auth/ownership/translist'),
		'BANK_TIME_CHECK'  => array('title'=>'은행점검조회',         'detailUrl'=>'/v1/api/bank/timecheck/detail'),
	);

	$ret = array();

	$header_string = "Content-Type:application/json;charset=UTF-8;";
	$headers[] = $header_string;


	$stimeStamp = time();

	$api_domain = $STBK['host'];
	$detailUrl  = $STBK_API[$apiGubun]['detailUrl'];
	$title      = $STBK_API[$apiGubun]['title'];

	if(!$detailUrl) {
		$ARR = array('result'=>false, 'message'=>'API구분값 전송오류');
		echo json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
		return;
	}

	//print_r($headers); exit;
	//print_r($data); exit;

	$url = $api_domain . $detailUrl;
	//echo $url; exit;

	$header_str = json_encode($headers, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$data_str = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
//$data_str = http_build_query($data, '', '&');


	$logSql = "
		INSERT INTO
			stbk_curl_request_log
		SET
			rdate      = CURDATE(),
			rtime      = CURTIME(),
			mb_no      = '".$member['mb_no']."',
			title      = '".$title."',
			toDomain   = '".$api_domain."',
			toUrl      = '".$detailUrl."',
			sendHeader = '".$header_str."',
			sendJson   = '".$data_str ."',
			ip         = '".$_SERVER['REMOTE_ADDR']."'";

	sql_query($logSql);
	$log_id = sql_insert_id();		// 로그ID


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_PORT , $STBK['port']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$method = strtoupper($method);

	if($method=="PUT") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	else if($method=="DELETE") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	else if($method=="POST") {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_PORT , $STBK['port']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	}
	else {		// DEFAULT GET
		$url = $api_domain;
	//$url.= ':' . $STBK['port'];
		$url.= ($detailUrl) ? $detailUrl : '';
		$url.= '?' . $data_str;
	}
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header      = substr($result, 0, $header_size);
	$body        = substr($result, $header_size);

	curl_close($ch);


	$ret['http_code'] = $http_code;
	$ret['head']      = $header;
	$ret['body']      = json_decode($body, true);
//$ret['req_url']   = $url;

	//////////////////////
	// 로그 기록 마무리
	//////////////////////
	$result_json = json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$thrSec = time() - $stimeStamp;

	$logSql = "
		UPDATE
			stbk_curl_request_log
		SET
			recvJson = '".$result_json."',
			transaction_id = '".$ret['body']['transaction_id']."',
			thrSec = '".$thrSec."'
		WHERE
			idx = '".$log_id."'";
	sql_query($logSql);

	return $ret;

}

// 거래번호 생성 (개인 회원일 경우에만 사용)
function makeTradeNo() {

	global $member;

	if(!$member['mb_no']) {
		alert('로그인이 필요합니다.');
		return false;
	}
	if($member['member_type']!='1') {
		alert('1원 인증은 개인회원을 대상으로 하는 서비스 입니다.');
		return false;
	}

	$mchtTrdNo = 'HF'.date('Ymd').'M'.$member['mb_no'].'T'.date('His');		// ex. HF20210101M817T120113

	return $mchtTrdNo;

}


// 예금주 성명조회 (헬로 -> 세틀뱅크)
function stbkAcntOwnerCheck($bankCd, $custAcntNo, $mchtCustNm='') {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;

	$STBK = $CONF['STLBANK'][$stbk_mode];

	$reqDt = date('Ymd');
	$reqTm = date('His');

	$mchtTrdNo = makeTradeNo();																								// 가맹점거래번호
	$mchtCustId = aes256ECBEncrypt($STBK['authkey'], $member['mb_no']);				// 고객아이디 혹은 Unique 한 Key (AES256)
	$custAcntNo_enc = aes256ECBEncrypt($STBK['authkey'], $custAcntNo);				// 계좌번호 (AES256)

	$pkt = $STBK['mid'] . $member['mb_no'] . $reqDt . $reqTm . $custAcntNo . $STBK['hashkey'];		// pktHash 구성 : 상점아이디 + 고객아이디(평문) + 요청일자 + 요청시간 + 계좌번호(평문) + 인증키
	$pktHash = bin2hex(hash("sha256", $pkt, true));																								// SHA256암호화 + hex인코딩

	$data = array();
	$data['hdInfo']     = 'SPAY_NA00_1.0';																		// 전문정보 코드 (※ 고정값)
	$data['mchtId']     = $STBK['mid'];																				// 상점아이디
	$data['mchtTrdNo']  = $mchtTrdNo;																					// 가맹점거래번호 한글제외 (※ 한글제외 OID201902210001)
	$data['mchtCustId'] = $mchtCustId;																				// 고객아이디
	$data['reqDt']      = $reqDt;
	$data['reqTm']      = $reqTm;
	$data['bankCd']     = $bankCd;
	$data['custAcntNo'] = $custAcntNo_enc;
	$data['mchtCustNm'] = '';
	$data['mchtCustNm'] = $mchtCustNm;																				// 예금주명 (예금주명을 입력하여 요청 시 입력받은 예금주명과 은행에서 전달받은 예금주명의 일치여부를 세틀뱅크에서 비교 확인 후 성공/실패 코드로 전달. 예금주명이 미입력으로 은행코드와 계좌번호만 전달 시에는 은행에서 전달 된 예금주명을 결과 값으로 리턴)
	$data['custIp']     = $_SERVER['REMOTE_ADDR'];
	$data['pktHash']    = $pktHash;

	if( preg_match("/stbk\.test\.php/", $_SERVER['PHP_SELF']) ) print_rr($data, 'font-size:12;color:#AAA');

	$CRES = stbkCurl('ACNT_OWNER_CHECK', $data, 'POST');
	$RESULT = $CRES['body'];

	return $RESULT;

	/* --------------------------------------------------------------------------
		// 응답전문내용
		outStatCd 	거래상태 				성공:0021 실패:0031
		outRsltCd 	거절코드
		outRsltMsg 	결과메시지
		mchtTrdNo 	가맹점거래번호
		trdNo				세틀뱅크거래번호
		mchtCustNm 	은행에서 조회된 예금주명(AES256)
	-------------------------------------------------------------------------- */
}


// 계좌점유인증 요청 (헬로 -> 세틀뱅크)
function stbkAuthRequest($bankCd, $custAcntNo, $acntOwnerName) {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;

	if(!$member['mb_no']) {
		alert('로그인이 필요합니다.');
		return false;
	}
	if($member['member_type']!='1') {
		alert('1원 인증은 개인회원을 대상으로 하는 서비스 입니다.');
		return false;
	}

	$STBK = $CONF['STLBANK'][$stbk_mode];

	$reqDt = date('Ymd');
	$reqTm = date('His');

	$mchtTrdNo = makeTradeNo();			// 가맹점거래번호

	// pktHash : 상점아이디 + 요청일자 + 요청시간 + 은행코드 + 계좌번호(평문) + 인증키
	// SHA256암호화 + hex인코딩
	$pkt = $STBK['mid'] . $reqDt . $reqTm . $bankCd . $custAcntNo . $STBK['hashkey'];
	$pktHash = bin2hex(hash("sha256", $pkt, true));

	$mchtCustId_enc    = aes256ECBEncrypt($STBK['authkey'], $member['mb_no']);
	$acntOwnerName_enc = aes256ECBEncrypt($STBK['authkey'], $acntOwnerName);
	$custAcntNo_enc    = aes256ECBEncrypt($STBK['authkey'], preg_replace("/-/", "", $custAcntNo));

	$remitterNm     = '헬로';					// 송금자명		(은행적요에 보여지는 내용은 7자리임 ex.1234헬로)
	$remitterNm_enc = aes256ECBEncrypt($STBK['authkey'], $remitterNm);			// 송금자명 (AES256)

	$data = array();
	$data['hdInfo']     = 'SPAY_AA10_1.0';							// 전문정보 코드 (※ 고정값)
	$data['mchtId']     = $STBK['mid'];
	$data['mchtTrdNo']  = $mchtTrdNo;
	$data['mchtCustId'] = $mchtCustId_enc;							// 상점에서 보내주는 고유 고객아이디 혹은 Unique 한 Key (AES256)
	$data['reqDt']      = $reqDt;
	$data['reqTm']      = $reqTm;
	$data['mchtCustNm'] = $acntOwnerName_enc;						// 통장 예금주명 (AES256)
	$data['bankCd']     = $bankCd;
	$data['custAcntNo'] = $custAcntNo_enc;
	$data['authType']   = '4';													// 인증번호유형 (1:숫자 3자리 / 2:영문대문자 + 숫자 2자리 / 3: 한글단어조합 4글자 / 4: 숫자4자리)
	$data['remitterNm'] = $remitterNm_enc;
	$data['textPos']    = 'F';													// 보낸사람 정보(송금인명칭) 위치 (F:전위 / R:후위 / Default: R)
	$data['authVldTm']  = 300;													// 인증 유효시간 : 계좌점유인증 요청 후 성공 응답 시 거래번호 발생 기준으로 인증 유효시간 지정. Default : 600초
	$data['apintTm']    = 60;														// 반복요청 방지 시간 : 지정된 시간 내 반복적으로 요청하는 경우 1원 입금을 진행하지 않고 처리중으로 응답. Default : 600초
	$data['custIp']     = $_SERVER['REMOTE_ADDR'];
	$data['pktHash']    = $pktHash;

	if( preg_match("/stbk\.test\.php/", $_SERVER['PHP_SELF']) ) print_rr($data, 'font-size:12;color:#AAA');

	$CRES = stbkCurl('AUTH_REQUEST', $data, 'POST');
	$RESULT = $CRES['body'];

	return $RESULT;

	/* --------------------------------------------------------------------------
		outStatCd 	거래상태 					성공:0021 실패:0031
		outRsltCd 	거절코드
		outRsltMsg 	결과메시지
		mchtTrdNo 	가맹점거래번호
		trdNo 			세틀뱅크거래번호
		mchtCustId 	고객아이디
		bankChkYn 	은행점검여부 			Y/N
		stDtm 			점검시작일시 			YmdHis
		edDtm 			점검종료일시 			YmdHis
	-------------------------------------------------------------------------- */
}


// 계좌점유인증 확인 (헬로 -> 세틀뱅크)
function stbkAuthCheck($authReq_mchtTrdNo, $authReq_trdNo, $authNo) {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;

	if(!$member['mb_no']) {
		alert('로그인이 필요합니다.');
		return false;
	}
	if($member['member_type']!='1') {
		alert('1원 인증은 개인회원을 대상으로 하는 서비스 입니다.');
		return false;
	}

	$STBK = $CONF['STLBANK'][$stbk_mode];

	$reqDt = date('Ymd');
	$reqTm = date('His');

	$mchtTrdNo = $authReq_mchtTrdNo;		// 가맹점거래번호  : 계좌점유인증 요청시 생성한 거래번호 재사용
	$trdNo     = $authReq_trdNo;				// 세틀뱅크거래번호: 계좌점유인증 요청시 전달받은 세틀뱅크거래번호 재사용

	$mchtCustId     = $member['mb_no'];
	$mchtCustId_enc = aes256ECBEncrypt($STBK['authkey'], $mchtCustId);


	// pktHash : 상점아이디 + 요청일자 + 요청시간 + 가맹점거래번호+ 인증키
	$pkt = $STBK['mid'] . $reqDt . $reqTm . $mchtTrdNo . $STBK['hashkey'];
	$pktHash = bin2hex(hash("sha256", $pkt, true));		// SHA256암호화 + hex인코딩



	$data = array();
	$data['hdInfo']     = 'SPAY_RC10_1.0';
	$data['mchtId']     = $STBK['mid'];
	$data['mchtTrdNo']  = $mchtTrdNo;					// 가맹점거래번호
	$data['trdNo']      = $trdNo;							// 세틀뱅크거래번호
	$data['mchtCustId'] = $mchtCustId_enc;
	$data['reqDt']      = $reqDt;
	$data['reqTm']      = $reqTm;
	$data['authNo']     = $authNo;						// 인증번호
	$data['custIp']     = $_SERVER['REMOTE_ADDR'];
	$data['pktHash']    = $pktHash;

	if( preg_match("/stbk\.test\.php/", $_SERVER['PHP_SELF']) ) print_rr($data, 'font-size:12;color:#AAA');

	$CRES = stbkCurl('AUTH_CHECK', $data, 'POST');
	$RESULT = $CRES['body'];

	return $RESULT;

	/* --------------------------------------------------------------------------
		outStatCd 	거래상태 			성공:0021 실패:0031
		outRsltCd 	거절코드
		outRsltMsg 	결과메시지
		mchtTrdNo 	가맹점거래번호
		trdNo 			세틀뱅크거래번호
		mchtCustId 	고객아이디(AES256)
	-------------------------------------------------------------------------- */
}


// 계좌점유인증내역조회 stbkAuthTransList(원거래일자, 세틀뱅크 원거래번호)
function stbkAuthTransList($orgTrdDt, $authReq_mchtTrdNo, $authReq_trdNo) {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;

	if(!$member['mb_no']) {
		alert('로그인이 필요합니다.');
		return false;
	}
	if($member['member_type']!='1') {
		alert('1원 인증은 개인회원을 대상으로 하는 서비스 입니다.');
		return false;
	}

	$STBK = $CONF['STLBANK'][$stbk_mode];

	$reqDt = date('Ymd');
	$reqTm = date('His');

	$mchtTrdNo = $authReq_mchtTrdNo;		// 가맹점거래번호  : 계좌점유인증 요청시 생성한 거래번호 재사용
	$trdNo     = $authReq_trdNo;				// 세틀뱅크거래번호: 계좌점유인증 요청시 전달받은 세틀뱅크거래번호 재사용

	$mchtCustId     = $member['mb_no'];
	$mchtCustId_enc = aes256ECBEncrypt($STBK['authkey'], $mchtCustId);


	// pktHash : 상점아이디 + 요청일자 + 요청시간 + 가맹점거래번호 + 인증키
	$pkt = $STBK['mid'] . $reqDt . $reqTm . $mchtTrdNo . $STBK['hashkey'];
	$pktHash = bin2hex(hash("sha256", $pkt, true));			// SHA256암호화 + hex인코딩

	$data = array();
	$data['hdInfo']     = 'SPAY_LT10_1.0';
	$data['mchtId']     = $STBK['mid'];
	$data['orgTrdDt']   = $orgTrdDt;					// 원거래일자(Ymd)
	$data['mchtTrdNo']  = $mchtTrdNo;					// 가맹점 거래번호
	$data['trdNo']      = $trdNo;							// 세틀뱅크 원 거래번호
	$data['mchtCustId'] = $mchtCustId_enc;		// 고객아이디(AES256)
	$data['reqDt']      = $reqDt;
	$data['reqTm']      = $reqTm;
	$data['custIp']     = $_SERVER['REMOTE_ADDR'];
	$data['pktHash']    = $pktHash;

	if( preg_match("/stbk\.test\.php/", $_SERVER['PHP_SELF']) ) print_rr($data, 'font-size:12;color:#AAA');

	$CRES = stbkCurl('AUTH_TRANSLIST', $data, 'POST');
	$RESULT = $CRES['body'];

	return $RESULT;

	/* --------------------------------------------------------------------------
		outStatCd 			거래상태 			성공:0021 실패:0031
		outRsltCd				거절코드
		outRsltMsg 			결과메시지
		mchtTrdNo				가맹점거래번호
		trdNo 					세틀뱅크거래번호
		mchtCustId			고객아이디(AES256)
		bankCd 					은행코드
		custAcntNo			계좌번호(마스킹)('-'제외) 	"123****890"
		custAcntSumry 	통장인자내용(고객의 통장에 찍힌 인자내용) (AES256) 	"이베이 A12"
		reqTrdDtm 			인증요청일시 	YmdHis
		chkTrdDtm 			인증확인일시 	YmdHis
		trdStatCd 			인증성공/실패 	성공:0021 실패:0031
		trdRsltCd 			인증실패코드(거절코드 테이블 참조)
		trdRsltMsg 			인증결과메시지
	-------------------------------------------------------------------------- */
}


// 은행점검조회
function stbkbankTimeCheck($bankCd) {

	global $g5;
	global $CONF;
	global $member;
	global $stbk_mode;

	$STBK = $CONF['STLBANK'][$stbk_mode];

	$reqDt = date('Ymd');
	$reqTm = date('His');

	// pktHash : 상점아이디 + 요청일자 + 요청시간 + 은행코드 + 인증키

	$pkt = $STBK['mid'] . $reqDt . $reqTm . $bankCd . $STBK['hashkey'];
	$pktHash = bin2hex(hash("sha256", $pkt, true));			// SHA256암호화 + hex인코딩

	$data = array();
	$data['hdInfo']   = 'SPAY_TP1W_1.0';		// 필수: 전문정보 코드 (고정값)
	$data['pktDivCd'] = 'AA';								// 필수 : 처리구분 (고정값)
	$data['mchtId']   = $STBK['mid'];				// 필수:
	$data['reqDt']    = $reqDt;
	$data['reqTm']    = $reqTm;
	$data['bankCd']   = $bankCd;
	$data['pktHash']  = $pktHash;

	if( preg_match("/stbk\.test\.php/", $_SERVER['PHP_SELF']) ) print_rr($data, 'font-size:12;color:#AAA');

	$CRES = stbkCurl('BANK_TIME_CHECK', $data, 'POST');
	$RESULT = $CRES['body'];

	return $RESULT;

	/* --------------------------------------------------------------------------
		outStatCd 		거래상태				성공:0021 실패:0031
		outRsltCd 		거절코드
		outRsltMsg		결과메시지
		bankCd				은행코드
		bankChkYn			은행점검여부		Y/N (은행의 정기/비정기 점검시간인 경우, 은행으로부터 장애상황에 대한 공유나 거래지연으로 확인되는 경우에 은행점검으로 응답됩니다.)
		stDtm 				점검시작일시		YmdHis
		edDtm 				점검종료일시		YmdHis
	-------------------------------------------------------------------------- */
}


///////////////////////////////////////////////////////////////////////////////
// 거절코드 내용 리턴
///////////////////////////////////////////////////////////////////////////////
function stbkErrMsg($stbk_error_code) {

	if(!$stbk_error_code) { return false; }

	$msg = '';
	switch($stbk_error_code) {
		case 'ST01' : $msg = "존재하지 않는 계좌"; break;
		case 'ST02' : $msg = "유효하지 않는 계좌"; break;
		case 'ST03' : $msg = "이중출금 발생"; break;
		case 'ST04' : $msg = "VAN 요청중 시스템 에러"; break;
		case 'ST05' : $msg = "VAN 응답정보 없음"; break;
		case 'ST06' : $msg = "거래번호 정보가 없음"; break;			//가맹점 거래번호를 새로 채번하지 마시고 인증요청 시 사용한 거래번호를 전달
		case 'ST07' : $msg = "통신장애"; break;
		case 'ST08' : $msg = "이미 등록된 계좌"; break;
		case 'ST09' : $msg = "유효하지 않는 요청전문"; break;

		case 'ST10' : $msg = "내부 시스템 에러"; break;
		case 'ST11' : $msg = "은행점검 시간"; break;
		case 'ST12' : $msg = "출금계좌 잔액부족"; break;
		case 'ST13' : $msg = "ARS 인증 결과가 없음"; break;
		case 'ST14' : $msg = "ARS 인증 요청 값이 상이함"; break;
		case 'ST15' : $msg = "자동이체해지계좌"; break;
		case 'ST16' : $msg = "출금계좌거래제한"; break;
		case 'ST17' : $msg = "주민번호 사업자번호 오류"; break;
		case 'ST18' : $msg = "계좌오류 (간편계좌등록불가)"; break;
		case 'ST19' : $msg = "기타거래불가"; break;

		case 'ST20' : $msg = "계좌오류"; break;
		case 'ST21' : $msg = "계좌번호를 확인해 주세요"; break;			// 수취인 계좌 없음
		case 'ST22' : $msg = "법적 제한 계좌"; break;
		case 'ST23' : $msg = "비실명 계좌"; break;
		case 'ST24' : $msg = "본인명의 계좌를 입력해 주세요"; break;		// 예금주 불일치
		case 'ST25' : $msg = "이미 취소된 거래"; break;
		case 'ST26' : $msg = "취소금액 오류"; break;
		case 'ST27' : $msg = "ARS인증 실패"; break;
		case 'ST28' : $msg = "ARS수신불가"; break;
		case 'ST29' : $msg = "계좌 등록 진행중"; break;

		case 'ST30' : $msg = "환불 진행중"; break;
		case 'ST31' : $msg = "이중송금 발생"; break;
		case 'ST32' : $msg = "납부자 성명조회 실패"; break;
		case 'ST33' : $msg = "1회 한도초과"; break;
		case 'ST34' : $msg = "1일 한도초과"; break;
		case 'ST35' : $msg = "사고계좌 입니다."; break;
		case 'ST36' : $msg = "일정시간이 지나 연결이 끊어졌습니다"; break;
		case 'ST37' : $msg = "간편결제 취소"; break;
		case 'ST38' : $msg = "요청 진행 중"; break;
		case 'ST39' : $msg = "환불중복 요청"; break;

		case 'ST40' : $msg = "처리 중 요청이 있음"; break;
		case 'ST41' : $msg = "서비스용량초과"; break;
		case 'ST42' : $msg = "시스템 BUSY"; break;
		case 'ST43' : $msg = "이미 등록된 계좌입니다"; break;
		case 'ST44' : $msg = "거래 불가 은행"; break;

		case 'ST50' : $msg = "중복전문요청"; break;
		case 'ST51' : $msg = "이미 등록된 현금영수증 사용자"; break;
		case 'ST52' : $msg = "미등록 현금영수증 사용자"; break;
		case 'ST53' : $msg = "이미 해지된 계좌"; break;

		case 'ST60' : $msg = "거래실패"; break;
		case 'ST61' : $msg = "1회한도 금액초과"; break;
		case 'ST62' : $msg = "일한도 금액초과"; break;
		case 'ST63' : $msg = "월한도 금액초과"; break;
		case 'ST64' : $msg = "일한도 건수초과"; break;
		case 'ST65' : $msg = "월한도 건수초과"; break;
		case 'ST66' : $msg = "비밀번호 등록 실패"; break;
		case 'ST67' : $msg = "비밀번호 불일치"; break;
		case 'ST68' : $msg = "서비스 이용정지"; break;
		case 'ST69' : $msg = "정책상 해당 결제서비스를 이용할 수 없습니다. 다른 결제수단으로 이용 부탁드립니다"; break;
	}

	return $msg;

}



function aes256ECBEncrypt($key, $data) {
	if(32 !== strlen($key)) { $key = hash('MD5', $key, true); }
	return @base64_encode(openssl_encrypt($data, "aes-256-ecb", $key, true, str_repeat(chr(0), 16)));
}

function aes256ECBDecrypt($key, $data) {
  if(32 !== strlen($key)) { $key = hash('MD5', $key, true); }
	return @openssl_decrypt(base64_decode($data), "aes-256-ecb", $key, true, str_repeat(chr(0), 16));
}

?>
