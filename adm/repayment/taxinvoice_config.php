<?php
/**
* 팜빌 전자세금계산서 API PHP SDK Example
*
* PHP SDK 연동환경 설정방법 안내 : blog.linkhub.co.kr/584
* 업테이트 일자 : 2017-11-14
* 연동기술지원 연락처 : 1600-9854 / 070-4304-2991
* 연동기술지원 이메일 : code@linkhub.co.kr
*
* <테스트 연동개발 준비사항>
* 1) 24, 27번 라인에 선언된 링크아이디(LinkID)와 비밀키(SecretKey)를 링크허브 가입시 메일로 발급받은 인증정보를 참조하여 변경합니다.
* 2) 팝빌 개발용 사이트(test.popbill.com)에 연동회원으로 가입합니다.
* 3) 전자세금계산서 발행을 위해 공인인증서를 등록합니다.
*    - 팝빌사이트 로그인 > [전자세금계산서] > [환경설정] > [공인인증서 관리]
*    - 공인인증서 등록 팝업 URL (GetPopbillURL API)을 이용하여 등록
*/


$LinkID    = 'HELLOFUNDING';																			// 링크아이디
$SecretKey = '+1oguM5SPcGYnLW4cvDkH4VpXU0RiIWvZR0QH6mlego=';			// 비밀키
$test_mode = false;																								// 연동환경 설정값, 개발용(true), 상용(false)


///////////////////////////////////////////////////////////////////////////////
// 공급자 정보
///////////////////////////////////////////////////////////////////////////////
$INVOICER['CorpNum']     = '789-81-00529';												// 사업자 등록번호
$INVOICER['CorpName']    = '주식회사 헬로핀테크';									// 법인명
$INVOICER['CorpOwner']   = '최수석';															// 대표자명
$INVOICER['CorpAddr']    = '서울특별시 강남구 테헤란로98길 8, 5층(대치동, KT&G 대치타워)';
$INVOICER['BizClass']    = "온라인 정보제공";											// 종목
$INVOICER['BizType']     = "서비스, 도소매";											// 업태
$INVOICER['ContactName'] = "헬로펀딩";														// 담당자명
$INVOICER['Email']       = "hellofunding@naver.com";							// 담당자 이메일
$INVOICER['Tel']         = "1588-6760";														// 담당자 연락처
$INVOICER['HP']          = "010-8894-4740";		// 담당자 휴대폰 번호
$INVOICER['officer']     = "이상규";
$INVOICER['userid']		   = 'hellofunding';

// 통신방식 기본은 CURL , curl 사용에 문제가 있을경우 STREAM 사용가능.
// STREAM 사용시에는 php.ini의 allow_fopen_url = on 으로 설정해야함.
define('LINKHUB_COMM_MODE','CURL');


?>