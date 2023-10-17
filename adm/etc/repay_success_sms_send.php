<?
###############################################################################
## 원리금 상환 문자 발송
###############################################################################

//exit;


set_time_limit(0);

include_once("_common.php");
include_once("../../lib/sms.lib.php");

if(!$is_admin) exit;

// 원리금 지급 상품번호 배열
//$product_arr = '130,142,160,171,173,174,175,176,177,180,181,184,185,186,187,188,190,191,192,198,193,202,195,204,196,197,199,201,203,205,207,206,212'; // 2018-03-05
$product_arr = '130,142,160,171,173,174,175,176,177,180,181,184,185,187,188,190,191,192,198,193,202,195,196,197,199,201,205,207,206,212,213,215,218'; // 2018-04-05

$sql = "
	SELECT
		B.mb_hp, B.receive_method
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B
	ON
		A.member_idx=B.mb_no
	WHERE
		A.product_idx IN($product_arr)
	-- AND LEFT(B.mb_hp, 3) IN('010','011','016','017','018','019')
		AND A.invest_state='Y'
	GROUP BY
		A.member_idx
	ORDER BY
		A.idx ASC";
$res = sql_query($sql);
$rows = $res->num_rows;

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);
	$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'], false);
}
$list_count = count($LIST);


$send_msg  = "[헬로펀딩] 투자 수익금 지급 완료({RECEIVE_METHOD})
☆홈페이지에서 확인가능
www.hellofunding.co.kr";


$send_date = date("Y-m-d H:i:s", time()+300);		//발송시간

for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	$receive_method = ($LIST[$i]['receive_method']=='2') ? '예치금' : '환급계좌';

	$last_send_msg = preg_replace('/\{RECEIVE_METHOD\}/', $receive_method, $send_msg);

	if($_REQUEST['action']==date('YmdHi')) {

		if(unit_sms_send($_admin_sms_number, $LIST[$i]['mb_hp'], $last_send_msg, $send_date)) {
			debug_flush($j . ": " . $LIST[$i]['mb_hp'] . "<br>\n");
		}

	}
	else {

		debug_flush($j . ": unit_sms_send({$_admin_sms_number}, {$LIST[$i]['mb_hp']}, {$last_send_msg}, {$send_date})<br>\n");
		usleep(10000);

	}

	$receive_method = $last_send_msg = NULL;

}

debug_flush("<font color='red'>종료</font>");

exit;

?>