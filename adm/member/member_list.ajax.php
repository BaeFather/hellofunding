<?

// member_list.php 상단 카운트 요약 데이터

include_once($_SERVER['DOCUMENT_ROOT'] . "/common.cli.php");


while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }

$limit20ds = date('Y', strtotime('-30 years'));
$limit20de = date('Y', strtotime('-21 years'));
$limit30ds = date('Y', strtotime('-40 years'));
$limit30de = date('Y', strtotime('-31 years'));
$limit40ds = date('Y', strtotime('-50 years'));
$limit40de = date('Y', strtotime('-41 years'));
$limit50ds = date('Y', strtotime('-60 years'));
$limit50de = date('Y', strtotime('-51 years'));
$limit60ds = date('Y', strtotime('-70 years'));
$limit60de = date('Y', strtotime('-61 years'));

$mem_sql = "
	SELECT
		COUNT(mb_no) AS cnt
	FROM
		g5_member
	WHERE (1)
		AND mb_level BETWEEN '1' AND '8'";

///////////////////
// 투자회원
///////////////////
$CNT['investor']['indi']    = sql_fetch($mem_sql . " AND member_group='F' AND member_type='1'")['cnt'];		// 투자-개인회원
$CNT['investor']['company'] = sql_fetch($mem_sql . " AND member_group='F' AND member_type='2'")['cnt'];		// 투자-법인회원
$CNT['investor']['total']   = array_sum($CNT['investor']);

///////////////////
// 대출회원
///////////////////
$CNT['loaner']['indi']    = sql_fetch($mem_sql . " AND member_group='L' AND member_type='1'")['cnt'];		// 대출-개인회원
$CNT['loaner']['company'] = sql_fetch($mem_sql . " AND member_group='L' AND member_type='2'")['cnt'];		// 대출-법인회원
$CNT['loaner']['total']   = array_sum($CNT['loaner']);

$CNT['member']['total'] = $CNT['investor']['total'] + $CNT['loaner']['total'];		// 전체회원합계


////////////////////
// 기타정보 카운트
////////////////////
$CNT['judge']['wait']   = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE (1) AND member_group='F' AND mb_level='0'")['cnt'];			// 승인대기 회원
$CNT['judge']['reject'] = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE (1) AND member_group='F' AND mb_level='100'")['cnt'];		// 승인거절 회원

$CNT['is_rest'] = sql_fetch($mem_sql . " AND member_group='F' AND is_rest='Y'")['cnt'];		// 휴면계정(최종로그인이 1년 이상인 회원)

$CNT['message_allow']['email']      = sql_fetch($mem_sql . " AND member_group='F' AND mb_level BETWEEN '1' AND '5' AND is_rest='N' AND mb_mailling=1")['cnt'];		// 메일수신
$CNT['message_allow']['rest_email'] = sql_fetch($mem_sql . " AND member_group='F' AND mb_level BETWEEN '1' AND '5' AND is_rest='Y' AND mb_mailling=1")['cnt'];		// 메일수신(휴면계정)
$CNT['message_allow']['sms']        = sql_fetch($mem_sql . " AND member_group='F' AND mb_level BETWEEN '1' AND '5' AND is_rest='N' AND mb_sms=1")['cnt'];					// SMS수신
$CNT['message_allow']['rest_sms']   = sql_fetch($mem_sql . " AND member_group='F' AND mb_level BETWEEN '1' AND '5' AND is_rest='Y' AND mb_sms=1")['cnt'];					// SMS수신(휴면계정)

/////////////////////////////
// 업무 제휴사 등록회원 (순수 제휴사 유입자)
/////////////////////////////
$CNT['syndi']['wowstar']  = sql_fetch($mem_sql . " AND member_group='F' AND wowstar_userid!='' AND mb_datetime = wowstar_rdate")['cnt'];			// 한경TV
$CNT['syndi']['finnq']    = sql_fetch($mem_sql . " AND member_group='F' AND finnq_userid!='' AND mb_datetime = finnq_rdate")['cnt'];					// 핀크
$CNT['syndi']['chosun']   = sql_fetch($mem_sql . " AND member_group='F' AND chosun_userid!='' AND mb_datetime = chosun_rdate")['cnt'];				// 조선일보
$CNT['syndi']['r114']     = sql_fetch($mem_sql . " AND member_group='F' AND r114_userid!='' AND mb_datetime = r114_rdate")['cnt'];						// 부동산114
$CNT['syndi']['oligo']    = sql_fetch($mem_sql . " AND member_group='F' AND oligo_userid!='' AND mb_datetime = oligo_rdate")['cnt'];					// 올리고
$CNT['syndi']['itembay']  = sql_fetch($mem_sql . " AND member_group='F' AND itembay_userid!='' AND mb_datetime = itembay_rdate")['cnt'];			// 아이템베이
$CNT['syndi']['kakaopay'] = sql_fetch($mem_sql . " AND member_group='F' AND kakaopay_userid!='' AND mb_datetime = kakaopay_rdate")['cnt'];		// 카카오페이


/////////////////////////////
// 마케팅 제휴사 등록회원
/////////////////////////////
$CNT['partner']['tvtalk']   = sql_fetch($mem_sql . " AND member_group='F' AND pid='TvTalk'")['cnt'];			// 티비톡
$CNT['partner']['cashcow']  = sql_fetch($mem_sql . " AND member_group='F' AND pid='cashcow'")['cnt'];			// 캐시카우 제휴 이벤트 회원
$CNT['partner']['toomics']  = sql_fetch($mem_sql . " AND member_group='F' AND pid='toomics'")['cnt'];			// 투믹스 제휴 이벤트 회원
$CNT['partner']['gmnc']     = sql_fetch($mem_sql . " AND member_group='F' AND pid='gmnc'")['cnt'];				// 공감엠엔씨 광고 가입 회원
$CNT['partner']['naverpay'] = sql_fetch($mem_sql . " AND member_group='F' AND pid='naverpay'")['cnt'];		// 네이버페이
$CNT['partner']['N_gfa']    = sql_fetch($mem_sql . " AND member_group='F' AND pid='N_gfa'")['cnt'];				// 네이버GFA
$CNT['partner']['okcashbag']= sql_fetch($mem_sql . " AND member_group='F' AND pid='okcashbag'")['cnt'];		// 오케이캐쉬백


/////////////////////////////
// 외부 행사 유치 회원
/////////////////////////////
$CNT['expo']['donga']            = sql_fetch($mem_sql . " AND member_group='F' AND rec_mb_id='donga_expo'")['cnt'];					// 동아재테크핀테크쇼 회원
$CNT['expo']['seoul_money_show'] = sql_fetch($mem_sql . " AND member_group='F' AND rec_mb_id='seoul_money_show'")['cnt'];		// 서울머니쇼 회원

/////////////////////////////
// 내부 이벤트 유치 회원
/////////////////////////////
$CNT['event']['100B']  = sql_fetch($mem_sql . " AND member_group='F' AND event_id='100B'")['cnt'];					// 천억돌파 이벤트 회원
$CNT['event']['100B2'] = sql_fetch($mem_sql . " AND member_group='F' AND event_id='100BEVENT2'")['cnt'];		// 럭키박스 회원

/////////////////////////////
// 연령대별 회원 (만 나이)
/////////////////////////////
$sql10d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) > '$limit20de'";
$sql20d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) BETWEEN '$limit20ds' AND '$limit20de'";
$sql30d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) BETWEEN '$limit30ds' AND '$limit30de'";
$sql40d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) BETWEEN '$limit40ds' AND '$limit40de'";
$sql50d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) BETWEEN '$limit50ds' AND '$limit50de'";
$sql60d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) BETWEEN '$limit60ds' AND '$limit60de'";
$sql70d = $mem_sql . " AND member_group='F' AND member_type='1' AND LEFT(mb_birth,4) < '$limit60ds'";


$CNT['age']['10d'] = sql_fetch($sql10d)['cnt'];				// 10대 이하
$CNT['age']['20d'] = sql_fetch($sql20d)['cnt'];				// 20대
$CNT['age']['30d'] = sql_fetch($sql30d)['cnt'];				// 30대
$CNT['age']['40d'] = sql_fetch($sql40d)['cnt'];				// 40대
$CNT['age']['50d'] = sql_fetch($sql50d)['cnt'];				// 50대
$CNT['age']['60d'] = sql_fetch($sql60d)['cnt'];				// 60대
$CNT['age']['70d'] = sql_fetch($sql70d)['cnt'];				// 70대 이상

/////////////////////////////
// KYC 1개월내 KYC 등록자
/////////////////////////////
$CNT['KYC']['wait']    = sql_fetch($mem_sql . " AND member_group='F' AND member_type='1' AND kyc_reg_dd >= '".date("Y-m-d", strtotime("first day of -1month"))."' AND kyc_allow_yn='W' AND mb_level BETWEEN '1' AND '5'")['cnt'];		// 대기
$CNT['KYC']['ing']     = sql_fetch($mem_sql . " AND member_group='F' AND member_type='1' AND kyc_reg_dd >= '".date("Y-m-d", strtotime("first day of -1month"))."' AND kyc_allow_yn='I' AND mb_level BETWEEN '1' AND '5'")['cnt'];		// 심사중
$CNT['KYC']['allow']   = sql_fetch($mem_sql . " AND member_group='F' AND member_type='1' AND kyc_reg_dd >= '".date("Y-m-d", strtotime("first day of -1month"))."' AND kyc_allow_yn='Y' AND mb_level BETWEEN '1' AND '5'")['cnt'];		// 승인
$CNT['KYC']['return']  = sql_fetch($mem_sql . " AND member_group='F' AND member_type='1' AND kyc_reg_dd >= '".date("Y-m-d", strtotime("first day of -1month"))."' AND kyc_allow_yn='N' AND mb_level BETWEEN '1' AND '5'")['cnt'];		// 반려




$RETURN_ARR['cnt1_1'] = number_format($CNT['investor']['indi']);
$RETURN_ARR['cnt1_2'] = number_format($CNT['investor']['company']);
$RETURN_ARR['cnt1_3'] = number_format($CNT['investor']['total']);
$RETURN_ARR['cnt1_4'] = number_format($CNT['judge']['wait']);
$RETURN_ARR['cnt1_5'] = number_format($CNT['judge']['reject']);
$RETURN_ARR['cnt1_6'] = number_format($CNT['is_rest']);

$RETURN_ARR['cnt2_1'] = number_format($CNT['loaner']['indi']);
$RETURN_ARR['cnt2_2'] = number_format($CNT['loaner']['company']);
$RETURN_ARR['cnt2_3'] = number_format($CNT['loaner']['total']);

$RETURN_ARR['cnt3_1'] = number_format($CNT['age']['10d']);
$RETURN_ARR['cnt3_2'] = number_format($CNT['age']['20d']);
$RETURN_ARR['cnt3_3'] = number_format($CNT['age']['30d']);
$RETURN_ARR['cnt3_4'] = number_format($CNT['age']['40d']);
$RETURN_ARR['cnt3_5'] = number_format($CNT['age']['50d']);
$RETURN_ARR['cnt3_6'] = number_format($CNT['age']['60d']);
$RETURN_ARR['cnt3_7'] = number_format($CNT['age']['70d']);

$RETURN_ARR['cnt4_1'] = number_format($CNT['message_allow']['sms']);
$RETURN_ARR['cnt4_2'] = number_format($CNT['message_allow']['email']);

$RETURN_ARR['cnt5_1'] = number_format($CNT['syndi']['wowstar']);
$RETURN_ARR['cnt5_2'] = number_format($CNT['syndi']['finnq']);
$RETURN_ARR['cnt5_3'] = number_format($CNT['syndi']['chosun']);
$RETURN_ARR['cnt5_4'] = number_format($CNT['syndi']['r114']);
$RETURN_ARR['cnt5_5'] = number_format($CNT['syndi']['itembay']);
$RETURN_ARR['cnt5_6'] = number_format($CNT['syndi']['oligo']);
$RETURN_ARR['cnt5_7'] = number_format($CNT['syndi']['kakaopay']);

$RETURN_ARR['cnt6_1'] = number_format($CNT['partner']['tvtalk']);
$RETURN_ARR['cnt6_2'] = number_format($CNT['partner']['cashcow']);
$RETURN_ARR['cnt6_3'] = number_format($CNT['partner']['toomics']);
$RETURN_ARR['cnt6_4'] = number_format($CNT['partner']['gmnc']);
$RETURN_ARR['cnt6_5'] = number_format($CNT['partner']['naverpay']);
$RETURN_ARR['cnt6_6'] = number_format($CNT['partner']['N_gfa']);
$RETURN_ARR['cnt6_7'] = number_format($CNT['partner']['okcashbag']);

$RETURN_ARR['cnt7_1'] = number_format($CNT['expo']['seoul_money_show']);
$RETURN_ARR['cnt7_2'] = number_format($CNT['expo']['donga']);

$RETURN_ARR['cnt8_1'] = number_format($CNT['event']['100B']);
$RETURN_ARR['cnt8_2'] = number_format($CNT['event']['100B2']);

$RETURN_ARR['cnt9_1'] = number_format($CNT['KYC']['wait']);
$RETURN_ARR['cnt9_2'] = number_format($CNT['KYC']['ing']);
$RETURN_ARR['cnt9_3'] = number_format($CNT['KYC']['allow']);
$RETURN_ARR['cnt9_4'] = number_format($CNT['KYC']['return']);


echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

sql_close();
exit;

?>