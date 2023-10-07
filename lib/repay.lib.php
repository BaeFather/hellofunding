<?php
###############################################
## 투자자 정산 처리 함수
###############################################

/*
 * 2019-01-21 계좌번호 복호화 추가
 */

// 통합 원리금정산 함수 ------------------------------------------------------------------------------------
//   ** 개별 정산 라이브러리는 common.php 의 investStatement() 참조 **
function repayCalculation($product_idx, $invest_member_id='', $invest_period='', $loan_start_date='', $loan_end_date='', $invest_usefee='', $invest_usefee_type='') {

	global $CONF, $BANK, $VBANK, $member;

	if(!$product_idx) return false;

	$invest_member_id   = trim($invest_member_id);				// 특정 투자자만 조회 할 경우
	if($invest_member_id) {
		$r = sql_fetch("SELECT mb_no FROM g5_member WHERE mb_id='".$invest_member_id."'");
		$invest_member_idx = $r['mb_no'];
		unset($r);
	}

	$invest_period      = trim($invest_period);						// (시뮬레이션용) 투자개월수
	$loan_start_date    = trim($loan_start_date);					// (시뮬레이션용) 투자시작일
	$loan_end_date      = trim($loan_end_date);						// (시뮬레이션용) 투자만기일
	$invest_usefee      = trim($invest_usefee);						// (시뮬레이션용) 플랫폼이용료율
	$invest_usefee_type = trim($invest_usefee_type);			// (시뮬레이션용) 플랫폼이용료 징수방식


	//원금상환일 +1일 처리 예외적용 상품 배열
	$EXCEPTION_PRODUCT = array(94,95,97,98,109,111,117);


	$where = " 1=1 ";
	$where.= " AND B.idx=A.product_idx ";
	$where.= " AND A.product_idx='$product_idx' ";
	$where.= " AND A.invest_state='Y' ";

	///////////////////
	// 상품정보
	///////////////////
	$prdt_sql = "
		SELECT
			COUNT(A.idx) AS invest_count
			,SUM(A.amount) AS invest_principal
			,A.ib_regist, A.prin_rcv_no
			,B.idx, B.gr_idx, B.ai_grp_idx, B.state, B.category, B.title
			,B.invest_return, B.withhold_tax_rate, B.loan_interest_rate, B.overdue_rate, B.loan_usefee, B.invest_usefee, B.invest_usefee_type
			,B.invest_period, B.invest_days, B.recruit_period_start, B.recruit_period_end, B.recruit_amount, B.repay_type
			,B.ib_trust, B.ib_product_regist, B.loan_mb_no, B.repay_acct_no
			,(SELECT ref_no FROM KSNET_VR_ACCOUNT WHERE VR_ACCT_NO = B.repay_acct_no) AS ref_no
			,B.loan_dep_bank_cd1, B.loan_dep_acct_nb1, B.loan_dep_amt1, B.loan_dep_acct_memo1
			,B.loan_dep_bank_cd2, B.loan_dep_acct_nb2, B.loan_dep_amt2, B.loan_dep_acct_memo2
			,B.loan_dep_bank_cd3, B.loan_dep_acct_nb3, B.loan_dep_amt3, B.loan_dep_acct_memo3
			,B.loan_dep_bank_cd4, B.loan_dep_acct_nb4, B.loan_dep_amt4, B.loan_dep_acct_memo4
			,B.loan_dep_bank_cd5, B.loan_dep_acct_nb5, B.loan_dep_amt5, B.loan_dep_acct_memo5
			,B.open_datetime, B.open_date, B.start_datetime, B.start_date, B.end_datetime, B.end_date, B.display
			,B.loan_name, B.loan_contact, B.loan_address
			,B.insert_date, B.invest_end_date, B.loan_start_date, B.loan_end_date, B.loan_end_date_orig, B.advanced_payment, B.advance_invest, B.advance_invest_ratio
		FROM
			cf_product_invest A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		WHERE
			$where
		ORDER BY
			A.idx DESC";
	$PRDT = sql_fetch($prdt_sql, true);

	// 제3자 예치금 관리 시스템 적용여부
	$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;

	// 대출자 정보
	// 이상규 과장 요청 2020.03.27 대출실행전 대출자 성명 노출
	//if($ib_trust && $PRDT['loan_mb_no']) {
	if($PRDT['loan_mb_no']) {
		$LOANER = sql_fetch("SELECT * FROM g5_member WHERE mb_no='".$PRDT['loan_mb_no']."'");
		$LOANER['mb_hp'] = masterDecrypt($LOANER['mb_hp'], false);
		$LOANER['account_num'] = masterDecrypt($LOANER['account_num'], false);
	}

	// 투자 개월수 설정
	if($invest_period) {
		$PRDT['invest_period'] = $invest_period;
	}
	else {
		$PRDT['invest_period'] = ($PRDT['invest_period']) ? $PRDT['invest_period'] : 1;
	}

	// 대출시작일
	$PRDT['loan_start_date'] = ($PRDT['loan_start_date']!='0000-00-00') ? $PRDT['loan_start_date'] : '';
	if($loan_start_date && $PRDT['loan_start_date'] != $loan_start_date) {
		$PRDT['loan_start_date'] = $loan_start_date;		// 시뮬레이션용
	}
	if(!$PRDT['loan_start_date']) $PRDT['loan_start_date'] = date('Y-m-d');  //투자시뮬레이션에서만 적용되는 사항 :: 셋팅된 시작일자나 변수로 넘어온 시작일자가 없을 경우 오늘을 시작일자로

	// 대출종료일 (실 상품 대출종료일 전일 경우)
	$PRDT['loan_end_date'] = ($PRDT['loan_end_date']!='0000-00-00') ? $PRDT['loan_end_date'] : '';
	if($loan_end_date && $PRDT['loan_end_date'] != $loan_end_date) {
		$PRDT['loan_end_date'] = $loan_end_date;		// 시뮬레이션용
	}

	// 플랫폼 수수료 요율 (시뮬레이션용)
	if($invest_usefee) $PRDT['invest_usefee'] = $invest_usefee;

	// 플랫폼 수수료 징수방식 (시뮬레이션용)
	if($invest_usefee_type) $PRDT['invest_usefee_type'] = $invest_usefee_type;


	$INI['static_repay_day'] = 5;			// 기준정산일

	$SDATE_OBJ = new DateTime($PRDT['loan_start_date']);

	if($PRDT['loan_end_date'] && $PRDT['loan_end_date'] > '0000-00-00') {
		$EDATE_OBJ = new DateTime($PRDT['loan_end_date']);
	}
	else {
		if($PRDT['invest_period']==1 && $PRDT['invest_days']>0) {
			$EDATE_OBJ = new DateTime(date('Y-m-d', strtotime($PRDT['loan_start_date'].' +'.$PRDT['invest_days'].' day')));
		}
		else {
			$EDATE_OBJ = new DateTime(date('Y-m-d', strtotime($PRDT['loan_start_date'].' +'.$PRDT['invest_period'].' month')));
		}
	}

	$TOTAL_DATE_OBJ = date_diff($SDATE_OBJ, $EDATE_OBJ);

	$INI['loan_start_date'] = $PRDT['loan_start_date'];
	$INI['total_day_count'] = $TOTAL_DATE_OBJ->days;
	$INI['loan_end_date']   = $EDATE_OBJ->format('Y-m-d');

	$TMP0 = sql_fetch("SELECT MAX(turn) AS max_turn FROM cf_product_success WHERE product_idx='".$PRDT['idx']."'");
	$INI['max_paied_turn'] = ($TMP0['max_turn']) ? $TMP0['max_turn'] : 0;
	unset($TMP0);

	$INI['day_loan_interest']   = ($PRDT['invest_principal'] * ($PRDT['loan_interest_rate']/100)) / 365;	// 일별 대출이자 전체
	$INI['day_invest_interest'] = ($PRDT['invest_principal'] * ($PRDT['invest_return']/100)) / 365;				// 일별 투자자수익금 전체

	// 상환 계산 차수
	$INI['calc_count'] = $PRDT['invest_period'] + 1;


	////////////////////////////////
	// 투자자 및 투자정보 배열화
	////////////////////////////////

	$where2 = $where;
	$where2.= ($invest_member_idx) ? " AND C.mb_no='$invest_member_idx'" : "";

	$sql = "
		SELECT SQL_NO_CACHE
			A.*,
			B.withhold_tax_rate, B.invest_return, B.invest_period, B.invest_usefee, B.invest_usefee_type,
			C.mb_no, C.mb_id, C.member_type, C.mb_name, C.mb_co_name, C.mb_co_reg_num,
			C.is_creditor, C.is_owner_operator, C.receive_method, C.remit_fee,
			C.bank_code, C.account_num, C.bank_private_name, C.bank_private_name_sub,
			C.va_bank_code, C.virtual_account, C.va_private_name,
			C.va_bank_code2, C.virtual_account2, C.va_private_name2,
			C.insidebank_after_trans_target
		FROM
			cf_product_invest A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		LEFT JOIN
			g5_member C  ON A.member_idx=C.mb_no
		WHERE
			$where2
		ORDER BY
			A.idx DESC";

	$result = sql_query($sql);
	$invest_rows = $result->num_rows;

	for($j=0; $j<$invest_rows; $j++) {
		if( $r = sql_fetch_array($result) ) {
			$r['account_num'] = masterDecrypt($r['account_num'], false);

			$INVEST[] = $r;

			$sql2 = "
				SELECT
					member_idx,
					COUNT(idx) AS cnt_idx,
					SUM(amount) AS sum_amount
				FROM
					cf_product_invest
				WHERE 1=1
					AND member_idx='".$r['member_idx']."'
					AND invest_state IN('Y','R')";
			$r2 = sql_fetch($sql2);

			$MTOTAL_INVEST_SUM[$r2['member_idx']] = array('count'=>$r2['cnt_idx'], 'amount'=>$r2['sum_amount']);
		}
	}
	unset($r); unset($r2);

	//print_rr($INVEST, "font-size:11px;");
	//print_rr($MTOTAL_INVEST_SUM, "font-size:11px;");
	//print_rr($INI);


	$REPAY = array();

	if($PRDT['invest_period']==1 && $PRDT['invest_days']>0) {

		$INI['repay_count'] = 1;														// 정산 회차 추출

		$REPAY[0]['repay_date']   = $INI['loan_end_date'];
		$REPAY[0]['target_sdate'] = $INI['loan_start_date'];
		$REPAY[0]['target_edate'] = $INI['loan_end_date'];
		$REPAY[0]['day_count']	  = $INI['total_day_count'];
		$REPAY[0]['principal']    = $INI['principal'];


	}
	else {

		////////////////////////
		// 정산 차수 루프 시작
		////////////////////////
		$x = 0;
		for($i=0; $i<$INI['calc_count']; $i++) {

			$REPAY[$x]['repay_num'] = $x + 1;

			$EDATE_OBJ = new DateTime(date('Y-m-d', strtotime($SDATE_OBJ->format('Y-m').' last day next month')));	// 매 정산월의 마지막 일자
			$DIFF_OBJ  = date_diff($SDATE_OBJ, $EDATE_OBJ);

			if($EDATE_OBJ->format('Y-m-d') < $INI['loan_end_date']) {

				$repay_day = $SDATE_OBJ->format('Y-m').'-'.sprintf('%02d', $INI['static_repay_day']);
				$repay_day = date('Y-m-d', strtotime($repay_day.' +1 month'));

				$REPAY[$x]['repay_date']   = $repay_day;										// 정산지급일
				$REPAY[$x]['target_sdate'] = $SDATE_OBJ->format('Y-m-d');		// 정산시작일
				$REPAY[$x]['target_edate'] = $EDATE_OBJ->format('Y-m-d');		// 정산종료일
				$REPAY[$x]['day_count']    = $DIFF_OBJ->days + 1;						// 일자수
				$SDATE_OBJ->modify('first day of next month');

				$x++;

			}
			else {

				//마지막 달 계산
				$LOAN_DATE_OBJ    = new DateTime($INI['loan_end_date']);
				$DIFF_OBJ         = date_diff($SDATE_OBJ, $LOAN_DATE_OBJ);
				$repay_day        = $LOAN_DATE_OBJ->format('Y-m-d');
				$static_repay_day = substr($repay_day, 0, 7)."-".sprintf("%02d", $INI['static_repay_day']);

				$REPAY[$x]['repay_date']   = $repay_day;
				$REPAY[$x]['target_sdate'] = $SDATE_OBJ->format('Y-m-d');
				$REPAY[$x]['target_edate'] = $repay_day;
				$REPAY[$x]['day_count']	   = $DIFF_OBJ->days;

			}
		}

		$INI['repay_count']  = count($REPAY);

	}

	unset($EDATE_OBJ);
	unset($TOTAL_DATE_OBJ);
	unset($DIFF_OBJ);


	//$orig_invest_day_count = ceil( (strtotime($PRDT['loan_start_date'].' +'.$PRDT['invest_period'].' month') - strtotime($PRDT['loan_start_date'])) / 86400 );		// 중도상환등의 이벤트가 발생하기 전 이자계산일수
	//echo "(이자계산일수: ".$INI['total_day_count']."일 / 최초설정투자일수: ".$orig_invest_day_count."일)<br>\n";


	$INI['invest_count'] = $invest_rows;
//$INI['invest_count'] = $PRDT['invest_count'];


	// 핀크자료를 만들기 위한 임시 작업 ---------------------------------
	$PAIED_SUM = array(
		'invest_interest' => 0,
		'invest_interest' => 0,
		'invest_usefee'=> 0,
		'TAX' => array('interest_tax' => 0, 'local_tax' => 0, 'sum' => 0),
		'withhold' => 0,
		'interest' => 0,
		'repay_principal' => 0
	);
	// 핀크자료를 만들기 위한 임시 작업 ---------------------------------

	///////////////////////////////
	// 상환회차 루프 시작
	///////////////////////////////
	for($i=0,$turn=1; $i<$INI['repay_count']; $i++,$turn++) {

		$daysOfYear = ( in_array(substr($REPAY[$i]['target_sdate'],0,4), $CONF['LEAP_YEAR']) ) ? 366 : 365;		// ★★★ 일별이자 산출 변수 (윤년구분) ★★★

		/////////////////////////////////
		// 출력용 이자정산지급일 설정
		/////////////////////////////////
		if( $turn < count($REPAY) ) {
			$REPAY[$i]['repay_schedule_date'] = $REPAY[$i]['repay_date'];
		}
		else {
			$REPAY[$i]['repay_schedule_date'] = $INI['loan_end_date'];
		}


		/////////////////////////
		// 투자 성패기록 추출
		/////////////////////////
		$succ_sql = "SELECT * FROM cf_product_success WHERE product_idx='".$PRDT['idx']."' AND turn='".$turn."'";
	//$succ_sql = "SELECT * FROM cf_product_success WHERE product_idx='".$PRDT['idx']."' AND date='".$REPAY[$i]['repay_date']."'";
		$SUCC = sql_fetch($succ_sql);
		$REPAY[$i]['SUCCESS'] = $SUCC;

		///////////////////////////////
		// 투자자 루프 시작
		///////////////////////////////
		for($j=0; $j<$INI['invest_count']; $j++) {

			$REPAY[$i]['LIST'][$j]['day_invest_interest'] = ($INVEST[$j]['amount'] * ($PRDT['invest_return']/100)) / $daysOfYear;												// 일별 투자자 수익금

			$REPAY[$i]['LIST'][$j]['invest_idx']  = $INVEST[$j]['idx'];
			$REPAY[$i]['LIST'][$j]['mb_no']       = $INVEST[$j]['mb_no'];
			$REPAY[$i]['LIST'][$j]['mb_id']       = $INVEST[$j]['mb_id'];
			$REPAY[$i]['LIST'][$j]['mb_name']     = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_name'] : $INVEST[$j]['mb_name'];
			$REPAY[$i]['LIST'][$j]['jumin']       = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_reg_num'] : getJumin($INVEST[$j]['member_idx']);
			$REPAY[$i]['LIST'][$j]['member_type'] = $INVEST[$j]['member_type'];
			$REPAY[$i]['LIST'][$j]['is_owner_operator'] = $INVEST[$j]['is_owner_operator'];

			$REPAY[$i]['LIST'][$j]['insidebank_after_trans_target'] = $INVEST[$j]['insidebank_after_trans_target'];		//신한 예치금 이전 대상자 플래그

			//--------------------------------------------------------------------------------------------
			/*
			// 이자정산지급일에 따른 세율 변환
			$interest_tax_ratio = $CONF['interest_tax_ratio'];		// 이자소득세
			$local_tax_ratio    = $CONF['local_tax_ratio'];				// 지방세: 이자소득세의 10%

			// 개인/법인 투자자별 세율 변경
			if( ($PRDT['loan_start_date'] >= $CONF['lastTaxChangeDate']) || ($REPAY[$i]['repay_schedule_date'] >= $CONF['lastTaxChangeDate']) ) {		// <=== 세율변경 적용일 부터 출시된 상품 기준으로 변경된 세율 적용
				$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? $CONF['corp']['interest_tax_ratio'] : $CONF['indi']['interest_tax_ratio'];		// 이자소득세율
				$local_tax_ratio    = ($INVEST[$j]['member_type']=='2') ? $CONF['corp']['local_tax_ratio'] : $CONF['indi']['local_tax_ratio'];					// 지방세율
			}
			*/

			// 세율조정 발생시기를 단정할 수 없으므로 조건시을 대입한다.
			// 2021-08-21 온투법 승인일
			// 2021-10-21 헬로핀테크 헬로크라우드대부 합병일
			// 법인은 무조건 27.5%, 개인은 정산일 기준 다르게 적용
			if($PRDT['loan_start_date'] >= '2021-08-27') {
				$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14;
			}
			else {
				if( $REPAY[$i]['repay_schedule_date'] < '2021-10-21' ) {

					$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.25;

					// 0.14로 정산된것 상품회차 예외처리
					if( $product_idx == '6281' && $turn >= 3) {
						if($INVEST[$j]['member_type']=='1') $interest_tax_ratio = 0.14;
					}

					// 0.14로 정산된 상품 예외처리
					if( in_array($product_idx, array('6561','6573','6584','6596','6607')) ) {
						if($INVEST[$j]['member_type']=='1') $interest_tax_ratio = 0.14;
					}

				}
				else {
					$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14;
				}
			}

			$local_tax_ratio = 0.1;		// 소득세: interest_tax_ratio의 10%

			//--------------------------------------------------------------------------------------------


			/////////////////////////////////////////////////////
			// 인사이드뱅크 회수금(상환금) 배분 처리내역 설정
			/////////////////////////////////////////////////////
			if($ib_trust) {
				$REPAY[$i]['LIST'][$j]['ib_regist'] = $INVEST[$j]['ib_regist'];

				$ib_sql = "
					SELECT
						TR_AMT, CTAX_AMT, FEE, JI_DATE, JI_TIME, RESP_CODE
					FROM
						IB_FB_P2P_REPAY_REQ_DETAIL
					WHERE 1
						AND invest_idx='".$INVEST[$j]['idx']."'
						AND turn='".$turn."'
					ORDER BY
						rdate DESC
					LIMIT 1";
				//echo $ib_sql."<br>\n";
				$IB = sql_fetch($ib_sql);

				$REPAY[$i]['LIST'][$j]['ib_withdraw'] = $IB['RESP_CODE'];		// 회수금 배분처리 성공 플래그
				if($IB['RESP_CODE']=='00000000') {
					$REPAY[$i]['LIST'][$j]['ib_withdraw_datetime'] = date('Y-m-d H:i', strtotime($IB['JI_DATE'].$IB['JI_TIME']));		// 회수금 배분처리일시
				}
			}


			///////////////////////////////////
			// 지급기록 추출 및 지급계좌 설정
			///////////////////////////////////
			$give_sql = "
				SELECT
					idx, `date`, invest_amount, interest, principal, interest_tax, local_tax, fee, is_creditor, remit_fee, receive_method, bank_name, account_num, bank_private_name, banking_date, mgtKey
				FROM
					cf_product_give
				WHERE 1
					AND invest_idx='".$INVEST[$j]['idx']."'
					AND product_idx='".$PRDT['idx']."'
					AND turn='".$turn."'
					AND is_overdue='N'";
			$GIVE = sql_fetch($give_sql);

			$REPAY[$i]['LIST'][$j]['paied'] = ($GIVE['idx']) ? 'Y' : 'N';

			$REPAY[$i]['LIST'][$j]['give_idx']     = ($GIVE['idx']) ? $GIVE['idx'] : '';
			$REPAY[$i]['LIST'][$j]['paied_date']   = $GIVE['date'];
			$REPAY[$i]['LIST'][$j]['paied_amount'] = $GIVE['interest'];		// 이자 실입금액
		//$REPAY[$i]['LIST'][$j]['paied_amount'] = $GIVE['invest_amount'];		// 이자 실입금액 : invest_amount 사용 자제요망
			$REPAY[$i]['LIST'][$j]['remit_fee']    = ($GIVE['remit_fee']=='1') ? $GIVE['remit_fee'] : $INVEST[$j]['remit_fee'];
			$REPAY[$i]['LIST'][$j]['mgtKey']       = $GIVE['mgtKey'];

			if($REPAY[$i]['LIST'][$j]['paied']=="Y") {
				$REPAY[$i]['LIST'][$j]['is_creditor']       = $GIVE['is_creditor'];
				$REPAY[$i]['LIST'][$j]['receive_method']	  = $GIVE['receive_method'];
				$REPAY[$i]['LIST'][$j]['bank']			        = $GIVE['bank_name'];
				$REPAY[$i]['LIST'][$j]['bank_code']			    = "";		// 이미 지급됬는데 별 필요없을듯
				$REPAY[$i]['LIST'][$j]['account_num']       = $GIVE['account_num'];
				$REPAY[$i]['LIST'][$j]['bank_private_name'] = $GIVE['bank_private_name'];
				$REPAY[$i]['LIST'][$j]['banking_date']      = $GIVE['banking_date'];
			}
			else {
				$REPAY[$i]['LIST'][$j]['is_creditor']    = $INVEST[$j]['is_creditor'];
				$REPAY[$i]['LIST'][$j]['receive_method'] = $INVEST[$j]['receive_method'];
				if($INVEST[$j]['receive_method']=='1') {
					$REPAY[$i]['LIST'][$j]['bank']              = $BANK[$INVEST[$j]['bank_code']];
					$REPAY[$i]['LIST'][$j]['bank_code']         = $INVEST[$j]['bank_code'];
					$REPAY[$i]['LIST'][$j]['account_num']       = $INVEST[$j]['account_num'];
					$REPAY[$i]['LIST'][$j]['bank_private_name'] = $INVEST[$j]['bank_private_name'];
					$REPAY[$i]['LIST'][$j]['bank_private_name'].= ($INVEST[$j]['bank_private_name_sub']) ? "(".$INVEST[$j]['bank_private_name_sub'].")" : "";
				}
				else if($INVEST[$j]['receive_method']=='2') {		// 예치금환급 선택회원은 제3자 예치시스템적용상품 여부와 상관없이 무조건 신한가상계좌로 입금받도록 수정 : 2018-04-05
					$REPAY[$i]['LIST'][$j]['bank']              = $BANK[$INVEST[$j]['va_bank_code2']];
					$REPAY[$i]['LIST'][$j]['bank_code']         = $INVEST[$j]['va_bank_code2'];
					$REPAY[$i]['LIST'][$j]['account_num']       = $INVEST[$j]['virtual_account2'];
					$REPAY[$i]['LIST'][$j]['bank_private_name'] = $INVEST[$j]['va_private_name2'];
				}
				else {
					$REPAY[$i]['LIST'][$j]['bank']              = "";
					$REPAY[$i]['LIST'][$j]['bank_code']         = "";
					$REPAY[$i]['LIST'][$j]['account_num']       = "";
					$REPAY[$i]['LIST'][$j]['bank_private_name'] = "";
				}
				$REPAY[$i]['LIST'][$j]['banking_date']        = "";
			}

			$REPAY[$i]['LIST'][$j]['amount']          = $INVEST[$j]['amount'];
			$REPAY[$i]['LIST'][$j]['invest_interest'] = floor($REPAY[$i]['LIST'][$j]['day_invest_interest'] * $REPAY[$i]['day_count']);


			////////////////////////////////////////////
			// 일별 플랫폼이용료 설정 (예외설정사항을 최우선으로 적용)
			////////////////////////////////////////////
			$EXTFEE_ROW = sql_fetch("SELECT idx, fee FROM cf_platform_fee WHERE member_idx='".$INVEST[$j]['mb_no']."' AND product_idx='".$product_idx."'");
			if($EXTFEE_ROW['idx']) {
				$REPAY[$i]['LIST'][$j]['day_invest_usefee'] = ($INVEST[$j]['amount'] * ($EXTFEE_ROW['fee']/100)) / $daysOfYear;
			}
			else {
				$REPAY[$i]['LIST'][$j]['day_invest_usefee'] = ($REPAY[$i]['LIST'][$j]['remit_fee']=='1') ? 0 : ($INVEST[$j]['amount'] * ($PRDT['invest_usefee']/100)) / $daysOfYear;		// 플랫폼 수수료 면제 대상자처리 -> 일별 플랫폼 수수료를 0으로 설정
			}

			$REPAY[$i]['LIST'][$j]['invest_usefee'] = floor($REPAY[$i]['LIST'][$j]['day_invest_usefee'] * $REPAY[$i]['day_count']);	// 소수점이하 절사

			if($INVEST[$j]['invest_usefee_type']=='B') {
				$sum_invest_usefee[$j] += $REPAY[$i]['LIST'][$j]['invest_usefee'];		// 만기일시징수방식일때의 투자자플랫폼이용료 계산
				$REPAY[$i]['LIST'][$j]['invest_usefee'] = ($turn==$INI['repay_count']) ? 0 : $sum_invest_usefee[$j];
			}


			// 세액 계산
			$interest_tax = floor( ($REPAY[$i]['LIST'][$j]['invest_interest'] * $interest_tax_ratio) / 10 ) * 10;					// 당월 이자소득세 = 이자수익 * 소득세율 (원단위이하 절사)
			$local_tax    = floor( ($interest_tax * $local_tax_ratio) / 10 ) * 10;																				// 당월 지방소득세 = 이자소득세의 10% (원단위이하 절사)

			// 원천징수 제외
			if($REPAY[$i]['LIST'][$j]['is_creditor']=='Y') {
				// 대부업회원
				$interest_tax = $local_tax = 0;
			}
			else {
				// 법인 이자소득세 1000원 미만인 경우 (소액부징수)
				if($INVEST[$j]['member_type']=='2') {
					if($interest_tax < 1000 && $REPAY[$i]['repay_date'] > '2021-11-19') {
						$interest_tax = $local_tax = 0;
					}
				}
			}

			$REPAY[$i]['LIST'][$j]['TAX']['sum'] = $interest_tax + $local_tax;		// 당월 세금 합계
			$REPAY[$i]['LIST'][$j]['withhold']   = $REPAY[$i]['LIST'][$j]['TAX']['sum'] + $REPAY[$i]['LIST'][$j]['invest_usefee'];								// 당월 징수할 금액 (세금 + 플랫폼수수료)
			$REPAY[$i]['LIST'][$j]['interest']   = $REPAY[$i]['LIST'][$j]['invest_interest'] - $REPAY[$i]['LIST'][$j]['withhold'];								// 실 수령액


			$REPAY[$i]['LIST'][$j]['repay_principal'] = ($turn < $INI['repay_count']) ? 0 : $INVEST[$j]['amount'];			// 상환원금

			// 상환원금
			if($product_idx=='171') {
				$REPAY[$i]['LIST'][$j]['repay_principal'] = 0;			// 171번 상품은 연체정산시 원금을 처리하도록 한다.
			}




			/////////////////////////
			//차수별(당월) 합산
			/////////////////////////
			$REPAY[$i]['SUM']['caption'] = "차수별(당월) 합계";
			$REPAY[$i]['SUM']['amount']              += $REPAY[$i]['LIST'][$j]['amount'];
			$REPAY[$i]['SUM']['invest_interest']     += $REPAY[$i]['LIST'][$j]['invest_interest'];
			$REPAY[$i]['SUM']['invest_usefee']       += $REPAY[$i]['LIST'][$j]['invest_usefee'];
			$REPAY[$i]['SUM']['TAX']['interest_tax'] += $REPAY[$i]['LIST'][$j]['TAX']['interest_tax'];
			$REPAY[$i]['SUM']['TAX']['local_tax']    += $REPAY[$i]['LIST'][$j]['TAX']['local_tax'];
			$REPAY[$i]['SUM']['TAX']['sum']          += $REPAY[$i]['LIST'][$j]['TAX']['sum'];
			$REPAY[$i]['SUM']['withhold']            += $REPAY[$i]['LIST'][$j]['withhold'];
			$REPAY[$i]['SUM']['interest']            += $REPAY[$i]['LIST'][$j]['interest'];
			$REPAY[$i]['SUM']['repay_principal']     += $REPAY[$i]['LIST'][$j]['repay_principal'];


			/////////////////////////
			// 회원별 누적
			/////////////////////////
			$member_id = $REPAY[$i]['LIST'][$j]['mb_id'];

			$TMP[$member_id]['invest_interest']     += $REPAY[$i]['LIST'][$j]['invest_interest'];
			$TMP[$member_id]['invest_usefee']       += $REPAY[$i]['LIST'][$j]['invest_usefee'];
			$TMP[$member_id]['TAX']['interest_tax'] += $REPAY[$i]['LIST'][$j]['TAX']['interest_tax'];
			$TMP[$member_id]['TAX']['local_tax']    += $REPAY[$i]['LIST'][$j]['TAX']['local_tax'];
			$TMP[$member_id]['TAX']['sum']          += $REPAY[$i]['LIST'][$j]['TAX']['sum'];
			$TMP[$member_id]['withhold']            += $REPAY[$i]['LIST'][$j]['withhold'];
			$TMP[$member_id]['interest']						+= $REPAY[$i]['LIST'][$j]['interest'];
			$TMP[$member_id]['repay_principal']     += $REPAY[$i]['LIST'][$j]['repay_principal'];

			$REPAY[$i]['MEMBER_NUJUK']['caption'] = "회차별 회원별 누적분";
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['invest_interest']     = $TMP[$member_id]['invest_interest'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['invest_usefee']       = $TMP[$member_id]['invest_usefee'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['TAX']['interest_tax'] = $TMP[$member_id]['TAX']['interest_tax'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['TAX']['local_tax']    = $TMP[$member_id]['TAX']['local_tax'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['TAX']['sum']          = $TMP[$member_id]['TAX']['sum'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['withhold']            = $TMP[$member_id]['withhold'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['interest']            = $TMP[$member_id]['interest'];
			$REPAY[$i]['MEMBER_NUJUK'][$member_id]['repay_principal']     = $TMP[$member_id]['repay_principal'];

			////////////////////////////////
			// 당월 회원 누적분 합계
			////////////////////////////////
			$REPAY[$i]['NUJUK_SUM']['caption'] = "당월 회원 누적분 합계";
			$REPAY[$i]['NUJUK_SUM']['invest_interest']    += $TMP[$member_id]['invest_interest'];
			$REPAY[$i]['NUJUK_SUM']['invest_usefee']      += $TMP[$member_id]['invest_usefee'];
			$REPAY[$i]['NUJUK_SUM']['TAX']['interest_tax']+= $TMP[$member_id]['TAX']['interest_tax'];
			$REPAY[$i]['NUJUK_SUM']['TAX']['local_tax']   += $TMP[$member_id]['TAX']['local_tax'];
			$REPAY[$i]['NUJUK_SUM']['TAX']['sum']         += $TMP[$member_id]['TAX']['sum'];
			$REPAY[$i]['NUJUK_SUM']['withhold']           += $TMP[$member_id]['withhold'];
			$REPAY[$i]['NUJUK_SUM']['interest']           += $TMP[$member_id]['interest'];
			$REPAY[$i]['NUJUK_SUM']['repay_principal']    += $TMP[$member_id]['repay_principal'];

			///////////////////
			// 회원별 전체합계
			///////////////////
			$REPAY_SUM[$member_id]['invest_interest']     = $TMP[$member_id]['invest_interest'];
			$REPAY_SUM[$member_id]['invest_usefee']       = $TMP[$member_id]['invest_usefee'];
			$REPAY_SUM[$member_id]['TAX']['interest_tax'] = $TMP[$member_id]['TAX']['interest_tax'];
			$REPAY_SUM[$member_id]['TAX']['local_tax']    = $TMP[$member_id]['TAX']['local_tax'];
			$REPAY_SUM[$member_id]['TAX']['sum']          = $TMP[$member_id]['TAX']['sum'];
			$REPAY_SUM[$member_id]['withhold']            = $TMP[$member_id]['withhold'];
			$REPAY_SUM[$member_id]['interest']            = $TMP[$member_id]['interest'];
			$REPAY_SUM[$member_id]['repay_principal']     = $TMP[$member_id]['repay_principal'];

			////////////////////////////
			// 지급내역 (핀크자료용)
			////////////////////////////
			if($REPAY[$i]['LIST'][$j]['paied']=="Y") {
				$PAIED_SUM['invest_interest']     += $GIVE['interest'] + $GIVE['interest_tax'] + $GIVE['local_tax'] + $GIVE['fee'];
				$PAIED_SUM['invest_usefee']       += $GIVE['fee'];
				$PAIED_SUM['TAX']['interest_tax'] += $GIVE['interest_tax'];
				$PAIED_SUM['TAX']['local_tax']    += $GIVE['local_tax'];
				$PAIED_SUM['TAX']['sum']          += $GIVE['interest_tax'] + $GIVE['local_tax'];
				$PAIED_SUM['withhold']            += $PAIED_SUM['TAX']['sum'] + $GIVE['fee'];
				$PAIED_SUM['interest']            += $GIVE['interest'];
				$PAIED_SUM['repay_principal']     += $GIVE['principal'];
			}

		}
		// 정산차수 루프 끝

		//unset($TMP);  //배열 비움


		///////////////////
		// 전체합계
		///////////////////
		$REPAY_SUM['invest_interest']     += $REPAY[$i]['SUM']['invest_interest'];
		$REPAY_SUM['invest_usefee']       += $REPAY[$i]['SUM']['invest_usefee'];
		$REPAY_SUM['TAX']['interest_tax'] += $REPAY[$i]['SUM']['TAX']['interest_tax'];
		$REPAY_SUM['TAX']['local_tax']    += $REPAY[$i]['SUM']['TAX']['local_tax'];
		$REPAY_SUM['TAX']['sum']          += $REPAY[$i]['SUM']['TAX']['sum'];
		$REPAY_SUM['withhold']            += $REPAY[$i]['SUM']['TAX']['sum'] + $REPAY[$i]['SUM']['invest_usefee'];
		$REPAY_SUM['interest']            += $REPAY[$i]['SUM']['interest'];
		$REPAY_SUM['repay_principal']     += $REPAY[$i]['SUM']['repay_principal'];

		$SDATE_OBJ->modify('first day of next month');



		/////////////////////////////////
		// 연체내역 배열화
		/////////////////////////////////

		// 핀크자료를 만들기 위한 임시 작업 ---------------------------------
		$OVD_REPAY_SUM = array(
			'repay_principal' => 0,
			'invest_interest' => 0,
			'TAX' => array('interest_tax' => 0, 'local_tax' => 0, 'sum' => 0),
			'invest_usefee'   => 0,
			'interest'        => 0
		);

		$OVD_PAIED_SUM = array(
			'repay_principal' => 0,
			'invest_interest' => 0,
			'TAX' => array('interest_tax' => 0, 'local_tax' => 0, 'sum' => 0),
			'invest_usefee'   => 0,
			'interest'        => 0
		);
		// 핀크자료를 만들기 위한 임시 작업 ---------------------------------

		if($REPAY[$i]['SUCCESS']['overdue_start_date']>'0000-00-00') {

			$REPAY[$i]['OVERDUE_SUCCESS'] = $REPAY[$i]['SUCCESS'];

			$overdue_sdate = $REPAY[$i]['OVERDUE_SUCCESS']['overdue_start_date'];
			$overdue_edate = ($REPAY[$i]['OVERDUE_SUCCESS']['overdue_end_date']=='' || $REPAY[$i]['OVERDUE_SUCCESS']['overdue_end_date']=='0000-00-00') ? G5_TIME_YMD : $REPAY[$i]['OVERDUE_SUCCESS']['overdue_end_date'];
			//$overdue_edate = "2022-04-15";  // 특정일까지의 연체이자를 산출하려할때 임의 대입

			$OVD_SDATE_OBJ = new DateTime($overdue_sdate);
			$OVD_EDATE_OBJ = new DateTime($overdue_edate);
			$OVD_TOTAL_DATE_OBJ = date_diff($OVD_SDATE_OBJ, $OVD_EDATE_OBJ);
			//print_rr($OVD_TOTAL_DATE_OBJ,'font-size:11px');

			$REPAY[$i]['OVERDUE']['start_date'] = $overdue_sdate;
			$REPAY[$i]['OVERDUE']['end_date']   = $overdue_edate;
			$REPAY[$i]['OVERDUE']['day_count']  = $OVD_TOTAL_DATE_OBJ->days;


			for($j=0; $j<$INI['invest_count']; $j++) {

				$REPAY[$i]['OVERDUE_LIST'][$j]['day_invest_interest'] = ($INVEST[$j]['amount'] * ($PRDT['overdue_rate']/100)) / $daysOfYear;													// 일별 투자자 수익금 (연체이자율 대입)
				$REPAY[$i]['OVERDUE_LIST'][$j]['day_invest_usefee']   = ($INVEST[$j]['amount'] * ($PRDT['invest_usefee']/100)) / $daysOfYear;												// 일별 플랫폼이용료 (365일 기준)

				$REPAY[$i]['OVERDUE_LIST'][$j]['invest_idx']  = $INVEST[$j]['idx'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['mb_no']       = $INVEST[$j]['mb_no'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['mb_id']       = $INVEST[$j]['mb_id'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['mb_name']     = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_name'] : $INVEST[$j]['mb_name'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['jumin']       = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_reg_num'] : getJumin($INVEST[$j]['member_idx']);
				$REPAY[$i]['OVERDUE_LIST'][$j]['member_type'] = $INVEST[$j]['member_type'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['is_owner_operator'] = $INVEST[$j]['is_owner_operator'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['insidebank_after_trans_target'] = $INVEST[$j]['insidebank_after_trans_target'];		//신한 예치금 이전 대상자 플래그

				//--------------------------------------------------------------------------------------------
				/*
				// 이자정산지급일에 따른 세율 변환
				$interest_tax_ratio = $CONF['interest_tax_ratio'];		// 이자소득세
				$local_tax_ratio    = $CONF['local_tax_ratio'];				// 지방세: 이자소득세의 10%

				// 개인/법인 투자자별 세율 변경
				if( ($PRDT['loan_start_date'] >= $CONF['lastTaxChangeDate']) || ($REPAY[$i]['repay_schedule_date'] >= $CONF['lastTaxChangeDate']) ) {
					$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? $CONF['corp']['interest_tax_ratio'] : $CONF['indi']['interest_tax_ratio'];		// 이자소득세율
					$local_tax_ratio    = ($INVEST[$j]['member_type']=='2') ? $CONF['corp']['local_tax_ratio'] : $CONF['indi']['local_tax_ratio'];					// 지방세율
				}
				*/

				// 2021-10-21 수정
				// 법인은 무조건 27.5%, 개인은 정산일 기준 다르게 적용
				if($PRDT['loan_start_date'] >= '2021-08-27') {
					$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14;
				}
				else {
					if( $REPAY[$i]['repay_schedule_date'] < '2021-10-21' ) {
						$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.25;

						if( $product_idx == '6281' && $turn >= 3) { $interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14; }		// 0.14로 정산된것들 예외처리
						if( in_array($product_idx, array('6561','6573','6584','6596','6607')) ) { $interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14; }		// 0.14로 정산된것들 예외처리

					}
					else {
						$interest_tax_ratio = ($INVEST[$j]['member_type']=='2') ? 0.25 : 0.14;
					}
				}

				$local_tax_ratio = 0.1;		// interest_tax_ratio의 10%

				//--------------------------------------------------------------------------------------------


				///////////////////////////////////
				// 연체이자 지급기록 추출 및 지급계좌 설정
				///////////////////////////////////
				$ovd_give_sql = "
					SELECT
						idx, `date`, invest_amount, interest, principal, interest_tax, local_tax, fee, is_creditor, remit_fee, receive_method, bank_name, account_num, bank_private_name, banking_date, mgtKey
					FROM
						cf_product_give
					WHERE 1
						AND invest_idx='".$INVEST[$j]['idx']."'
						AND product_idx='".$PRDT['idx']."'
						AND turn='".$turn."'
						AND is_overdue='Y'";
				$OVD_GIVE = sql_fetch($ovd_give_sql);

				$REPAY[$i]['OVERDUE_LIST'][$j]['paied']        = ($OVD_GIVE['idx']) ? 'Y' : 'N';
				$REPAY[$i]['OVERDUE_LIST'][$j]['give_idx']     = ($OVD_GIVE['idx']) ? $OVD_GIVE['idx'] : '';
				$REPAY[$i]['OVERDUE_LIST'][$j]['paied_date']   = $OVD_GIVE['date'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['paied_amount'] = $OVD_GIVE['interest'];		// 실입금액
				$REPAY[$i]['OVERDUE_LIST'][$j]['remit_fee']    = ($OVD_GIVE['remit_fee']=='1') ? $OVD_GIVE['remit_fee'] : $INVEST[$j]['remit_fee'];
				$REPAY[$i]['OVERDUE_LIST'][$j]['mgtKey']       = $OVD_GIVE['mgtKey'];

				if($REPAY[$i]['OVERDUE_LIST'][$j]['paied']=="Y") {
					$REPAY[$i]['OVERDUE_LIST'][$j]['is_creditor']       = $OVD_GIVE['is_creditor'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['receive_method']	  = $OVD_GIVE['receive_method'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['bank']			        = $OVD_GIVE['bank_name'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['bank_code']			    = "";		// 이미 지급됬는데 별 필요없을듯
					$REPAY[$i]['OVERDUE_LIST'][$j]['account_num']       = $OVD_GIVE['account_num'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'] = $OVD_GIVE['bank_private_name'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['banking_date']      = $OVD_GIVE['banking_date'];
				}
				else {
					$REPAY[$i]['OVERDUE_LIST'][$j]['is_creditor']    = $INVEST[$j]['is_creditor'];
					$REPAY[$i]['OVERDUE_LIST'][$j]['receive_method'] = $INVEST[$j]['receive_method'];
					if($INVEST[$j]['receive_method']=='1') {
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank']              = $BANK[$INVEST[$j]['bank_code']];
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank_code']         = $INVEST[$j]['bank_code'];
						$REPAY[$i]['OVERDUE_LIST'][$j]['account_num']       = $INVEST[$j]['account_num'];
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'] = $INVEST[$j]['bank_private_name'];
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'].= ($INVEST[$j]['bank_private_name_sub']) ? "(".$INVEST[$j]['bank_private_name_sub'].")" : "";
					}
					else if($INVEST[$j]['receive_method']=='2') {
						if($ib_trust) {
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank']              = $BANK[$INVEST[$j]['va_bank_code2']];
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank_code']         = $INVEST[$j]['va_bank_code2'];
							$REPAY[$i]['OVERDUE_LIST'][$j]['account_num']       = $INVEST[$j]['virtual_account2'];
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'] = $INVEST[$j]['va_private_name2'];
						}
						else {
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank']              = $BANK[$INVEST[$j]['va_bank_code']];
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank_code']         = $INVEST[$j]['va_bank_code'];
							$REPAY[$i]['OVERDUE_LIST'][$j]['account_num']       = $INVEST[$j]['virtual_account'];
							$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'] = $INVEST[$j]['va_private_name'];
						}
					}
					else {
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank']              = "";
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank_code']         = "";
						$REPAY[$i]['OVERDUE_LIST'][$j]['account_num']       = "";
						$REPAY[$i]['OVERDUE_LIST'][$j]['bank_private_name'] = "";
					}
					$REPAY[$i]['OVERDUE_LIST'][$j]['banking_date']        = "";
				}

				$REPAY[$i]['OVERDUE_LIST'][$j]['amount']          = $INVEST[$j]['amount'];

				// 상환원금
				if($product_idx=='171') {
					$REPAY[$i]['OVERDUE_LIST'][$j]['repay_principal'] = $INVEST[$j]['amount'];			// 171번 상품은 연체정산시 원금을 처리하도록 한다.
				}

				$REPAY[$i]['OVERDUE_LIST'][$j]['invest_interest'] = floor($REPAY[$i]['OVERDUE_LIST'][$j]['day_invest_interest'] * $REPAY[$i]['OVERDUE']['day_count']);

				if($REPAY[$i]['OVERDUE_LIST'][$j]['remit_fee']=='1') $REPAY[$i]['OVERDUE_LIST'][$j]['day_invest_usefee'] = 0;			// 플랫폼 수수료 면제 대상자처리 -> 일별 플랫폼 수수료를 0으로 설정

				// 투자자플랫폼이용료(분할징수) :::: 일별플랫폼이용료 * 일자수
				$REPAY[$i]['OVERDUE_LIST'][$j]['invest_usefee'] = floor($REPAY[$i]['OVERDUE_LIST'][$j]['day_invest_usefee'] * $REPAY[$i]['OVERDUE']['day_count']);	// 소수점이하 절사



				$ovd_interest_tax = floor( ($REPAY[$i]['OVERDUE_LIST'][$j]['invest_interest'] * $interest_tax_ratio) / 10 ) * 10;					// 당월 이자소득세 = 이자수익 * 소득세율 (원단위이하 절사)
				$ovd_local_tax    = floor( ($ovd_interest_tax * $local_tax_ratio) / 10 ) * 10;																						// 당월 지방소득세 = 이자소득세의 10% (원단위이하 절사)

				// 원천징수 제외
				if($REPAY[$i]['OVERDUE_LIST'][$j]['is_creditor']=='Y') {
					// 대부업 회원
					$ovd_interest_tax = $ovd_local_tax = 0;
				}
				else {
					// 법인 이자소득세 1000원 미만인 경우 (소액부징수)
					if($INVEST[$j]['member_type']=='2') {
						if($ovd_interest_tax < 1000 && $REPAY[$i]['repay_date'] > '2021-11-19') {
							$ovd_interest_tax = $ovd_local_tax = 0;
						}
					}
				}

				$REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['interest_tax'] = $ovd_interest_tax;																																										// 당월 이자소득세 = 이자수익 * 0.25
				$REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['local_tax']    = $ovd_local_tax;																																												// 당월 지방소득세(원단위 절사)
				$REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['sum'] = $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['interest_tax'] + $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['local_tax'];		// 당월 세금 합계
				$REPAY[$i]['OVERDUE_LIST'][$j]['withhold']   = $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['sum'] + $REPAY[$i]['OVERDUE_LIST'][$j]['invest_usefee'];								// 당월 징수할 금액 (세금 + 플랫폼수수료)
				$REPAY[$i]['OVERDUE_LIST'][$j]['interest']   = $REPAY[$i]['OVERDUE_LIST'][$j]['invest_interest'] - $REPAY[$i]['OVERDUE_LIST'][$j]['withhold'];								// 실 수령액

				/////////////////////////
				// 연체액 합계 합산
				/////////////////////////
				$REPAY[$i]['OVERDUE_SUM']['caption'] = "연체액 합계";
				$REPAY[$i]['OVERDUE_SUM']['amount']              += $REPAY[$i]['OVERDUE_LIST'][$j]['amount'];
				$REPAY[$i]['OVERDUE_SUM']['invest_interest']     += $REPAY[$i]['OVERDUE_LIST'][$j]['invest_interest'];
				$REPAY[$i]['OVERDUE_SUM']['invest_usefee']       += $REPAY[$i]['OVERDUE_LIST'][$j]['invest_usefee'];
				$REPAY[$i]['OVERDUE_SUM']['TAX']['interest_tax'] += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['interest_tax'];
				$REPAY[$i]['OVERDUE_SUM']['TAX']['local_tax']    += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['local_tax'];
				$REPAY[$i]['OVERDUE_SUM']['TAX']['sum']          += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['sum'];
				$REPAY[$i]['OVERDUE_SUM']['withhold']            += $REPAY[$i]['OVERDUE_LIST'][$j]['withhold'];
				$REPAY[$i]['OVERDUE_SUM']['interest']            += $REPAY[$i]['OVERDUE_LIST'][$j]['interest'];
				$REPAY[$i]['OVERDUE_SUM']['repay_principal']     += $REPAY[$i]['OVERDUE_LIST'][$j]['repay_principal'];

				////////////////////////////
				// 지급내역 (핀크자료용)
				////////////////////////////
				if($REPAY[$i]['OVERDUE_LIST'][$j]['paied']=="Y") {
					$OVD_PAIED_SUM['invest_interest']     += $OVD_GIVE['invest_interest'];
					$OVD_PAIED_SUM['invest_usefee']       += $OVD_GIVE['fee'];
					$OVD_PAIED_SUM['TAX']['interest_tax'] += $OVD_GIVE['interest_tax'];
					$OVD_PAIED_SUM['TAX']['local_tax']    += $OVD_GIVE['local_tax'];
					$OVD_PAIED_SUM['TAX']['sum']					+= $OVD_GIVE['interest_tax'] + $OVD_GIVE['local_tax'];
					$OVD_PAIED_SUM['withhold']						+= $OVD_GIVE['interest_tax'] + $OVD_GIVE['local_tax'] + $OVD_GIVE['fee'];
					$OVD_PAIED_SUM['interest']            += $OVD_GIVE['interest'];
					$OVD_PAIED_SUM['repay_principal']     += $OVD_GIVE['principal'];
				}
				else {
					$OVD_REPAY_SUM['invest_interest']     += $REPAY[$i]['OVERDUE_LIST'][$j]['invest_interest'];
					$OVD_REPAY_SUM['invest_usefee']       += $REPAY[$i]['OVERDUE_LIST'][$j]['invest_usefee'];
					$OVD_REPAY_SUM['TAX']['interest_tax'] += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['interest_tax'];
					$OVD_REPAY_SUM['TAX']['local_tax']    += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['local_tax'];
					$OVD_REPAY_SUM['TAX']['sum']          += $REPAY[$i]['OVERDUE_LIST'][$j]['TAX']['sum'];
					$OVD_PAIED_SUM['withhold']            += $REPAY[$i]['OVERDUE_LIST'][$j]['withhold'];
					$OVD_REPAY_SUM['interest']            += $REPAY[$i]['OVERDUE_LIST'][$j]['interest'];
					$OVD_REPAY_SUM['repay_principal']     += $REPAY[$i]['OVERDUE_LIST'][$j]['repay_principal'];
				}

			}

			//print_rr($REPAY[$i]['OVERDUE'], 'font-size:11px');		//연체정보 출력

		}

	}
	// 투자건(투자자) 루프 끝

	unset($TMP);  //배열 비움


	$return_arr = array(
		'PRDT'=>$PRDT,
		'LOANER'=>$LOANER,
		'INI'=>$INI,
		'INVEST'=>$INVEST,
		'MTOTAL_INVEST_SUM'=>$MTOTAL_INVEST_SUM,
		'REPAY'=>$REPAY,
		'REPAY_SUM'=>$REPAY_SUM,
		'PAIED_SUM'=>$PAIED_SUM,
		'OVD_REPAY_SUM'=>$OVD_REPAY_SUM,
		'OVD_PAIED_SUM'=>$OVD_PAIED_SUM
	);

	return $return_arr;

}


?>
