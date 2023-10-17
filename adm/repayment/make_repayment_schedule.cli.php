#!/usr/local/php/bin/php -q
<?
###############################################################################
## 상환스케쥴 생성
## /usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_repayment_schedule.cli.php [1.상품번호] [2.회원번호] [3.삭제기준일] [4.debug]
## 대상 테이블 : cf_product_give_test
## 참조 테이블 : cf_product_bill_00000
## ※ 개별적으로 실행가능하나 cf_product_bill_0X000 에 저장된 값을 기반으로
##    회차별 지급일정 데이터를 생성하므로 /adm/repayment/make_bill_exec.php 생성시
##    본 파일이 병행 호출된다.
###############################################################################

if(!@$_SERVER['argv'][1]) { echo "상품번호누락!!\n"; exit; }

$prd_idx         = @$_SERVER['argv'][1];
$member_idx      = @$_SERVER['argv'][2];
$drop_apply_date = (@$_SERVER['argv'][3]) ? trim($_SERVER['argv'][3]) : date('Y-m-d');
$debug           = @$_SERVER['argv'][4];


$path = '/home/crowdfund/public_html';
include_once($path . '/config.cli.php');
include_once($path . '/data/dbconfig.php');
include_once($path . '/data/sms_dbconfig.php');
include_once($path . '/lib/common.lib.php');
include_once($path . '/lib/crypt.lib.php');

//---------------------------------------------------------------------------
$link = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
sql_set_charset("UTF8", $link);
//---------------------------------------------------------------------------

include_once($path . '/adm/repayment/repayment_util.lib.php');


$TBL['product'] = "cf_product";
$TBL['give']    = "cf_product_give_test";
$TBL['invest']  = "cf_product_invest";
$TBL['bill']    = getBillTable($prd_idx, $link);
//print_r($TBL); echo "\n\n"; exit;

$CONF['interest_tax_ratio'] = 0.25;		// 이자소득세 : 25%
$CONF['local_tax_ratio']    = 0.1;		// 지방세: 이자소득세의 10% => 합계 27.5%

///////////////////
// 상품정보
///////////////////
$sql = "
	SELECT
		A.idx, A.state, A.recruit_amount, A.invest_period, A.invest_days, A.invest_return, A.withhold_tax_rate, A.invest_usefee, A.loan_start_date, A.loan_end_date
	FROM
		{$TBL['product']} A
	WHERE 1
		AND A.idx='".$prd_idx."' AND state!='' AND A.invest_end_date!=''";
$PRDT = sql_fetch($sql, true, $link);

$exceptionProduct = ($PRDT['idx'] <= 172  && substr($PRDT['loan_end_date'],-2) <= '05') ? 1 : 0;
$shortTermProduct = ($PRDT['invest_days']>0) ? 1 : 0;

$PRDT['total_invest_days'] = @repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);																					// 상환대상일수
$PRDT['total_repay_turn']  = @repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);		// 전체상환차수


// 투자 정보
$sql = "SELECT idx, member_idx, amount FROM {$TBL['invest']} WHERE 1 AND product_idx='".$PRDT['idx']."' AND invest_state='Y'";
$sql.= ($member_idx) ? " AND member_idx='".$member_idx."'" : "";
$sql.= " ORDER BY idx ASC";
$res = sql_query($sql, true, $link);

$INVEST = array();
while( $R = sql_fetch_array($res) ) {
	if($R['idx']) {
		array_push($INVEST, $R);
	}
}
sql_free_result($res);
//print_r($INVEST); echo "\n";

$invest_count = count($INVEST);

// 기존 정산예정데이터 삭제
$delete_sql = "DELETE FROM {$TBL['give']} WHERE product_idx='".$PRDT['idx']."' AND `date`>='".$drop_apply_date."' AND (finished='' OR banking_date IS NULL)";
$delete_res = sql_query($delete_sql, true, $link);


for($i=0,$turn=1; $i<$PRDT['total_repay_turn']; $i++,$turn++) {

	for($j=0,$n=1; $j<$invest_count; $j++,$n++) {

		//echo "[".$turn . '-'. $n . "]\n";

		// give 테이블 조회
		$sql = "
			SELECT
				idx, `date`, finished,
				(interest + interest_tax + local_tax + fee) AS invest_interest,
				interest, fee, banking_date
			FROM
				{$TBL['give']}
			WHERE 1
				AND invest_idx='".$INVEST[$j]['idx']."' AND turn='".$turn."' AND turn_sno='0'";
		$GIVE = sql_fetch($sql, true, $link);
		//print_r($GIVE); echo "\n";

		$MB = newGetMember($INVEST[$j]['member_idx'], $GIVE['date']);			// 재정산시 정산 시기에 맞는 회원정보를 가져온다.
		$MB['account_num'] = masterDecrypt($MB['account_num'], false);
		//print_r($MB); echo "\n";

		// 정산종료 플래그가 없는 데이터만 입력
		if(!$GIVE['finished']) {

			$sql = "
				SELECT
					invest_idx, remain_principal, repay_date,
					IFNULL(SUM(day_interest),0) AS interest,
					IFNULL(SUM(fee),0) AS fee
				FROM
					{$TBL['bill']}
				WHERE 1
					AND invest_idx='".$INVEST[$j]['idx']."' AND turn='".$turn."' AND turn_sno='0' AND is_overdue='N'";
			//echo $sql."\n\n";
			$BILL = sql_fetch($sql, true, $link);
			$BILL['interest'] = floor(customRoundOff($BILL['interest']));		// 일별이자합계(세전)
			$BILL['fee']      = floor($BILL['fee']);

			$principal    = ($turn == $PRDT['total_repay_turn']) ? $BILL['remain_principal'] : 0;

			// 대부업은 원천징수 면제
			if($MB['is_creditor']=='Y') {
				$interest_tax = 0;
				$local_tax    = 0;
			}
			else {
				$interest_tax = floor( ( ($BILL['interest'] * $CONF['interest_tax_ratio']) / 10) ) * 10;			// 당월 이자소득세 = 이자수익 * 0.25
				$local_tax    = floor( ( ($interest_tax * $CONF['local_tax_ratio']) / 10) ) * 10;				// 당월 지방소득세(원단위 절사)
			}

			$inverest     = $BILL['interest'] - $interest_tax - $local_tax - $BILL['fee'];								// 세후 이자

			$sqlx = "
				INSERT INTO
					{$TBL['give']}
				SET
					`date`         = '".$BILL['repay_date']."',
					invest_idx     = '".$INVEST[$j]['idx']."',
					product_idx    = '".$PRDT['idx']."',
					member_idx     = '".$INVEST[$j]['member_idx']."',
					turn           = '".$turn."',
					turn_sno       = '0',
					invest_amount  = '".$INVEST[$j]['amount']."',
					interest       = '".$inverest."',
					principal      = '".$principal."',
					interest_tax   = '".$interest_tax."',
					local_tax      = '".$local_tax."',
					fee            = '".$BILL['fee']."',
					is_creditor    = '".$MB['is_creditor']."',
					remit_fee      = '".$MB['remit_fee']."',
					receive_method = '',
					bank_name      = '',
					bank_private_name = '',
					account_num    = ''";

			//echo $sqlx . "\n\n";
			sql_query($sqlx, true, $link);

		}

	}

}



sql_close($link);

echo "finished\n";

exit;

?>