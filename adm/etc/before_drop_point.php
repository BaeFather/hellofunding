<?

// 함부로 실행 불가
// 수동 대량 이체하기 전 이체금액만큼 포인트 차감하기
// 현재는 신한 가상계좌를 발급받았으나 이체 미처리된 회원의 이체 사전 처리를 위해 사용됨.
// 이체전 기존 포인트를 0으로 초기화하고 다시 재무팀의 입금 확인 후 본 파일의 조건을 더해서 실행해주면 된다.
// 고상희 차장에게 2017-10-16일 대량 이체 실행후 출금내역중 2중 출금 된 것들 파악 할것 - 랙 걸렸을때 2중 출금 된것 같음.


set_time_limit(0);

include_once('./_common.php');
include_once(G5_PATH.'/lib/insidebank.lib.php');

foreach($_REQUEST as $k=>$v) {
	$_REQUEST[$k] = $v;
}

//$add_where = " AND mb_id IN ('jimyungs','akswns19','moon1016','nds0219','goodman4u','gibal1','jjjggg12','sscc703','yongba','anoboq')";
//$add_where = " AND mb_id IN ('sspark')";
//$add_where = " AND mb_id IN ('kwng55','imanais','dltjs1105','coyote67')";
//$add_where = " AND mb_id IN ('kkhee7233','s992388','dudwh1580','mywinsor','huiriri')";
//$add_where = " AND mb_id IN ('skyhany','com928','primaveraa','rnjsgur911','thelittlebear','woodang','taewooha','acsuim1','jinsej','happycow15')";
//$add_where = " AND mb_id IN ('ohlimoo','mhm1200','lhc125','jang6923','zl9256','newly0704','jang2832','ojs962003','trippy813')";
//$add_where = " AND mb_id IN ('iamkye12','newzenith')";
$add_where = " AND mb_id IN ('kjl0619')";

$sql = "
	SELECT
		mb_no, mb_id, mb_name, mb_co_name, mb_point, bank_code, account_num, bank_private_name, va_bank_code2, virtual_account2
	FROM
		g5_member
	WHERE 1
		AND virtual_account2!=''
		AND insidebank_after_trans_target='1'
		$add_where
	ORDER BY
		mb_no DESC";

$res  = sql_query($sql);
$rows = $res->num_rows;

for($i=0,$j=1; $i<$rows; $i++,$j++) {

	$LIST[$i] = sql_fetch_array($res);
	$LIST[$i]['account_num'] = masterDecrypt($LIST[$i]['account_num']);

	//echo $LIST[$i]['mb_id'] . "\r\n";
	//echo number_format($LIST[$i]['mb_point'] * (-1)) . "\r\n";

	$sql2 = "
		UPDATE
			g5_member
		SET
			receive_method='1',
			insidebank_after_trans_target=''
		WHERE
			mb_no='".$LIST[$i]['mb_no']."'";

	echo $LIST[$i]['mb_id'] ." : ";

	//예외금액 처리
	if($LIST[$i]['mb_id']=='happycow15') { $LIST[$i]['mb_point'] = 32193; }		// 강지섭 회원


	if( $action==date(YmdHi) ) {
		insert_point($LIST[$i]['mb_id'], $LIST[$i]['mb_point'] * (-1), "신한은행일괄이관 차감");    //$sql = "update g5_member set mb_point = mb_point - {$LIST[$i]['mb_point']} {$sql_search}";
		$result = sql_query($sql2);
	}

	echo get_point_sum($LIST[$i]['mb_id']);

	echo "<br>\n";

}

?>