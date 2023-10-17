<?
###############################################################################
## 투자자 이자 지급완료 문자 일괄 발송
##	repay_schedule.ajax 를 통해 호출
###############################################################################

include_once("_common.php");

$g5['title'] = "투자자 이자 지급완료 문자 일괄 발송";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

include_once(G5_PATH . "/lib/sms.lib.php");



$CHK = $_POST['chk'];


// 로그 등록
$sql = "
	INSERT INTO
		batch_sms_send_log
	SET
		repay_schedule_date = '".$_REQUEST['schedule_date']."',
		sdatetime = NOW(),
		admin_id = '".$member['mb_id']."'";
$res = sql_query($sql);
$log_idx = sql_insert_id();


$product_count = count($CHK);
$prd_idx_set = "";

if($product_count) {

	// 상품번호 셋팅
	for($i=0,$j=1; $i<$product_count; $i++,$j++) {
		$prd_idx_set.= explode('-', $CHK[$i])[0];
		$prd_idx_set.= ($j < $product_count) ? ',' : '';
	}
	//echo $prd_idx_set;


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
			AND A.product_idx IN(".$prd_idx_set.")
			AND A.invest_state='Y'
		GROUP BY
			A.member_idx
		ORDER BY
			B.mb_10 DESC,
			A.member_idx";
	$res  = sql_query($sql);
	$rows = sql_num_rows($res);
	for($i=0; $i<$rows; $i++){
		$LIST[$i] = sql_fetch_array($res);
		$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'],false);
	}

	$list_count = count($LIST);

	$send_msg  = "[헬로펀딩]투자수익금지급완료\n☆세부사항은홈페이지에서확인가능\nwww.hellofunding.co.kr";
	$send_date = "";

	$send_count = 0;

	/*카카오톡 알림톡 추가*/
	$tcode = "hello009";
	$KaKao_Message_Send = new KaKao_Message_Send();
	/*카카오톡 알림톡 추가*/

	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

		if( in_array(substr($LIST[$i]['mb_hp'], 0, 3), array('010','011','016','017','018','019')) ) {
/*
			// 테스트 발송 --------------------------------------------------------------------------
			if( unit_sms_send_test($_admin_sms_number, $LIST[$i]['mb_hp'], $send_msg, $send_date) ) {
				$send_count += 1;
				usleep(100);
			}
			// 테스트 발송 --------------------------------------------------------------------------
*/

			// 실제 발송 --------------------------------------------------------------------------
			if(date('Hi') >= '0900' && date('Hi') < '2100') {		// 실행시간 제한을 둠!!

				/* 카카오 알림톡 추가*/
				$member["mb_no"]		=	$LIST[$i]['member_idx'];
				$member["mb_name"]	=	$LIST[$i]['mb_name'];
				$member["mb_hp"]		=	$LIST[$i]['mb_hp'];

				$KaKao_Message_Send->MEMBER = $member;	// common.lib member 환경변수
				$KaKao_Message_Send->kakao_insert($tcode);

				$send_count += 1;
				usleep(100);
				/* 카카오 알림톡 추가*/
				/* 기존 sms*/
				/*
				if( unit_sms_send($_admin_sms_number, $LIST[$i]['mb_hp'], $send_msg, $send_date) ) {
					$send_count += 1;
					usleep(100);
				}
				*/

			}
			else {		// 정해진 시간외 실행시 테스트 발송테이블에 저장 (실제 발송 안됨)

				if( unit_sms_send_test($_admin_sms_number, $LIST[$i]['mb_hp'], $send_msg, $send_date) ) {
					$send_count += 1;
					usleep(100);
				}

			}
			// 실제 발송 --------------------------------------------------------------------------

		}

	}

	if($send_count > 0) {
		$succ_perc = floatRtrim(sprintf("%.2f", ($send_count/$list_count)*100));
		$ARR = array('result' => 'SUCCESS', 'msg' => "대상상품: " . number_format($product_count) . "건 \n투자자수: " . number_format($list_count) . "명 \n발송요청: " . number_format($send_count) . "건 (성공률: " . $succ_perc . "%)");
	}
	else {
		$ARR = array('result' => 'FAIL', 'msg' => "발송 내역이 없습니다.");
	}

}
else {

	$ARR = array('result' => 'FAIL', 'msg' => "대상 상품이 없습니다.");

}

$json_txt = json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
echo $json_txt;


// 로그 종료
$sql = "
	UPDATE
		batch_sms_send_log
	SET
		edatetime = NOW(),
		result_msg = '".$json_txt."'
	WHERE
		idx = '".$log_idx."'";

sql_query($sql);
$res = sql_query($sql);

exit;

?>
