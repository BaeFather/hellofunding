<?
###############################################################################
## 원금 연체 이자 일자별 자동 생성 (최종회차만)
## CRONTAB에 등록-> CLI 로 메일 실행
## 최종회차 연체건에만 적용 가능 !!!
## 초안 : 전승찬 차장, 보완 배재수 부장
## php -q /home/crowdfund/public_html/adm/repayment/make_bill_overdue.exec.php [mode] [연체시작일] [지정상품번호: 설정값이 없을 경우 전체상품으로 대상으로 처리]
## php -q /home/crowdfund/public_html/adm/repayment/make_bill_overdue.exec.php debug 2022-02-08 8068
###############################################################################

include_once('/home/crowdfund/public_html/common.cli.php');

$mode    = ($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : "debug";
$today   = ($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : date("Y-m-d");
$prd_idx = ($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : '';


//$testBillTable = "cf_product_bill_08000_test";

$fromday = $today;
$today   = date("Y-m-d");

$target_day = $fromday;

$a = 1;
while(strtotime($target_day) <= strtotime($today)) {

	if($mode=='debug') {	echo "target_day : " . $target_day . " / " . $today ."\n"; }

	// 기존 일별연체정산 데이터 삭제
	if($a==1) {
		$sql = "SELECT idx, loan_start_date, loan_end_date, invest_period, invest_days, calc_type FROM cf_product WHERE loan_end_date < '".$today."' AND state = '8'";
		$sql.= ($prd_idx) ? " AND idx = '".$prd_idx."'" : "";

		$res = sql_query($sql);
		$cnt = $res->num_rows;
		for($i=0; $i<$cnt ;$i++) {

			$PRDT = sql_fetch_array($res);

			$billTable  = ($testBillTable) ? $testBillTable : getBillTable($PRDT['idx']);			// 테이블명 cf_product_bill_0X000

			$shortTermProduct = ($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) ? true : false;
			$last_turn = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], false, $shortTermProduct);

			$bill_delete_sql = "DELETE FROM {$billTable} WHERE product_idx = '".$PRDT['idx']."' AND turn = '".$last_turn."' AND is_overdue='Y'";
			echo $bill_delete_sql . "\n";
			sql_query($bill_delete_sql);

		}
	}

	make_overdue_bill($target_day, $prd_idx);

	if($target_day < date('Y-m-d')) {
		$target_day = date("Y-m-d",  strtotime($target_day . " +1 day"));
	}
	else {
		break;
	}

	$a++;

}


function make_overdue_bill($today, $product_idx='') {

	global $CONF;
	global $mode;
	global $testBillTable;

	$yesterday = date("Y-m-d", strtotime($today . " -1 day"));  // 어제 날자

	//if($mode=='debug') { echo "대상일: " . $today . " / 전일: " . $yesterday . "\n"; }

	// 연체중인 상품을 가져온다
	// 3107 [제2892호] 서울 신림 역세권 숙박시설 ABL 2차
	// 5068 [제4782호] 김포 장기동 고창마을자연앤어울림아파트
	// 8068 [제7607호] 소상공인 확정매출채권K 418호
	// 8081 [제7620호] 소상공인 확정매출채권K 419호

	$sql = "SELECT idx, state, loan_start_date, loan_end_date, invest_period, invest_days, calc_type, overdue_rate, invest_usefee, title FROM cf_product WHERE 1 ";
	$sql.= " AND loan_end_date < '".$today."' AND state='8'";
	$sql.= ($product_idx) ? " AND idx='".$product_idx."'" : "";
	//if($mode=='debug') { echo $sql . "\n"; }
	$res = sql_query($sql);
	$cnt = $res->num_rows;

	$db_do = true;

	for($i=0; $i<$cnt ;$i++) {

		$PRDT = sql_fetch_array($res);

		$billTable  = ($testBillTable) ? $testBillTable : getBillTable($PRDT['idx']);			// 테이블명 cf_product_bill_0X000

		$shortTermProduct = ($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) ? true : false;
		$last_turn = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], false, $shortTermProduct);


	//if($mode=='debug') { echo "---------- 품번: ". $PRDT['idx']." ".$PRDT['title']." => " . $PRDT['loan_start_date'] . " ~ " . $PRDT['loan_end_date']." ".$PRDT['state']." ----------\n"; }

		$rtimestamp = time();  // 한 상품은 동일한 시간을 유지한다.


		// 투자자 목록
		$inv_sql = "SELECT * FROM cf_product_invest WHERE product_idx = '".$PRDT['idx']."' AND invest_state = 'Y'";
		$inv_res = sql_query($inv_sql);
		$inv_cnt = $inv_res->num_rows;

		// 회차, 일자순번 계산
		$LAST_TURN = getLastTurn($PRDT['idx']);
		$dno       = $LAST_TURN['dno'] + 1;
		$turn      = $LAST_TURN['turn'];

		// 해당일자의 부분상환기록을 참조한 회차내 순번 추출
		$ptl_sql  = "SELECT IFNULL(MAX(turn_sno), 0) AS turn_sno FROM cf_partial_redemption WHERE product_idx = '".$PRDT['idx']."' AND turn = '".$turn."' AND account_day <= '".$LAST_TURN['bill_date']."'";
		$turn_sno = sql_fetch($ptl_sql)['turn_sno'];   // $turn_sno = $LAST_TURN['turn_sno'];  <== 이거 안씀!!

		//echo $ptl_sql . "\n"; //exit;

		for($j=0; $j<$inv_cnt; $j++) {

			$INVEST = sql_fetch_array($inv_res);

			//if($mode=='debug') { echo $j." 투자자 - ".$INVEST['member_idx'] . "\n"; }

			// 해당 투자자의 마지막 데이타를 가져온다.
			$LAST_MB_TURN = getLastMemberTurn($PRDT['idx'], $LAST_TURN['dno'], $INVEST['member_idx'], $today);

			$daysOfYear   = ( in_array(substr($yesterday,0,4), $CONF['LEAP_YEAR']) ) ? 366 : 365;									// 기간 일수 (윤년구분)
			$day_interest = ($LAST_MB_TURN['remain_principal'] * ($PRDT['overdue_rate']/100)) / $daysOfYear;			// 일별 이자
			$day_fee      = ($LAST_MB_TURN['remain_principal'] * ($PRDT['invest_usefee']/100)) / $daysOfYear;			// 일별 플랫폼이용료

			// 어제자 연체 이자 삽입
			$ins_sql = "
				INSERT INTO
					{$billTable}
				SET
					product_idx = '".$PRDT['idx']."',
					member_idx = '".$INVEST['member_idx']."',
					invest_idx = '".$INVEST['idx']."',
					bill_date = '".$yesterday."',
					repay_date = '".$today."',
					dno = '".$dno."',
					turn = '".$turn."',
					turn_sno = '".$turn_sno."',
					invest_importance = '".$LAST_MB_TURN['invest_importance']."',
					invest_amount = '".$LAST_MB_TURN['invest_amount']."',
					partial_principal = '".$LAST_MB_TURN['partial_principal']."',
					remain_principal = '".$LAST_MB_TURN['remain_principal']."',
					day_interest = '".$day_interest."',
					fee = '".$day_fee."',
					is_overdue = 'Y',
					rtimestamp = '".$rtimestamp."'";

			//if($mode=='debug' && $INVEST['member_idx']=='50515') { echo $ins_sql . "\n"; }
			if($db_do) {
				$ins_res = sql_query($ins_sql);
				$ins_idx = sql_insert_id();
			}

		}		// 투자자루프 종료

		// 상환예정일이 어제엿던 데이타를 오늘 날자로 변경
		$up_sql = "
			UPDATE
				{$billTable}
			SET
				repay_date = '".$today."'
			WHERE product_idx = '".$PRDT['idx']."'
				AND turn = '".$turn."'
				AND is_overdue = 'Y'
				AND repay_date = '".$yesterday."'";

		//if($mode=='debug') { echo $up_sql . "\n"; }
		if($db_do) {
			$up_res = sql_query($up_sql);
			$up_cnt = sql_affected_rows();
		}

		// 실행로그 기록
		$log_sql = "
			INSERT INTO
				cf_log_auto_make_bill
			SET
				product_idx = '".$PRDT['idx']."',
				dno = '".$dno."',
				turn = '".$turn."',
				turn_sno = '".$turn_sno."',
				ins_datetime = NOW()";

		//if($db_do) sql_query($log_sql);
		//if($mode=='debug') { echo $log_sql . "\n"; }

	}		// 상품루프 종료

}


///////////////////////////////////////
// 연체정산의 최종 회차
///////////////////////////////////////
function getLastTurn($product_idx) {

	global $testBillTable;

	$ret = array();

	$billTable = ($testBillTable) ? $testBillTable : getBillTable($product_idx);

	$sql = "
		SELECT
			bill_date, turn, turn_sno, dno, is_overdue
		FROM
			{$billTable}
		WHERE 1
			AND product_idx = '".$product_idx."'
		ORDER BY
			turn DESC,
			dno DESC
		LIMIT 1";

	$row = sql_fetch($sql);
	$ret = $row;

	return $ret;

}

///////////////////////////////////////
// 특정 투자자의 특정일 정산 데이타
// 부분상환내역 계산
///////////////////////////////////////
function getLastMemberTurn($product_idx, $dno, $member_idx, $today) {

	global $testBillTable;

	$billTable = ($testBillTable) ? $testBillTable : getBillTable($product_idx);

	$sql  = "
		SELECT
			*
		FROM
			{$billTable}
		WHERE 1
			AND product_idx = '".$product_idx."'
			AND member_idx = '".$member_idx."'
			AND dno = '".$dno."'
		ORDER BY
			idx DESC
		LIMIT 1";
	$BILL = sql_fetch($sql);


	// 잔여투자금을 계산하기 위한 기존 원금입금합계 추출
	$sql  = "
		SELECT
			IFNULL(SUM(principal),0) AS sum_principal
		FROM
			cf_product_give
		WHERE 1
			AND product_idx = '".$product_idx."'
			AND member_idx = '".$member_idx."'
			AND `date` <= '".$today."'";
	$GIVE = sql_fetch($sql);

	// $today 일자 원금지급내역 확인	<===== 여기만 작업하면 될 듯
	$sql = "
		SELECT
			principal
		FROM
			cf_product_give
		WHERE
			product_idx = '".$product_idx."'
			AND member_idx = '".$member_idx."'
			AND `date` <= '".$today."'";
	$today_paid_principal = sql_fetch($sql)['principal'];


	$BILL['partial_principal'] = ($today_paid_principal) ? $today_paid_principal : 0;
	$BILL['remain_principal']  = $BILL['invest_amount'] - $GIVE['sum_principal'];

	/*
	if ($product_idx=="3107" and $dno>=639) {
		//$PRDT['invest_amount'] =
		$BILL['partial_principal'] =  189541980;
		$BILL['remain_principal']  = 1210458020;
	}
	*/

	return $BILL;

}

?>