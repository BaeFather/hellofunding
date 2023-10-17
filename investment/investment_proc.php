<?
################################################################################
## 투자 처리
## 2017-04-24 : 개인회원 상품별 투자 금액 제한 관련 내용 추가
## 2017-11-02 : 인사이드뱅크 전문처리파트 제거 -> 관리자 대출실행전 투자자등록프로세스전송 제작요망
## 2018-05-17 : 투자성공 문자발송 Disable처리
################################################################################

include_once('_common.php');
include_once(G5_LIB_PATH.'/sms.lib.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');
//include_once(G5_LIB_PATH.'/invest_queue.lib.php');

// 투자액션로그 기록함수 -------------------------------------
function investLog($logIdx='', $code='', $invest_idx='') {
	global $g5, $config, $member, $_REQUEST;

	// 로그테이블 체크 및 생성 ----------------------------------------
	$tres = sql_query("SHOW TABLES LIKE 'cf_product_invest_log'");
	if( $tres->num_rows == 0 ) {
		$tresx = sql_query("
			CREATE TABLE `cf_product_invest_log` (
				`idx` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`product_idx` VARCHAR(20) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`member_idx` VARCHAR(20) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`invest_idx` VARCHAR(20) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`amount` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
				`res_cd` VARCHAR(50) NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`rts` TIMESTAMP(6) NULL DEFAULT NULL,
				`ets` TIMESTAMP(6) NULL DEFAULT NULL,
				`sec` DECIMAL(12,6) UNSIGNED NULL DEFAULT NULL,
				PRIMARY KEY (`idx`) USING HASH,
				INDEX `product_idx` (`product_idx`, `member_idx`) USING HASH,
				INDEX `invest_idx` (`invest_idx`) USING HASH,
				INDEX `rts` (`rts`) USING HASH
			)
			COMMENT='투자소요시간 측정용'
			COLLATE='utf8_general_ci'
			ENGINE=MEMORY
			ROW_FORMAT=DYNAMIC");
	}
	// 로그테이블 체크 및 생성 ----------------------------------------

	$product_idx   = $_REQUEST['prd_idx'];
	$member_idx    = $member['mb_no'];
	$invest_amount = trim($_REQUEST['ajax_invest_value']);

	if(!$logIdx) {
		$sql = "
			INSERT INTO
				cf_product_invest_log
			SET
				product_idx = '".$product_idx."'
				,member_idx = '".$member_idx."'
				,amount = '".$invest_amount."'
				,rts = CURRENT_TIMESTAMP(6)";
		if( $res = sql_query($sql) ) {
			$logIdx = sql_insert_id();
			return $logIdx;
		}
	}
	else {
		$sql = "
			UPDATE
				cf_product_invest_log
			SET
				ets = CURRENT_TIMESTAMP(6)
				,sec = UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6)) - UNIX_TIMESTAMP(rts)
				,res_cd = '".$code."'
				,invest_idx = '".$invest_idx."'
			WHERE
				idx = '".$logIdx."'";
		if( $res = sql_query($sql) ) {
			return true;
		}
	}
}
// 투자액션로그 기록함수 -------------------------------------


///////////////////////////////////////////////////////////
// 갤럭시아머니트리(58740) 동시접속처리 차단을 위한 슬립타임 설정
///////////////////////////////////////////////////////////
//if( in_array($member['mb_no'], array('58740')) ) {
//	usleep(rand(500000,2000000));
//}

///////////////////////////////////////////////////////////
// :: 중복투자 제한 설정 ::
// 동일회원 동일상품 동일금액 10초내 재투자 신청시 차단
// 추가일 : 2021-12-25
///////////////////////////////////////////////////////////
$cTimeStamp = time()-15;
$cdd = date("Y-m-d", $cTimeStamp);
$cdt = date("H:i:s", $cTimeStamp);

$dupsql = "
	SELECT
		COUNT(idx) AS cnt
	FROM
		cf_product_invest_detail
	WHERE 1
		AND product_idx = '".trim($_REQUEST['prd_idx'])."'
		AND member_idx = '".$member['mb_no']."'
		AND invest_state = 'Y'
		AND amount = '".$ajax_invest_value."'
		AND insert_date = '".$cdd."'
		AND insert_time >= '".$cdt."'
";
//print_r($procsql); exit;
$DUPLICATE_INVEST = sql_fetch($dupsql);
if($DUPLICATE_INVEST['cnt'] > 0) { echo "ERROR:DUPLICATE_INVEST"; sql_close(); exit; }		// 로그 남기지 않고 출력만


///////////////////////////////////////////////////////////
// 투자처리중인 상품여부 체크
//   처리로그등록 후 10초내의 무결과로그건이 있으면
//   투자중인 데이터가 있는것으로...
///////////////////////////////////////////////////////////
$procsql = "
	SELECT
		COUNT(idx) AS cnt
	FROM
		cf_product_invest_log
	WHERE 1
		AND product_idx = '".trim($_REQUEST['prd_idx'])."'
		AND member_idx = '".trim($member['mb_no'])."'
		AND rts >= DATE_ADD(CURRENT_TIMESTAMP(6), INTERVAL -10 SECOND)
		AND res_cd = '' AND ets IS NULL
";
//print_r($procsql); exit;
$process_count = sql_fetch($procsql)['cnt'];
if($process_count) { echo "ERROR:ING_PROCESS"; sql_close(); exit; }		// 로그 남기지 않고 출력만



$investLog_idx = investLog();		// ◀◀◀◀◀ 투자액션로그 기록 시작



if($_REQUEST['mode']=='' && $_SERVER["REQUEST_METHOD"]!="POST") { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit; }
if(!$member['mb_id']) { $ret_code = "ERROR:LOGIN"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }
if(trim($_REQUEST['prd_idx'])=="") { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit; }


// 신한은행 점검시간 진입금지 --------------------------------------------------------------
if( date('Y-m-d H:i:s') >= $CONF['BANK_STOP_SDATE'] && date('Y-m-d H:i:s') < $CONF['BANK_STOP_EDATE'] ) { $ret_code = "ERROR:BANK_PAUSE"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }

// 금결원 점검시간 진입금지 --------------------------------------------------------------
if( date('H:i') >= $CONF['P2PCTR_PAUSE']['STIME'] || date('H:i') <= $CONF['P2PCTR_PAUSE']['ETIME'] ) { $ret_code = "ERROR:P2PCTR_PAUSE"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }


///////////////////////////////
// :: 투자 제한 설정 ::
///////////////////////////////
if( in_array($member['mb_no'], array('11838','14368')) ) { $ret_code = "ERROR:CHECKED_LIMIT_MEMBER"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }



$mb_no             = $member['mb_no'];
$prd_idx           = trim($_REQUEST["prd_idx"]);
$ajax_invest_value = trim($_REQUEST["ajax_invest_value"]);
$advance           = trim($_REQUEST['advance']);

if( ($ajax_invest_value%10000) > 0 ) { $ret_code = "ERROR:MONEY_UNIT"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }

/*
// 투자DB처리 Queue 등록
$REQ = INVEST_QueueRegist($prd_idx, $mb_no, $ajax_invest_value);		// Queue 등록 (상품번호, 회원번호, 투자금액)
if($REQ['result'] == 'success') {
	$queue_idx = $REQ['queue_idx'];
}
*/


///////////////////////////////////////////////////////////////////////
// 중앙기록관리 API를 통하여 회원DB 투자한도 업데이트 실행 1
///////////////////////////////////////////////////////////////////////
$exec_str = "/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/get_p2pctr_limit_amt.exec.php " .  $mb_no;
$exec_result = shell_exec($exec_str);
// 회원DB 정상 업데이트 사인이 없는 경우
if(!$exec_result) {
	$ret_code = "ERROR:P2PCTR_UPDATE_FAIL";
	echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
}


$MB_P2PCTR = sql_fetch("SELECT p2pctr_all_limit, p2pctr_imv_limit, p2pctr_mv_limit FROM g5_member WHERE mb_no='".$mb_no."'");
$member['invest_possible_amount']      = $MB_P2PCTR['p2pctr_all_limit'];
$member['invest_possible_amount_prpt'] = $MB_P2PCTR['p2pctr_imv_limit'];
$member['invest_possible_amount_ds']   = $MB_P2PCTR['p2pctr_mv_limit'];
unset($MB_P2PCTR);


$is_advance_invest = ($advance==1) ? 'Y' : 'N';			// 사전투자모드 설정

$sql = "
	SELECT
		A.idx, A.gr_idx, A.category, A.title,
		A.recruit_amount, A.live_invest_amount AS total_invest_amount, A.invest_end_date,
		A.invest_return, A.invest_period, A.invest_usefee,
		A.open_datetime, A.start_datetime, A.end_datetime, A.recruit_period_start, A.recruit_period_end,
		A.advance_invest, A.advance_invest_ratio, A.platform, A.only_vip, A.vip_mb_no
	FROM
		cf_product A
	WHERE 1=1
		AND A.idx = '".$prd_idx."'
		AND A.start_datetime <= '".G5_TIME_YMDHIS."'
		AND A.end_datetime >= '".G5_TIME_YMDHIS."'";
$PRDT = sql_fetch($sql);
if($_REQUEST['mode']=='test') { print_rr($sql); print_rr($PRDT); }
if(!$PRDT['idx']) { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.') - 투자요청상품번호오류'); sql_close(); exit; }
if( $PRDT['invest_end_date'] && ($PRDT['recruit_amount'] <= $PRDT['total_invest_amount']) ) { $ret_code = "ERROR:INVEST_END"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }

// 2021-02-08 : 전체 투자건수, 금액 개별쿼리로 전환
//$row = sql_fetch("SELECT IFNULL(SUM(amount),0) AS total_invest_amount FROM cf_product_invest WHERE product_idx='".$PRDT['idx']."' AND invest_state='Y'");
//$PRDT['total_invest_amount'] = $row['total_invest_amount'];
//unset($row);

$recruit_amount = $PRDT['recruit_amount'];
if(!$recruit_amount) { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit; }

$YmdHis               = preg_replace("/(-|:| )/", "", G5_TIME_YMDHIS);
$recruit_period_start = preg_replace("/-/", "", $PRDT['recruit_period_start']);
$recruit_period_end   = preg_replace("/-/", "", $PRDT['recruit_period_end']);
$product_open_date    = preg_replace("/(-|:| )/", "", $PRDT['open_datetime']);		// 상점오픈 (투자시작불가)
$product_invest_sdate = preg_replace("/(-|:| )/", "", $PRDT['start_datetime']);		// 투자시작
$product_invest_edate = preg_replace("/(-|:| )/", "", $PRDT['end_datetime']);			// 상품종료 (투자마감)

if($is_advance_invest=='Y') {
	$recruit_amount = round($recruit_amount * ($PRDT['advance_invest_ratio']/100));		// 사전투자비율에 따른 사전투자전체한도액
	if($PRDT['total_invest_amount'] >= $recruit_amount) { $ret_code = "ERROR:ADVANCE_INVEST_AMOUNT"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }		// 사전투자 가능한 금액을 초과 입력 하셨습니다.
	if($product_invest_sdate <= $YmdHis) { $ret_code = "ERROR:ADVANCE_INVEST_AMOUNT"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }										// 사전투자 가능 시간이 지났습니다.
}



/* 투자 수익율 */
if($PRDT['invest_return'] > 0) { $invest_return = $PRDT['invest_return']; }
else { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit; }

/* 투자기간 */
if($PRDT['invest_period'] > 0) { $invest_period = $PRDT['invest_period']; }
else { $ret_code = "ERROR:DATA"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit; }

/* 투자자 플랫폼 이용료 */
$invest_usefee = ($PRDT['invest_usefee'] > 0) ? $PRDT['invest_usefee'] : 0;



if($is_advance_invest!='Y') {
	if( $product_open_date < $YmdHis && $product_invest_edate > $YmdHis ) {
		if($product_invest_sdate < $YmdHis) {
			if($recruit_amount > $PRDT['total_invest_amount']) {  // 투자액 세팅
				// 투자 가능
			}
			else {
				$ret_code = "ERROR:INVEST_END"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
			}
		}
		else {
			$ret_code = "ERROR:DATE"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit;
		}
	}
	else {
		$ret_code = "ERROR:DATE";echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.')'); sql_close(); exit;
	}
}


$min_invest_limit = $CONF['min_invest_limit'];
$max_invest_limit = $CONF['max_invest_limit'];

if($ajax_invest_value < $min_invest_limit) {
	$ret_code = "ERROR:MIN_PRICE"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
}
if($max_invest_limit!="") {
	if($ajax_invest_value > $max_invest_limit) {
		$ret_code = "ERROR:MAX_PRICE"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
	}
}


if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 모집중이거나 이자상환중인 (원금상환이 완료되지 않은) 동일차주상품 SELECT (현재 투자하려는 상품도 포함)
	$sql2 = "SELECT idx FROM cf_product WHERE state IN ('', '1') AND gr_idx='".$PRDT['gr_idx']."' AND idx > '".$CONF['old_type_end_prdt_idx']."' ORDER BY idx";
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

		$sql3 = "SELECT IFNULL(SUM(amount), 0) AS sum_invest_amount FROM cf_product_invest WHERE member_idx='".$mb_no."' AND product_idx IN (".$prd_idx_arr.") AND invest_state='Y'";
		$GROUP_INVESTED = sql_fetch($sql3);
	}
}


// 동일차주상품 투자금액 체크 (개인-일반투자자, 개인-소득적격투자자 일때만 체크)
if( $is_group_product && $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {

	$group_invest_balance = $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] - $GROUP_INVESTED['sum_invest_amount'];

	// 동일차주 투자한도 초과
	if($group_invest_balance < $ajax_invest_value) {
		$ret_code = "ERROR:GROUP_INVEST_AMOUNT_LIMITED";
		echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
	}

}


// 상품의 잔여 모집금액
$need_recruit_amount = $recruit_amount - $PRDT['total_invest_amount'];

// 상품의 투자 가능금액 설정
$invest_possible_amount = $need_recruit_amount;

//--- 개인회원의 경우 투자등급에 따른 투자 가능금액 산출 -------------------
if($member['member_type']=='1') {

	if($member['member_investor_type']=='1') {
		// 투자금과 카테고리별 투자한도 비교
		if($PRDT['category']=='2') {
			if($ajax_invest_value > $member['invest_possible_amount_prpt']) { $ret_code = "ERROR:INVEST_AMOUNT_LIMITED_PRPT"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }		// 부동산 투자제한 금액 초과
		}
		else {
			if($ajax_invest_value > $member['invest_possible_amount']) { $ret_code = "ERROR:INVEST_AMOUNT_LIMITED"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }							// 동산 투자제한 금액 초과
		}
	}

	if( in_array($member['member_investor_type'], array('1','2')) ) {
		$limit_amount = ($is_group_product) ? $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] : $INDI_INVESTOR[$member['member_investor_type']]['single_product_limit'];
		$invest_possible_amount_tmp = $limit_amount - $GROUP_INVESTED['sum_invest_amount'];

		if($invest_possible_amount_tmp > $member['invest_possible_amount']) {
			$invest_possible_amount = ($member['member_investor_type']=='1' && $PRDT['category']=='2') ? $member['invest_possible_amount_prpt'] : $member['invest_possible_amount'];
		}
		else {
			$invest_possible_amount = ($member['member_investor_type']=='1' && $PRDT['category']=='2') ? $member['invest_possible_amount_prpt'] : $invest_possible_amount_tmp;
		}
	}

}

// 투자 가능금액이 잔여 모집액보다 크면 투자 가능금액 = 잔여모집액
if($invest_possible_amount >= $need_recruit_amount) {
	$invest_possible_amount = $need_recruit_amount;
}

// 예치금과 투자 할 금액 비교
if($member['mb_point'] < $ajax_invest_value) { $ret_code = "ERROR:BALANCE"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit; }

// 투자 가능 금액과 투자 할 금액 비교
if($invest_possible_amount < $ajax_invest_value) {
	$ret_code = ($is_advance_invest=='Y') ? "ERROR:ADVANCE_INVEST_AMOUNT" : "ERROR:INVEST";
	echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.') - 잔여모집금초과금액투자'); sql_close(); exit;
}



//**** 법인 및 전문투자자일 경우 단일상품 최대 투자가능금액은 전체모집금액의 40% 까지만 허용한다. (온투법관련 변경사항) ****//
if(G5_TIME_YMD >= $CONF['online_invest_policy_sdate']) {
	if($member['member_type']=='2' || ($member['member_type']=='1' && $member['member_investor_type']=='3')) {
		$sqlx = "SELECT IFNULL(SUM(amount), 0) AS invest_amount FROM cf_product_invest WHERE member_idx='".$mb_no."' AND product_idx='".$prd_idx."' AND invest_state='Y'";
		$PRO_INVESTOR = sql_fetch($sqlx);

		$total_invest_amount = $PRO_INVESTOR['invest_amount'] + $ajax_invest_value;
		$pro_max_limit_amount = floor(($PRDT['recruit_amount'] * $INDI_INVESTOR['3']['invest_able_perc'])/10000) * 10000;


		// 지정투자자 상품이며, 투자자가 본인이며, 법인인 경우 투자가능금액은 제한을 받지 않도록
		if( $PRDT['only_vip']=='1' && in_array($mb_no, explode(",", $PRDT['vip_mb_no'])) && $member['member_type']=='2' ) {
			$pro_max_limit_amount = $invest_possible_amount;
		}


		if($total_invest_amount > $pro_max_limit_amount) {
			$ret_code = "ERROR:MAX_INVEST_AMOUNT_OVER"; echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;
		}
	}
}



## 투자내역 등록 ########################################################
// [투자내역 관리 테이블 추가 : 2016-10-25]
// cf_product_invest : 합산금액 및 최종 처리일시 데이터 취급
// cf_product_invest_detail : 상세내역 전체 등록

$input_datetime = date('Y-m-d H:i:s');
$INPUT_DATE = explode(" ", $input_datetime);
$input_day  = $INPUT_DATE[0];
$input_time = $INPUT_DATE[1];

$INVEST = sql_fetch("SELECT idx FROM cf_product_invest WHERE member_idx='".$mb_no."' AND product_idx='".$prd_idx."' AND invest_state='Y' ORDER BY idx DESC LIMIT 1");

$first_invest = ($INVEST['idx']) ? false : true;		// 첫 투자 여부

// 동일한 투자번호를 가진 투자시도수 (정상/취소 무관)
$invest_try_count = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product_invest_detail WHERE invest_idx='".$INVEST['idx']."' AND member_idx='".$mb_no."'")['cnt'];


////////////////////////////////////////////////////////
// 최종 잔여모집액 체크
////////////////////////////////////////////////////////
//if( $need_recruit_amount <= ($PRDT['recruit_amount'] * 0.1) ) { usleep(rand(0, 1500000)); } 지연시간 셋팅 -> 오버펀딩 됨

$RECHECK = sql_fetch("SELECT IFNULL(SUM(amount),0) AS total_invest_amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y'");
$remain_recruit_amount = $recruit_amount - $RECHECK['total_invest_amount'];		// 잔여모집액

if( $ajax_invest_value > $remain_recruit_amount ) {  // 투자액 세팅
	$ret_code = "ERROR:INVEST"; echo $ret_code; investLog($investLog_idx, $ret_code.'('.__LINE__.') - 잔여모집금초과금액투자'); sql_close(); exit;
}
////////////////////////////////////////////////////////

if($first_invest) {

	// 최초 투자 -----------------------
	$query = "
		INSERT INTO
			cf_product_invest
		SET
			  amount            = '".$ajax_invest_value."'
			, member_idx        = '".$mb_no."'
			, product_idx       = '".$prd_idx."'
			, invest_state      = 'Y'
			, insert_date       = '".$input_day."'
			, insert_time       = '".$input_time."'
			, insert_datetime   = '".$input_datetime."'
			, is_advance_invest = '".$is_advance_invest."'
			, mb_type           = '".$member['member_type']."'
			, investor_type     = '".$member['member_investor_type']."'";
	//print_rr($query);
	if($cookie_syndi_id) { $query.= ", syndi_id = '".$cookie_syndi_id."'";	}
	$result = sql_query($query);

	$invest_idx = sql_insert_id();
	$prin_rcv_no = 'I' . $invest_idx;			// 원리금 수취권 번호: I투자번호 (패턴변경 : 2022-03-21 배부장)	//$prin_rcv_no(기존패턴) = 'M' . $mb_no . 'P' . $prd_idx . 'I' . $invest_idx;
	if($invest_try_count > 0) $prin_rcv_no.= '_' . ($invest_try_count+1);
	sql_query("UPDATE cf_product_invest SET prin_rcv_no='".$prin_rcv_no."' WHERE idx='".$invest_idx."'");

}
else {

	$invest_idx = $INVEST['idx'];
	$prin_rcv_no = 'I' . $invest_idx . '_' . ($invest_try_count+1);

	// 중복 투자 -----------------------
	$query = "
		UPDATE
			cf_product_invest
		SET
			  amount        = amount + ".$ajax_invest_value."
			, insert_date   = '".$input_day."'
			, insert_time   = '".$input_time."'
			, mb_type       = '".$member['member_type']."'
			, investor_type = '".$member['member_investor_type']."'
			, prin_rcv_no   = '".$prin_rcv_no."'
		WHERE
			idx = '".$invest_idx."'";
	$result = sql_query($query);

}


/////////////////////////////////////////////////
//투자건별내역 등록
/////////////////////////////////////////////////
if($result) {

	$query2 = "
		INSERT INTO
			 cf_product_invest_detail
		 SET
			   invest_idx   = '".$invest_idx."'
			 , amount       = '".$ajax_invest_value."'
			 , member_idx   = '".$mb_no."'
			 , product_idx  = '".$prd_idx."'
			 , invest_state = 'Y'
			 , insert_date  = '".$input_day."'
			 , insert_time  = '".$input_time."'
			 , is_advance_invest = '".$is_advance_invest."'";
	if($cookie_syndi_id) { $query2.= ", syndi_id = '".$cookie_syndi_id."'";	}
	$result2 = sql_query($query2);
	$invest_detail_idx = sql_insert_id();

	$after_remain_amount = $remain_recruit_amount - $ajax_invest_value;		// 본 투자건이 정상투자로 등록되면 남을 모집잔여금액

	// 투자액 모집완료시 투자종료일 표기
	//if( $recruit_amount <= ($PRDT['total_invest_amount'] + $ajax_invest_value) ) {  // 2021-06-02일까지 적용됬던 룰
	if( $after_remain_amount <= 0 ) {
		if($is_advance_invest == 'N') {
			$product_update = "UPDATE cf_product SET invest_end_date='".date('Y-m-d')."' WHERE idx = '".$prd_idx."'";
			sql_query($product_update);


			//IF(!preg_match("/소상공인 확정매출채권/i", $PRDT['title']))
			if ($PRDT['category']<>"3") {
				fn_cf_product_admin_report($prd_idx);		// 리포트 데이터 생성
				fn_hello_status_smssend($prd_idx);			// SMS전송
			}
			/*
			else {
				$chk_so_sql = "SELECT COUNT(*) scf_not_end FROM cf_product WHERE category='3' AND start_date='$input_day' AND invest_end_date=''";
				$chk_so_row = sql_fetch($chk_so_sql);
				if ($chk_so_row["scf_not_end"]==0) {
					$report_idx = fn_cf_product_admin_report_scf($input_day);		// 리포트 데이터 생성
					fn_hello_status_smssend_scf($report_idx);			// SMS전송
				}
			}
			*/

			// 캐시파일 초기화
			@unlink(G5_DATA_PATH."/cache/productList-active.php");
			@unlink(G5_DATA_PATH."/cache/productList-latest.php");
		}
	}

	$po_content = $PRDT['title']. "-투자";
	insert_point($member['mb_id'], $ajax_invest_value * (-1), $po_content, '@invest', $member['mb_id'], $member['mb_id'].'-'.uniqid(''), 0);

	//////////////////////////////////////////////////////////////////////
	// (!중요) 상품관리테이블에 실시간 모집금액 반영하기 :: 2021-02-15 추가
	//////////////////////////////////////////////////////////////////////
	$last_update_sql = "UPDATE cf_product SET live_invest_amount = live_invest_amount + {$ajax_invest_value} WHERE idx = '".$prd_idx."'";
	sql_query($last_update_sql);
	//////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////////////
	// 금결원 중앙기록관리 투자신청 기록
	//////////////////////////////////////////////////////////////////////
	$p2pctr_reg_result = p2pctr_invest_register($mb_no, $prd_idx);


	if($p2pctr_reg_result) {

		////////////////////////////////////
		// 투자한도 업데이트 실행 2
		////////////////////////////////////
		$exec_str = "/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/get_p2pctr_limit_amt.exec.php " .  $mb_no;
		$exec_result = shell_exec($exec_str);

		/*
		//--------------------------------------------------------------------
		// 투자완료 문자 발송 (특정회원에게만, 이정환차장 요청. 2018-05-04)
		//--------------------------------------------------------------------
		if( in_array($member['mb_id'], array('apollon','akorea')) ) {
			$SMS_DATA = sql_fetch("SELECT * FROM `g5_sms_userinfo` WHERE idx='2' AND use_yn='1'");
			if($SMS_DATA['msg']) {
				$sms_msg = preg_replace("/\{FUNDING_PRICE\}/", price_cutting($ajax_invest_value), $SMS_DATA['msg']);
				$sms_msg = preg_replace("/\{PROJECT_NAME\}/", $PRDT['title'], $sms_msg);
				$rst = unit_sms_send($CONF['admin_sms_number'], $member['mb_hp'], $sms_msg);
			}
		}
		//--------------------------------------------------------------------
		*/

	}
	else {

		///////////////////////////////////////////////////////////////////////
		// 2022-01-22 추가 : 중앙기록관리 결과가 정상이 아닌 경우  투자취소실행
		///////////////////////////////////////////////////////////////////////
		$exec_str = "/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/emergency_investment_cancel.proc.php " . $invest_detail_idx . " " . $mb_no;
		$exec_result = shell_exec($exec_str);
		if($exec_result) {

			// 상세투자건중 취소되지 않은 정상투자건이 있을 경우 중앙기록관리 재등록
			$invest_detail_count = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product_invest_detail WHERE invest_idx = '".$invest_idx."' AND invest_state='Y'")['cnt'];
			if($invest_detail_count) {
				p2pctr_invest_register($mb_no, $prd_idx);
			}

			$ret_code = "ERROR:P2PCTR_FAIL_CANCEL";
			echo $ret_code;
			investLog($investLog_idx, $ret_code, $invest_idx);
			sql_close();
			exit;

		}

	}


	// 투자성공시 로그
	$ret_code = ($is_advance_invest == 'Y') ? "SUCCESS:ADVANCE_INVEST" : "SUCCESS";
	echo $ret_code;
	investLog($investLog_idx, $ret_code, $invest_idx); sql_close(); exit;

	//INVEST_QueueUpdate($queue_idx, '1');		// Queue 종료처리 (투자성공:1)

}
else {

	$ret_code = "ERROR:INVEST_END";
	echo $ret_code; investLog($investLog_idx, $ret_code); sql_close(); exit;

	//INVEST_QueueUpdate($queue_idx, '2');		// Queue 종료처리 (처리실패:1)

}


@sql_close();
exit;

?>