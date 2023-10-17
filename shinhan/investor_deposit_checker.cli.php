#!/usr/local/php/bin/php -q
<?php
###############################################################################
## 인사이드뱅크 펌뱅킹 예치금 입금 통지내역 -> 회원 예치금으로 전환
##
## 주의 : public_html/config.php 를 호출하지 않아 상수설정정보가 없으므로
##        common.lib.php 에서 DB쿼리를 사용하는 함수는 사용하면 안된다!!!
##
## 주의2: 특별한 사유없이 본 파일을 강제로 실행하지 말것!!!!!!!!
##
## /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/investor_deposit_checker.cli.php (yes|debug) &
##
## 실행파일: schedule_work/shinhan_investor_deposit_checker.sh
##
## 20181019 : 개인회원 입금자명과 예금주명이 다를 경우 회원예치금으로 귀속시키지 않고
##            관리자 페이지에서 해당 예치금만큼 관리자가 지급해주는 방식으로 변경
## 20210525 : 카카오페이 입금 구분 루틴 추가
###############################################################################

set_time_limit(0);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$path = '/home/crowdfund/public_html';
include_once($path . '/data/dbconfig.php');
include_once($path . '/data/sms_dbconfig.php');
include_once($path . '/lib/common.lib.php');
include_once($path . '/lib/sms.lib.php');

// config.php 를 로드하지 않으므로 고정설정항목을 임시로 지정한다.
$CONF['admin_sms_number'] = '15886760';

// 서버비상상황 일때 문자알림 수신번호 설정
$CONF['event_receive_phone'] = array(
	//'01064063972',
	//'01086246176',
	//'01088944740',
	'01067241409',
	'01043380580'
);


$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';


$x = true;

while($x > 0) {

	if($action=='debug') { echo ("[" . date('Y-m-d H:i:s') . "]\n"); }

	//---------------------------------------------------------------------------
	$link = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
	sql_set_charset("UTF8", $link);
	//---------------------------------------------------------------------------

	$dd = date('Y-m-d');

	// 금융기관 점검시간 브레이크
	$sql0 = "SELECT idx, sdt, edt FROM cf_bank_pause_schedule WHERE sdd <= '".$dd."' AND edd >= '".$dd."' ORDER BY idx DESC LIMIT 1";
	$BANK_PAUSE = sql_fetch($sql0, true, $link);

	if($BANK_PAUSE['idx']) {
		if( date('Y-m-d H:i:s') >= $BANK_PAUSE['sdt'] && date('Y-m-d H:i:s') < $BANK_PAUSE['edt'] ) {
			break;
			sql_close($link);
			exit;
		}
	}


	$init_sql1 = "UPDATE IB_FB_P2P_IP SET REMITTER_NM = TRIM(REMITTER_NM) WHERE ERP_TRANS_DT BETWEEN '".date('YmdHis', strtotime('-1 hour'))."' AND '".date('YmdHis')."'";
	$init_sql2 = "UPDATE IB_FB_P2P_IP SET REMITTER_NM = REPLACE(REMITTER_NM,'　','') WHERE ERP_TRANS_DT BETWEEN '".date('YmdHis', strtotime('-1 hour'))."' AND '".date('YmdHis')."' AND REMITTER_NM LIKE '%　%'";

	if($action=='debug') {
		echo ($init_sql1."\n".$init_sql2."\n");
	}
	else {
		sql_query($init_sql1, true, $link);		// 예금주명에 특수문자 삭제
		sql_query($init_sql2, true, $link);		// 좌우공백 제거
	}


	// 일반 입금내역 추출
	$sql = "
		SELECT /* SQL_NO_CACHE */
			A.SR_DATE, A.FB_SEQ, A.CUST_ID, A.TR_AMT, A.ERP_TRANS_DT, A.REMITTER_NM,
			B.mb_no, B.mb_id, B.member_type, B.mb_name, B.mb_co_name, B.bank_private_name, B.finnq_userid, B.oligo_userid,
			A.sms_to_admin
		FROM
			IB_FB_P2P_IP A
		INNER JOIN
			g5_member B  ON A.CUST_ID=B.mb_no
		WHERE 1
			AND TR_AMT_GBN = '10'
			AND trans_to_point != 'OK'
			AND REMITTER_NM != '보정입금'
		ORDER BY
			ERP_TRANS_DT DESC";
	if($action=='debug') { echo ($sql . "\n"); }
	$res  = sql_query($sql, true, $link);
	$rows = $res->num_rows;

	$content = "";

	for($i=0; $i<$rows; $i++) {

		$LIST[$i] = sql_fetch_array($res);
		$LIST[$i]['TR_AMT'] = (int)$LIST[$i]['TR_AMT'];

		// 입금된 회원의 가장 최근 잔여포인트 기록정보 가져오기
		$DATA = sql_fetch("SELECT IFNULL(po_mb_point, 0) AS po_mb_point FROM g5_point WHERE mb_no='".$LIST[$i]['mb_no']."' ORDER BY po_datetime DESC, po_id DESC LIMIT 1", true, $link);
		$po_mb_point = $DATA['po_mb_point'] + $LIST[$i]['TR_AMT'];

		// 차명입금 확인 (외부 신디케이션(핀크,올리고) 마킹이 있는 회원의 입금건은 제외 => 투자금일 수 있으므로...)
		$name_matched = true;				// 2022-03-31 차명입금 체크하지 않도록 true 전환함
		$remitter_nm  = $mb_name = $bank_private_name = $month_str = "";
		$content      = "예치금 충전";

		///////////////////////////////////////////////////////////////////////////
		// 상금지급이벤트등의 입금시
		// (조건문 항목추가시 /deposit/ajax_deposit_check_insidebank.php 병행편집요망)
		///////////////////////////////////////////////////////////////////////////
		$remitter_nm = trim($LIST[$i]['REMITTER_NM']);

		$tmpRes = sql_query("SELECT name, match_type, print_title FROM cf_remitter_config WHERE is_usable='1' ORDER BY idx", true, $link);
		while( $REMITTER_CONF = sql_fetch_array($tmpRes) ) {
			if( in_array($REMITTER_CONF['match_type'], array('1','2')) ) {

				if($REMITTER_CONF['match_type']=='1') {
					if($remitter_nm == $REMITTER_CONF['name']) {
						$name_matched = true;
						$content.= ": " . $REMITTER_CONF['print_title'];
						break;
					}
				}
				else if($REMITTER_CONF['match_type']=='2') {
					if(preg_match("/".$REMITTER_CONF['name']."/", $remitter_nm)) {
						$name_matched = true;

						if( preg_match("/\([0-9]{1,2}\)/", $remitter_nm) ) {
							$month_str = str_f6($remitter_nm, "(", ")") . "월";																						// 괄호 안의 문자열 추출 (괄호안에 월이 숫자로만 표기되어 있는 경우)
							$content.= ": " . preg_replace("/\{month\}/", $month_str, $REMITTER_CONF['print_title']);			// 문자열 치환 및 가공 ex) {month} HELLO첫투자 이벤트 보상금 ===> "예치금 충전: 12월 HELLO첫투자 이벤트 보상금"
						}
						else if( preg_match("/\([0-9]{1,2}\월)/", $remitter_nm) ) {
							$month_str = str_f6($remitter_nm, "(", ")");																									// 괄호 안의 문자열 추출 (괄호안에 숫자+월로 표기된 경우.)
							$content.= ": " . preg_replace("/\{month\}/", $month_str, $REMITTER_CONF['print_title']);			// 문자열 치환 및 가공 ex) {month} HELLO첫투자 이벤트 보상금 ===> "예치금 충전: 12월 HELLO첫투자 이벤트 보상금"
						}
						else {
							$content.= ": " . $REMITTER_CONF['print_title'];
						}

						break;
					}
				}

			}
		}
		sql_free_result($tmpRes);



		if(!$name_matched) {

			///////////////////////////////////////
			// 3회이상 투자 이력이 있는 회원
			///////////////////////////////////////
			$INVEST = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product_invest WHERE member_idx='".$LIST[$i]['mb_no']."' AND invest_state='Y'", true, $link);
			if($INVEST['cnt'] >= 3) {
				$name_matched = true;
			}

		}

		/*
		if(!$name_matched) {
			///////////////////////////////////////
			// 카카오페이 투자금 여부 확인
			// bank_transfer_code 는 카카오페이 투자처리시 헬로에서 발급해서 카카오페이로 전송해준 값이다.
			///////////////////////////////////////
			$KKP_INVEST = sql_fetch("SELECT idx FROM cf_product_invest WHERE member_idx='".$LIST[$i]['mb_no']."' AND invest_state='W' AND bank_transfer_code='".$remitter_nm."'", true, $link);
			if($KKP_INVEST['idx']) {
				$name_matched = true;

				// kakaopay_deposit_check (카카오페이 회원별 투자금 입금현황 테이블) 업데이트
				$kkpSql = "
					UPDATE
						kakaopay_deposit_check
					SET
						deposit = 'Y',
						check_date = NOW()
					WHERE 1
						AND invest_idx = '".$KKP_INVEST['idx']."'";

				sql_query($kkpSql, true, $link);
			}
		}
		*/

		if(!$name_matched) {

			//////////////////////////////////////
			// 개인회원 처리
			//////////////////////////////////////
			if($LIST[$i]['member_type']=='1') {

				if($LIST[$i]['finnq_userid'] || $LIST[$i]['oligo_userid']) {
					$name_matched = true;
				}
				else {

					$remitter_nm       = preg_replace("/( |　|	)/", "", trim($LIST[$i]['REMITTER_NM']));	// 입금자명 공백문자제거
					$mb_name           = preg_replace("/( )/", "", trim($LIST[$i]['mb_name']));
					$bank_private_name = preg_replace("/( )/", "", trim($LIST[$i]['bank_private_name']));

					if( in_array($remitter_nm, array('(주)헬로핀테크',$mb_name, $bank_private_name)) || preg_match("/$mb_name/", $remitter_nm) ) {
						$name_matched = true;
					}

					// 자동승인 설정 입금자리스트 참조
					if(!$name_matched) {
						$res2 = sql_query("SELECT allow_remitter_name FROM IB_auth_deposit_to_amount WHERE mb_no='".$LIST[$i]['mb_no']."' ORDER BY rdate DESC", true, $link);
						$LIST[$i]['ALLOW_REMITTERS'] = array();
						while( $row = sql_fetch_array($res2) ) {
							array_push($LIST[$i]['ALLOW_REMITTERS'], $row['allow_remitter_name']);
						}

						if( in_array($remitter_nm, $LIST[$i]['ALLOW_REMITTERS']) ) {
							$name_matched = true;
						}
					}

				}

			}
			//////////////////////////////////////
			// 법인회원 처리
			//////////////////////////////////////
			else if($LIST[$i]['member_type']=='2') {
				$name_matched = true;
			}

		}



		/////////////////////////////////////////////////////
		// 회원정보의 예금주명과 입금자명이 다른경우 처리
		/////////////////////////////////////////////////////
		if($name_matched) {

			if($remitter_nm=="보정입금") {

				// 보정입금시 포인트 테이블에는 기록하지 않는다.
				if($action=='yes') {
					/*
					$resX2 = sql_query("
						UPDATE
							IB_FB_P2P_IP
						SET
							trans_to_point = 'OK',
							trans_date = '".date('Y-m-d H:i:s')."'
						WHERE (1)
							AND FB_SEQ = '".$LIST[$i]['FB_SEQ']."'
							AND ERP_TRANS_DT = '".$LIST[$i]['ERP_TRANS_DT']."'
							AND CUST_ID = '".$LIST[$i]['CUST_ID']."'", true, $link);
					*/
				}

			}
			else {

				// 입금내역-> 포인트 테이블에 등록;
				$sqlX1 = "
					INSERT INTO
						g5_point
					SET
						mb_no = '".$LIST[$i]['mb_no']."',
						mb_id = '".$LIST[$i]['mb_id']."',
						po_datetime = NOW(),
						po_content = '".$content."',
						po_point = '".$LIST[$i]['TR_AMT']."',
						po_expire_date = '9999-12-31',
						po_mb_point = '".(int)$po_mb_point."',
						po_rel_table = '@deposit'";

				if($action=='yes') {
					if( $resX1 = sql_query($sqlX1, true, $link) ) {
						// 등록내역 확인 플래그 업데이트
						$resX2 = sql_query("
							UPDATE
								IB_FB_P2P_IP
							SET
								trans_to_point='OK',
								trans_date='".date('Y-m-d H:i:s')."'
							WHERE (1)
								AND FB_SEQ='".$LIST[$i]['FB_SEQ']."'
								AND ERP_TRANS_DT='".$LIST[$i]['ERP_TRANS_DT']."'
								AND CUST_ID='".$LIST[$i]['CUST_ID']."'", true, $link);
					}
				}

				// 누적 잔여포인트 가져오기
				$POINT_LOG = sql_fetch("SELECT po_mb_point AS sum_po_point FROM g5_point WHERE mb_id='".$LIST[$i]['mb_id']."' ORDER BY po_datetime DESC, po_id DESC LIMIT 1", true, $link);


				$sqlX2 = "UPDATE g5_member SET mb_point='".$POINT_LOG['sum_po_point']."' WHERE mb_id='".$LIST[$i]['mb_id']."'";
				if($action=='yes') { sql_query($sqlX2, true, $link); }

				if($action=='debug') {
					echo ($sqlX1 . "\n");
					echo ($sqlX2 . "\n");
				}

				unset($sqlX1);
				unset($sqlX2);
				unset($DATA);

			}

		}
		else {

			if($action=='yes') {

				// 차명입금 발생시 담당자에게 확인 문자 발송 ----------------------------------------
				if($LIST[$i]['SR_DATE']==date('Ymd') && $LIST[$i]['sms_to_admin']=='') {

					//---------------------------------------------------------------------------
					$link3 = sql_connect(G5_MYSQL_HOST3, G5_MYSQL_USER3, G5_MYSQL_PASSWORD3, G5_MYSQL_DB3);
					sql_set_charset('UTF8', $link3);
					//---------------------------------------------------------------------------

					$sms_msg = $LIST[$i]['mb_id'] . "(" . $LIST[$i]['mb_name'] . ") 가상계좌\n" .
										"입금:" . $LIST[$i]['REMITTER_NM'] . "\n" .
										"금액:" . number_format((int)$LIST[$i]['TR_AMT']) . "원";

					// 문자발송
					$sms_send_count = 0;
					for($k=0; $k<count($CONF['event_receive_phone']); $k++) {
						unit_sms_send_smtnt($CONF['admin_sms_number'], $CONF['event_receive_phone'][$k], $sms_msg);
						$sms_send_count += 1;
					}
					sql_close($link3);

					if($sms_send_count > 0) {
						$updateSql = "
							UPDATE
								IB_FB_P2P_IP
							SET
								sms_to_admin = '".date('Y-m-d H:i:s')."'
							WHERE 1
								AND FB_SEQ = '".$LIST[$i]['FB_SEQ']."'
								AND ERP_TRANS_DT = '".$LIST[$i]['ERP_TRANS_DT']."'";
						sql_query($updateSql, true, $link);
					}

					// 관리자는 메세지 확인 후 입금자 확인 절차를 거쳐 관리자 페이지의 차명입금리스트에서 입금승인 처리를 하여, 포인트로그에 차명입금 표기 및 trans_to_point 값을 OK로 변경한다.

				}
				// 차명입금 발생시 담당자에게 확인 문자 발송 ----------------------------------------

			}

		}

	}

	sql_free_result($res);
	unset($LIST);

	sql_close($link);

	sleep(15);

}

?>