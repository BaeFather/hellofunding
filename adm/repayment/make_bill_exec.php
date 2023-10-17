#!/usr/local/php/bin/php -q
<?
###############################################################################
## 일별 정산내역 (이자, 플랫폼이용료) 명세 만들기
## /usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_bill_exec.php [1.all 또는 상품번호] [2.mode]
## 명세표 재생성실행시 최초실행당시의 회원정보와 현재의 회원정보간 차이가 있을 수 있으므로,
## 금일 이전 데이터는 유지, 이후 데이터만 삭제하고 재생성한다. ==> 전체 재생성으로 변경함(2021-11-02)
###############################################################################

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path . '/common.cli.php');
include_once($base_path . '/adm/repayment/repayment_util.lib.php');

if(!$_SERVER['argv'][1]) exit;

$prd_idx = trim($_SERVER['argv'][1]);
$action  = $_SERVER['argv'][2];
//$drop_apply_date = (@$_SERVER['argv'][2]) ? trim($_SERVER['argv'][2]) : date('Y-m-d');


$sql0 = "SELECT idx FROM cf_product WHERE 1=1 ";
if($_SERVER['argv'][1]=='all') {
	$sql0.= "";
}
else {
	if( preg_match("/-/", $_SERVER['argv'][1]) ) {
		$TMPARR = explode("-", $_SERVER['argv'][1]);
		if( count($TMPARR)==2 ) {
			$sql0.= " AND idx BETWEEN " . $TMPARR[0] . " AND " . $TMPARR[1];
		}
		else {
			echo "명세서를 제작할 품번의 범위를 정확이 설정해야 합니다. 최소값-최대값\n"; exit;
		}
	}
	else if( preg_match("/\,/", preg_replace("/ /", "", $prd_idx)) ) {
		$PRD_IDX = explode(",", $prd_idx);
		$prd_count = count($PRD_IDX);

		$sql0.= " AND idx IN(";
		for($i=0,$j=1; $i<$prd_count; $i++,$j++) {
			$sql0.= "'".$PRD_IDX[$i]."'";
			$sql0.= ($j < $prd_count) ? ',' : '';
		}
		$sql0.= ") ";
	}
	else {
		$sql0.= " AND idx='".$prd_idx."'";
	}
}
$sql0.= " AND state NOT IN('','3','6','7')";			// 3:투자금모집실패|6:대출취소(기표전)|7:대출취소(기표후) 제외
$sql0.= " ORDER BY loan_start_date ASC, idx ASC";
//echo $sql0 . "\n\n"; exit;

$res0  = sql_query($sql0);
$rows0 = $res0->num_rows;

while($row = sql_fetch_array($res0)) {

	$bill_log_idx = sql_fetch("SELECT idx FROM cf_product_bill_exec_log WHERE product_idx='".$prd_idx."' ORDER BY idx DESC LIMIT 1")['idx'];

	$bill_table = getBillTable($row['idx']);

	// 상품 정보
	$sql = "
		SELECT
			A.idx, A.start_num, A.recruit_amount, A.invest_period, A.invest_days, A.invest_return, A.overdue_rate, A.withhold_tax_rate, A.invest_usefee, A.repay_type, A.loan_start_date, A.loan_end_date, A.ib_trust,
			(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y') AS invest_amount,
			(SELECT COUNT(product_idx) FROM {$bill_table} WHERE product_idx=A.idx) AS bill_count
		FROM
			cf_product A
		WHERE 1=1
			AND A.idx='".$row['idx']."'";
	//echo $sql . "\n\n";
	$PRDT = sql_fetch($sql);
	//print_r($PRDT);

	if($action=='debug') {
		echo "품번.".$row['idx']." (".$PRDT['start_num']."호) :::: ";
	}

	// 특별처리상품 플래그 (초기상품중 종료일이 5일 이전일때 이전회차와 최종상환회차를 동일회차로 처리한 상품 구분)  : 110,115,126,127,149,151,157
	$exceptionProduct = ($PRDT['idx'] <= 172 && substr($PRDT['loan_end_date'],-2) <= '05') ? true : false;
	$shortTermProduct = ($PRDT['invest_days'] > 0) ? 1 : 0;

	$total_invest_days = repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);																						// 상환대상일수
	$total_repay_turn  = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);		// 상환차수


	$exec = true;

	if( $PRDT['recruit_amount'] > $PRDT['invest_amount'] ) $exec = false;
	if( $PRDT['loan_start_date'] == '0000-00-00' || $PRDT['loan_end_date'] == '0000-00-00' ) $exec = false;
	if( $PRDT['loan_end_date'] <= $PRDT['loan_start_date'] ) $exec = false;

	$rtimestamp = time();

	if($exec) {

		// 생성된 정산내역중 정산대상일이 금일이후의 데이터 또는 정산일이 상품종료일보다 큰 데이터 삭제 (연체명세건 제외 -> 연체명세는 생성되지도 않음)
		if($PRDT['bill_count'] > 0) {

			$last_paid_turn = sql_fetch("SELECT IFNULL(MAX(turn),0) AS last_paid_turn FROM cf_product_give WHERE 1 AND product_idx = '".$PRDT['idx']."' AND turn_sno = '0' AND (banking_date IS NOT NULL OR banking_date > '0000-00-00 00:00:00')")['last_paid_turn'];

			$pay_wait_turn = $last_paid_turn + 1;

			$delete_sql = "
				DELETE FROM
					{$bill_table}
				WHERE 1
					AND product_idx='".$PRDT['idx']."' AND turn >= '".$pay_wait_turn."'";
			if($action=='debug') { echo $delete_sql . "\n"; }
			sql_query($delete_sql);

		}


		///////////////////////////
		// 투자자 정보
		///////////////////////////
		$res = sql_query("
			SELECT
				A.idx, A.member_idx, A.amount,
				B.mb_id, B.mb_level, B.member_type, B.member_investor_type, B.is_creditor, B.is_sbiz_owner,
				B.remit_fee, B.remit_fee_sdate
			FROM
				cf_product_invest A
			LEFT JOIN
				g5_member B  ON A.member_idx=B.mb_no
			WHERE 1
				AND A.product_idx = '".$PRDT['idx']."'
				AND A.invest_state IN ('Y','R')
			ORDER BY
				A.idx");
		$invest_count = sql_num_rows($res);
		for($x=0; $x<$invest_count; $x++) {
			$INVEST[$x] = sql_fetch_array($res);
		}



		if($action=='debug') {
			echo "입력시도 : " . number_format($invest_count * $total_invest_days) . "건, ";
		}

		$inserted_count = 0;
		$turn = 1;
		$turn_sno = 0;

		$PARTIAL_AMOUNT = array();

		// 투자일수 루프
		for($i=0,$j=1; $i<$total_invest_days; $i++,$j++) {

			$BILL_DATE[$i] = date("Y-m-d", strtotime($PRDT['loan_start_date']." +$i days"));

			if( !$shortTermProduct ) {
				if($i > 0) {
					if( substr($BILL_DATE[$i],0,7) > substr($BILL_DATE[$i-1],0,7) ) {
						$turn += 1;
						$turn_sno = 0;
					}
				}
			}

			// 예외처리 상품에 대한 최종회차 턴수 조정
			if( $exceptionProduct ) {
				if( $turn > $total_repay_turn ) {
					$turn = $total_repay_turn;
				}
			}

			// 지급예정일 설정 (공유일 처리 안함)
			$repay_date = ( $turn < $total_repay_turn ) ? date("Y-m", strtotime("first day of ".$BILL_DATE[$i]." +1 month")) . "-05" : $PRDT['loan_end_date'];
			if($repay_date > $PRDT['loan_end_date']) $repay_date = $PRDT['loan_end_date'];

			// 부분상환 누적액
			$NUJUK_PARTIAL = sql_fetch("
				SELECT
					IFNULL(SUM(amount),0) AS principal
				FROM
					cf_partial_redemption
				WHERE 1
					AND product_idx='".$PRDT['idx']."' AND account_day<='" . $BILL_DATE[$i] . "'");

			if($i > 0) {
				$PREV_NUJUK_PARTIAL = sql_fetch("
					SELECT
						IFNULL(SUM(amount),0) AS principal
					FROM
						cf_partial_redemption
					WHERE 1
						AND product_idx='".$PRDT['idx']."' AND account_day<='" . $BILL_DATE[$i-1] . "'");		// 어제까지의 부분상환 누적액

				// 당일 부분상환액이 전일까지의 부분상환액 보다 큰 경우 turn_sno를 상승시킨다.
				if($NUJUK_PARTIAL['principal'] > $PREV_NUJUK_PARTIAL['principal']) {
					$turn_sno += 1;
				}
			}

			// 일별 부분상환 액
			$partial_sql = "
				SELECT
					amount
				FROM
					cf_partial_redemption
				WHERE 1
					AND product_idx='".$PRDT['idx']."' AND account_day='" . $BILL_DATE[$i] . "'
				ORDER BY
					idx DESC
				LIMIT 1";
			//echo $partial_sql . "\n";
			$PARTIAL = sql_fetch($partial_sql);


			/////////////////////////////////////////////////////////////////////////
			// 투자자 루프
			// 일별 정산데이터 등록
			/////////////////////////////////////////////////////////////////////////
			for($x=0,$y=1; $x<$invest_count; $x++,$y++) {

				$bill_check_sql = "
					SELECT
						idx
					FROM
						{$bill_table}
					WHERE 1
						AND invest_idx = '".$INVEST[$x]['idx']."'
						AND bill_date = '".$BILL_DATE[$i]."'
						AND turn = '".$turn."'
						AND turn_sno = '".$turn_sno."'";

				$SETTED_BILL = sql_fetch($bill_check_sql);


				$invest_importance = @sprintf("%.10f", ($INVEST[$x]['amount'] / $PRDT['recruit_amount']) * 100);		// 투자비중
				$partial_principal = @floor($PARTIAL['amount'] * ($invest_importance / 100));												// 전체상환액중 본인투자금액 (투자비중 기준, 소수점이하 절사)

				$MB_NUJUK_PARTIAL[$INVEST[$x]['idx']] += $partial_principal;		// 투자자별 누적 상환금

				//echo $INVEST[$x]['idx'] . " 기지급원금 : " . $MB_NUJUK_PARTIAL[$INVEST[$x]['idx']] . "\n";

				if(!$SETTED_BILL['idx']) {

					$remain_principal = $INVEST[$x]['amount'] - $MB_NUJUK_PARTIAL[$INVEST[$x]['idx']];					// 부분상환 차감후 원금

					$daysOfYear   = ( in_array(substr($BILL_DATE[$i],0,4), $CONF['LEAP_YEAR']) ) ? 366 : 365;		// ★★★ 일별이자 산출 변수 (윤년구분) ★★★
					$day_interest = ($remain_principal * ($PRDT['invest_return']/100)) / $daysOfYear;						// 일별 이자
					$day_fee      = ($remain_principal * ($PRDT['invest_usefee']/100)) / $daysOfYear;						// 일별 플랫폼이용료

					// 플랫폼수수료면제자료가 정확하지 못한경우도 있으니 기지급건에 대한 플랫폼 수수료 지급여부를 가져온다.
					$arr_id = $INVEST[$x]['idx'] . '-' . $turn . '-' . $turn_sno;
					if( !is_array($GIVED[$arr_id]) ) {
						$gived_sql = "SELECT idx, fee, remit_fee FROM cf_product_give WHERE invest_idx='".$INVEST[$x]['idx']."' AND turn='".$turn."' AND turn_sno='".$turn_sno."'";
						$GIVED[$arr_id] = sql_fetch($gived_sql);
					}
					//if( $GIVED[$arr_id]['idx'] && ($GIVED[$arr_id]['fee']==0 || $GIVED[$arr_id]['remit_fee']=='1') ) $day_fee = 0;


					// 플랫폼수수료면제자인 경우 면제설정일 이후부터 수수료 0 처리
					if($day_fee > 0) {
						if($INVEST[$x]['remit_fee']=='1' && $BILL_DATE[$i] >= $INVEST[$x]['remit_fee_sdate']) {
							$day_fee = 0;
						}
					}

					//if($INVEST[$x]['mb_id']=='akorea') {
					//	echo $INVEST[$x]['remit_fee'] . " : " . $BILL_DATE[$i] . " : " . $turn . "-" . $turn_sno . " ::::  day_fee : " . $day_fee . "\n";
					//}

					if($action=='debug') {
						echo "product_idx : " . $row['idx'] . "\n";
						echo "turn : " . $turn . "\n";
						echo "day_no : " . $j . "\n";
						echo "invest_idx : " . $INVEST[$x]['idx'] . "\n";
					}


					$sqlx = "
						INSERT INTO
							$bill_table
						SET
							product_idx       = '".$PRDT['idx']."',
							member_idx        = '".$INVEST[$x]['member_idx']."',
							invest_idx        = '".$INVEST[$x]['idx']."',
							bill_date         = '".$BILL_DATE[$i]."',
							repay_date        = '".$repay_date."',
							dno               = '".$j."',
							turn              = '".$turn."',
							turn_sno          = '".$turn_sno."',
							invest_amount     = '".$INVEST[$x]['amount']."',
							invest_importance = '".$invest_importance."',
							partial_principal = '".$MB_NUJUK_PARTIAL[$INVEST[$x]['idx']]."',
							remain_principal  = '".$remain_principal."',
							day_interest      = '".$day_interest."',
							fee               = '".$day_fee."',
							rtimestamp        = '".$rtimestamp."'";
					if($action=='debug') { echo $j . "-" . $y ." : ". $sqlx . "\n"; }

					if( $resx = sql_query($sqlx) ) {
						$inserted_count += 1;

						sql_query("UPDATE cf_product_bill_exec_log SET recordedCount = recordedCount+1 WHERE idx='".$bill_log_idx."'");		// 카운트 등록

						if($rows0 > 1) usleep(300);
					}

					$partial_principal = NULL;

				}

			}

		}		// end for($i=0,$j=1; $i<$total_invest_days; $i++,$j++)



		// 전승찬 2021-10-01 휴가복귀후 실행 예정
		// 정산 요약 테이블
		// 1. cf_product_turn_member (회차별, 회원별) 과
		// 2. cf_product_turn_member_sum (회차별) 데이타를 만들기 위해 추가함
		shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_turn_member.php {$PRDT['idx']} > /dev/null &");

		//if($inserted_count > 0) {
		//  shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_repayment_schedule.cli.php {$PRDT['idx']} {$drop_apply_date}");
		//}

	}

	if($action=='debug') {
		echo "입력완료 : " . number_format($inserted_count) . "건\n";
	}

	unset($PRDT);

	if($rows0 > 1) sleep(1);

}


sql_close();
exit;

?>