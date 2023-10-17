<?

exit;

################################################################################
## 투자 처리 (안씀 investment_proc.php 로 대체됨)
## 2017-04-24 : 개인회원 상품별 투자 금액 제한 관련 내용 추가
################################################################################

include_once('_common.php');
include_once('../lib/sms.lib.php');

if ($_REQUEST['mode']=='' && $_SERVER["REQUEST_METHOD"]!="POST") { echo "ERROR-DATA"; exit; }

$prd_idx           = $_POST["prd_idx"];
$ajax_invest_value = $_POST["ajax_invest_value"];

if(!$member["mb_id"]) { echo "ERROR-LOGIN"; exit; }
if($prd_idx=="") { echo "ERROR-DATA"; exit; }

$sql = "
	SELECT
		A.idx, A.gr_idx, A.category, A.title,
		A.recruit_amount, A.invest_return, A.invest_period, A.invest_usefee,
		A.open_datetime, A.start_datetime, A.end_datetime, A.recruit_period_start, A.recruit_period_end,
		( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_amount,
		( SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_count
	FROM
		cf_product A
	WHERE 1=1
		AND A.idx = '$prd_idx'";
$PRDT = sql_fetch($sql);
if($_REQUEST['mode']=='test') { echo $sql."<br>\n"; print_rr($PRDT); }
if(!$PRDT['idx']) { echo "ERROR-DATA"; exit; }

/* 투자 금액 */
if($PRDT["recruit_amount"]>0) { $recruit_amount = $PRDT["recruit_amount"]; }
else { echo "ERROR-DATA"; exit; }

/* 투자 수익율 */
if($PRDT["invest_return"]>0) { $invest_return = $PRDT["invest_return"]; }
else { echo "ERROR-DATA"; exit; }

/* 투자기간 */
if($PRDT["invest_period"]>0) { $invest_period = $PRDT["invest_period"]; }
else { echo "ERROR-DATA"; exit; }

/* 투자자 플랫폼 이용료 */
if($PRDT["invest_usefee"]>0) { $invest_usefee = $PRDT["invest_usefee"]; }
else { $PRDT["invest_usefee"] = 0; /* echo "ERROR-DATA"; exit; */ }

$product_open_date    = preg_replace("/( |:|-)/", "", $PRDT['open_datetime']);		/* 상점오픈 (투자시작가능)*/
$product_invest_sdate = preg_replace("/( |:|-)/", "", $PRDT['start_datetime']);		/* 상품오픈 (투자시작가능) */
$product_invest_edate = preg_replace("/( |:|-)/", "", $PRDT['end_datetime']);			/* 상품종료 (투자마감) */

if(preg_replace("/-/", "", $PRDT["recruit_period_start"])<=date("Ymd") && preg_replace("/-/", "", $PRDT["recruit_period_end"])>=date("Ymd")){
	if($product_open_date>date("YmdHis")){
		echo "ERROR-DATE";
		exit;
	}
	else{
		if($product_invest_sdate<=date("YmdHis") && $product_invest_edate>=date("YmdHis")){
			if($PRDT["recruit_amount"] > $PRDT["total_invest_amount"]){  // 투자액 세팅
				// 투자 가능
			}
			else{
				echo "ERROR-INVEST-END"; exit;
			}
		}
		else{
			echo "ERROR-DATE"; exit;
		}
	}
}
else{
	echo "ERROR-DATE"; exit;
}


$min_invest_limit = $CONF['min_invest_limit'];
$max_invest_limit = $CONF['max_invest_limit'];

if($ajax_invest_value < $min_invest_limit) { echo "ERROR-MIN-PRICE"; exit; }
if($max_invest_limit!="") {
	if($ajax_invest_value > $max_invest_limit) { echo "ERROR-MAX-PRICE"; exit; }
}
if($ajax_invest_value < 100000) { echo "ERROR-MIN-PRICE"; exit; }


/////////////////////////////////////////////////
// 핀크 중복투자 확인 (기본적으로 중복투자 불허함)
/////////////////////////////////////////////////
if($member['syndi_id']=='finnq') {
	$INVESTED = sql_fetch("SELECT IFNULL(SUM(amount), 0) AS invest_amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND member_idx='".$member['mb_no']."' AND invest_state='Y' AND syndi_id='finnq'");
	if($INVESTED['invest_amount'] > 0) {
		echo "ERROR-FINNQ-DUPLICATE-INVEST"; exit;
	}
}


if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 모집중이거나 이자상환중인 (원금상환이 완료되지 않은) 동일차주상품 SELECT (현재 열람중인 상품도 포함)
	$sql2 = "SELECT idx FROM cf_product WHERE state IN ('', '1') AND gr_idx='".$PRDT['gr_idx']."' AND idx > '{$CONF['old_type_end_prdt_idx']}' ORDER BY idx";
	//echo $sql2."<br>\n";
	$res  = sql_query($sql2);
	$rcnt = $res->num_rows;
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

		$sql3 = "SELECT IFNULL(SUM(amount), 0) AS sum_invest_amount FROM cf_product_invest WHERE member_idx='".$member['mb_no']."' AND product_idx IN ($prd_idx_arr) AND invest_state='Y'";
		//echo $sql3."<br>\n";
		$INVEST_PRDT = sql_fetch($sql3);
	}
}

// 잔여 모집금액
$need_recruit_amount = $PRDT["recruit_amount"] - $PRDT["total_invest_amount"];

// 투자 가능금액 설정
$invest_possible_amount = $need_recruit_amount;

//--- 개인회원의 경우 투자등급에 따른 투자 가능금액 산출 -------------------
if($member['member_type']=='1') {
	if( in_array($member['member_investor_type'], array('1','2')) ) {
		$limit_amount = ($is_group_product) ? $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] : $INDI_INVESTOR[$member['member_investor_type']]['single_product_limit'];
		$_invest_possible_amount = $limit_amount - $INVEST_PRDT['sum_invest_amount'];

		if($_invest_possible_amount > $member['invest_possible_amount']) {
			$invest_possible_amount = ($PRDT['category']=='2' && $member['member_investor_type']=='1') ? $member['invest_possible_amount_prpt'] : $member['invest_possible_amount'];
		}
		else {
			$invest_possible_amount = ($PRDT['category']=='2' && $member['member_investor_type']=='1') ? $member['invest_possible_amount_prpt'] : $_invest_possible_amount;
		}
	}
}

// 투자 가능금액이 잔여 모집액보다 크면 투자 가능금액 = 잔여모집액
if($invest_possible_amount >= $need_recruit_amount) {
	$invest_possible_amount = $need_recruit_amount;
}

$balance_value = $member["mb_point"];
if($balance_value < $ajax_invest_value) { echo "ERROR-BALANCE"; exit; }						//예치금과 투자할금액  비교
if($invest_possible_amount < $ajax_invest_value) { echo "ERROR-INVEST"; exit; }		//투자가능금액과 투자할금액 비교



## 투자내역 등록 ########################################################
// [투자내역 관리 테이블 추가 : 2016-10-25]
// cf_product_invest : 합산금액 및 최종 처리일시 데이터 취급
// cf_product_invest_detail : 상세내역 전체 등록

$input_day  = date("Y-m-d");
$input_time = date("H:i:s");

$INVEST = sql_fetch("SELECT idx FROM cf_product_invest WHERE member_idx='".$member['mb_no']."' AND product_idx='$prd_idx' AND invest_state='Y' ORDER BY idx DESC LIMIT 1");

if($INVEST['idx']) {				//해당 상품에 기존 투자내역이 있으면 합산 업데이트
	$query = "
		UPDATE
			cf_product_invest
		SET
			amount = amount + {$ajax_invest_value},
			insert_date  = '{$input_day}',
			insert_time  = '{$input_time}'
		WHERE
			idx = '{$INVEST['idx']}'";
	$result = sql_query($query);
	$invest_idx = $INVEST['idx'];
}
else {
	$query = "
		INSERT INTO
			cf_product_invest
		SET
			amount       = '{$ajax_invest_value}',
			member_idx   = '{$member['mb_no']}',
			product_idx  = '{$prd_idx}',
			invest_state = 'Y',
			insert_date  = '{$input_day}',
			insert_time  = '{$input_time}'";
	$result = sql_query($query);
	$invest_idx = sql_insert_id();
}

if($result) {
	//투자건별내역 등록
	$query2 = "
		INSERT INTO
			 cf_product_invest_detail
		 SET
			 invest_idx   = '{$invest_idx}',
			 amount       = '{$ajax_invest_value}',
			 member_idx   = '{$member['mb_no']}',
			 product_idx  = '{$prd_idx}',
			 invest_state = 'Y',
			 insert_date  = '{$input_day}',
			 insert_time  = '{$input_time}'";
	$result2 = sql_query($query2);


	if( $PRDT["recruit_amount"] <= ($PRDT["total_invest_amount"] + $ajax_invest_value) ) {
		$product_update = "UPDATE cf_product SET invest_end_date = '".date("Y-m-d")."' WHERE idx = '".$PRDT["idx"]."'";
		sql_query($product_update);

		// 캐시파일 초기화
		@unlink(G5_DATA_PATH."/cache/productList-active.php");
		@unlink(G5_DATA_PATH."/cache/productList-latest.php");
	}

	$po_content = $PRDT["title"]. "-투자";
	insert_point($member["mb_id"], $ajax_invest_value * (-1), $po_content, '@invest', $member['mb_id'], $member['mb_id'].'-'.uniqid(''), 0);

	//////////////////////////////////////////////////////////////////////
	// 투자완료 문자 발송 (특정회원에게만, 이정환차장 요청. 2018-05-04)
	//////////////////////////////////////////////////////////////////////
	if( in_array($member['mb_id'], array('apollon','akorea')) ) {

		$SMS_DATA = sql_fetch("SELECT * FROM `g5_sms_userinfo` WHERE idx='2' AND use_yn='1'");
		if($SMS_DATA['msg']) {
			$sms_msg = preg_replace("/\{FUNDING_PRICE\}/", price_cutting($ajax_invest_value), $SMS_DATA['msg']);
			$sms_msg = preg_replace("/\{PROJECT_NAME\}/", $PRDT['title'], $sms_msg);
			$rst = unit_sms_send($_admin_sms_number, $member['mb_hp'], $sms_msg);
		}

	}


	echo "SUCCESS"; exit;

}
else {
	echo "ERROR-INVEST-END"; exit;
}

?>