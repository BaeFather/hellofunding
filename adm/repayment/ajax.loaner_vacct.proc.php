<?
###############################################################################
##  상환계좌 해제 (입금차단)
###############################################################################

include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');


$mode    = trim($_POST['mode']);
$prd_idx = trim($_POST['idx']);

if(!$prd_idx) {
	$RETURN_ARR = array('result'=>'FAIL', 'message'=>'상품번호 전송오류');
	json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	exit;
}

if(!$mode) {
	$RETURN_ARR = array('result'=>'FAIL', 'message'=>'작동모드 전송오류');
	json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	exit;
}


$PRDT = sql_fetch("SELECT ib_trust, ib_product_regist, repay_acct_no FROM cf_product WHERE idx='".$prd_idx."'");
if(!$PRDT['repay_acct_no']) {
	$RETURN_ARR = array('result'=>'FAIL', 'message'=>'해당상품은 상환용 가상계좌가 설정되지 않은 상품입니다.');
	json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	exit;
}

$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;

if($mode=='drop') {

	$chage_count = 0;

	$DATA1 = sql_fetch("SELECT COUNT(*) AS cnt FROM KSNET_VR_ACCOUNT WHERE VR_ACCT_NO = '".$PRDT['repay_acct_no']."' AND USE_FLAG = 'Y'");

	if($DATA1['cnt']) {
		$sql1 = "
			UPDATE
				KSNET_VR_ACCOUNT
			SET
				USE_FLAG = 'N',
				FINAL_DATE = '".date('Ymd')."'
			WHERE
				VR_ACCT_NO = '".$PRDT['repay_acct_no']."'";
		//echo $sql1."\n";
		$res1 = sql_query($sql1);
		$chage_count += sql_affected_rows();
	}


	$DATA2 = sql_fetch("SELECT COUNT(FB_SEQ) AS cnt FROM IB_vact_hellocrowd WHERE acct_no = '".$PRDT['repay_acct_no']."' AND acct_st = '1'");

	if($DATA2['cnt']) {
		$sql2 = "
			UPDATE
				IB_vact_hellocrowd
			SET
				acct_st = '9',
				close_il = '".date('Ymd')."'
			WHERE
				acct_no = '".$PRDT['repay_acct_no']."'";
		//echo $sql2."\n";
		$res2 = sql_query($sql2);
		$chage_count += sql_affected_rows();
	}

	if($chage_count) {
		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
	}
	else {
		$RETURN_ARR = array('result'=>'FAIL', 'message'=>'변동사항 없음!');
	}

	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

}

sql_close();

exit;

?>