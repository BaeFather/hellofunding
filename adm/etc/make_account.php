<?

// 함부로 실행 불가
// 조건에 맞는 회원 리스트를 추출하여 신한은행 가상계좌를 발급해준다.

set_time_limit(0);

include_once('./_common.php');
include_once(G5_PATH . "/lib/insidebank.lib.php");
include_once(G5_PATH . '/lib/sms.lib.php');

$action = trim($_REQUEST['action']);

//-- 환급계좌정보를 등록한 회원중 신한가상계좌 미발급자, 예치금이 0원 이상인 회원
/*
$sql = "
	SELECT
		mb_no, mb_id, mb_name, mb_co_name, mb_point, bank_code, account_num, bank_private_name, bank_private_name_sub
	FROM
		g5_member
	WHERE 1
		AND mb_level=1
		AND mb_point > 0
		AND bank_code!='' AND bank_private_name!='' AND account_num!=''
		AND virtual_account2=''
	ORDER BY
		mb_point DESC, mb_no";
*/

//-- 환급계좌정보를 등록한 회원중 신한가상계좌 미발급자, 예치금이 0원인 회원
/*
$sql = "
SELECT
	mb_no, mb_id, mb_name, mb_co_name, mb_point, bank_code, account_num, bank_private_name, bank_private_name_sub
FROM
	g5_member
WHERE 1
	AND virtual_account2=''
	AND mb_level=1
	AND mb_point=0
	AND bank_code!='' AND bank_private_name!='' AND account_num!=''
ORDER BY
	member_type DESC,
	mb_point DESC,
	mb_no DESC";
*/

//-- 투자건 이력이 있는 환급계좌소유 회원
/*
$sql = "
SELECT
	B.mb_no, B.mb_id, B.mb_name, B.mb_co_name, B.mb_point, B.bank_code, B.account_num, B.bank_private_name, B.bank_private_name_sub
FROM
	cf_product_invest A
LEFT JOIN
	g5_member B
ON
	A.member_idx=B.mb_no
WHERE 1
	AND B.member_group='F'
	AND B.bank_code!='' AND B.bank_private_name!='' AND B.account_num!='' AND B.virtual_account2=''
GROUP BY
	mb_no";
*/

//-- 전환대상자 재처리
$sql = "
SELECT
	mb_no, mb_id, mb_name, mb_co_name, mb_point,
	bank_code, account_num, bank_private_name, bank_private_name_sub, virtual_account2, insidebank_after_trans_target
FROM
	g5_member
WHERE 1
	AND insidebank_after_trans_target='1'
	AND bank_code!='' AND bank_private_name!='' AND account_num!=''
	AND virtual_account2=''
ORDER BY
	mb_point DESC, mb_no";

$res  = sql_query($sql);
$rows = $res->num_rows;

for($i=0,$j=1; $i<$rows; $i++,$j++) {

	$LIST[$i] = sql_fetch_array($res);

	if($action==date(YmdHi)) {

		$result = sh_make_account($LIST[$i]['mb_no']);

		if($result['RCODE']=='00000000') {

			// 회원정보 재호출
			$MB = sql_fetch("SELECT mb_no, mb_id, mb_name, mb_co_name, bank_name, bank_code, bank_private_name, account_num, virtual_account, va_bank_code2, virtual_account2, va_private_name2 FROM g5_member WHERE mb_no='".$LIST[$i]['mb_no']."'");
			$MB['mb_hp'] = masterDecrypt($MB['mb_hp'], false);
			$va_bank = $BANK[$MB['va_bank_code2']];

			/*
			$sms_row = sql_fetch("SELECT msg FROM `g5_sms_userinfo` WHERE use_yn='1' AND idx='15'");
			if($sms_row['msg']) {
				$sms_msg = str_replace("{USER_NAME}", $MB['mb_name'], $sms_row['msg']);       // 성명변경
				$sms_msg = str_replace("{BANK}", $va_bank, $sms_msg);                         // 은행명 변경
				$sms_msg = str_replace("{ACCOUNT_NAME}", $MB['va_private_name2'], $sms_msg);  // 예금주명 변경
				$sms_msg = str_replace("{ACCOUNT}", $MB['virtual_account2'], $sms_msg);       // 계좌번호 변경
				$rst = unit_sms_send($_admin_sms_number, $MB['mb_hp'], $sms_msg);             // 문자발송 실행
			}
			*/

			if(sql_query("UPDATE g5_member SET receive_method='1' WHERE mb_no='".$LIST[$i]['mb_no']."'")) {
				if($MB['virtual_account']) {
					sql_query("UPDATE vacs_vact SET acct_st='9', close_il='".date('Ymd')."' WHERE acct_no='".$MB['virtual_account']."'");		// 세틀 뱅크계좌 해지처리
				}
				sql_query("UPDATE g5_member SET receive_method='1' WHERE virtual_account2='".$MB['virtual_account2']."'");		// 원리금 수취방식 변경
				debug_flush($j . '. SUCCESS : ' . $LIST[$i]['mb_no'] . ' ' . $LIST[$i]['mb_id'] . ' ' . $MB['va_bank_code2'] . ' ' . $MB['virtual_account2'] . ' ' . $MB['va_private_name2']. '<br>' . PHP_EOL);
			}

		}
		else {

			debug_flush($j.'. FAILED : ' . $result['ERRMSG']. '(' . $result['RCODE'] . ')<br>' . PHP_EOL);

		}

	}
	else {

		print_rr($LIST[$i], 'font-size:12px;');

	}

}

/*
거래고유번호	FB_SEQ	○	X	10	10자리필수 (3.4절 3번참조)
전문번호	REQ_NUM	○	X	3	고객관리 : 010
거래구분	SUBMIT_GBN	○	X	2	등록 : 01
고객ID	CUST_ID	○	X	20
고객명	CUST_NM	○	X	30	개인/개인사업:개인성명
기업/법인:사업자명
고객부기명	CUST_SUB_NM	△	X	30	개인사업자/기업(2) 필수
개인사업자 고객명 입력가능
대표자고객명	REP_NM	△	X	30	기업/법인 필수
생년월일자	BIRTH_DATE	△	X	8	YYYYMMDD
개인일경우 필수
사업자번호	SUP_REG_NB	△	X	10	개인사업자/기업(법인) 필수
개인사업자구분	PRI_SUP_GBN	○	9	1	1:개인
2:개인사업자/기업(법인)
휴대폰지역번호	HP_NO1	○	X	4
휴대폰국번호	HP_NO2	○	X	4
휴대폰일련번호	HP_NO3	○	X	4
은행코드	BANK_CD	○	X	3
은행계좌	ACCT_NB	○	X	20	입출금가능계좌(당/타행)
가상계좌번호	CMS_NB	○	X	16	신한발급가상계좌(예치용)
*/

exit;

?>