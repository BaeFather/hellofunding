<?
###############################################################################
## 원리금 상환 완료 문자 발송
## - 중복투자자 걸러냄
###############################################################################

exit;

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path . "/common.cli.php");
include_once($base_path . "/lib/sms.lib.php");

//if(!$is_admin) exit;

//echo date('YmdH'); exit;

//$send_msg  = "[헬로펀딩]\n회원님이 투자하신 광명시 광명동 공동주택 신축 상품이 원리금과 지연이자를 포함하여 상환되었습니다.\n\n심려와 불편을 드려 죄송합니다.\n감사합니다.";		// 2021-06-30 광명동 공동주택 연체 및 원리금 상환
$send_msg  = "[헬로펀딩] 수익금 지급완료\n\n지급내역은 홈페이지에서 확인 가능합니다.\n\n감사합니다.";		// 2021-06월 정산부터 적용
//$send_msg  = "[헬로펀딩]투자수익금지급완료\n☆세부사항은홈페이지에서확인가능\nwww.hellofunding.co.kr";		// 2021-05월 정산까지 적용
//$send_msg  = "[헬로펀딩] 상환\n\n회원님이 투자하신 여수시 충무동 숙박시설 신축 상품이 상환되었습니다.\n상환내역은 투자현황에서 확인해주세요\n\n감사합니다.\n\n헬로펀딩 홈페이지 : https://bit.ly/2Pn2U5x";
//$send_msg  = "[헬로펀딩] 회원님이 투자하신 광주광역시 우산동 주상복합 신축 상품이 중도상환되었습니다. 감사합니다.";		// 2021-09-06일 광주광역시 우산동 주상복합 신축 상품 완료문자
//$send_msg  = "[헬로펀딩]\n회원님이 투자하신 서울 용산구 다세대 신축 상품이 상환되었습니다.\n감사합니다.";
//$send_msg  = "[헬로펀딩] 수익금 지급 지연 안내\n\n안녕하세요 헬로펀딩입니다.\n\n회원님이 투자하신 상품의 수익금 지급이 지연되어 안내해 드립니다.\n\n상품명 : 광주 우산동 주상복합 신축 상품\n\n본 상품의  수익금 지급자금은 자금관리중인 신탁사의 계좌에 입금되어 있으나, 수행될 절차 지연으로 2021.10.8일 전후 수익금에 대한 지연이자(연 20%)와 함께 지급될 예정임을 알려드립니다.\n\n당사는 차주 및 신탁사의 업무 진행을 계속적으로 확인하여 약속 기일전이라도 수익금이 지급될 수 있도록 최선을 하겠습니다.\n\n감사합니다.";
//$send_msg  = "[헬로펀딩] 광주광역시 우산동 주상복합 신축 이자 지급 안내\n\n안녕하세요 헬로펀딩입니다.\n\n광주광역시 우산동 주상복합 신축 상품의 이자 지급이 완료되었습니다.\n\n이자 지급 시 이자에 대하여 연체 이자율 20%(연)를 적용하여 지급되었습니다.\n\n상환 지연으로 투자자분들께 많은 걱정을 끼쳐 드린 점 다시 한번 사과드립니다.\n\n당사는 앞으로도 투자자 재산보호를 최우선으로 생각하고 업무를 처리하도록 하겠습니다.\n\n감사합니다.";

//$send_msg = addSlashes($send_msg);

$send_timestamp = time() + 1200;
$send_date = date("Y-m-d H:i", $send_timestamp) . ':00';

$product = "4444,4470,4471,4483,4503,4504,4505,4514,4612,4684,4713,4733,4910,4945,5039,5068,5096,5157,5168,5219,5270,5320,5372,5392,5402,5412,5461,5480,5512,5576,5544,5611,5588,5663,5829,5863,5933,5979,6092,6093,6116,6150,6162,6163,6187,6210,6211,6246,6247,6305,6306,6307,6364,6387,6411,6412,6413,6426,6517,6562,6585,6641,6653,6665,6711,6747,6735,6783,6784,6807,6830,6853,6854,6902,6903,6926,6960,6961,6984,7008,7055,7042,7068,7202";

// 직원우선, 회원번호순
$sql="
	SELECT
		COUNT(A.idx) AS invest_count,
		A.member_idx,
		B.mb_id, B.mb_name, B.mb_hp
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1=1
		AND A.product_idx IN(".$product.")
		AND A.invest_state='Y'
	GROUP BY
		A.member_idx
	ORDER BY
		B.mb_10 DESC,
		A.member_idx";
$res  = sql_query($sql);
$rows = $res->num_rows;
for($i=0; $i<$rows; $i++){
	$LIST[$i] = sql_fetch_array($res);
	$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'],false);
}
//print_rr($LIST);exit;

$list_count = count($LIST);

if(!$list_count) exit;


for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	if( in_array(substr($LIST[$i]['mb_hp'], 0, 3), array('010','011','016','017','018','019')) ) {

		if( $_REQUEST['action'] == date('YmdH') ) {
			// 실제발송
			if( unit_sms_send_smtnt($_admin_sms_number, $LIST[$i]['mb_hp'], $send_msg, $send_date) ) {
				debug_flush($j . ":" . $LIST[$i]['mb_hp'] . "<br>\n");
				usleep(1000);
			}
		}
		else {
			// 시뮬레이션 (실행내용 출력만)
			debug_flush($j." : unit_sms_send_smtnt('{$_admin_sms_number}', '{$LIST[$i]['mb_hp']}', '{$send_msg}', '{$send_date}');<br/>\n");
			if( ($j%100) == 0 ) debug_flush("<br/>\n");
		}

	}

	// 출금 요청 과부하시 정산 오류 발생우려가 있어 천천히 유입되도록 발송시간 텀을 부여한다.
	if( ($j%500) == 0 ) {
		$send_timestamp += 180;
		$send_date = date("Y-m-d H:i", $send_timestamp) . ':00';
	}

}


debug_flush("<font color='red'>종료</font>");

exit;

?>