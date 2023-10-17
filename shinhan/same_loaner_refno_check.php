<?
###############################################################################
## 원리금상환입금내역중 단일상환계좌 복수대출상품의 상환상품번호(참조번호) 자동기록하기
## * 6-22 * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/same_loaner_refno_check.php
###############################################################################

set_time_limit(0);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.cli.php');


$sdt = date('YmdHis', strtotime('-1 day'));
$edt = date('YmdHis');

// 1. 상환내역중 '상환상품번호(참조번호)'가 등록되지 않은 건 추출
$sql = "
	SELECT
		CUST_ID, ACCT_NB, TR_AMT, ERP_TRANS_DT
	FROM
		IB_FB_P2P_IP
	WHERE 1
		AND TR_AMT_GBN = '20'
		AND ERP_TRANS_DT BETWEEN '".$sdt."' AND '".$edt."'
		AND repay_prd_idx = ''
	ORDER BY
		ERP_TRANS_DT DESC";
//print_r($sql);
$res = sql_query($sql);

while($ROW = sql_fetch_array($res)) {

	if($ROW['CUST_ID']) {

		// 2. 1)에서 출력된 가상계좌정보로 KSNET 가상계좌 설정정보의 '참조번호'를 조회한다
		$KSNET = sql_fetch("SELECT REF_NO FROM KSNET_VR_ACCOUNT WHERE VR_ACCT_NO = '".$ROW['ACCT_NB']."' AND USE_FLAG = 'Y'");

		// 3. 상환내역에 '참조번호'를 등록한다.
		if($KSNET['REF_NO']) {

			$sqlx = "
				UPDATE
					IB_FB_P2P_IP
				SET
					repay_prd_idx = '".$KSNET['REF_NO']."'
				WHERE 1
					AND CUST_ID = '".$ROW['CUST_ID']."'
					AND ACCT_NB = '".$ROW['ACCT_NB']."'
					AND TR_AMT = '".$ROW['TR_AMT']."'
					AND TR_AMT_GBN = '20'
					AND ERP_TRANS_DT = '".$ROW['ERP_TRANS_DT']."'
					AND repay_prd_idx = ''";
			//echo $sqlx."\n";

			sql_query($sqlx);

		}

	}

}

sql_free_result($res);

sql_close();
exit;

?>