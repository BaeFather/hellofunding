#!/usr/local/php/bin/php -q
<?
###############################################################################
## 대출자 플랫폼이용료 자동수금 스케쥴 생성
##   - 기표, 중도상환처리시 본 파일을 실행 (/adm/repayment/ajax.state_proc.php 에서 호출)
## /usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_loaner_fee_collect_schedule.php [품번]
###############################################################################


set_time_limit(600);

$base_path = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');

$prd_idx = $_SERVER['argv'][1];

if(!$prd_idx) {
	echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}

$sql = "
	SELECT
		A.idx, A.state, A.category, A.category2, A.mortgage_guarantees, A.title,
		A.recruit_amount, A.loan_mb_no, A.loan_usefee, A.loan_usefee_type, A.loan_usefee_repay_count,
		A.loan_start_date, A.loan_end_date,
		(SELECT commission_fee FROM cf_product_container WHERE product_idx=A.idx) AS commission_fee
	FROM
		cf_product A
	WHERE 1
		AND A.idx = '".$prd_idx."'
		AND A.loan_mb_no!='' AND A.isTest = ''";
$PRDT = sql_fetch($sql);

if( !$PRDT['idx'] ) { echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음 (idx)'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit; }
if( $PRDT['state']=='') { echo json_encode(array('result'=>'FAIL', 'message'=>'상태값 없음 (state)'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit; }
if( !in_array($PRDT['state'], array('1','2','5','8')) ) { echo json_encode(array('result'=>'FAIL', 'message'=>'상태값 오류'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit; }
if( $PRDT['loan_start_date']=='0000-00-00' || $PRDT['loan_end_date']=='0000-00-00' ) { echo json_encode(array('result'=>'FAIL', 'message'=>'대출기간 오류'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit; }
if( $PRDT['loan_usefee'] <= 0 ) { echo json_encode(array('result'=>'FAIL', 'message'=>'대출자 플랫폼수수료율 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit; }


// 자동수금계좌정보 가져오기
$ACCT_ROW = sql_fetch("SELECT bank_code, acct_no FROM cf_loaner_auto_collect_acct WHERE product_idx = '".$prd_idx."' AND agree='1' AND allow='1' AND loan_usefee='1'");
$PRDT['bank_code'] = $ACCT_ROW['bank_code'];
$PRDT['acct_no']   = $ACCT_ROW['acct_no'];

if($PRDT['category']=='2' && $PRDT['mortgage_guarantees']=='1') {
	$is_mortgage = true;
}

$PRDT['loan_usefee_repay_count'] = ($PRDT['loan_usefee_type']=='B') ? '1' : $PRDT['loan_usefee_repay_count'];		// 선취일 경우 1, 후취인 경우 분납횟수

if($PRDT['category']=='3' && ($PRDT['category2']=='1' || $PRDT['category2']=='5' || $PRDT['category2']=='6')) {
	$stdYear = ($PRDT['loan_start_date'] > '0000-00-00') ? substr($PRDT['loan_start_date'],0,4) : date('Y');		// 기준년도
	$dayCountOfYear = ( in_array($stdYear, $CONF['LEAP_YEAR']) ) ? 366 : 365;

	$repayDayCount = repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);
	$PRDT['loan_usefee_amt'] = floor((($PRDT['recruit_amount'] * $PRDT['loan_usefee']/100) / $dayCountOfYear) * $repayDayCount);				// (A) 총 대출자플랫폼수수료 (소상공인)
}
else {
	$PRDT['loan_usefee_amt'] = floor($PRDT['recruit_amount'] * $PRDT['loan_usefee'] / 100);				// (A) 총 대출자플랫폼수수료
}
$PRDT['commission_fee_amt'] = floor($PRDT['recruit_amount'] * $PRDT['commission_fee'] / 100);			// (B) 총 중개수수료
$PRDT['schedule_amt']       = $PRDT['loan_usefee_amt'] + $PRDT['commission_fee_amt'];							// 수수료 합계 (A) + (B)

$loan_end_proc_date = getUsableDate($PRDT['loan_end_date']);		// 대출종료일(휴일제외)

$schedule_ym = (substr($PRDT['loan_start_date'], -2) > '01') ? date("Y-m", strtotime("first day of ".$PRDT['loan_start_date']." +1 month")) : substr($PRDT['loan_start_date'], 0, 7);

$MONTH['schedule_amt']       = floor($PRDT['schedule_amt'] / $PRDT['loan_usefee_repay_count']);
$MONTH['commission_fee_amt'] = ceil($PRDT['commission_fee_amt'] / $PRDT['loan_usefee_repay_count']);
$MONTH['loan_usefee_amt']    = $MONTH['schedule_amt'] - $MONTH['commission_fee_amt'];

//$MONTH['loan_usefee_amt']    = $PRDT['loan_usefee_amt'] / $PRDT['loan_usefee_repay_count'];
//$MONTH['commission_fee_amt'] = $PRDT['commission_fee_amt'] / $PRDT['loan_usefee_repay_count'];


$NUJUK['loan_usefee_amt']    = 0;
$NUJUK['commission_fee_amt'] = 0;
$NUJUK['schedule_amt']       = 0;
$NUJUK['repay_amt']          = 0;

$turn = 1;
$LIST = array();

//print_r($PRDT);
//print_r($MONTH);

for($i=0,$j=1; $i<$PRDT['loan_usefee_repay_count']; $i++,$j++) {

	if($PRDT['loan_usefee_type']=='B') {
		// 선취상품이라도 매출채권-실시간선지급 상품은 대출종료일에 수급하도록 일정 조정함.
		$schedule_date = ( $PRDT['category']=='3' && preg_match("/\(실시간/", $PRDT['title']) ) ? $PRDT['loan_end_date'] : $PRDT['loan_start_date'];
	}
	else {
		$schedule_date = date("Y-m-d", strtotime("first day of {$schedule_ym} +{$i} month"));
	}

	$schedule_date = getUsableDate($schedule_date);				// 유효정산일(영업일)

	$loan_usefee_amt    = $MONTH['loan_usefee_amt'];
	$commission_fee_amt = $MONTH['commission_fee_amt'];

	if($j==$PRDT['loan_usefee_repay_count']) {
		$loan_usefee_amt    = $PRDT['loan_usefee_amt'] - $NUJUK['loan_usefee_amt'];
		$commission_fee_amt = $PRDT['commission_fee_amt'] - $NUJUK['commission_fee_amt'];
	}

	$schedule_amt = $loan_usefee_amt + $commission_fee_amt;
	$repay_amt    = $schedule_amt;

	$supply_price = ceil($schedule_amt / 1.1);		// 공급가액 (정산예정액 기준)
	$tax          = $repay_amt - $supply_price;		// 부가세


	$NUJUK['loan_usefee_amt']    += $loan_usefee_amt;
	$NUJUK['commission_fee_amt'] += $commission_fee_amt;
	$NUJUK['schedule_amt']       += $schedule_amt;				// 납입예정액
	$NUJUK['repay_amt']          += $repay_amt;						// 정산예정액

	$schedule_amt_balance = $PRDT['schedule_amt'] - $NUJUK['schedule_amt'];		// 수수료잔액


/*
	echo $turn . "회차 | ";
	echo "수급예정일: " . $schedule_date . " | ";
	echo "대출자수수료: " . number_format($loan_usefee_amt) . " | ";
	echo "중개수수료: " . number_format($commission_fee_amt) . " | ";
	echo "수취예정금액: " . number_format($schedule_amt) . " | ";
	echo "정산금액: " . number_format($repay_amt) . " | ";
	echo "수취누적액: " . number_format($NUJUK['repay_amt']) . " | ";
	echo "공급가: " . number_format($supply_price) . " | ";
	echo "세액: " . number_format($tax) . " | ";
	echo "수취후잔액: " . number_format($schedule_amt_balance) . " | ";
	echo "\n";
*/

	$ARR = array(
		'turn' => $turn,
		'schedule_date'        => $schedule_date,
		'loan_usefee_amt'      => $loan_usefee_amt,
		'commission_fee_amt'   => $commission_fee_amt,
		'schedule_amt'         => $schedule_amt,
		'repay_amt'            => $repay_amt,
		'repay_amt_nujuk'      => $NUJUK['repay_amt'],
		'supply_price'         => $supply_price,
		'tax'                  => $tax,
		'schedule_amt_balance' => $schedule_amt_balance,
		'bank_code'            => $PRDT['bank_code'],
		'acct_no'              => $PRDT['acct_no']
	);

	array_push($LIST, $ARR);


	if($loan_end_proc_date <= $schedule_date) {
		break;
	}

	$turn++;

}


//print_r($LIST);

$list_count = count($LIST);

if($list_count) {

	for($i=0; $i<$list_count; $i++) {

		// 처리되지 않은 스케쥴 확인 및 정보수정
		$sql = "
			SELECT
				idx, schedule_date, turn, bank_code, acct_no,
				loan_usefee_amt, commission_fee_amt, schedule_amt, deposit_amt, repay_amt, supply_price, tax,
				mgtKey, collect_ok
			FROM
				cf_loaner_fee_collect
			WHERE 1
				AND product_idx = '".$prd_idx."'
				AND turn = '".$LIST[$i]['turn']."'
				AND is_drop=''";
		//print_r($sql); echo "\n";
		$C_ROW = sql_fetch($sql);

		// 수금액 및 수금예정일 설정
		//$schedule_date = ($PRDT['loan_usefee_type']=='A') ? $LIST[$i]['schedule_date'] : '';

		if(!$C_ROW['idx']) {

			$sqlx = "
				INSERT INTO
					cf_loaner_fee_collect
				SET
					product_idx        = '".$prd_idx."',
					loan_usefee_type   = '".$PRDT['loan_usefee_type']."',
					turn               = '".$LIST[$i]['turn']."',
					schedule_date      = '".$LIST[$i]['schedule_date']."',
					loan_usefee_amt    = '".$LIST[$i]['loan_usefee_amt']."',
					commission_fee_amt = '".$LIST[$i]['commission_fee_amt']."',
					schedule_amt       = '".$LIST[$i]['schedule_amt']."',
					bank_code          = '".$LIST[$i]['bank_code']."',
					acct_no            = '".$LIST[$i]['acct_no']."',
					repay_amt          = '".$LIST[$i]['repay_amt']."',
					supply_price       = '".$LIST[$i]['supply_price']."',
					tax                = '".$LIST[$i]['tax']."',
					rdate              = NOW()";
					//echo $sqlx."\n";
					sql_query($sqlx);

		}
		else {

			// 미처리 되었고 등록당시의 정보와 현재의 정보가 다른 경우 UPDATE
			if($C_ROW['collect_ok']=='' && $C_ROW['mgtKey']=='') {

				if( strcmp($C_ROW['schedule_date'], $LIST[$i]['schedule_date']) )            $CHANGE_SQL[] = "schedule_date = '".$LIST[$i]['schedule_date']."'";
				if( strcmp($C_ROW['turn'], $LIST[$i]['turn']) )                              $CHANGE_SQL[] = "turn = '".$LIST[$i]['turn']."'";
				if( strcmp($C_ROW['loan_usefee_amt'], $LIST[$i]['loan_usefee_amt']) )        $CHANGE_SQL[] = "loan_usefee_amt = '".$LIST[$i]['loan_usefee_amt']."'";
				if( strcmp($C_ROW['commission_fee_amt'], $LIST[$i]['commission_fee_amt']) )  $CHANGE_SQL[] = "commission_fee_amt = '".$LIST[$i]['commission_fee_amt']."'";
				if( strcmp($C_ROW['schedule_amt'], $LIST[$i]['schedule_amt']) )              $CHANGE_SQL[] = "schedule_amt = '".$LIST[$i]['schedule_amt']."'";
				if( strcmp($C_ROW['bank_code'], $LIST[$i]['bank_code']) )                    $CHANGE_SQL[] = "bank_code = '".$LIST[$i]['bank_code']."'";
				if( strcmp($C_ROW['acct_no'], $LIST[$i]['acct_no']) )                        $CHANGE_SQL[] = "acct_no = '".$LIST[$i]['acct_no']."'";
				if( strcmp($C_ROW['repay_amt'], $LIST[$i]['repay_amt']) )                    $CHANGE_SQL[] = "repay_amt = '".$LIST[$i]['repay_amt']."'";
				if( strcmp($C_ROW['supply_price'], $LIST[$i]['supply_price']) )              $CHANGE_SQL[] = "supply_price = '".$LIST[$i]['supply_price']."'";
				if( strcmp($C_ROW['tax'], $LIST[$i]['tax']) )                                $CHANGE_SQL[] = "tax = '".$LIST[$i]['tax']."'";

				$change_fld_count = count($CHANGE_SQL);

				if( count($CHANGE_SQL) ) {

					$sqlx = "UPDATE cf_loaner_fee_collect SET ";
					for($k=0,$n=1; $k<$change_fld_count; $k++,$n++) {
						$sqlx.= $CHANGE_SQL[$k]. ", ";
					}
					$sqlx.= "last_editdate = NOW()";
					$sqlx.= " WHERE idx = '".$C_ROW['idx']."'";

					echo $sqlx . "\n\n";
					sql_query($sqlx);


					unset($sqlx);
					unset($CHANGE_SQL);
					usleep(10000);

				}

			}

		}

	}

}


sql_close();
exit;

?>