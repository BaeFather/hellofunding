#!/usr/local/php/bin/php -q
<?
###############################################################################
## 현금영수증 발행
##		공급가액(플랫폼수수료)가 1원 이상인것만 발행
###############################################################################

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path.'/config.php');
include_once($base_path.'/lib/common.lib.php');
include_once($base_path.'/lib/crypt.lib.php');
include_once($base_path.'/data/taxinvoice_inc/taxinvoice_config.php');
include_once($base_path.'/LINKHUB/Popbill/PopbillCashbill.php');	// 현금영수증 발행용(개인) 라이브러리

include_once($base_path.'/data/dbconfig.php');
$connect_db = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD) or die('MySQL Connect Error!!!');
$select_db  = sql_select_db(G5_MYSQL_DB, $connect_db) or die('MySQL DB Error!!!');

$g5['connect_db'] = $connect_db;
sql_set_charset('utf8', $connect_db);

//$test_mode = true;

$logTable = ($test_mode==true) ? "cf_loanerTaxinvoiceLog_test" : "cf_loanerTaxinvoiceLog";		// 투자자용 계산서발행과는 혼용하지 않는다.
$exec_dt  = date('YmdHis');
$CorpNum  = preg_replace('/-/', '', $INVOICER['CorpNum']);	// 팝빌회원 사업자번호, '-' 제외 10자리
$UserID   = $INVOICER['userid'];		// 팝빌회원 아이디


$IDX = trim(@$_SERVER['argv']['1']);
if($IDX=='') {
	echo "대출자수수료 수취 현황 IDX가 전달되지 않음!"; exit;
}

$CashbillService = new CashbillService($LinkID, $SecretKey);
$CashbillService->IsTest($test_mode);			// 연동환경 설정값, 개발용(true), 상용(false)


// 현금영수증 발행대상 추출 (개인-일반회원)
$sql = "
	SELECT
		A.idx, A.product_idx, A.turn, A.repay_amt, A.supply_price, A.tax,
		C.mb_no, C.mb_name, C.mb_hp, C.mb_email
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	LEFT JOIN
		g5_member C  ON B.loan_mb_no=C.mb_no
	WHERE 1
		AND A.idx IN (".$IDX.")
		AND A.mgtKey = ''
		AND C.member_type='1'
	ORDER BY
		A.idx ASC";
//echo $sql;
$res  = sql_query($sql);
$rows = sql_num_rows($res);

if($rows) {

	$LIST = array();
	for($i=0; $i<$rows; $i++) {

		$R = sql_fetch_array($res);
		$LIST[$i] = $R;

		$LIST[$i]['mb_hp']        = ($R['mb_hp']) ? masterDecrypt($R['mb_hp'], false) : '';
		$LIST[$i]['price']        = $R['repay_amt'];										// 거래금액
		$LIST[$i]['supply_price'] = $R['supply_price'];									// 공급가액
		$LIST[$i]['tax']          = $R['tax'];													// 부가세
		$LIST[$i]['jumin']        = getJumin($R['mb_no']);

		$LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM {$logTable} WHERE LEFT(req_date, 10)='".date('Y-m-d')."'");
		$LIST[$i]['mgtKey'] = "P_" . $R['product_idx'] . '_' . sprintf('%02d', $R['turn']) . '_' . $LOG['cnt'];			// 문서관리번호 설정(P_상품번호_회차번호_처리수)

		unset($R);

	}
	//print_r($LIST); exit;


	$list_count = count($LIST);

	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

		// 발행요청전 mgtKey 발행여부 다시 한번 체크
		$PROCESSED = sql_fetch("SELECT mgtKey FROM cf_loaner_fee_collect WHERE idx='".$LIST[$i]['idx']."'");
		if($PROCESSED['mgtKey']=='') {

			$forceIssue         = false;									// 지연발행 강제여부
			$memo               = '';											// 즉시발행 메모
			$emailSubject       = '';											// 안내메일 제목, 미기재시 기본제목으로 전송
			$writeSpecification = false;									// 거래명세서 동시작성 여부
			$dealInvoiceMgtKey  = '';											// 거래명세서 동시작성시 명세서 관리번호 - 최대 24자리 숫자, 영문, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
			$memo               = "현금영수증 즉시발행 메모";


			$Cashbill = new Cashbill();		// 현금영수증 객체 생성

			$Cashbill->mgtKey            = $LIST[$i]['mgtKey'];											// [필수] 현금영수증 문서관리번호,
			$Cashbill->tradeType         = '승인거래';															// [필수] 거래유형, (승인거래, 취소거래) 중 기재
			$Cashbill->orgConfirmNum     = '';																			// [취소 현금영수증 발행시 필수] 원본 현금영수증 국세청 승인번호 - 국세청 승인번호는 GetInfo API의 ConfirmNum 항목으로 확인할 수 있습니다.
			$Cashbill->orgTradeDate      = '';																			// [취소 현금영수증 발행시 필수] 원본 현금영수증 거래일자 - 현금영수증 거래일자는 GetInfo API의 TradeDate 항목으로 확인할 수 있습니다.
			$Cashbill->identityNum       = ($LIST[$i]['jumin']) ? $LIST[$i]['jumin'] : $LIST[$i]['mb_hp'];		// [필수] 거래처 식별번호, 거래유형에 따라 작성 (소득공제용 - 주민등록/휴대폰/카드번호 기재가능, 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호 기재가능)
			$Cashbill->taxationType      = '과세';																	// [필수] 과세, 비과세 중 기재
			$Cashbill->supplyCost        = $LIST[$i]['supply_price'];								// [필수] 공급가액, ','콤마 불가 숫자만 가능
			$Cashbill->tax               = $LIST[$i]['tax'];												// [필수] 세액, ','콤마 불가 숫자만 가능
			$Cashbill->serviceFee        = '0';																			// [필수] 봉사료, ','콤마 불가 숫자만 가능
			$Cashbill->totalAmount       = $LIST[$i]['price'];											// [필수] 거래금액, ','콤마 불가 숫자만 가능
			$Cashbill->tradeUsage        = '소득공제용';														// [필수] 소득공제용, 지출증빙용 중 기재
			$Cashbill->franchiseCorpNum  = $CorpNum;																// [필수] 발행자 사업자번호
			$Cashbill->franchiseCorpName = $INVOICER['CorpName'];										// 발행자 상호
			$Cashbill->franchiseCEOName  = $INVOICER['CorpOwner'];									// 발행자 대표자 성명
			$Cashbill->franchiseAddr     = $INVOICER['CorpAddr'];										// 발행자 주소
			$Cashbill->franchiseTEL      = $INVOICER['Tel'];												// 발행자 연락처

			$Cashbill->customerName      = $LIST[$i]['mb_name'];										// 고객명
			$Cashbill->itemName          = "플랫폼 이용료";													// 상품명
			$Cashbill->orderNumber       = $LIST[$i]['mgtKey'];											// 주문번호
			$Cashbill->email             = $LIST[$i]['mb_email'];										// 고객 메일주소
			$Cashbill->hp                = '';																			// 고객 휴대폰 번호
			$Cashbill->smssendYN         = false;																		// 발행시 알림문자 전송여부

			try {
				$result  = $CashbillService->RegistIssue($CorpNum, $Cashbill, $memo);
				$code    = $result->code;
				$message = $result->message;
			}
			catch(PopbillException $pe) {
				$code    = $pe->getCode();
				$message = $pe->getMessage();
			}
			//echo '[' . $code . '] ' . $message . PHP_EOL;

			$message = sql_real_escape_string($message);

			$log_sql = "
				INSERT INTO
					{$logTable}
				SET
					mgtKey     = '".$LIST[$i]['mgtKey']."',
					req_type   = '현금영수증',
					action     = '발행',
					exec_dt    = '".$exec_dt."',
					code       = '".$code."',
					msg        = '".$message."',
					proc_mb_id = 'system',
					req_date   = NOW()";
			//if($test_mode==true) echo $log_sql."\n";
			sql_query($log_sql);

			if($code=='1') {

				$update_sql = "
					UPDATE
						cf_loaner_fee_collect
					SET
						mgtKey = '".$LIST[$i]['mgtKey']."',
						tax_req_date = CURDATE()
					WHERE
						idx='".$LIST[$i]['idx']."'";
				//if($test_mode==true) echo $update_sql . "\n";
				sql_query($update_sql);

			}

		}	// end if($PROCESSED['cnt']==0)

		unset($LIST[$i]);

		if($list_count > 500) {
			usleep(333333);		// 딜레이타임 조금....
		}

	}	// end for

}	// end if($rows)
sql_free_result($res);

//sleep(6);

echo "Finish\n";

sql_close();
exit;

?>