#!/usr/local/php/bin/php -q
<?
###############################################################################
## 스케쥴러에 의한 자동투자 실행 (상품투자시작시간 5분전 실행)
## 1. 자동투자상품 리스트 추출. 자동투자한도금액 추출
## 2. 해당상품 채권그룹 자동투자 설정자 추출
## 3. 자동투자 설정자의 투자제한금액등의 체크 후 투자정보 테이블에 저장
## * * * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/investment/auto_invest_proc.php yes|debug
###############################################################################

if( date('Ymd') >= '20210201' ) exit;				// 2021년02월01일 이후 부터 전체상품 자동투자 서비스 종료

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.cli.php");
include_once($base_path . "/lib/sms.lib.php");

$mode = (@$_SERVER['argv']['1']=='yes') ? $_SERVER['argv']['1'] : 'debug';

$record_start_date = date('Y-m-d H:i:s');
$is_real = ($mode=='yes') ? '1' : '';

$target_start_date = date("Y-m-d H:i", time()+300);


// 주석처리요망: 시뮬레이션 테스트 할때만 활성화 할 것
//$TEST = array('product_idx' => "4528" , "member_idx" => "16218", "recruit_amount"=>"429720000", "mb_point"=>"300000000");		// 주석처리요망: 시뮬레이션 테스트 할때만 활성화 할 것

if($TEST['product_idx'] || $TEST['member_idx']) {
	$mode = "debug";
}

if($TEST['product_idx']) {
	$product_where = " AND A.idx='".$TEST['product_idx']."'";
}
else {
	$product_where = "";
	$product_where.= " AND A.ai_grp_idx!=''";
	$product_where.= " AND A.state=''";
	$product_where.= " AND A.display='Y'";
	$product_where.= " AND LEFT(A.start_datetime, 16)='".$target_start_date."'";
	$product_where.= " AND B.idx!=''";
}


$sql = "
	SELECT
		A.idx, A.gr_idx, A.category, A.title, A.recruit_amount, A.start_datetime, A.ai_grp_idx, A.invest_return, A.invest_period, A.platform,
		B.grp_title, B.auto_inv_unlimited, B.auto_inv_limit_per, B.inv_order
	FROM
		cf_product A
	LEFT JOIN
		cf_auto_invest_config B  ON A.ai_grp_idx=B.idx
	WHERE 1=1
		$product_where
	ORDER BY
		A.start_datetime, A.idx";
//if($mode=='debug') { debug_flush($sql . "\n"); }
$res = sql_query($sql);
if( !$res->num_rows ) {
	sql_close();
	exit;
}




$intLastEnd = 0;	// 자동투자금모집완료시 문자 1번 만 전송 하기위한 체크 변수

// 자동투자 채권그룹소속상품 루프 시작 ------------------------------------------------
WHILE($PRDT = sql_fetch_array($res)) {


	IF($mode=='debug') { debug_flush("\n[" . $PRDT['grp_title'] . " - " . $PRDT['title'] . "]\n\n"); }

	if($TEST['product_idx'] && $TEST['recruit_amount']) {
		$PRDT['recruit_amount'] = $TEST['recruit_amount'];			// 투자목표금액 - 테스트용 임의 설정
	}

	$PRDT['pro_max_limit_amount'] = @floor(($PRDT['recruit_amount']*$INDI_INVESTOR['3']['invest_able_perc'])/10000) * 10000;			// 법인/전문 최대투자한도


	//-- 모집중이거나 이자상환중인 동일차주상품 SELECT 시작 (현재 실행내역에 있는 상품도 포함)
	$sqlx  = "SELECT idx FROM cf_product WHERE state IN ('', '1') AND gr_idx='".$PRDT['gr_idx']."' AND idx>'".$CONF['old_type_end_prdt_idx']."' AND display='Y' ORDER BY idx";
	$resx  = sql_query($sqlx);
	$rowsx = $resx->num_rows;

	IF($rowsx) {
		$is_group_product = ($rowsx > 1) ? true : false;
		$prd_idx_arr = '';
		FOR($x=0,$y=1; $x<$rowsx; $x++,$y++) {
			$r = sql_fetch_array($resx);
			$prd_idx_arr.= "'".$r['idx']."'";
			$prd_idx_arr.= ($y < $rowsx) ? "," : "";
		}
	}
	//-- 모집중이거나 이자상환중인 동일차주상품 SELECT 끝

	$PRDT['auto_inv_limit_amt'] = ($PRDT['auto_inv_unlimited']=='1') ? $PRDT['recruit_amount'] : round($PRDT['recruit_amount'] * ($PRDT['auto_inv_limit_per']/100));
	$PRDT['avail_rdate'] = date("Y-m-d H:i:s",  strtotime($PRDT['start_datetime'])-60);		// 상품오픈 10분전에 셋팅된 정보만 추출하기 위한 제한시간변수

	// 현재 모집금액 합계
	$QUERY = "SELECT IFNULL(SUM(amount),0) AS sum_amount FROM cf_product_invest_detail WHERE product_idx='".$PRDT['idx']."' AND invest_state='Y' AND is_auto_invest='1'";
	$INV_TOTAL = sql_fetch($QUERY);


	// 현재모집금 >= 자동투자목표금액 일 경우
	IF($INV_TOTAL['sum_amount'] >= $PRDT['auto_inv_limit_amt']) {

		$invest_posible_flag = 0;
		$result_code = '1';
		$result_msg = "자동투자금모집완료";
		$intLastEnd++;

	}
	ELSE {

		///////////////////////////////////////////////////////////////////////////
		// 자동투자대상자 소트 설정 : 변경일 2020-01-28
		///////////////////////////////////////////////////////////////////////////
		// 변경 후 ) 1. 자동투자선순위대상자(회원 플래그) > 2. 한번도 자동 투자 안한사람 (플래그 확인) > 3. 설정시간순서(ASC)
		// 변경 전 ) 1. 한번도 자동 투자 안한사람 (플래그 확인) > 2. 설정한 시간순서 > 3. 헬로페이 소상공인 상품만 예외처리 회원 확인 (회원 플래그)
		///////////////////////////////////////////////////////////////////////////

		/*카카오톡 알림톡 추가 클래스 선언*/
		$tcode = "hello005";
		$KaKao_Message_Send = new KaKao_Message_Send();
		$KaKao_Message_Send->PRODUCT_NAME = $PRDT["title"];
		/*카카오톡 알림톡 추가 클래스 선언*/

		$orderBy = "B.is_sbiz_owner DESC";																// 1순위: 자동투자선순위대상자
		$orderBy.= ",CASE auto_invest_count WHEN 0 THEN 1 ELSE 2 END";		// 2순위: 자동투자이력 없는 회원
		SWITCH($PRDT['inv_order'])
		{
			CASE "1" : $orderBy.= ',B.member_type ASC';	BREAK;				//개인우선
			CASE "2" : $orderBy.= ',B.member_type DESC';	BREAK;			//법인우선
			CASE "3" : $orderBy.= ',A.setup_amount2 DESC';	BREAK;		//고액우선
			DEFAULT  : $orderBy.= '';	BREAK;
		}
		$orderBy.= ',A.idx ASC';


		$member_where = "";
		$member_where.= " AND A.ai_grp_idx='".$PRDT['ai_grp_idx']."'";
		$member_where.= " AND B.mb_level='1' AND B.is_rest='N'";
		$member_where.= ($TEST['member_idx']) ? " AND A.member_idx='".$TEST['member_idx']."'" : "";
		///////////////////////////////
		// :: 투자 제한 설정 ::
		///////////////////////////////
		// 양흥조 회원 : 2중계정 ( 2019-07-09 이철규사원 / 본 회원계정은 투자를 제한하도록 협의함)
		// (주)메가지디 회원 : 법인폐업 ( 2021-01-04 이철규주임)
		///////////////////////////////
		$member_where.= " AND A.member_idx NOT IN ('11838','14368')";


		// 해당채권그룹에 자동투자 설정된 회원추출 (휴면계정 제외)
		$sql2 = "
			SELECT
				A.idx AS user_config_idx, A.member_idx, A.setup_amount, A.setup_amount2, A.rdate, A.syndi_id,
				B.mb_id, B.mb_name, B.member_type, B.member_investor_type, B.receive_method, B.mb_hp, B.bank_code, B.account_num, B.va_bank_code2, B.virtual_account2,
				(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE member_idx=A.member_idx AND invest_state='Y') AS auto_invest_count
			FROM
				cf_auto_invest_config_user A
			LEFT JOIN
				g5_member B  ON A.member_idx=B.mb_no
			WHERE 1
				$member_where
			ORDER BY
				$orderBy";
	//if($mode=='debug') { debug_flush($sql2."\n"); }
		$res2  = sql_query($sql2);
		$rows2 = $res2->num_rows;

		// 실행로그 등록
		$log_sql = "
			INSERT INTO
				cf_auto_invest_log
			SET
				exec_date   = '".$record_start_date."',
				product_idx = '".$PRDT['idx']."',
				target_cnt  = '".$rows2."',
				is_real     = '".$is_real."',
				rdate       = NOW()";
		if($mode=='yes') {
			@sql_query($log_sql);
		}
	//if($mode=='debug') { debug_flush($log_sql."\n"); }



		$log_exec_cnt = 0;
		$log_drop_cnt = 0;

		// 투자자 루프 시작 -------------------------------------------------------------------------------------------------------------------------
		FOR($i=0,$j=1; $i<$rows2; $i++,$j++) {

			$invest_posible_flag = 1;
			$result_code = '';
			$result_msg  = '';

			$PRDT['AUTO_INV_MB'][$i] = sql_fetch_array($res2);

			$PRDT['AUTO_INV_MB'][$i]['mb_hp']		    = masterDecrypt($PRDT['AUTO_INV_MB'][$i]['mb_hp'], false);
			$PRDT['AUTO_INV_MB'][$i]['account_num'] = masterDecrypt($PRDT['AUTO_INV_MB'][$i]['account_num'], false);

			if( empty(trim($PRDT['AUTO_INV_MB'][$i]['va_bank_code2'])) || empty(trim($PRDT['AUTO_INV_MB'][$i]['virtual_account2'])) ) {
				$invest_posible_flag = 0;
				if($result_code=='') {
					$result_code = '8';
					$result_msg = "가상계좌 미발급";
				}
			}
			if( empty(trim($PRDT['AUTO_INV_MB'][$i]['bank_code'])) || empty(trim($PRDT['AUTO_INV_MB'][$i]['account_num'])) ) {
				$invest_posible_flag = 0;
				if($result_code=='') {
					$result_code = '9';
					$result_msg = "환급계좌 미등록";
				}
			}

			// 현재 모집금액 합계
			$INV_TOTAL_NOW = sql_fetch("SELECT IFNULL(SUM(amount),0) AS sum_amount FROM cf_product_invest_detail WHERE product_idx='".$PRDT['idx']."' AND invest_state='Y' AND is_auto_invest='1'");

			// 모집금액합계 임의조정
			if($TEST['invested_amount']) {
				$INV_TOTAL['sum_amount'] = $TEST['invested_amount'];
				$INV_TOTAL_NOW['sum_amount'] = $TEST['invested_amount'];
			}

			// 현재모집금 >= 자동투자목표금액의 경우
			IF($INV_TOTAL_NOW['sum_amount'] >= $PRDT['auto_inv_limit_amt']) {
				$invest_posible_flag = 0;
				$intLastEnd++;
				if($result_code=='') {
					$result_code = '1';
					$result_msg = "자동투자금모집완료";
				}
			}

			IF($PRDT['avail_rdate'] < $PRDT['AUTO_INV_MB'][$i]['rdate']) {
				$invest_posible_flag = 0;
				if($result_code=='') {
					$result_code = '6';
					$result_msg = "자동투자대상지정시간 이후 설정";
				}
			}

			// 잔여 모집금액
			$need_recruit_amount = $PRDT['auto_inv_limit_amt'] - $INV_TOTAL_NOW['sum_amount'];

			// 투자 가능금액 설정
			$invest_possible_amount = $need_recruit_amount;

			$MB = get_member($PRDT['AUTO_INV_MB'][$i]['mb_id']);

			// 기 자동투자내역 조회
			$MB_Query = "SELECT COUNT(idx) AS cnt FROM cf_product_invest_detail WHERE member_idx='".$PRDT['AUTO_INV_MB'][$i]['member_idx']."' AND product_idx='".$PRDT['idx']."' AND invest_state='Y' AND is_auto_invest='1'";

			$MB_INVESTED = sql_fetch($MB_Query);
			if($MB_INVESTED['cnt'] > 0) {
				$invest_posible_flag = 0;
				if($result_code=='') {
					$result_code = '2';
					$result_msg = "투자내역존재함";
				}
			}

			$PRDT['AUTO_INV_MB'][$i]['member_type']            = $MB['member_type'];
			$PRDT['AUTO_INV_MB'][$i]['member_investor_type']   = $MB['member_investor_type'];
			$PRDT['AUTO_INV_MB'][$i]['mb_name']                = ($PRDT['AUTO_INV_MB'][$i]['member_type']=='2') ? $MB['mb_co_name'] : $MB['mb_name'];
			$PRDT['AUTO_INV_MB'][$i]['mb_point']               = get_point_sum($MB['mb_id']);
			if($TEST['member_idx'] && $TEST['mb_point']) {
				$PRDT['AUTO_INV_MB'][$i]['mb_point'] = $TEST['mb_point'];			// 테스트용 임의 설정
			}


			IF(!$PRDT['AUTO_INV_MB'][$i]['mb_point'] || $PRDT['AUTO_INV_MB'][$i]['mb_point'] < 10000) {
				$PRDT['AUTO_INV_MB'][$i]['mb_point'] = 0;
			}
			ELSE {
				$PRDT['AUTO_INV_MB'][$i]['mb_point'] = floor($PRDT['AUTO_INV_MB'][$i]['mb_point'] / 10000) * 10000;
			}

			if($mode=='debug') {
				debug_flush("모집금액 : " . number_format($PRDT['recruit_amount']) . " | ");
				debug_flush("자동투자목표액 : " . number_format($PRDT['auto_inv_limit_amt']) . " | ");
				debug_flush("현재모집급액 : " . number_format($INV_TOTAL['sum_amount']) . " | ");
				debug_flush("잔여모집급액 : " . number_format($need_recruit_amount) . " | ");
				debug_flush("법인/전문 최대투자한도 : " . number_format($PRDT['pro_max_limit_amount']) . "\n\n");

				debug_flush("회원번호 : ".$MB["mb_no"]." (".$MB["mb_id"].")\n");
				debug_flush("설정투자금액 : 최소 " . number_format($PRDT['AUTO_INV_MB'][$i]['setup_amount']) . " ~ 최대 " . number_format($PRDT['AUTO_INV_MB'][$i]['setup_amount2']) . "\n");
				debug_flush("보유예치금 : " . number_format($PRDT['AUTO_INV_MB'][$i]['mb_point']) . "\n");
			}


			///////////////////////////////////////////////////////////////////////////
			// 투자 가능금액 설정 (2019-07-26 수정)
			// $MB['invest_possible_amount']  투자 가능금액(사이트 투자제한 금액 기준)
			///////////////////////////////////////////////////////////////////////////
			$PRDT['AUTO_INV_MB'][$i]['invest_possible_amount'] = $MB['invest_possible_amount'];

			IF($PRDT['AUTO_INV_MB'][$i]['member_type']=='1') {		// 개인회원일 경우
				if($PRDT['AUTO_INV_MB'][$i]['member_investor_type']=='1') {		// 일반 투자자일 경우
					$PRDT['AUTO_INV_MB'][$i]['invest_possible_amount'] = ($PRDT['category']=='2') ? $MB['invest_possible_amount_prpt'] : $MB['invest_possible_amount'];
				}
				else {		// 전문.소득적격 투자자일 경우
					$PRDT['AUTO_INV_MB'][$i]['invest_possible_amount'] = $MB['invest_possible_amount'];
				}
			}

			$DUP_INV_GRP = NULL;

			$intUsePoint = 0;		//   실제투자가능금액

			IF($PRDT['AUTO_INV_MB'][$i]['mb_point'] > $PRDT['AUTO_INV_MB'][$i]['setup_amount2']) {
				$intUsePoint =	$PRDT['AUTO_INV_MB'][$i]['setup_amount2'];
			}
			ELSE {
				IF($PRDT['AUTO_INV_MB'][$i]['mb_point'] >= $PRDT['AUTO_INV_MB'][$i]['setup_amount'] && $PRDT['AUTO_INV_MB'][$i]['mb_point'] <= $PRDT['AUTO_INV_MB'][$i]['setup_amount2']) {
					$intUsePoint =	$PRDT['AUTO_INV_MB'][$i]['mb_point'];
				}
			}

			IF(!$MB_INVESTED['cnt']) {

				// 개인회원(일반투자자, 소득적격투자자)에 대한 투자제한 설정
				IF( $PRDT['AUTO_INV_MB'][$i]['member_type']=='1' && in_array($PRDT['AUTO_INV_MB'][$i]['member_investor_type'], array('1','2')) ) {

					$sql3 = "
						SELECT
							COUNT(idx) AS invest_count,
							IFNULL(SUM(amount), 0) AS sum_invest_amount
						FROM
							cf_product_invest
						WHERE 1
							AND member_idx = '".$PRDT['AUTO_INV_MB'][$i]['member_idx']."'
							AND product_idx IN (".$prd_idx_arr.")
							AND invest_state = 'Y'";

					$INVESTING = sql_fetch($sql3);

					$DUP_INV_GRP['cnt']    = $INVESTING['invest_count'];
					$DUP_INV_GRP['amount'] = $INVESTING['sum_invest_amount'];


					IF($is_group_product) {
						$limit_amount = $INDI_INVESTOR[$PRDT['AUTO_INV_MB'][$i]['member_investor_type']]['group_product_limit'];
					}
					ELSE {
						$limit_amount = $INDI_INVESTOR[$PRDT['AUTO_INV_MB'][$i]['member_investor_type']]['single_product_limit'];
					}

					$_invest_possible_amount = $limit_amount - $INVESTING['sum_invest_amount'];

					// 동일차주 금액계산
					IF($_invest_possible_amount == 0)	{
						$invest_posible_flag = 0;
						IF($result_code=='')
						{
							$result_code = '7';
							$result_msg = "동일차주투자금액초과";
						}
					}

					IF($_invest_possible_amount > $PRDT['AUTO_INV_MB'][$i]['invest_possible_amount']) {
						$invest_possible_amount = $PRDT['AUTO_INV_MB'][$i]['invest_possible_amount'];
					}
					ELSE {
						$invest_possible_amount = $_invest_possible_amount;
					}

				}

				IF($invest_possible_amount >= $need_recruit_amount) {
					$invest_possible_amount = $need_recruit_amount;
				}

				//$balance_value = $PRDT['AUTO_INV_MB'][$i]['mb_point']; //예치금

				// 예치금 < 투자설정금액의 경우
				IF($intUsePoint == 0) {

					$invest_posible_flag = 0;
					if($result_code=='') {
						$result_code = '3';
						$result_msg = "예치금부족";
					}

				}
				ELSE {

					// 남은 잔여금 체크,  투자가능금액 체크, 투자가능금액 > 잔여금 보다 크다면 자동투자 설정한 구간에 존재하는지 다시 확인
					IF( $invest_possible_amount && ($invest_possible_amount < $intUsePoint) ) {

						IF($invest_possible_amount >= $PRDT['AUTO_INV_MB'][$i]['setup_amount'] && $invest_possible_amount <= $PRDT['AUTO_INV_MB'][$i]['setup_amount2']) {

							$intUsePoint = floor($invest_possible_amount / 10000) * 10000;

						}
						ELSE {

							$intUsePoint = 0;
							$invest_posible_flag = 0;

							if($result_code=='') {
								$result_code = '4';
								$result_msg = "투자가능금액초과설정";
							}

						}

					}

				}

				IF($PRDT['AUTO_INV_MB'][$i]['invest_possible_amount'] > 0) {

					///////////////////////////////////////////////////////////////////////////////////////////
					// 20200826 추가 (법인, 개인전문투자자 : 총 모집금액의 40% 까지만 투자가능)
					if($PRDT['AUTO_INV_MB'][$i]['member_type']=='2' || ($PRDT['AUTO_INV_MB'][$i]['member_type']=='1' && $PRDT['AUTO_INV_MB'][$i]['member_investor_type']=='3')) {

						// 실투자가능액이 법인투자최대한도액 보다 클 경우
						//   기존 - 한도초과 에러 출력
						//   변경 - 실투자가능액을 법인투자최대한도액으로 대체

						if($intUsePoint >= $PRDT['pro_max_limit_amount']) {
							if($PRDT['pro_max_limit_amount'] >= $PRDT['AUTO_INV_MB'][$i]['setup_amount'] && $PRDT['pro_max_limit_amount'] <= $PRDT['AUTO_INV_MB'][$i]['setup_amount2']) {
								$intUsePoint = $PRDT['pro_max_limit_amount'];
							}
							else {
								// 예치금이 충분한 경우라도 법인투자가능금액이 자동투자 설정치범주에 미치지 못하는 경우
								$invest_posible_flag = 0;
								if($result_code=='') {
									$result_code = '6';
									$result_msg = "최소설정금액미만(법인/전문투자자 한도액기준)";
								}
							}
						}

					}
					///////////////////////////////////////////////////////////////////////////////////////////

				}
				else {

					// 개인 기투자내역 상황 확인
					$invest_posible_flag = 0;
					IF($result_code=='') {
						$result_code = '5';
						$result_msg = "한도초과";
					}

				}


				if($mode=='debug') {
					//debug_flush("투자처리순번 : ".$MB_INVESTED['cnt'] . "\n");
					debug_flush("투자가능금액 : " . number_format($invest_possible_amount) . "\n");
					debug_flush("실투자가능금액 : " . number_format($intUsePoint) . "\n");
				}


				// [투자내역 등록] ----------------------------------------------------------
				IF($invest_posible_flag) {

					$log_exec_cnt++;

					$input_datetime = date('Y-m-d H:i:s');
					$INPUT_DATE = explode(" ", $input_datetime);
					$input_day  = $INPUT_DATE[0];
					$input_time = $INPUT_DATE[1];

					//////////////////
					// 투자내역 등록
					//////////////////

					$query = "
						INSERT INTO
							cf_product_invest
						SET
							amount          = '".$intUsePoint."',
							member_idx      = '".$PRDT['AUTO_INV_MB'][$i]['member_idx']."',
							product_idx     = '".$PRDT['idx']."',
							invest_state    = 'Y',
							insert_date     = '".$input_day."',
							insert_time     = '".$input_time."',
							insert_datetime = '".$input_datetime."',
							syndi_id        = '".$PRDT['AUTO_INV_MB'][$i]['syndi_id']."'";
					if($mode=='debug') { debug_flush(">>>> 투자내역 등록\n" . $query . "\n"); }
					if($mode=='yes') {

						$result = sql_query($query);
						$invest_idx = sql_insert_id();

						// 원리금 수취권 번호 업데이트 ---------------
						$invest_try_count = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product_invest_detail WHERE member_idx='".$PRDT['AUTO_INV_MB'][$i]['member_idx']."' AND product_idx='".$PRDT['idx']."'")['cnt'];		// 투자시도수

						$prin_rcv_no = 'I' . $invest_idx;
						if($invest_try_count > 0) $prin_rcv_no.= '_' . ($invest_try_count+1);

						sql_query("UPDATE cf_product_invest SET prin_rcv_no='".$prin_rcv_no."' WHERE idx='".$invest_idx."'");
						// 원리금 수취권 번호 업데이트 ---------------


						//////////////////////
						// 투자상세내역 등록
						//////////////////////
						$query2 = "
							INSERT INTO
								 cf_product_invest_detail
							 SET
								 invest_idx     = '".$invest_idx."',
								 amount         = '".$intUsePoint."',
								 member_idx     = '".$PRDT['AUTO_INV_MB'][$i]['member_idx']."',
								 product_idx    = '".$PRDT['idx']."',
								 invest_state   = 'Y',
								 insert_date    = '".$input_day."',
								 insert_time    = '".$input_time."',
								 is_auto_invest = '1',
								 syndi_id       = '".$PRDT['AUTO_INV_MB'][$i]['syndi_id']."'";
						$result2 = sql_query($query2);

						// 투자데이터 기록 성공시
						if($invest_idx) {

							$result_code = "0";
							$result_msg  = "투자완료";

							////////////////////////////
							// **** 예치금 차감 ***** //
							////////////////////////////
							$po_content = $PRDT["title"]. "-투자(자동)";
							insert_point($PRDT['AUTO_INV_MB'][$i]['mb_id'], $intUsePoint * (-1), $po_content, '@invest', $PRDT['AUTO_INV_MB'][$i]['mb_id'], $PRDT['AUTO_INV_MB'][$i]['mb_id'].'-'.uniqid(''), 0);


							////////////////////////////
							// 투자신청완료 문자 발송 //
							////////////////////////////

							$member["mb_no"]  	=	$PRDT['AUTO_INV_MB'][$i]['member_idx'];
							$member["mb_name"] 	=	$PRDT['AUTO_INV_MB'][$i]['mb_name'];
							$member["mb_hp"] 		=	$PRDT['AUTO_INV_MB'][$i]['mb_hp'];

							/*카카오톡 알림톡 추가*/
							$KaKao_Message_Send->INVEST_MONEY = $intUsePoint;
							$KaKao_Message_Send->MEMBER = $member;	// common.lib member 환경변수
							$KaKao_Message_Send->kakao_insert($tcode);
							/*카카오톡 알림톡 추가*/
							/* 기존 sms lib

								$SMS_DATA   = sql_fetch("SELECT * FROM `g5_sms_userinfo` WHERE use_yn='1' AND idx='2'");
								IF($SMS_DATA['msg']) {
									$sms_msg = preg_replace("/\{PROJECT_NAME\}/", $PRDT['title'], $SMS_DATA['msg']);
									$sms_msg = preg_replace("/\{FUNDING_PRICE\}/", price_cutting($intUsePoint), $sms_msg);

									$rst = unit_sms_send($_admin_sms_number, $PRDT['AUTO_INV_MB'][$i]['mb_hp'], $sms_msg);
								}

							*/
							/////////////////////////////////////////////////
							// 투자예약 데이터 순서 변경을 위한 설정 재등록
							/////////////////////////////////////////////////
							$CFGS = sql_fetch("SELECT * FROM cf_auto_invest_config_user WHERE idx='".$PRDT['AUTO_INV_MB'][$i]['user_config_idx']."'");

							$edate = date('Y-m-d H:i:s');

							// 기존내역 삭제
							sql_query("DELETE FROM cf_auto_invest_config_user WHERE idx='".$CFGS['idx']."'");

							// 설정 재등록
							$lsql = "
								INSERT INTO
									cf_auto_invest_config_user
								SET
									ai_grp_idx           = '".$CFGS['ai_grp_idx']."',
									member_idx           = '".$CFGS['member_idx']."',
									setup_amount         = '".$CFGS['setup_amount']."',
									setup_amount2        = '".$CFGS['setup_amount2']."',
									invest_warning_agree = '".$CFGS['invest_warning_agree']."',
									rdate                = '".$CFGS['rdate']."',
									edate                = '".$edate."',
									syndi_id             = '".$CFGS['syndi_id']."'";
							$lres = sql_query($lsql);


							/////////////////////////////////////////////////////
							// 올리고 자동투자 설정자 자동투자완료 레포팅 전달
							/////////////////////////////////////////////////////
							if( preg_match("/oligo/i", $PRDT['platform']) ) {
								@shell_exec("/usr/local/php/bin/php -q " . G5_SYNDICATE_PATH . "/oligo/report/investResultReport.php " . $invest_idx);
							}


							unset($CFGS);
						}

					}		// end if($mode=='yes')

				}		// end if($invest_posible_flag)
				ELSE {

					$log_drop_cnt++;

				}
				// [투자내역 등록 끝] ----------------------------------------------------------

			}
			ELSE {

				$log_drop_cnt++;

			}

			//////////////////////////////////////////
			// 상세실행로그등록
			//////////////////////////////////////////
			$log_sql = "
				INSERT INTO
					cf_auto_invest_log_detail
				SET
					exec_date   = '".$record_start_date."',
					product_idx = '".$PRDT['idx']."',
					member_idx  = '".$PRDT['AUTO_INV_MB'][$i]['member_idx']."',
					amount      = '".$intUsePoint."',
					rcode       = '".$result_code."',
					msg         = '".$result_msg."',
					is_real     = '".$is_real."'";
			if($mode=='yes') {
				$log_res = sql_query($log_sql);
			}


			//////////////////////////////////////////////
			// 투자액 모집완료시 투자종료일(플래그) 표기
			//////////////////////////////////////////////

			$INV_FINAL = sql_fetch("SELECT IFNULL(SUM(amount),0) AS sum_amount FROM cf_product_invest WHERE product_idx='".$PRDT['idx']."' AND invest_state='Y'");

			if( $PRDT['recruit_amount'] == $INV_FINAL['sum_amount'] ) {
				$product_update = "
					UPDATE
						cf_product
					SET
						invest_end_date = '".date('Y-m-d')."'
					WHERE
						idx = '".$PRDT['idx']."'";
				if($mode=='yes') {
					sql_query($product_update);
				}
			}

			unset($MB);

			if($mode=='debug') {

				//debug_flush("동일차주상품투자건 : " . number_format($DUP_INV_GRP['cnt']) . "\n");
				//debug_flush("동일차주상품투자금액 : " . number_format($DUP_INV_GRP['amount']) . "\n");
				//debug_flush("기투자내역 : " . number_format($MB_INVESTED['cnt']) . "건\n");
				debug_flush("에러코드: " . $result_code . "\n");
				debug_flush("상세메세지: " . $result_msg . "\n");

			}

			usleep(1000);

		}  // end for
		// 투자자 루프 끝 ------------------------------------------------
		sql_free_result($res2);

		// 실행로그 수정 마감
		$log_sql = "
			UPDATE
				cf_auto_invest_log
			SET
				exec_cnt = '".$log_exec_cnt."',
				drop_cnt = '".$log_drop_cnt."',
				fdate    = NOW()
			WHERE (1)
				AND exec_date   = '".$record_start_date."'
				AND product_idx = '".$PRDT['idx']."'
				AND is_real     = '".$is_real."'";
		if($mode=='yes') {
			@sql_query($log_sql);
		}

		////////////////////////////////////////////////
		// 올리고에 등록된 상품일 경우 상태값 전송
		////////////////////////////////////////////////
		if($mode=='yes') {
			if( preg_match("/oligo/i", $PRDT['platform']) ) {
				@shell_exec("/usr/local/php/bin/php -q " . G5_SYNDICATE_PATH . "/oligo/report/productStateReport.php " . $PRDT['idx']);
			}
		}

	}		// 선별 및 투자처리 끝

	if($mode=='yes') {
		IF($intLastEnd == 1) {
			IF(!preg_match("/소상공인/i", $PRDT['title'])) {
				fn_cf_product_admin_report($prd_idx);		/* 리포트 데이터 생성*/
				fn_hello_status_smssend($prd_idx);			/* sms전송 */
			}
		}
	}


	if($mode=='debug') { debug_flush("\n-----------------------------------------------------------------------------------------------------------------------------------------------\n"); }


	unset($PRDT);
}
// 자동투자 채권그룹소속상품 루프 끝 ------------------------------------------------
sql_free_result($res);

sql_close();
exit;
?>
