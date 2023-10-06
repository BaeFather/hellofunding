<?php

///////////////////////////////////////////////////////////////////////////////
// 서비스 설정
///////////////////////////////////////////////////////////////////////////////
// manager.hellofunding.co.kr 에서 저장시 www1으로 1분마다 동기화 실행
// www1 -> www2 는 디렉토리 동기화 처리되어있음.
// CRONTAB :: */1 * * * * rsync -avz /home/crowdfund/public_html/config.service.php crowdfund@10.22.160.29::public_sync
///////////////////////////////////////////////////////////////////////////////

$CONF['api_server_url'] = "https://ext.hellofunding.co.kr";

$CONF['customer_mail']       = 'hellofunding@gmail.com';
$CONF['customer_local_mail'] = 'info@hellofunding.kr';
$CONF['customer_phone']      = '1588-6760';
$CONF['admin_sms_number']    = '15886760';			// 고객센터 대표번호
$CONF['judam_sms_number']    = '15885210';			// 주담대 상담번호
$CONF['represent']           = '최수석';				// 대표자명

$_admin_sms_number = $CONF['admin_sms_number'];

// 메인 출력사항 표기 제목
$PRNT_SUBJECT['average_return']    = '평균수익률(연)';
$PRNT_SUBJECT['total_invest']      = '누적대출액';
$PRNT_SUBJECT['total_repay']       = '누적상환액';
$PRNT_SUBJECT['invest_ing_amount'] = '대출잔액';


// 상품 등급을 위한 배열 설정(구)
$_evaluation_grade_array = array();
$_evaluation_grade_array[15] = 'A1';
$_evaluation_grade_array[14] = 'A2';
$_evaluation_grade_array[13] = 'A3';
$_evaluation_grade_array[12] = 'B1';
$_evaluation_grade_array[11] = 'B2';
$_evaluation_grade_array[10] = 'C3';
$_evaluation_grade_array[9] = 'C1';
$_evaluation_grade_array[8] = 'C2';
$_evaluation_grade_array[7] = 'C3';
$_evaluation_grade_array[6] = 'D1';
$_evaluation_grade_array[5] = 'D2';
$_evaluation_grade_array[4] = 'D3';
$_evaluation_grade_array[3] = 'E1';
$_evaluation_grade_array[2] = 'E2';
$_evaluation_grade_array[1] = 'E3';


// 상품 등급을 위한 배열 설정(신: 5단체계)
$_gudge_grade_array = array(
	'0' => 'D',
	'1' => 'D', '2' => 'D', '3' => 'D', '4' => 'D', '5' => 'C', '6' => 'C', '7' => 'C', '8' => 'C', '9' => 'B', '10' => 'B',
	'11' => 'B', '12' => 'B', '13' => 'A', '14' => 'A', '15' => 'A', '16' => 'A', '17' => 'S', '18' => 'S', '19' => 'S', '20' => 'S'
);

/*
// 상품 등급을 위한 배열 설정(신: 20단체계)
$_gudge_grade_array = array(
  '0' => 'E',
	'1' => 'E', '2' => 'E', '3' => 'E', '4' => 'E', '5' => 'D3', '6' => 'D2', '7' => 'D1', '8' => 'C3', '9' => 'C2', '10' => 'C1',
	'11' => 'B3', '12' => 'B2', '13' => 'B1', '14' => 'A3', '15' => 'A2', '16' => 'A1', '17' => 'S', '18' => 'S', '19' => 'S', '20' => 'S'
);
*/

$BANK = array(
	'004' => '국민은행',
	'081' => 'KEB하나은행',
	'088' => '신한은행',
	'071' => '우체국',
	'011' => '농협은행',
	'020' => '우리은행',
	'089' => '케이뱅크',
	'090' => '카카오뱅크',
	'092' => '토스뱅크',
	'007' => '수협중앙회',
	'023' => 'SC은행',
	'002' => '산업은행',
	'003' => '기업은행',
	'027' => '한국씨티은행',
	'031' => '대구은행',
	'032' => '부산은행',
	'034' => '광주은행',
	'035' => '제주은행',
	'037' => '전북은행',
	'039' => '경남은행',
	'045' => '새마을금고중앙회',
	'048' => '신협중앙회',
	'050' => '상호저축은행',
	'012' => '지역농․축협',
	'064' => '산림조합중앙회',

	'102' => '대신저축은행',
	'103' => '에스비아이저축은행',
	'104' => '에이치케이저축은행',
	'105' => '웰컴저축은행',
	'106' => '신한저축은행',

	'209' => '유안타증권',
	'218' => 'KB증권(구 현대증권)',
	'221' => '골든브릿지투자증권',
	'222' => '한양증권',
	'223' => '리딩투자증권',
	'224' => 'BNK투자증권',
	'225' => 'IBK투자증권',
	'226' => 'KB증권',
	'227' => 'KTB투자증권',
	'230' => '미래에셋증권',
	'238' => '대우증권',
	'240' => '삼성증권',
	'243' => '한국투자증권',
	'247' => 'NH투자증권',
	'261' => '교보증권',
	'262' => '하이투자증권',
	'263' => 'HMC투자증권',
	'264' => '키움증권',
	'265' => '이베스트투자증권',
	'266' => 'SK증권',
	'267' => '대신증권',
	'269' => '한화투자증권',
	'270' => '하나대투증권',
	'278' => '신한금융투자',
	'279' => '동부증권',
	'280' => '유진투자증권',
	'287' => '메리츠종합금융증권',
	'290' => '부국증권',
	'291' => '신영증권',
	'292' => '엘아이지투자증권',
	'293' => '한국증권금융',
	'294' => '펀드온라인코리아',
	'295' => '우리종합금융',

//'054' => 'HSBC은행',
//'055' => '도이치은행',
//'052' => '모건스탠리은행',
//'056' => '알비에스피엘씨은행',
//'057' => '제이피모간체이스은행',
//'058' => '미즈호은행',
//'059' => '미쓰비시도쿄UFJ은행',
//'060' => 'BOA은행',
//'061' => '비엔피파리바은행',
//'062' => '중국공상은행',
//'063' => '중국은행',
//'065' => '대화은행',
//'066' => '교통은행',

//'076' => '신용보증기금',
//'077' => '기술보증기금',
//'093' => '한국주택금융공사',
//'094' => '서울보증보험',
//'095' => '경찰청',
//'096' => '한국전자금융(주)',
//'099' => '금융결제원',
//'001' => '한국은행',
//'008' => '수출입은행',

//'296' => '삼성선물',
//'297' => '외환선물',
//'298' => '현대선물',

//'041' => '우리카드',
//'044' => '외환카드',
//'361' => 'BC카드',
//'367' => '현대카드',
//'368' => '롯데카드',
//'366' => '신한카드',
//'369' => '수협카드',
//'370' => '씨티카드',
//'371' => 'NH카드',
//'374' => '하나SK카드',
//'381' => 'KB국민카드',
//'364' => '광주카드',
//'365' => '삼성카드',
//'372' => '전북카드',
//'373' => '제주카드',

//'431' => '미래에셋생명',
//'452' => '삼성생명',
//'453' => '흥국생명'
);


// 심사자 리스트
$JUDGE = array(
	'A01' => '최수석',
	'B01' => '남기중',
	'C01' => '채영민',
	'D01' => '김인',
	'E01' => '김숙현'
);

// 가상계좌 코드 및 번호
$VBANK = array(
	'003' => 'IBK기업은행',
	'023' => 'SC제일은행',
	'031' => '대구은행'
);


$CONF['online_invest_policy_sdate'] = '2020-08-27';			// 온라인투자연계금융업 및 이용자 보호에 관한 법률

/*
개인회원 투자자유형별 금액 제한

<<< 2020년 08월 26일까지 시행되는 정책 >>>
1=>일반       : 2000만원 (부동산은 1000만원까지만, 동일차주 500만원까지)
2=>소득적격   : 4000만원 (동일차주 2000만원)
3=>전문투자자 : 무제한

<<< 2020년 08월 27일부터 시행하는 정책 >>>
1=>일반       : 1000만원 (부동산은 500만원까지만, 동일차주 500만원까지)
2=>소득적격   : 4000만원 (동일차주 2000만원)
3=>전문투자자 : 무제한 (1개 상품 당 모집금액 중 40% 까지만 투자 가능)
*/

$INDI_INVESTOR['1'] = array(
	'title'                => '일반투자자',
	'site_limit'           => 20000000,		// 전체한도
	'single_product_limit' => 5000000,		// 단일상품 투자한도
	'group_product_limit'  => 5000000,		// 동일차입자그룹상품 투자한도
	'prpt_limit'           => 10000000		// 부동산 투자한도
);
$INDI_INVESTOR['2'] = array(
	'title'                => '소득적격투자자',
	'site_limit'           => 40000000,
	'single_product_limit' => 20000000,
	'group_product_limit'  => 20000000
);
$INDI_INVESTOR['3'] = array(
	'title'                => '전문투자자',
	'site_limit'           => 999999999999,
	'single_product_limit' => 999999999999,
	'group_product_limit'  => 999999999999,
	'invest_able_perc'     => (100 * 0.01)			// 투자건당 최대투자가능금액(무제한)
);


if(date('Y-m-d') >= $CONF['online_invest_policy_sdate']) {
	$INDI_INVESTOR['1'] = array(
		'title'                => '일반투자자',
		'site_limit'           => 10000000,
		'single_product_limit' => 5000000,
		'group_product_limit'  => 5000000,
		'prpt_limit'           => 5000000
	);
	$INDI_INVESTOR['3']['invest_able_perc'] = (40 * 0.01);		// 전문투자자 투자건당 최대투자가능금액 (모집금액의 40%)
}


/////////////////////////////////////////////////////
// 온투업자 등록 이후 투자 한도
// 적용일 : 2021-09-13
/////////////////////////////////////////////////////
$INDI_INVESTOR['1'] = array(
	'title'                => '일반투자자',
	'site_limit'           => 30000000,		// 전체한도 (업계한도)
	'single_product_limit' =>  5000000,		// 단일상품 투자한도
	'group_product_limit'  =>  5000000,		// 동일차입자그룹상품 투자한도
	'prpt_limit'           => 10000000		// 부동산 투자한도
);

$INDI_INVESTOR['2'] = array(
	'title'                => '소득적격투자자',
	'site_limit'           => 100000000,	// 전체한도 (업계한도)
	'single_product_limit' =>  20000000,	// 단일상품 투자한도
	'group_product_limit'  =>  20000000,	// 동일차입자그룹상품 투자한도
	'prpt_limit'           => 100000000		// 부동산 투자한도
);

$INDI_INVESTOR['3'] = array(
	'title'                => '전문투자자',
	'site_limit'           => 999999999999,		// 전체한도 (업계한도)
	'single_product_limit' => 999999999999,		// 단일상품 투자한도
	'group_product_limit'  => 999999999999,		// 동일차입자그룹상품 투자한도
	'prpt_limit'           => 999999999999,		// 부동산 투자한도
	'invest_able_perc'     => 40 * 0.01				// 전문투자자 투자건당 최대투자가능금액 (모집금액의 40%)
);

$CORP_INVESTOR = array(
	'title'                => '법인투자자',
	'site_limit'           => 999999999999,		// 전체한도 (업계한도)
	'single_product_limit' => 999999999999,		// 단일상품 투자한도
	'group_product_limit'  => 999999999999,		// 동일차입자그룹상품 투자한도
	'prpt_limit'           => 999999999999,		// 부동산 투자한도
	'invest_able_perc'     => 40 * 0.01				// 전문투자자 투자건당 최대투자가능금액 (모집금액의 40%) <=== 법인투자자도 적용???
);


$CONF['loan_guideline_date0']  = '2017-05-28';		// 가이드라인 최초 적용일
$CONF['loan_guideline_date1']  = '2018-02-27';		// 가이드라인 2차 적용일
$CONF['old_type_end_date']     = $CONF['loan_guideline_date1'];
$CONF['old_type_end_prdt_idx'] = '135';

// 투자금 설정
$CONF['min_invest_limit'] = 10000;			// 최소투자금액단위
$CONF['max_invest_limit'] = '';


// 상환방식 정의
define('REPAY_METHOD_ALL', '1');								// 만기일시상환
define('REPAY_METHOD_PRINCIPLE', '2');					// 원리금균등상환
define('REPAY_METHOD_PRINCIPLE_EQUAL', '3');		// 원금균등상환

// 투자방식 정의
define('INVEST_TYPE_ESTATE', '1');							// 부동산 담보
define('INVEST_TYPE_STATE', '2');								// 동산담보
define('INVEST_TYPE_PERSONAL_CREDIT', '3');			// 개인신용
define('INVEST_TYPE_BUSINESS_CREDIT', '4');			// 사업자신용
define('INVEST_TYPE_PORTFOLIO', '5');						// 포토폴리오
define('INVEST_TYPE_ETC', '6');									// 기타신용

// 지역코드
$locationMapCode = array( // 세종이 없음
    '서울' => '11', //
    '경기' => '12', //
    '부산' => '13', //
    '대구' => '14', //
    '인천' => '15', //
    '대전' => '16', //
    '울산' => '17', //
    '광주' => '18', //
    '경상남도' => '19', //
    '경상북도' => '20', //
    '전라남도' => '21', //
    '전라북도' => '22', //
    '충청남도' => '23', //
    '충청북도' => '24', //
    '강원도' => '25', //
    '제주도' => '26', //
);


// 나이스평가정보 계좌 인증 모듈 서비스 계정
$CONF['niceUid'] = 'NID100158';				// 고객사에 부여한 구분 id
$CONF['svcPwd']  = 'funny123@';				// 고객사에 부여한 서비스 이용 패스워드

// 나이스평가정보 실명 확인 모듈 서비스 계정
$CONF['niceSitecode']   = 'AB917';						// NICE로부터 부여받은 사이트 코드
$CONF['niceSitepasswd'] = '8vJBrEtmUvdb';			// NICE로부터 부여받은 사이트 패스워드

// 신한은행 계좌발급용
$CONF['VACT_COMPANY_CODE']['HLP'] = '20007212';		// 예치금신탁계좌 기관코드 (헬로핀테크)
$CONF['VACT_COMPANY_CODE']['HLC'] = '20007213';		// 상환용가상계좌 기관코드 (헬로크라우드대부)

// 신한은행 점검시간
$CONF['BANK_STOP_SDATE'] = '2022-08-16 18:00:00';
$CONF['BANK_STOP_EDATE'] = '2022-08-16 18:20:00';

// 신한은행 일일점검시간
$CONF['DAY_BANK_STOP_STIME'] = '23:30:00';
$CONF['DAY_BANK_STOP_ETIME'] = '00:30:00';


// 신한 원리금 지급 시간 (은행지급시간이므로 인사이드뱅크에는 최소 30분 전까지는 전문이 발송되어야 함)
// 현행
$CONF['IB_REPAY_REQ'] = array(
	'1' => '05',
	'2' => '10',
	'3' => '17'
);

// 세틀뱅크 계좌점유인증(1원이체인증)			※ 출금이체 제한조건 : 계좌당 5회/일 (요청시 증가 가능하다고 하며, 개발단/상용단에서의 조건은 동일함)
$stbk_mode = 'REAL';	// 상용모드		(라이브러리에서 호출되므로 변수명 변경금지)

$CONF['STLBANK']['TEST'] = array(
	'mid'     => 'M2194041',
	'host'    => 'https://tbnpay.settlebank.co.kr',
	'port'    => '443',
	'authkey' => 'SETTLEBANKISGOODSETTLEBANKISGOOD',
	'hashkey' => 'ST190808090913247723'
);
$CONF['STLBANK']['REAL'] = array(
	'mid'     => 'M2194198',
	'host'    => 'https://npay.settlebank.co.kr',
	'port'    => '443',
	'authkey' => 'BMKJ4gJD3k0FWZgIOXW6mo404Pw0D5Dk',		// 개인정보 암호키 (32byte)
	'hashkey' => 'ST2109021540352804899'								// 해시생성 인증키 (20byte)
);


// [중앙기록관리] API 계정
$CONF['p2pctr']['host'] = 'https://openapi.p2pcenter.or.kr/v1.0/';
$CONF['p2pctr']['code']	= 'K210500031';				// 헬로핀테크 기관코드(상용)
// [중앙기록관리] API 점검시간
$CONF['P2PCTR_PAUSE']['STIME'] = '23:20';
$CONF['P2PCTR_PAUSE']['ETIME'] = '00:30';


// [신분증 OCR(유스비)] API 계정 (신분증 OCR 및 진위여부 확인) ★★★ 환경설정 파일 위치 : ~/useb/useb.config.php ★★★
$CONF['useb']['token_email']  = 'arpino123@hellofunding.co.kr';
$CONF['useb']['token_passwd'] = 'hellofunding';


// 신디케이션 회원 : 배열키값은 DB필드명과 매칭시켜줄 것 (와우스타는 예외임)
$CONF['SYNDICATOR'] = array(
	'finnq'       => array('name'=>'핀크', 'disabled'=>''),
	'oligo'       => array('name'=>'올리고', 'disabled'=>''),
	'r114'        => array('name'=>'부동산114', 'disabled'=>''),
	'itembay'     => array('name'=>'아이템베이', 'disabled'=>''),
	'kakaopay'    => array('name'=>'카카오페이', 'disabled'=>'1'),
	'hktvwowstar' => array('name'=>'와우스타', 'disabled'=>'1'),
	'chosun'      => array('name'=>'땅집고', 'disabled'=>'1'),
);

// 제휴사
$CONF['PARTNER'] = array(
	'A001'          => array('name'=>'트러스트부동산',  'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'A002'          => array('name'=>'인더뉴스',        'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'A003'          => array('name'=>'와우스타천억',    'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'ppomppu'       => array('name'=>'뽐뿌',            'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'TvTalk'        => array('name'=>'티비톡',          'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'cashcow'       => array('name'=>'캐시카우',        'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'toomics'       => array('name'=>'투믹스',          'recommend_able'=>'N', 'referer' => '', 'describe' => ''),
	'gmnc'          => array('name'=>'공감엠엔씨',      'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'naver'         => array('name'=>'네이버키워드',    'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'naverbrand'    => array('name'=>'브랜드검색',      'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'naverbrand_m'  => array('name'=>'브랜드검색M',     'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'google'        => array('name'=>'구글키워드',      'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'googlekeyword' => array('name'=>'구글키워드M',     'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'join_GDN'      => array('name'=>'구글DA광고',      'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'remberapp'     => array('name'=>'리멤버앱',        'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'daum'          => array('name'=>'다음키워드',      'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'daumkeyword'   => array('name'=>'다음키워드M',     'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'gfa'           => array('name'=>'네이버GFA',       'recommend_able'=>'Y', 'referer' => '', 'describe' => ''),
	'naverpay'      => array('name'=>'네이버페이',      'recommend_able'=>'N', 'referer' => 'campaign.naver.com', 'describe' => '네이버페이 쿠폰지급용'),
	'okcashbag'     => array('name'=>'오케이캐쉬백',    'recommend_able'=>'N', 'referer' => '', 'describe' => '오케이캐쉬백 쿠폰지급용'),
	'okcashbag_ev'  => array('name'=>'오케이캐쉬백EV',  'recommend_able'=>'N', 'referer' => '', 'describe' => '오케이캐쉬백 자체 이벤트 체크용'),
	'prap'          => array('name'=>'프랩',            'recommend_able'=>'Y', 'referer' => '', 'describe' => '')
);


// 고정 휴일
$CONF['STATIC_HOLYDAY'] = array(
	'2020-01-01', '2020-03-01','2020-05-01', '2020-05-05', '2020-06-06', '2020-08-15', '2020-10-03', '2020-10-09', '2020-12-25',
	'2021-01-01', '2021-03-01','2021-05-01', '2021-05-05', '2021-06-06', '2021-08-15', '2021-10-03', '2021-10-09', '2021-12-25',
	'2022-01-01', '2022-03-01','2022-05-01', '2022-05-05', '2022-06-06', '2022-08-15', '2022-10-03', '2022-10-09', '2022-12-25',
	'2023-01-01', '2023-03-01','2023-05-01', '2023-05-05', '2023-06-06', '2023-08-15', '2023-10-03', '2023-10-09', '2023-12-25',
	'2024-01-01', '2024-03-01','2024-05-01', '2024-05-05', '2024-06-06', '2024-08-15', '2024-10-03', '2024-10-09', '2024-12-25',
	'2025-01-01', '2025-03-01','2025-05-01', '2025-05-05', '2025-06-06', '2025-08-15', '2025-10-03', '2025-10-09', '2025-12-25',
	'2026-01-01', '2026-03-01','2026-05-01', '2026-05-05', '2026-06-06', '2026-08-15', '2026-10-03', '2026-10-09', '2026-12-25',
	'2027-01-01', '2027-03-01','2027-05-01', '2027-05-05', '2027-06-06', '2027-08-15', '2027-10-03', '2027-10-09', '2027-12-25',
	'2028-01-01', '2028-03-01','2028-05-01', '2028-05-05', '2028-06-06', '2028-08-15', '2028-10-03', '2028-10-09', '2028-12-25',
	'2029-01-01', '2029-03-01','2029-05-01', '2029-05-05', '2029-06-06', '2029-08-15', '2029-10-03', '2029-10-09', '2029-12-25',
	'2030-01-01', '2030-03-01','2030-05-01', '2030-05-05', '2030-06-06', '2030-08-15', '2030-10-03', '2030-10-09', '2030-12-25',
);

// 유동 휴일 (음력명절/석가탄신일/대체휴일/임시휴무일등)
$CONF['DYNAMIC_HOLYDAY'] = array(
	'2018-02-15','2018-02-16','2018-02-17','2018-05-07','2018-05-22','2018-09-23','2018-09-24','2018-09-25','2018-09-26',
	'2019-02-04','2019-02-05','2019-02-06','2019-05-06','2019-09-12','2019-09-13','2019-09-14',
	'2020-01-24','2020-01-27','2020-04-15','2020-04-30','2020-09-30','2020-10-01','2020-10-02',
	'2021-02-11','2021-02-12','2021-05-19','2021-09-20','2021-09-21','2021-09-22','2021-10-04','2021-10-11',
	'2022-01-31','2022-02-01','2022-02-02','2022-03-09','2022-06-01','2022-09-09','2022-09-10','2022-09-11','2022-09-12','2022-10-10',		// 2022-06-01: 지방선거일(임시공휴일)
	'2023-01-21','2023-01-22','2023-01-23','2023-01-24','2023-09-28','2023-09-29','2023-09-30',
	'2024-02-09','2024-02-10','2024-02-11','2024-05-15','2024-09-16','2024-09-17','2024-09-18',
	'2025-01-28','2025-01-29','2025-01-30','2025-10-06','2025-10-07','2025-10-09',
	'2026-02-16','2026-02-17','2026-02-18','2026-09-24','2026-09-25','2026-09-26',
	'2027-02-08','2027-05-13','2027-09-14','2027-09-15','2027-09-16',
	'2028-02-26','2028-02-27','2028-02-28','2028-05-02','2028-10-02','2028-10-03','2028-10-04',
	'2029-02-12','2029-02-13','2029-02-14','2029-09-21',
	'2030-02-04','2030-05-09','2030-09-11','2030-09-12','2030-09-13',
);

// 윤년( 366일/1년 계산)
$CONF['LEAP_YEAR'] = array(
	'2020','2024','2028','2032','2036',
	'2040','2044','2048','2052','2056',
	'2060','2064','2068','2072','2076',
	'2080','2084','2088','2092','2096',
	'2104','2108','2112','2116','2120',
	'2124','2128','2132','2136','2140',
	'2144','2148','2152','2156','2160'
);


///////////////////////////////////////////////////////////
// 세율 설정 - 본 설정은 특성상 정산라이브러리에 반영 불가
//             정산라이브러리내부에서 개별로 설정하여 사용해야 함.
///////////////////////////////////////////////////////////
// 2021-08-21 온투법승인일
// 2021-10-21 헬로핀테크 헬로크라우드대부 합병일
// 비고: 2021-10-21일 헬로핀테크,헬로크라우드대부 합병일 부터 개인의 원천징수율도 15.4% 되도록 변경.
//       온투법승인일로부터 합병일 사이에 개인원천징수율 15.4%로 나간 상품도 있음.
//       ==> 6281번상품-1,2회차 / 6561,6573,6584,6596,6607번 상품
///////////////////////////////////////////////////////////
// 세율 설정 : 2021-05-17
$CONF['interest_tax_ratio'] = 0.25;		// 이자소득세 : 25%
$CONF['local_tax_ratio']    = 0.1;		// 지방세: 이자소득세의 10% => 합계 27.5%

// 세율 설정 : 2021-08-27일 (27일 포함) 이후 (개인,법인간 세율 분리)
$CONF['lastTaxChangeDate'] = '2021-08-27';
$CONF['corp'] = array(
	'interest_tax_ratio' => 0.25,		// 이자소득세 : 25%
	'local_tax_ratio'    => 0.1			// 지방세: 이자소득세의 10% => 합계 27.5%
);
$CONF['indi'] = array(
	'interest_tax_ratio' => 0.14,		// 이자소득세 : 14%
	'local_tax_ratio'    => 0.1			// 지방세: 이자소득세의 10% => 합계 15.4%
);


// 마케팅유입자 미성년자 차단 설정시 제한 나이 (설정나이 미만인 경우 차단)
$CONF['from_allow_age'] = 19;

// 차단 아이디 설정 (로그인은 되나 엘럿창 출력후 바로 로그아웃 처리됨)
$CONF['BLOCKOUT_ID'] = array('louss9971', 'yantai111');

$CONF['deposit_request_limit_day'] = 1; // 최종입금시점에서 출금가능한 시간까지의 텀 (0 이면 제한없음) 2018-10-24 적용
//$CONF['deposit_request_limit_day'] = 7; // 최종입금시점에서 출금가능한 시간까지의 텀 (0 이면 제한없음) 2018-10-23 적용

// 예치금 출금제한 해제처리 회원
$WITHDRAWAL_BYPASS_USER = array(
	//'galddae','noongx2','jsk5035','fintech01','fintech02','fintech03','fintech04','fintech05','fintech07','cjh4637','symba2','chic8079'
);


// 서버비상상황 일때 문자알림 수신번호 설정
$CONF['event_receive_phone'] = array(
	'01064063972',
	'01086246176',
	'01088944740',
	'01090838172',
	'01050297528',
  '01044265733'
);

// 개발자 회원
$CONF['DEVELOPER'] = array(
	'sori9th',		// 배재수
	'romrom',			// 전승찬
);

// 내부 운영가능 권한회원 (정산페이지 기능 운용가능)
$CONF['OPERATOR'] = array(
	'admin_sori9th',
	'admin_hellosiesta',	// 이상규
	'admin_sundol4',			// 이철규
//'admin_foolish34',		// 정현빈
	'admin_hokudo',				// 조윤주
	'admin_eksql71',			// 김단비
	'keejoy1',						// 이기륜
	'admin_hello9414',		// 김정은
	'admin_tnqls3605',		// 김수빈
	'admin_jihye6898',		// 이지혜
	'admin_gammee62',			// 김인
	'admin_rhksdn2207',		// 김관우
);

// 내부 테스트 회원
$CONF['GOODS_OFFICER'] = array(
	'hellofunding',			// 헬로펀딩 투심위
	'outbound',					// 외부관리자
	'hellosiesta',			// 이상규
	'sundol4',					// 이철규
	'andcl76',					// 최선희
//'foolish34',				// 정현빈
	'eksql71',					// 김단비
	'sori9th',					// 배재수
	'romrom',						// 전승찬
	'keejoy',						// 이기륜
	'hello9414',				// 김정은
	'jihye6898',				// 이지혜
	'tnqls3605',				// 김수빈
	'yr022801',					// 고상희 차장
);

// 사용자 시뮬레이션 가능 회원
$CONF['SECRET_LOGIN_USER'] = array(
	'admin_hellosiesta',
	'admin_sori9th',
	'admin_romrom',
	'admin_supermario',
	'admin_sundol4',
//'admin_foolish34'
);

// 회원가입테스트 (개인 중복가입 허용 휴대번호)
$CONF['JOIN_TEST_HP'] = array(
	'01056179090',	// 류재영
//'01032809295',	// 최선희
//'01067241409',	// 이철규
//'01043380580',	// 정현빈
//'01064063972',	// 배재수
);


// 상환계좌등록용 투자마무리 회원ID
$CONF['INVEST_FINISHER']  = 'hellosiesta';		// 일반상품용 : 이상규
$CONF['INVEST_FINISHER2'] = 'hellofintech';		// 기타비용(수수료) 처리상품 전용용 : 헬로핀테크 법인계정


$CONF['LoadKeyPwd'] = 'hellofintech';

// 아이디/비번 입력형식 정의
$ID_LIMIT = array(
	'easy'=>array('min_length'=>6, 'max_length'=>15, 'str_type'=>'', 'describe'=>'영문 또는 영문/숫자 조합, 6-15자리 등록 가능합니다.'),
	'hard'=>array('min_length'=>6, 'max_length'=>15, 'str_type'=>'alpha_num', 'describe'=>'영문 또는 영문/숫자 조합, 6~15자리 등록 가능합니다.')
);
$PW_LIMIT = array(
	'easy'=>array('min_length'=>4, 'max_length'=>15, 'str_type'=>'alpha_num', 'describe'=>'영문/숫자 조합, 4-15자리 등록 가능합니다.'),
	'hard'=>array('min_length'=>8, 'max_length'=>15, 'str_type'=>'alpha_num_special', 'describe'=>'영문/숫자/특수문자 조합, 8-15자리 등록 가능합니다.')
);
$idpw_type = 'hard';		// 현재사용할 아이디/비번 조합 난이도

// 올리고 레포팅 주소 (상용)
$CONF['oligo_report_url'] = 'https://m.mycereal.co.kr:8443';


/*
3023                           ::: 정상이자 지급:2020-08-05 / 원금,연체이자 지급:2020-08-10
3187,3194,3201                 ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-10
3215,3223,3224                 ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-15
3315,3324,3334,3341,3359,3382  ::: 정상이자 지급:2020-10-05
3515,3538,3575,3638'           ::: 정상이자 지급:2020-11-05
*/
$CONF['OVDPRDT'] = array(
	'3023','3187','3194','3201','3215','3223','3224','3315','3334','3324','3341','3359','3382','3391',
	'3515','3538','3575','3638'
);


// 모바일앱 구분
if( @$_SERVER['HTTP_X_REQUESTED_WITH']=='kr.webadsky.hellofunding' || @base64_decode($_GET[md5('token')]) || @base64_decode($_GET[md5('ver')]) ) {
	$CONF['flatform'] = 'app';
	if( @preg_match("/android/i", $_SERVER['HTTP_USER_AGENT']) ) {
		$CONF['app_os']         = 'android';
		$CONF['app_latest_ver'] = '1.2';
	}
	else {
		$CONF['app_os']         = 'apple';
		$CONF['app_latest_ver'] = '1.0.2';
	}
}

if( @preg_match("/chrome|android/i", $_SERVER['HTTP_USER_AGENT']) ) {
	$CONF['app_install_url'] = 'https://play.google.com/store/apps/details?id=kr.webadsky.hellofunding';
//$CONF['app_install_url'] = 'market://details?id=kr.webadsky.hellofunding';
//$CONF['app_install_url'] = 'intent://scan/#Intent;scheme=zxing;package=kr.webadsky.hellofunding.client.android;end';
}
else if( @preg_match("/(iphone|ipad)/i", $_SERVER['HTTP_USER_AGENT']) ) {
	$CONF['app_install_url'] = 'itms-apps://itunes.apple.com/kr/app/apple-store/id1447067245';
	//https://itunes.apple.com/app/id1447067245
}



// 관리자 페이지 외부접속 허용 스위치 (false -> 외부에서 관리자접속시 관리자별 allow_location 에 따라 문자인증 후 접속가능 / true : 외부에서 관리자 바로 접속가능)
$CONF['bypass_admin_outer_connect'] = false;
//if($_REQUEST['REMOTE_ADDR']=='115.21.113.102') $CONF['bypass_admin_outer_connect'] = false;


$kyc_test_member = array('sori9th','sori9th2','kakyo0812','hellosiesta','romrom','sundol4','foolish34','eksql71','ysm1351');


$CONF['loading_time_check'] = 0;		//페이지 로딩타임체커 0: OFF, 1: ON

// 중요: 유스비 이용불가 공지 발생시 대처 : member_new/kyc_alim_step0.php 에 스크립트 및 시간설정 !!!!

?>