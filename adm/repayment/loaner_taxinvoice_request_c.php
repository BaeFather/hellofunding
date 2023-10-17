#!/usr/local/php/bin/php -q
<?
###############################################################################
## 세금계산서 발행
##		공급가액(플랫폼수수료)가 1원 이상인것만 발행
###############################################################################

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path.'/config.php');
include_once($base_path.'/lib/common.lib.php');
include_once($base_path.'/lib/crypt.lib.php');
include_once($base_path.'/data/taxinvoice_inc/taxinvoice_config.php');
include_once($base_path.'/LINKHUB/Popbill/PopbillTaxinvoice.php');	// 세금계산서 발행용(법인) 라이브러리

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
$write_date = date('Ymd');		// 발행일을 전문전송일과 동일하게 변경 : 2018-02-07 (배석:이정환 차장, 고상희 차장)

$IDX = trim(@$_SERVER['argv']['1']);
if($IDX=='') {
	echo "대출자수수료 수취 현황 IDX가 전달되지 않음!"; exit;
}

$TaxinvoiceService = new TaxinvoiceService($LinkID, $SecretKey);
$TaxinvoiceService->IsTest($test_mode);		// 연동환경 설정값, 개발용(true), 상업용(false)


// 세금계산서 발행대상 추출 (법인회원)
$sql = "
	SELECT
		A.idx, A.product_idx, A.turn, A.repay_amt, A.supply_price, A.tax,
		C.mb_no, C.mb_co_name, C.mb_co_reg_num, C.mb_co_owner, C.mb_name, C.mb_hp, C.mb_email
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	LEFT JOIN
		g5_member C  ON B.loan_mb_no=C.mb_no
	WHERE 1
		AND A.idx IN (".$IDX.")
		AND A.mgtKey = ''
		AND C.member_type='2'
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
	//$LIST[$i]['jumin']        = getJumin($R['mb_no']);

		$LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM {$logTable} WHERE LEFT(req_date, 10)='".date('Y-m-d')."'");
		$LIST[$i]['mgtKey'] = "C_" . $R['product_idx'] . '_' . sprintf('%02d', $R['turn']) . '_' . $LOG['cnt'];			// 문서관리번호 설정(C_상품번호_회차번호_처리수)

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

			// 공급받는자 정보 정리
			$INVOICEE['Type']        = '사업자';
			$INVOICEE['CorpNum']     = preg_replace("/( |-)/", "", $LIST[$i]['mb_co_reg_num']);
			$INVOICEE['CorpName']    = $LIST[$i]['mb_co_name'];
			$INVOICEE['CorpOwner']   = ($LIST[$i]['mb_co_owner']) ? $LIST[$i]['mb_co_owner'] : $LIST[$i]['mb_name'];
			$INVOICEE['CorpAddr']    = '';
			$INVOICEE['BizType']     = '';
			$INVOICEE['BizClass']    = '';
			$INVOICEE['ContactName'] = '';
			$INVOICEE['Email']       = $LIST[$i]['mb_email'];
			$INVOICEE['TEL']         = '';
			$INVOICEE['HP']          = '';


			$Taxinvoice = new Taxinvoice();																// 세금계산서 객체 생성

			////////////////////////////////////////////////////////////////
			//                       세금계산서 정보
			////////////////////////////////////////////////////////////////
			$Taxinvoice->writeDate       = $write_date;										// [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
			$Taxinvoice->issueType       = '정발행';											// [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
			$Taxinvoice->chargeDirection = '정과금';											// [필수] 과금방향 - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
			$Taxinvoice->purposeType     = '영수';												// [필수] '영수', '청구' 중 기재
			$Taxinvoice->taxType         = '과세';												// [필수] 과세형태, '과세', '영세', '면세' 중 기재
			$Taxinvoice->issueTiming     = '직접발행';										// [필수] 발행시점, 발행예정시 동작, '직접발행', '승인시자동발행' 중 기재

			////////////////////////////////////////////////////////////////
			//                        공급자 정보
			////////////////////////////////////////////////////////////////
			$Taxinvoice->invoicerCorpNum     = $CorpNum;									// [필수] 공급자 사업자번호
			$Taxinvoice->invoicerTaxRegID    = '';												//        공급자 종사업장 식별번호, 4자리 숫자 문자열
			$Taxinvoice->invoicerCorpName    = $INVOICER['CorpName'];			// [필수] 공급자 상호
			$Taxinvoice->invoicerMgtKey      = $LIST[$i]['mgtKey'];				// [필수] 공급자 문서관리번호, 최대 24자리 숫자, 영문, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
			$Taxinvoice->invoicerCEOName     = $INVOICER['CorpOwner'];		// [필수] 공급자 대표자성명
			$Taxinvoice->invoicerAddr        = $INVOICER['CorpAddr'];			//        공급자 주소
			$Taxinvoice->invoicerBizClass    = $INVOICER['BizClass'];			//        공급자 종목
			$Taxinvoice->invoicerBizType     = $INVOICER['BizType'];			//        공급자 업태
			$Taxinvoice->invoicerContactName = $INVOICER['ContactName'];	//        공급자 담당자 성명
			$Taxinvoice->invoicerEmail       = $INVOICER['Email'];				//        공급자 담당자 메일주소
			$Taxinvoice->invoicerTEL         = $INVOICER['Tel'];					//        공급자 담당자 연락처
			$Taxinvoice->invoicerHP          = $INVOICER['HP'];						//        공급자 휴대폰 번호
			$Taxinvoice->invoicerSMSSendYN   = false;											//        정발행시 공급받는자 담당자에게 알림문자 전송여부 - 안내문자 전송시 포인트가 차감되며 전송실패시 환불처리 됩니다.

			////////////////////////////////////////////////////////////////
			//                     공급받는자 정보
			////////////////////////////////////////////////////////////////
			$Taxinvoice->invoiceeType         = $INVOICEE['Type'];				// [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
			$Taxinvoice->invoiceeCorpNum      = $INVOICEE['CorpNum'];			// [필수] 공급받는자 사업자번호
			$Taxinvoice->invoiceeTaxRegID     = '';												//        공급받는자 종사업장 식별번호, 4자리 숫자 문자열
			$Taxinvoice->invoiceeCorpName     = $INVOICEE['CorpName'];		// [필수] 공급자 상호
			$Taxinvoice->invoiceeMgtKey       = '';												// [역발행시 필수] 공급받는자 문서관리번호, 최대 24자리 숫자, 영문, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
			$Taxinvoice->invoiceeCEOName      = $INVOICEE['CorpOwner'];		// [필수] 공급받는자 대표자성명
			$Taxinvoice->invoiceeAddr         = $INVOICEE['CorpAddr'];		//        공급받는자 주소
			$Taxinvoice->invoiceeBizType      = $INVOICEE['BizType'];			//        공급받는자 업태
			$Taxinvoice->invoiceeBizClass     = $INVOICEE['BizClass'];		//        공급받는자 종목
			$Taxinvoice->invoiceeContactName1 = $INVOICEE['ContactName'];	//        공급받는자 담당자 성명
			$Taxinvoice->invoiceeEmail1       = $INVOICEE['Email'];				//        공급받는자 담당자 메일주소
			$Taxinvoice->invoiceeTEL1         = $INVOICEE['TEL'];					//        공급받는자 담당자 연락처
			$Taxinvoice->invoiceeHP1          = $INVOICEE['HP'];					//        공급받는자 담당자 휴대폰 번호
			$Taxinvoice->invoiceeSMSSendYN    = false;										//        역발행요청시 공급자 담당자에게 알림문자 전송여부 - 문자전송지 포인트가 차감되며, 전송실패시 포인트 환불처리됩니다.

			//////////////////////////////////////////////////////////////
			//                      세금계산서 기재정보
			//////////////////////////////////////////////////////////////
			$Taxinvoice->supplyCostTotal = $LIST[$i]['supply_price'];		// [필수] 공급가액 합계
			$Taxinvoice->taxTotal        = $LIST[$i]['tax'];						// [필수] 세액 합계
			$Taxinvoice->totalAmount     = $LIST[$i]['price'];					// [필수] 합계금액, (공급가액 합계 + 세액 합계)
			$Taxinvoice->serialNum       = '';													//        기재상 '일련번호'항목
			$Taxinvoice->cash            = '';													//        기재상 '현금'항목
			$Taxinvoice->chkBill         = '';													//        기재상 '수표'항목
			$Taxinvoice->note            = '';													//        기재상 '어음'항목
			$Taxinvoice->credit          = '';													//        기재상 '외상'항목

			$Taxinvoice->remark1         = '';													//        기재상 '비고' 항목1
			$Taxinvoice->remark2         = '';													//        기재상 '비고' 항목2
			$Taxinvoice->remark3         = '';													//        기재상 '비고' 항목3

			$Taxinvoice->kwon            = '0';													//        기재상 '권' 항목, 최대값 32767
			$Taxinvoice->ho              = '0';													//        기재상 '호' 항목, 최대값 32767

			$Taxinvoice->businessLicenseYN = false;											//        사업자등록증 이미지파일 첨부여부
			$Taxinvoice->bankBookYN        = false;											//        통장사본 이미지파일 첨부여부

			//////////////////////////////////////////////////////////////
			//                      상세항목(품목) 정보
			//////////////////////////////////////////////////////////////
			$Taxinvoice->detailList   = array();
			$Taxinvoice->detailList[] = new TaxinvoiceDetail();
			$Taxinvoice->detailList[0]->serialNum  = 1;								// [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
			$Taxinvoice->detailList[0]->purchaseDT = $write_date;			// 거래일자
			$Taxinvoice->detailList[0]->itemName   = "플랫폼 이용료";	// 품명
			$Taxinvoice->detailList[0]->spec       = '';							// 규격
			$Taxinvoice->detailList[0]->qty        = '';							// 수량
			$Taxinvoice->detailList[0]->unitCost   = '';							// 단가
			$Taxinvoice->detailList[0]->supplyCost = $LIST[$i]['supply_price'];		// 공급가액
			$Taxinvoice->detailList[0]->tax        = $LIST[$i]['tax'];						// 세액
			$Taxinvoice->detailList[0]->remark     = '';													// 비고

			//////////////////////////////////////////////////////////////
			//                      추가담당자 정보
			// - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
			// 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
			//////////////////////////////////////////////////////////////
			$Taxinvoice->addContactList   = array();
			$Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
			$Taxinvoice->addContactList[0]->serialNum   = 1;											// 일련번호 1부터 순차기재
			$Taxinvoice->addContactList[0]->email       = $INVOICER['Email'];			// 이메일주소
			$Taxinvoice->addContactList[0]->contactName	= $INVOICER['officer'];		// 담당자명

			try {
				$result  = $TaxinvoiceService->RegistIssue($CorpNum, $Taxinvoice, $UserID, $writeSpecification, $forceIssue, $memo, $emailSubject, $dealInvoiceMgtKey);
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
					req_type   = '세금계산서',
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