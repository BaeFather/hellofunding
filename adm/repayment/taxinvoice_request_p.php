<?
###############################################################################
## 현금영수증 발행
##		공급가액(플랫폼수수료)가 1원 이상인것만 발행
###############################################################################

set_time_limit(0);

// 부하방지를 위하여 일괄발행 프로세스가 2개 이상 동작시 exit 처리함.
if( exec("ps -ef | grep -v grep | grep taxinvoice_request_p.php | wc -l") > 1 ) {
	exit;
}

$base_path = "/home/crowdfund/public_html";

include_once($base_path.'/config.php');
include_once($base_path.'/lib/common.lib.php');
include_once($base_path.'/lib/crypt.lib.php');
include_once($base_path.'/data/taxinvoice_inc/taxinvoice_config.php');
include_once($base_path.'/LINKHUB/Popbill/PopbillCashbill.php');	// 현금영수증 발행용(개인) 라이브러리
include_once($base_path.'/data/dbconfig.php');

$logTable = ($test_mode) ? "TaxinvoiceLog_test" : "TaxinvoiceLog";
$exec_dt  = date('YmdHis');
$CorpNum  = preg_replace('/-/', '', $INVOICER['CorpNum']);	// 팝빌회원 사업자번호, '-' 제외 10자리
$UserID   = $INVOICER['userid'];		// 팝빌회원 아이디


$banking_date = trim(@$_SERVER['argv']['1']);
if($banking_date=='') {
	echo "정산처리일이 전달되지 않음!"; exit;
}
else {
	$banking_date_s = $banking_date . " 00:00:00";
	$banking_date_e = $banking_date . " 23:59:59";
}

$CashbillService = new CashbillService($LinkID, $SecretKey);
$CashbillService->IsTest($test_mode);			// 연동환경 설정값, 개발용(true), 상용(false)

$where = "";
$where.= " AND A.fee > 0";
$where.= " AND A.banking_date BETWEEN '".$banking_date_s."' AND '".$banking_date_e."'";
$where.= " AND B.mb_level='1' AND B.member_type = '1'";
$where.= " AND A.is_creditor != 'Y' AND B.is_owner_operator=''";
$where.= " AND B.remit_fee = ''";

$limitTimestamp = time() + 3600;


$no = 1;
$x = true;
while($x > 0) {

	$connect_db = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD) or die('MySQL Connect Error!!!');
	$select_db  = sql_select_db(G5_MYSQL_DB, $connect_db) or die('MySQL DB Error!!!');

	$g5['connect_db'] = $connect_db;
	sql_set_charset('utf8', $connect_db);


	// 현금영수증 발행대상(미발행자) 추출 (개인-일반회원)
	$sql = "
		SELECT
			count(A.idx) AS cnt
		FROM
			cf_product_give A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE (1)
			AND A.mgtKey = ''
			$where";
	print_r($sql);
	$WAIT = sql_fetch($sql);
	if(!$WAIT['cnt']) {
		echo "Finish\n";
		sql_close();
		exit;
	}


	$sql = "
		SELECT
			A.idx, A.member_idx, A.invest_idx, A.turn, A.is_overdue, A.fee,
			B.mb_co_name, B.mb_name, B.mb_hp, B.mb_email
		FROM
			cf_product_give A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE (1)
			AND A.mgtKey = ''
			$where
		ORDER BY
			A.idx LIMIT 1";
	//echo $sql; exit;

	if( $R = sql_fetch($sql) ) {

		$mb_hp = $supply_price = $tax = $jumin = $mgtKey = '';

		$fee   = $R['fee'];
		$mb_hp = masterDecrypt($R['mb_hp'], false);
		$jumin = getJumin($R['member_idx']);

		// 거래금액 $fee
		$supply_price = ceil($fee / 1.1);																				// 공급가액
		$tax    = $fee - $supply_price;												// 부가세
		$mgtKey = "P_" . $R['invest_idx'] . '_';	// 문서관리번호 설정
		$mgtKey.= ($test_mode==true) ? sprintf('%03d', rand(1, 100)) : sprintf('%03d', $R['turn']);
		if($R['is_overdue']=='Y') $mgtKey.= '_OVD';

/*
		// 이미 발행된 현금영수증중 발행플래그가 없는 건 발행플래그 및 발행 로그 강제 업데이트 --------
		$log_sql = "
			INSERT INTO
				{$logTable}
			SET
				mgtKey     = '".$mgtKey."',
				req_type   = '현금영수증',
				action     = '발행',
				exec_dt    = '".date('YmdHis')."',
				code       = '1',
				msg        = '발행완료(수동등록)',
				proc_mb_id = 'admin_sori9th',
				req_date   = NOW()";
		sql_query($log_sql);
		echo $log_sql . "(" . sql_affected_rows() . ")\n";

		$update_sql = "UPDATE cf_product_give SET mgtKey='".$mgtKey."' WHERE idx='".$R['idx']."'";
		sql_query($update_sql);
		echo $update_sql . "(" . sql_affected_rows() . ")\n\n";
		// 이미 발행된 현금영수증중 발행플래그가 없는 건 발행플래그 및 발행 로그 강제 업데이트 --------
*/


		// 발행요청전 mgtKey 발행여부 다시 한번 체크
		$PROCESSED = sql_fetch("SELECT mgtKey FROM cf_product_give WHERE idx='".$R['idx']."'");

		if($PROCESSED['mgtKey']=='') {

			$forceIssue         = false;									// 지연발행 강제여부
			$memo               = '';											// 즉시발행 메모
			$emailSubject       = '';											// 안내메일 제목, 미기재시 기본제목으로 전송
			$writeSpecification = false;									// 거래명세서 동시작성 여부
			$dealInvoiceMgtKey  = '';											// 거래명세서 동시작성시 명세서 관리번호 - 최대 24자리 숫자, 영문, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
			$memo               = "현금영수증 즉시발행 메모";


			$Cashbill = new Cashbill();		// 현금영수증 객체 생성

			$Cashbill->mgtKey            = $mgtKey;											// [필수] 현금영수증 문서관리번호,
			$Cashbill->tradeType         = '승인거래';															// [필수] 거래유형, (승인거래, 취소거래) 중 기재
			$Cashbill->orgConfirmNum     = '';																			// [취소 현금영수증 발행시 필수] 원본 현금영수증 국세청 승인번호 - 국세청 승인번호는 GetInfo API의 ConfirmNum 항목으로 확인할 수 있습니다.
			$Cashbill->orgTradeDate      = '';																			// [취소 현금영수증 발행시 필수] 원본 현금영수증 거래일자 - 현금영수증 거래일자는 GetInfo API의 TradeDate 항목으로 확인할 수 있습니다.
			$Cashbill->identityNum       = ($jumin) ? $jumin : $mb_hp;		// [필수] 거래처 식별번호, 거래유형에 따라 작성 (소득공제용 - 주민등록/휴대폰/카드번호 기재가능, 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호 기재가능)
			$Cashbill->taxationType      = '과세';																	// [필수] 과세, 비과세 중 기재
			$Cashbill->supplyCost        = $supply_price;								// [필수] 공급가액, ','콤마 불가 숫자만 가능
			$Cashbill->tax               = $tax;												// [필수] 세액, ','콤마 불가 숫자만 가능
			$Cashbill->serviceFee        = '0';																			// [필수] 봉사료, ','콤마 불가 숫자만 가능
			$Cashbill->totalAmount       = $fee;											// [필수] 거래금액, ','콤마 불가 숫자만 가능
			$Cashbill->tradeUsage        = '소득공제용';														// [필수] 소득공제용, 지출증빙용 중 기재
			$Cashbill->franchiseCorpNum  = $CorpNum;																// [필수] 발행자 사업자번호
			$Cashbill->franchiseCorpName = $INVOICER['CorpName'];										// 발행자 상호
			$Cashbill->franchiseCEOName  = $INVOICER['CorpOwner'];									// 발행자 대표자 성명
			$Cashbill->franchiseAddr     = $INVOICER['CorpAddr'];										// 발행자 주소
			$Cashbill->franchiseTEL      = $INVOICER['Tel'];												// 발행자 연락처

			$Cashbill->customerName      = $R['mb_name'];										// 고객명
			$Cashbill->itemName          = "플랫폼 이용료";													// 상품명
			$Cashbill->orderNumber       = $mgtKey;											// 주문번호
			$Cashbill->email             = $R['mb_email'];										// 고객 메일주소
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

			//echo "[" . $no . "] 문서관리번호 : " . $mgtKey . " / 결과코드 : " . $code . " / 결과상세 : " . $message . "\n";

			$message = sql_real_escape_string($message);

			$log_sql = "
				INSERT INTO
					{$logTable}
				SET
					mgtKey     = '".$mgtKey."',
					req_type   = '현금영수증',
					action     = '발행',
					exec_dt    = '".$exec_dt."',
					code       = '".$code."',
					msg        = '".$message."',
					proc_mb_id = 'system',
					req_date   = NOW()";
			//echo $log_sql."\n";
			sql_query($log_sql);

			// 현금영수증발행결과 $code : 1 - 정상발행 / -14001019 : 동일한 가맹점 관리번호가 등록되어 있습니다.
			if($code=='1' || $code=='-14001019') {

				$update_sql = "UPDATE cf_product_give SET mgtKey='".$mgtKey."' WHERE idx='".$R['idx']."'";
				echo $update_sql."\n\n";
				sql_query($update_sql);

			}

		}	// end ($PROCESSED['mgtKey']=='')


	}


	// 무한반복을 피하기 위한 처리완료수 체크
	if($no % 100) {
		$sql = "
			SELECT
				count(A.idx) AS cnt
			FROM
				cf_product_give A
			LEFT JOIN
				g5_member B  ON A.member_idx=B.mb_no
			WHERE (1)
				$where";
		$TOTAL = sql_fetch($sql);
		if($TOTAL['cnt'] < $no) exit;
	}


	sql_close();

	// 무한반복을 피하기 위한 타임리미트 체크
	if(time() >= $limitTimestamp) {
		exit;
	}

	$no++;

}

exit;

?>