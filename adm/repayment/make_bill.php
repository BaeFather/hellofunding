<?
###############################################################################
## ★★★★★ 상품별,일별,회원별 정산내역 만들기 ★★★★★
## 상품의 대출실행년도별 테이블을 생성하여 이용할 것!!!
###############################################################################

set_time_limit(0);

include_once('./_common.php');

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

if(!$prd_idx) {
	$RETURN_ARR = array('result'=>'FAIL', 'message'=>"품번오류");
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); exit;
}

///////////////////////////
// 투자상품정보
///////////////////////////
$PRDT = sql_fetch("
	SELECT
		title, state, invest_period, invest_days, loan_start_date, loan_end_date
	FROM
		cf_product
	WHERE
		idx='".$prd_idx."'");
//print_rr($PRDT, 'font-size:12px'); exit;


if(!$PRDT) {
	$RETURN_ARR = array('result'=>'PRDT_NULL', 'message'=>'투자번호가 전송되지 않았습니다.');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}
if($PRDT['loan_start_date']=='0000-00-00') {
	$RETURN_ARR = array('result'=>'CHECK_SDATE', 'message'=>'대출실행일 설정을 확인하십시요.');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}
else if($PRDT['loan_end_date']=='0000-00-00') {
	$RETURN_ARR = array('result'=>'CHECK_EDATE', 'message'=>'대출종료일 설정을 확인하십시요.');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}
else if($PRDT['loan_end_date']<=$PRDT['loan_start_date']) {
	$RETURN_ARR = array('result'=>'CHECK_DATE_BALANCE', 'message'=>'대출실행 및 종료일 설정을 정확히 확인하십시요.');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}


if(@shell_exec("ps -ef | grep -v grep | grep 'make_bill_exec.php {$prd_idx}' | wc -l") > 0) {

	$EXEC_LOG = sql_fetch("SELECT scheduleCount, recordedCount FROM cf_product_bill_exec_log WHERE product_idx='".$prd_idx."' ORDER BY idx DESC LIMIT 1");
	$ing_perc = $EXEC_LOG['recordedCount'] / $EXEC_LOG['scheduleCount'] * 100;

	$message = "본 상품에 대한 빌링 프로세스가 실행중입니다.\n";
	$message.= "빌링등록현황 : " . number_format($EXEC_LOG['recordedCount'])." / ". number_format($EXEC_LOG['scheduleCount']) . " (".sprintf('%.2f', $ing_perc)."%)";
	$RETURN_ARR = array('result'=>'FAIL', 'message'=>$message);

	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;

}


$INVEST = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y'");

$exceptionProduct  = false;
$shortTermProduct  = ($PRDT['invest_days'] > 0) ? 1 : 0;
$total_invest_days = repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);																						// 상환대상일수
$total_repay_turn  = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);		// 상환차수
$schedule_rows     = $INVEST['cnt'] * $total_invest_days;

$sql = "
	INSERT INTO
		cf_product_bill_exec_log
	SET
		dt = NOW()
		, product_idx = '".$prd_idx."'
		, days = '".$total_invest_days."'
		, investCount = '".$INVEST['cnt']."'
		, scheduleCount = '".$schedule_rows."'
		, reg_admin_id = '".$member['mb_id']."'";
sql_query($sql);

// 생성시작
$exec_path = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/repayment/make_bill_exec.php {$prd_idx}";
//$exec_path.= ($drop_apply_date) ? " " . $drop_apply_date : "";
$exec_path.= " > /dev/null &";
@shell_exec($exec_path);

$message = '';
$message.= "품번 : ".$prd_idx."\n";
$message.= "상품명 : ".$PRDT['title']."\n";
$message.= "정산일수 : ".$total_invest_days."일\n";
$message.= "투자자수 : ".$INVEST['cnt']."명\n";
$message.= number_format($schedule_rows) . "개 일별정산데이터 생성 시작";

$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>$message);
echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

sql_close();

exit;

?>