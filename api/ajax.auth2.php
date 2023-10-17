<?
/**********************************************************************************************
	NICE평가정보 휴대폰 인증 서비스
	서버 네트웍크 및 방확벽 관련하여 아래 IP와 Port를 오픈해 주셔야 이용 가능합니다.
	IP : 121.131.196.200 / Port : 3700 ~ 3715

	step2. 입력된 인증번호의 일치 확인 - 인증 마무리
**********************************************************************************************/
$sSiteCode    = 'AB917';									// 사이트 코드 (NICE평가정보에서 발급한 사이트코드)
$sSitePw      = '8vJBrEtmUvdb';						// 사이트 패스워드 (NICE평가정보에서 발급한 사이트패스워드)

$mcheckplus_path = "/home/crowdfund/NICE/CheckPlusSafe_SOCK_PHP/64bit/MCheckPlus";

//인자값 : CNFM 사이트코드 사이트패스워드 응답SEQ 인증번호 요청SEQ(option)
$tmp = "$mcheckplus_path CNFM $sSiteCode $sSitePw $res $tfCertifyNumber $rep";
$sResultData = `$mcheckplus_path CNFM $sSiteCode $sSitePw $res $tfCertifyNumber $rep`;

//echo "결과 : $sResultData";
/*
연동 결과 코드
	0 : 정상
	-1 ~ -6 : 암/복호화 오류
	-7 ~ -8 : 통신 오류
	-9, 12 : 입력값 오류

	응답 코드(getReturnCode)
	0000 : 인증번호 확인 성공
	0001 : 인증번호 불일치
	0031 : 응답 고유번호 확인 불가
	0032 : 주민번호 불일치
	0033 : 요청 고유번호 불일치
	0034 : 기 인증 완료 건
*/

$res_tmp = explode("|", $sResultData);

/* 출력정보 예시
0000
20191127192827
20191127192745-366
MAB917201911271201009125
INyVTTfK1vsLDA598G6B2NRiusDTQfNW5awDL3vBlnOmS7VsqtQ7iQNM5mbhZ+kQcWygzhjFs0yFku7gLWgkGA==
MC0GCCqGSIb3DQIJAyEAr4lTdH7sONQYKKIft0CotF8EHO7JIuNSXe2HBd9tUIc=
*/

$strCI = "";
$strReturnCode = $res_tmp[0];
if( $strReturnCode == '0000' ) {

	$strCI = $res_tmp[4];

	sql_query("
		UPDATE
			cf_auth_nice
		SET
			auth_finish='1',
			ci='".$strCI."'
		WHERE 1
			AND reqseq='".$rep."'
			AND rescode='0000'
			AND resseq='".$res."'");


}
?>