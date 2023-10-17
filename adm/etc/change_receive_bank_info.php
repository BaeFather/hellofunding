<?
## 기존 지급내역 테이블에 지급 계좌정보 수정입력

include_once("_common.php");

if($_REQUEST['action']!='yes') exit;

$sql = "
	SELECT
		A.idx AS product_idx, A.state, A.title,
		B.idx AS invest_idx, B.amount,
		C.mb_no, C.mb_id, C.mb_name, C.receive_method, C.bank_name, C.bank_private_name, C.account_num, C.va_bank_code, C.va_private_name, C.virtual_account
	FROM
		cf_product A,
		cf_product_invest B,
		g5_member C
	WHERE 1=1
		AND B.product_idx=A.idx
		AND C.mb_no=B.member_idx
	ORDER BY A.idx ASC, invest_idx ASC
";

$res = sql_query($sql);
while($row = sql_fetch_array($res)) {

	print_rr($row, 'font-size:11px');

	if($row['receive_method']=='1') {
		$bank_name = $row['bank_name'];
		$bank_private_name = $row['bank_private_name'];
		$account_num = preg_replace("/(-| )/", "", $row['account_num']);
	}
	else if($row['receive_method']=='2') {
		$bank_name = $BANK[$row['va_bank_code']];
		$bank_private_name = $row['va_private_name'];
		$account_num =  preg_replace("/(-| )/", "", $row['virtual_account']);
	}
	else {
		$bank_name = '';
		$bank_private_name = '';
		$account_num = '';
	}

	$sql2 = "
		UPDATE
			cf_product_give
		SET
			receive_method = '".$row['receive_method']."',
			bank_name = '$bank_name',
			bank_private_name = '$bank_private_name',
			account_num = '$account_num'
		WHERE 1=1
			AND invest_idx = '".$row['invest_idx']."'
			AND product_idx = '".$row['product_idx']."'
	";
	echo $sql2."<br>\n";
	$res2 = sql_query($sql2);

}

?>