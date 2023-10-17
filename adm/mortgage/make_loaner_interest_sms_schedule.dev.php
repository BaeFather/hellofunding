#!/usr/local/php/bin/php -q
<?
$send_count = array(5,3,1,0);  // 5일전 발송, 3일전 발송, 1일전 발송

$base_path = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');

$prd_idx = $_SERVER['argv'][1];

if(!$prd_idx) {
	echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}

// 이미 생성된 스케줄러가 있는치 체크한다.
$chk_sql = "SELECT COUNT(idx) chk_cnt FROM cf_loaner_push_schedule WHERE product_idx='$prd_idx'";
$chk_row = sql_fetch($chk_sql);

if ($chk_row["chk_cnt"]) {
	echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 중복'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}

// X일전 문자 메시지 정의 ---------------------------------------------------------------
$before_sms = "안녕하세요. 헬로펀딩 입니다. 
{USER_NAME}님 당사 대출금에 대한 이자납부 사전안내드리며
아래의 입금안내에 따라 고객님의 전용가상계좌에 이자 입금을 안내드립니다.

납부회차 : {YYYY}년 {M}월 ({PAY_COUNT}회차)
이자 : {PRICE} 원
입금계좌 : 신한은행 {ACCOUNT_NAME} {ACCOUNT}

이자 약정일 {M}월{D}일까지 안내된 금액에 따라 입금부탁드립니다. 
감사합니다.";

// 당일 문자 메시지 정의 ----------------------------------------------------------------
$today_sms = "안녕하세요. {USER_NAME}님 헬로펀딩 입니다. 
금일은 당사 대출금에 대한 이자 약정일입니다.
아래의 입금안내에 따라 고객님 전용 가상계좌에 이자 입금을 바랍니다.

납부회차 : {YYYY}년{M}월({PAY_COUNT}회차)
이자 : {PRICE} 원
입금계좌 : 신한은행 {ACCOUNT_NAME} {ACCOUNT}

금일 중으로 이자 입금을 부탁 드리며, 미입금시 연체관리 및 연체이율 부과가 될 수 있으므로 반드시 이자 입금 처리 바랍니다.
감사합니다.";
// -------------------------------------------------------------------------------

// 만료 30일전 안내 문자 메시지 정의 ----------------------------------------------------------------
$count_sms = "[헬로펀딩] 만기 안내
안녕하세요 {USER_NAME}님 헬로펀딩입니다.
대출 만기일 안내드립니다.

- 대출금 : {REMAIN_AMT}원
- 대출금리 : {eyul}%
- 만기일 : {YYYYMMDD} 

만기 이후 신규대출(기간 연장)이 필요하신 경우
주택금융사업팀으로 연락 부탁드립니다.

※ 신용상태, 담보시세등 심사기준에 따라
대출이 불가능하거나 대출 금리가 인상될 수있습니다. ※

주택금융사업팀 : 1588-5210";
// -------------------------------------------------------------------------------



$chaju = get_chaju_info($prd_idx);  // 문자믈 보낼 대상(차주)을 가져옴. 배열. 여러명이 될수도 있음


$sql = "SELECT idx, loan_start_date, loan_end_date, loan_mb_no, category, mortgage_guarantees FROM cf_product where idx='$prd_idx'";
$PRDT = sql_fetch($sql); // 대출 상품 정보(대출실행일, 대출종료일등)를 가져온다.

if ($PRDT["category"]<>"2") die("부동산 담보 대출 상품이 아닙니다.");
if ($PRDT["mortgage_guarantees"]<>"1") die("주택 담보 대출 상품이 아닙니다.");

$mb_no = $PRDT["loan_mb_no"];
$loan_day = $PRDT["loan_start_date"];
$start_ym = date( 'Y-m', strtotime( substr($loan_day,0,7)."-01" . ' +1 month' ) )."-01"; // 이자문자안내는 다음달 1일부터 나감
$end_ym   = $PRDT["loan_end_date"]; // 이자문자안내는 대출 종료월까지 나감
$end_ym = date( 'Y-m', strtotime( substr($end_ym,0,7)."-01" . ' +1 month' ) )."-01"; // 이자문자안내는 다음달 1일부터 나감



$chk_for = 1;  // 무한루프 방지
$turn = 1; // 회차

echo "\n대출기간 $loan_day ~ $end_ym ( 이자 $start_ym ~ $end_ym) \n\n";

for ($i = $start_ym ; $i <= $end_ym ; $i=date( 'Y-m-d', strtotime( $i . ' +1 month' ) ) ) {
	
	$chk_for++;
	if ($chk_for>36) die("safe die\n");
	
	$tg_ym = date( 'Ym', strtotime( $i . ' -1 month' ) ); // 이자 대상 월
	
	$kijun_abs = substr($i,0,7) ."-01";  // 해당월 1일이 이자 수취일
	$eja_day = getUsableDate($kijun_abs, "after");  // 휴일일 경우 다음 은행 영업일이 이자 수취일
	echo "$chk_for 이자수취일 ". $kijun_abs." --> $eja_day " ."\n";
	
	$bill_tbl = get_bill_tbl_name($prd_idx);
	/*
	$sql_ej = "SELECT SUM(floor(A.day_interest)) eja 
				 FROM $bill_tbl A 
				WHERE A.product_idx = $prd_idx 
				  AND A.turn = $turn
				  AND A.is_overdue='N' 
				  AND A.turn_sno=0 ";
	*/
	/*
	$sql_ej = "SELECT SUM(floor(A.day_interest)) eja 
				 FROM $bill_tbl A 
				WHERE A.product_idx = $prd_idx 
				  AND A.turn = $turn ";
	$sql_row = sql_fetch($sql_ej);
	$eja = $sql_row["eja"]; 
	*/
	$eja = 0;
	

	$sql_pturn = "INSERT INTO cf_product_turn
					 SET product_idx = '$prd_idx',
					     turn = '$turn',
						 tym = '$tg_ym',
						 ym = '".substr($i,0,7)."',
						 dday = '".substr($eja_day,8,2)."',
						 eja = '$eja'";
	sql_query($sql_pturn);

	for ($j=0 ; $j<count($send_count) ; $j++) {   // 문자 발송일만큼 루프돌림
	
		
		$sch_date = getbankingdate_pm($eja_day, "-" , $send_count[$j] );  //은행영업일로 해당일수 만큼 디스카운트 해서 문자 발송일을 구함
		
		if ($send_count[$j]=="0") $msg_gubun = "당일";
		else $msg_gubun = $send_count[$j]."일전";
		//echo $send_count[$j]."일전 ".$sch_date."\n";
		
		if ($sch_date<=$loan_day) continue;  // 문자 발송일이 대출일 이전이면 스킵
		
		if ($sch_date == $eja_day) $msg = $today_sms; // 당일 문자 내용
		else $msg = $before_sms;  // 예정 안내 문자 내용
		
		for ($l=0 ; $l<count($chaju); $l++) {  // 차주수만큼 루프
		
			if (!$chaju[$l]['mb_hp']) {
				//echo "회원번호 ".$chaju[$l]['mb_no']." 핸드폰 번오 오류\n";
				//continue;
			}

			$hp = masterEncrypt($chaju[$l]["mb_hp"], false);
			$hpk = substr($chaju[$l]["mb_hp"], -4);
		
			// 문자 내용 구성
			$msg = str_replace("{USER_NAME}", $chaju[$l]['mb_name'], $msg);
			$msg = str_replace("{YYYY}", substr($eja_day,0,4) , $msg);
			$msg = str_replace("{M}", substr($eja_day,5,2)*1 , $msg);
			$msg = str_replace("{PAY_COUNT}", $turn , $msg);
			$msg = str_replace("{ACCOUNT_NAME}", $chaju[$l]['va_private_name2'] , $msg);
			$msg = str_replace("{ACCOUNT}", $chaju[$l]['virtual_account2'] , $msg);
			$msg = str_replace("{D}", substr($eja_day,8,2)*1 , $msg);
		
			$ins_sql = "INSERT INTO cf_loaner_push_schedule SET 
							tg_ym = '$tg_ym',
							product_idx = '$prd_idx',
							mb_no = '$mb_no',
							turn = '$turn',
							dday = '$eja_day',
							eja = '$eja',
							msg_gubun = '$msg_gubun',
							send_date = '$sch_date',
							send_time = '10:00:00',
							mb_hp = '$hp',
							mb_hp_key = '$hpk',
							msg = '".$msg."',
							send_status = '0',
							input_datetime = NOW()
							";
			sql_query($ins_sql);
			
			if ($sch_date == $eja_day) {  // 당일이면 오후 4시에 한번더 발송
				$ins_sql2 = "INSERT INTO cf_loaner_push_schedule SET 
							tg_ym = '$tg_ym',
							product_idx = '$prd_idx',
							mb_no = '$mb_no',
							turn = '$turn',
							dday = '$eja_day',
							eja = '$eja',
							msg_gubun = '$msg_gubun',
							send_date = '$sch_date',
							send_time = '16:00:00',
							mb_hp = '$hp',
							mb_hp_key = '$hpk',
							msg = '".$msg."',
							send_status = '0',
							input_datetime = NOW()
							";
				sql_query($ins_sql2); //당일에는 2번 보냄
			}
		
		}

	}
	$turn++;

	echo "\n";
}

/*
$count_sms = "[헬로펀딩] 만기 안내
안녕하세요 {USER_NAME}님 헬로펀딩입니다.
대출 만기일 안내드립니다.

- 대출금 : {REMAIN_AMT}원
- 대출금리 : {eyul}%
- 만기일 : {YYYYMMDD} 

만기 이후 신규대출(기간 연장)이 필요하신 경우
주택금융사업팀으로 연락 부탁드립니다.

※ 신용상태, 담보시세등 심사기준에 따라
대출이 불가능하거나 대출 금리가 인상될 수있습니다. ※

주택금융사업팀 : 1588-5210";
*/
$count_end_ym = date('Y-m-d', strtotime( $PRDT["loan_end_date"] . '-1 month') );  // 만료 1달전
$count_tg_ym = str_replace("-","", substr( $PRDT["loan_end_date"] , 0 , 7));
if ($count_end_ym<=date("Y-m-d")) $count_end_ym="";
if ($count_end_ym) {
	$msg = $count_sms;
	$msg = str_replace("{USER_NAME}", $chaju[$l]['mb_name'], $msg);
	$msg = str_replace("{REMAIN_AMT}", "", $msg);
	$msg = str_replace("{eyul}", "", $msg);
	$msg = str_replace("{YYYYMMDD}", "", $msg);
	$ins_sql3 = "INSERT INTO cf_loaner_push_schedule 
						 SET tg_ym = '',
							 product_idx = '$prd_idx',
							 mb_no = '$mb_no',
							 turn = '',
							 dday = '".$PRDT["loan_end_date"]."',
							 eja = '',
							 msg_gubun = '만기안내',
							 send_date = '$count_end_ym',
							 send_time = '11:00:00',
							 mb_hp = '$hp',
							 mb_hp_key = '$hpk',
							 msg = '".$msg."',
							 send_status = '2',
							 input_datetime = NOW()
					";
	sql_query($ins_sql3); 
	
}

?>
<?
function getbankingdate_pm($kj , $pm, $d) {
	$tdate = date( 'Y-m-d', strtotime( $kj . ' '.$pm.' day' ) );
	$to = $d;
	$ndate = $kj;
		
	$safe_check = 20;
	for ($i=0 ; $i<$to ; $i++) {
		
		$safe_check++;
		if ($i>$safe_check) die();
		
		$ndate = date( 'Y-m-d', strtotime( $ndate . ' -1 day' ) );
		if ($ndate<>getUsableDate($ndate, "after")) {
			$i--; 
		}
		
	}
	return $ndate;
}

function get_chaju_info($prd_idx) {
	
	$LIST = array();
	
	$sql = "SELECT loan_mb_no FROM cf_product WHERE idx='$prd_idx'";
	$row = sql_fetch($sql);
	echo $row[loan_mb_no]."\n";
	
	$sqlm = "SELECT mb_no, mb_name, mb_hp, va_bank_code2, virtual_account2, va_private_name2 FROM g5_member WHERE mb_no='$row[loan_mb_no]'";
	$rowm = sql_fetch($sqlm);
	$rowm['mb_hp'] = masterDecrypt($rowm['mb_hp'], false);
	//$rowm['mb_hp'] = "010-1234-5678";
	
	$LIST[0] = $rowm;

	return $LIST;
}
function get_bill_tbl_name($prd_idx) {
	$tbl_name = floor($prd_idx/1000)*1000;
	$tbl_name = "cf_product_bill_".str_pad($tbl_name, 5, "0", STR_PAD_LEFT);

	return $tbl_name;
}
?>