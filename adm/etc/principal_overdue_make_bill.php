#!/usr/local/php/bin/php -q
<?
###############################################################################
##  원금 연체 이자 일자별 자동 생성
## crowdfund계정 CLI 로 메일 실행됨
## 30 0 * * * php -q /home/crowdfund/public_html/adm/etc/principal_overdue_make_bill.php [일자] [품번]
###############################################################################
include_once('/home/crowdfund/public_html/common.cli.php');


$today = (@trim($_SERVER['argv']['1'])) ? @trim($_SERVER['argv']['1']) : date("Y-m-d");
$prd_idx = (@trim($_SERVER['argv']['2'])) ? @trim($_SERVER['argv']['2']) : '';

$fromday = $today;
$today   = $fromday;
$thisday = $fromday;

$chk_cnt = 0;  // 무한루프 방지 스위치

while (strtotime($thisday) <= strtotime($today)) {


	make_overdue_bill($thisday);
	echo "\n\n";

	$thisday = date("Y-m-d",  strtotime("+1 day", strtotime($thisday)));
	$chk_cnt++;
	if($chk_cnt>1095) die("safe die");

}
?>
<?
function make_overdue_bill($today) {

	global $CONF;
	global $prd_idx;

	$yesterday = date("Y-m-d", strtotime($today." -1 day"));  // 어제 날자

	echo "오늘 ". $today." 어제 ".$yesterday."\n\n";

	// 연체중인 상품을 가져온다

	// 3107 [제2892호] 서울 신림 역세권 숙박시설 ABL 2차

	//$sql = "SELECT * FROM cf_product WHERE loan_end_date < '$today' AND state='8' AND idx='5068'";		// 5068 [제4782호] 김포 장기동 고창마을자연앤어울림아파트
	//$sql = "SELECT * FROM cf_product WHERE loan_end_date < '$today' AND state='8' AND idx='5611'";		// 5611 [제5280호] 용인 동천동 한빛마을래미안이스트팰리스3단지아파트

	$sql = "SELECT * FROM cf_product WHERE loan_end_date < '".$today."' AND state='8'";
	if($prd_idx) $sql.= " AND idx='".$prd_idx."'";

	$res = sql_query($sql);
	$cnt = $res->num_rows;

	$db_do = "Y";

	for ($i=0 ; $i<$cnt ; $i++) {

		$row = sql_fetch_array($res);

		echo "---------------------------------------- ". $row['idx']." - ".$row['title']." => ".$row['loan_end_date']." ".$row['state']." -----------------------\n";

		// cf_product_bill_0X000 의 테이블 명을 겨져온다.
		$tblname = getBillTable($row['idx']);

		$rtimestamp = time();  // 한 상품은 동일한 시간을 유지한다.

		// 투자자 목록을 가져온다.
		$inv_sql = "SELECT * FROM cf_product_invest WHERE product_idx='".$row['idx']."' AND invest_state='Y'";
		$inv_res = sql_query($inv_sql);
		$inv_cnt = $inv_res->num_rows;

		// 회차, 일자순번 계산
		$last_turn = get_last_turn($row['idx']);
		$dno = $last_turn['dno'] + 1 ;
		$turn = $last_turn['turn'] ;
		if($last_turn['is_overdue']=="N") $turn_sno = $last_turn['turn_sno'] + 1;
		else $turn_sno = $last_turn['turn_sno'];

		for ($j=0 ; $j<$inv_cnt ; $j++) {

			$inv_row = sql_fetch_array($inv_res);

			echo $j." 투자자 - ".$inv_row['member_idx']."\n";

			// 해당 투자자의 마지막 데이타를 가져온다.
			$last_member_turn = get_last_member_turn($row['idx'], $last_turn['dno'], $inv_row['member_idx'], $today);

			// 윤년 계산
			$daysOfYear   = ( in_array(substr($yesterday,0,4), $CONF['LEAP_YEAR']) ) ? 366 : 365;											// 일별이자 산출 변수 (윤년구분)
			// 연체 이자 계산
			$day_interest = ( $last_member_turn['remain_principal'] * ($row['overdue_rate']/100) ) / $daysOfYear;			// 일별 이자
			// 플랫폼 이용료 계산
			$day_fee      = ( $last_member_turn['remain_principal'] * ($row['invest_usefee']/100) ) / $daysOfYear;		// 일별 플랫폼이용료
			$day_fee = number_format($day_fee, 8, ".","");


			// 상환예정일이 어제엿던 데이타를 오늘 날자로 변경
			$up_sql = "
				UPDATE
					$tblname
				SET
					repay_date='$today'
				WHERE 1
					AND product_idx = '".$row['idx']."'
					AND turn = '".$turn."'
					AND turn_sno = '".$turn_sno."'
					AND is_overdue='Y'
					AND repay_date = '".$yesterday."'";

			if($db_do=="Y") $up_res = sql_query($up_sql);
			$up_cnt = sql_affected_rows();
			//echo $up_cnt updated . "\n" . $up_sql."\n";


			// 동일한 조건의 row 존재시 삭제처리
			$del_sql = "
				DELETE FROM
					$tblname
				WHERE 1
					AND invest_idx = '".$inv_row['idx']."'
					AND bill_date = '".$yesterday."'
					AND repay_date = '".$today."'
					AND dno = '".$dno."'
					AND turn = '".$turn."'
					AND turn_sno = '".$turn_sno."'
					AND is_overdue = 'Y'";
			if($db_do=="Y") sql_query($del_sql);

			// 어제자 연체 이자 삽입
			$ins_sql = "
				INSERT INTO
					$tblname
				SET
					product_idx = '".$row['idx']."',
					member_idx = '".$inv_row['member_idx']."',
					invest_idx = '".$inv_row['idx']."',
					bill_date = '".$yesterday."',
					repay_date = '".$today."',
					dno = '".$dno."',
					turn = '".$turn."',
					turn_sno = '".$turn_sno."',
					is_overdue = 'Y',
					invest_importance = '".$last_member_turn['invest_importance']."',
					invest_amount = '".$last_member_turn['invest_amount']."',
					partial_principal = '".$last_member_turn['partial_principal']."',
					remain_principal = '".$last_member_turn['remain_principal']."',
					day_interest = '".$day_interest."',
					fee = '".$day_fee."',
					rtimestamp = '".$rtimestamp."'";

			if($db_do=="Y") sql_query($ins_sql);

			//echo $del_sql.";\n";
			//echo $ins_sql.";\n";
			echo "\n";

		}

		$log_sql = "
				INSERT INTO
					cf_log_auto_make_bill
				SET
					product_idx = '".$row['idx']."',
					dno = '".$dno."',
					turn = '".$turn."',
					turn_sno = '".$turn_sno."',
					ins_datetime = NOW()";
		if($db_do=="Y") sql_query($log_sql);
		//echo $log_sql."\n\n";

	}

}
?>

<?
function get_last_turn($idx) {

	$ret = array();

	$tblname = getBillTable($idx);
	$sql = "
		SELECT
			turn, turn_sno, dno, is_overdue
		FROM
			$tblname
		WHERE 1
			AND product_idx='".$idx."'
		ORDER BY
			turn DESC,
			turn_sno DESC,
			dno DESC
		LIMIT 1";
	$res = sql_query($sql);
	if($res->num_rows) {
		$row = sql_fetch_array($res);
		$ret = $row;
	}

	return $ret;

}

function get_last_member_turn($idx, $dno, $midx, $today) {

	$tblname = getBillTable($idx);

	$sql = "SELECT * FROM $tblname WHERE product_idx='".$idx."' AND member_idx='".$midx."' AND dno='".$dno."'";
	$row = sql_fetch($sql);

	$give_sql = "SELECT SUM(principal) sum_prin FROM cf_product_give WHERE product_idx='".$idx."' AND member_idx='".$midx."' AND date<'".$today."'";
	$give_row = sql_fetch($give_sql);

	$row['partial_principal'] = $give_row['sum_prin'];
	$row['remain_principal'] = $row['invest_amount'] - $give_row['sum_prin'];

	/*
	if($idx=="3107" and $dno>=639) {
		//$row['invest_amount'] =
		$row['partial_principal'] =  189541980;
		$row['remain_principal']  = 1210458020;
	}
	*/

	return $row;

}

?>