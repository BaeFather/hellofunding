<?
exit;
################################################################################
## 투자 처리 (인사이드뱅크 전문전송 처리)
## 2017-04-24 : 개인회원 상품별 투자 금액 제한 관련 내용 추가
################################################################################

include_once('_common.php');
include_once('../lib/insidebank.lib.php');
include_once('../lib/sms.lib.php');

if($_REQUEST['mode']=='' && $_SERVER["REQUEST_METHOD"]!="POST") { echo "ERROR-DATA"; exit; }
if(!$member["mb_id"]) { echo "ERROR-LOGIN"; exit; }
if(trim($_REQUEST['prd_idx'])=="") { echo "ERROR-DATA"; exit; }

// 기존계좌의 금액이전이 완료되지 않은 경우 투자 차단
if($member['insidebank_after_trans_target']=='1') { echo "ERROR-DATA"; exit; }

$mb_no             = $member['mb_no'];
$prd_idx           = trim($_REQUEST["prd_idx"]);
$ajax_invest_value = trim($_REQUEST["ajax_invest_value"]);
$advance           = trim($_REQUEST['advance']);

$is_advance_invest = ($advance==1) ? 'Y' : 'N';			// 사전투자모드 설정

$sql = "
	SELECT
		A.idx, A.gr_idx, A.title,
		A.recruit_amount, A.invest_return, A.invest_period, A.invest_usefee,
		A.open_datetime, A.start_datetime, A.end_datetime, A.recruit_period_start, A.recruit_period_end,
		A.advance_invest, A.advance_invest_ratio,
		( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_amount,
		( SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_count
	FROM
		cf_product A
	WHERE 1=1
		AND A.idx='$prd_idx'";
$PRDT = sql_fetch($sql);
if($_REQUEST['mode']=='test') { echo $sql."<br>\n"; print_rr($PRDT); }
if($PRDT['idx']=='' || $PRDT['recruit_amount']=='') { echo "ERROR-DATA"; exit; }

$recruit_amount = $PRDT['recruit_amount'];
if(!$recruit_amount) { echo "ERROR-DATA"; exit; }

$YmdHis = preg_replace("/(-|:| )/", "", G5_TIME_YMDHIS);
$recruit_period_start = preg_replace("/-/", "", $PRDT['recruit_period_start']);
$recruit_period_end   = preg_replace("/-/", "", $PRDT['recruit_period_end']);
$product_open_date    = preg_replace("/(-|:| )/", "", $PRDT['open_datetime']);		// 상점오픈 (투자시작불가)
$product_invest_sdate = preg_replace("/(-|:| )/", "", $PRDT['start_datetime']);		// 투자시작
$product_invest_edate = preg_replace("/(-|:| )/", "", $PRDT['end_datetime']);			// 상품종료 (투자마감)

if($is_advance_invest=='Y') {
	$recruit_amount = round($recruit_amount * ($PRDT['advance_invest_ratio']/100));											// 사전투자비율에 따른 사전투자전체한도액
	if($PRDT['total_invest_amount'] >= $recruit_amount) { echo "ERROR-ADVANCE-INVEST-AMOUNT"; exit; }		// 사전투자 가능한 금액을 초과 입력 하셨습니다.
	if($product_invest_sdate <= $YmdHis) { echo "ERROR-ADVANCE-INVEST-DATE"; exit; }							// 사전투자 가능 시간이 지났습니다.
}



/* 투자 수익율 */
if($PRDT["invest_return"] > 0) { $invest_return = $PRDT["invest_return"]; }
else { echo "ERROR-DATA"; exit; }

/* 투자기간 */
if($PRDT["invest_period"] > 0) { $invest_period = $PRDT["invest_period"]; }
else { echo "ERROR-DATA"; exit; }

/* 투자자 플랫폼 이용료 */
if($PRDT["invest_usefee"] > 0) { $invest_usefee = $PRDT["invest_usefee"]; }
else { $PRDT["invest_usefee"] = 0; /* echo "ERROR-DATA"; exit; */ }


if($is_advance_invest!='Y') {
	if( $product_open_date < $YmdHis && $product_invest_edate > $YmdHis ) {
		if($product_invest_sdate < $YmdHis) {
			if($recruit_amount > $PRDT["total_invest_amount"]) {  // 투자액 세팅
				// 투자 가능
			}
			else {
				echo "ERROR-INVEST-END"; exit;
			}
		}
		else {
			echo "ERROR-DATE"; exit;
		}
	}
	else {
		echo "ERROR-DATE"; exit;
	}
}


/* 투자금 관련 설정 */
$invest_row = sql_fetch("SELECT * FROM cf_invest");
if($invest_row) {
	if($invest_row["min_invest_nolimit"]=="Y") {
		$min_invest_limit=100000;
	}
	else {
		if($invest_row["min_invest_limit"]=="") {
			$min_invest_limit=100000;
		}
		else {
			$min_invest_limit = ($invest_row["min_invest_limit"]<100000) ? 100000 : $invest_row["min_invest_limit"];
		}
	}

	if($invest_row["max_invest_nolimit"]=="Y") {
		$max_invest_limit="";
	}
	else {
		$max_invest_limit = ($invest_row["min_invest_limit"]=="") ? "" : $invest_row["max_invest_limit"];
	}
}
else {
	$min_invest_limit = 100000;
	$max_invest_limit = "";
}


if($ajax_invest_value < $min_invest_limit) { echo "ERROR-MIN-PRICE"; exit; }
if($max_invest_limit!="") {
	if($ajax_invest_value > $max_invest_limit) { echo "ERROR-MAX-PRICE"; exit; }
}
if($ajax_invest_value < 100000) { echo "ERROR-MIN-PRICE"; exit; }


if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 모집중이거나 이자상환중인 (원금상환이 완료되지 않은) 동일차주상품 SELECT (현재 열람중인 상품도 포함)
	$sql2 = "SELECT idx FROM cf_product WHERE state IN ('', '1') AND gr_idx='".$PRDT['gr_idx']."' AND idx > '{$CONF['old_type_end_prdt_idx']}' ORDER BY idx";
	//echo $sql2."<br>\n";
	$res  = sql_query($sql2);
	$rcnt = sql_num_rows($res);
	if($rcnt) {
		if($rcnt > 1) {
			$is_group_product = true;
		}
		$prd_idx_arr = '';
		for($i=0,$j=1; $i<$rcnt; $i++,$j++) {
			$r = sql_fetch_array($res);
			$prd_idx_arr.="'".$r['idx']."'";
			$prd_idx_arr.= ($j<$rcnt) ? "," : "";
		}

		$sql3 = "SELECT IFNULL(SUM(amount), 0) AS sum_invest_amount FROM cf_product_invest WHERE member_idx='$mb_no' AND product_idx IN ($prd_idx_arr) AND invest_state='Y'";
		//echo $sql3."<br>\n";
		$INVEST_PRDT = sql_fetch($sql3);
	}
}

// 잔여 모집금액
$need_recruit_amount = $recruit_amount - $PRDT["total_invest_amount"];

// 투자 가능금액 설정
$invest_possible_amount = $need_recruit_amount;

//--- 개인회원의 경우 투자등급에 따른 투자 가능금액 산출 -------------------
if($member['member_type']=='1') {
	if( in_array($member['member_investor_type'], array('1','2')) ) {
		$limit_amount = ($is_group_product) ? $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] : $INDI_INVESTOR[$member['member_investor_type']]['single_product_limit'];
		$_invest_possible_amount = $limit_amount - $INVEST_PRDT['sum_invest_amount'];

		if($_invest_possible_amount > $member['invest_possible_amount']) {
			$invest_possible_amount = $member['invest_possible_amount'];
		}
		else {
			$invest_possible_amount = $_invest_possible_amount;
		}
	}
}

// 투자 가능금액이 잔여 모집액보다 크면 투자 가능금액 = 잔여모집액
if($invest_possible_amount >= $need_recruit_amount) {
	$invest_possible_amount = $need_recruit_amount;
}

$balance_value = $member["mb_point"];
if($balance_value < $ajax_invest_value) { echo "ERROR-BALANCE"; exit; }						//예치금과 투자 할 금액 비교
if($invest_possible_amount < $ajax_invest_value) {	//투자 가능 금액과 투자 할 금액 비교
	echo ($is_advance_invest=='Y') ? "ERROR-ADVANCE-INVEST-AMOUNT($invest_possible_amount)" : "ERROR-INVEST"; exit;
}


## 투자내역 등록 ########################################################
// [투자내역 관리 테이블 추가 : 2016-10-25]
// cf_product_invest : 합산금액 및 최종 처리일시 데이터 취급
// cf_product_invest_detail : 상세내역 전체 등록

$input_day  = date("Y-m-d");
$input_time = date("H:i:s");

$INVEST = sql_fetch("SELECT idx, amount, prin_rcv_no FROM cf_product_invest WHERE member_idx='$mb_no' AND product_idx='$prd_idx' AND invest_state='Y' ORDER BY idx DESC LIMIT 1");

$first_invest = ($INVEST['idx']) ? false : true;

///////////////////////////////////////////////////////////////////////////////
// 최초투자
///////////////////////////////////////////////////////////////////////////////
if($first_invest) {

	// 투자번호를 가져오기 위한 임시 등록
	$query = "
		INSERT INTO
			cf_product_invest
		SET
			amount='0',
			member_idx='$mb_no',
			product_idx='$prd_idx',
			is_advance_invest='$is_advance_invest'";
	$result = sql_query($query);
	$invest_idx = sql_insert_id();

	$prin_rcv_no = 'M' . $mb_no . 'P' . $prd_idx . 'I' . $invest_idx;		//원리금 수취권 번호: M회원번호P상품번호I투자번호

	///////////////////////////////////////////////////////
	// 인사이드뱅크 전문 구성 및 발송 (전문번호:2200)
	///////////////////////////////////////////////////////
	$ARR['REQ_NUM']     = "020";								// 전문번호
	$ARR['SUBMIT_GBN']  = "02";									// 거래구분 (투자자등록:02 | 투자자변경:06 | 투자자취소:07)
	$ARR['LOAN_SEQ']    = $prd_idx;							// 대출식별번호 (대출상품번호)
	$ARR['INV_SEQ']     = $invest_idx;					// 대출등록시 투자자건수에 대한 일련번호
	$ARR['INV_CUST_ID'] = $mb_no;								// 투자자ID (투자자식별번호)
	$ARR['PRIN_RCV_NO'] = $prin_rcv_no;					// 원리금 수취권 번호
	$ARR['INV_AMT']     = $ajax_invest_value;		// 투자금액

	$RETURN_ARR = insidebank_request('256', $ARR);	/*** 인사이드뱅크 전문(2200) 전송 ***/

	if($RETURN_ARR['RCODE']=='00000000') {
		$query = "
			UPDATE
				cf_product_invest
			SET
				invest_state = 'Y',
				amount = '$ajax_invest_value',
				insert_date = '$input_day',
				insert_time = '$input_time',
				prin_rcv_no = '$prin_rcv_no'
			WHERE
				idx = '$invest_idx'";
		$result = sql_query($query);
	}
	else {

		// 인사이드뱅크전송결과 에러시 임시 등록한 투자내역 삭제
		$res = sql_query("DELETE FROM cf_product_invest WHERE idx='$invest_idx'");

		echo $RETURN_ARR['ERRMSG'];
		exit;

	}

}
///////////////////////////////////////////////////////////////////////////////
// 추가투자: 해당 상품에 기존 투자내역이 있으면 합산 업데이트
///////////////////////////////////////////////////////////////////////////////
else {

	$invest_idx = $INVEST['idx'];
	$invest_amount = $INVEST['amount'] + $ajax_invest_value;

	$prin_rcv_no = $INVEST['prin_rcv_no'];		//원리금 수취권 번호: M회원번호P상품번호I투자번호

	$ARR['REQ_NUM']     = "020";							// 전문번호
	$ARR['SUBMIT_GBN']  = "06";								// 거래구분 (투자자등록:02 | 투자자변경:06 | 투자자취소:07)
	$ARR['LOAN_SEQ']    = $prd_idx;						// 대출식별번호 (대출상품번호)
	$ARR['INV_SEQ']     = $invest_idx;				// 대출등록시 투자자건수에 대한 일련번호
	$ARR['INV_CUST_ID'] = $mb_no;							// 투자자ID (투자자식별번호)
	$ARR['PRIN_RCV_NO'] = $prin_rcv_no;				// 원리금 수취권 번호: M회원번호P상품번호I투자번호
	$ARR['INV_AMT']     = $invest_amount;			// 투자금액

	$RETURN_ARR = insidebank_request('256', $ARR);	/*** 인사이드뱅크 전문(2200) 전송 ***/

	if($RETURN_ARR['RCODE']=='00000000') {
		$query = "
			UPDATE
				cf_product_invest
			SET
				amount = '$invest_amount',
				insert_date = '$input_day',
				insert_time = '$input_time'
			WHERE
				idx = '$invest_idx'";
		$result = sql_query($query);
	}
	else {

		echo $RETURN_ARR['ERRMSG'];
		exit;

	}

}

//////////////////////
//투자건별내역 등록
//////////////////////
if($result) {

	$query2 = "
		INSERT INTO
			 cf_product_invest_detail
		 SET
			 invest_idx   = '$invest_idx',
			 amount       = '$ajax_invest_value',
			 member_idx   = '$mb_no',
			 product_idx  = '$prd_idx',
			 invest_state = 'Y',
			 insert_date  = '$input_day',
			 insert_time  = '$input_time',
			 is_advance_invest = '$is_advance_invest'";
	$result2 = sql_query($query2);

	if( $recruit_amount <= ($PRDT["total_invest_amount"] + $ajax_invest_value) ) {
		if($is_advance_invest!='Y') {
			$product_update = "UPDATE cf_product SET invest_end_date = '".date("Y-m-d")."' WHERE idx = '".$PRDT["idx"]."'";
			sql_query($product_update);
		}
	}

	$po_content = $PRDT["title"]. "-투자";
	insert_point($member["mb_id"], $ajax_invest_value * (-1), $po_content, '@invest', $member['mb_id'], $member['mb_id'].'-'.uniqid(''), 0);

//$sms_query = "SELECT * FROM `g5_sms_userinfo_default` where use_yn='1' and idx = '2' ";
	$sms_query = "SELECT * FROM `g5_sms_userinfo` WHERE use_yn='1' AND idx='2'";
	$sms_row   = sql_fetch($sms_query);
	if($sms_row["msg"]) {

		$ajax_invest_value2 = number_format($ajax_invest_value/10000);

		$sms_msg = str_replace("{PROJECT_NAME}", $PRDT["title"], $sms_row["msg"]);
		$sms_msg = str_replace("{FUNDING_PRICE}", price_cutting($ajax_invest_value), $sms_msg);
		$sms_msg = str_replace("{TERM}", $PRDT["invest_period"], $sms_msg);
		$sms_msg = str_replace("{YEAR_PROFIT_PER}", $PRDT["invest_return"], $sms_msg);
		$sms_msg = str_replace("{TARGET_PRICE}", price_cutting($PRDT["recruit_amount"]), $sms_msg);

		$rst = unit_sms_send($_admin_sms_number, $member["mb_hp"], $sms_msg);

	}

	echo ($is_advance_invest) ? "SUCCESS-ADVANCE-INVEST" : "SUCCESS"; exit;

}
else {

	echo "ERROR-INVEST-END"; exit;

}

?>