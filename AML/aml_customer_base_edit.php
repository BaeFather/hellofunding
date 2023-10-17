<?
###############################################################################
## WLF 인증과정
##  1. KYC 등록완료 (심사전)
##  2. KYC정보를 이용한 WLF 데이터 추출
##  3. WLF실행
##     정상 코드인 경우
##       개인 : 신분증 심사 및 1원인증 통과자 -> KYC 자동승인
##              신분증 및 환급계좌정보를 첨부파일등으로 업로드 -> 심사중 -> 관리자가 직접 승인
##       법인 : KYC 심사중 -> 관리자가 직접 승인
##			비정상 코드인 경우
##        aml_wlf_log 테이블 생성
##				g5_member 에 필드 추가 (wlf_st : 0 실패,1 성공, wlf_log_idx : wlf 로그번호 등록)
## [결과코드]
##	200: Watch List 대상입니다. 준법감시팀에 결재요청/승인 하시기 바랍니다.
##	400: 준법감시팀에서 거래거절로 결재처리된 고객입니다.
##	900: Watch List 대상이 아닙니다.
##	※ 폼 참조 : http://10.22.162.37:8080/view/AML/common/include/HELLOPP/interface/wlf_test.jsp
###############################################################################

$path = '/home/crowdfund/public_html';
include_once($path . '/AML/_common.php');

$sdt = get_microtime();


$mb_no = '79';			// akorea:228, hellofintech:50524

$CURL_RES = WLFSend($mb_no, 'WLF 전송테스트');

echo "결과 : "; print_rr($CURL_RES);


echo sprintf("%.2f", (get_microtime()-$sdt));


?>