<?
///////////////////////////////////////////////////////////////////////////////
// 면세점 월별 대출내역 (헬로페이 사업팀 요청)
///////////////////////////////////////////////////////////////////////////////

set_time_limit(0);

include_once("_common.php");
include_once(G5_LIB_PATH . "/insidebank.lib.php");

$sym = trim($_REQUEST['sym']);
if( !preg_match("/-/", $sym) ) { echo '대출기간을 똑바로 설정하시오!! (ex. 2020-01)'; exit; }
$SYM = explode('-', $sym);


$sql = "
	SELECT
		A.title, A.recruit_amount, A.loan_start_date, A.loan_end_date,
		B.DCA_IP_BANK_ID, B.DCA_IP_ACCT_NB, B.DCA_IP_AMT, B.IP_DATE
	FROM
		cf_product A
	LEFT JOIN
		IB_FB_P2P_DC_IP B  ON A.idx=B.DC_NB
	WHERE 1
		AND A.category='3' AND A.category2='2'
		AND A.state IN('1','2','5')
		AND LEFT(A.loan_start_date,7)='".$sym."'
		-- AND A.ib_loan_start='S'
	ORDER BY
		A.loan_start_date,
		A.start_num,
		A.idx";
$res = sql_query($sql);
$rows = $res->num_rows;

$ACCOUNT = array();

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);

	if(!$ACCOUNT[$LIST[$i]['DCA_IP_ACCT_NB']]) {

		// 수취인조회(4000, 예금주명 리턴)
		$ARR['REQ_NUM'] = "040";
		$ARR['BANK_CD'] = $LIST[$i]['DCA_IP_BANK_ID'];
		$ARR['ACCT_NB'] = $LIST[$i]['DCA_IP_ACCT_NB'];

		$insidebank_result = insidebank_request('256', $ARR);			//$insidebank_result = array('RCODE'=>"00000000", 'ERRMSG'=>"", 'ACCT_OWNER_NM'=>"예금주명", 'FB_SEQ'=>"HEL0150201");
		//echo json_encode($insidebank_result);

		$ACCOUNT[$LIST[$i]['DCA_IP_ACCT_NB']] = $insidebank_result['ACCT_OWNER_NM'];


	}

}
$list_count = count($LIST);

$page_title = "면세점대출지급내역(".$SYM[0]."년".$SYM[1]."월)";

if($_REQUEST['download']=='1') {

	$file_name = date('Ymd') . "_".$page_title.".xls";
	$file_name = iconv("utf-8", "euc-kr", $file_name);

	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=$file_name" );
	header( "Content-description: PHP5 Generated Data" );

}
else {
	echo "<p>".$page_title. " <button onClick='location.href=\"".$_SERVER['REQUEST_URI']."&download=1\"'>엑셀 다운로드</button></p>\n";
}

echo "<table border=1 style='font-size:9pt'>
	<tr>
		<td align=center bgcolor='#D9E1F2'>상품명</td>
		<td align=center bgcolor='#D9E1F2'>모집금액</td>
		<td align=center bgcolor='#D9E1F2'>대출일</td>
		<td align=center bgcolor='#D9E1F2'>종료일</td>
		<td align=center bgcolor='#D9E1F2'>지급은행</td>
		<td align=center bgcolor='#D9E1F2'>지급계좌번호</td>
		<td align=center bgcolor='#D9E1F2'>예금주명</td>
		<td align=center bgcolor='#D9E1F2'>지급금액</td>
		<td align=center bgcolor='#D9E1F2'>지급일</td>
	</tr>\n";

for($i=0; $i<$list_count; $i++) {

		echo "
	<tr>
		<td align=center>".$LIST[$i]['title']."</td>
		<td align=right>".number_format($LIST[$i]['recruit_amount'])."</td>
		<td align=center style=\"mso-number-format:'@';\">".$LIST[$i]['loan_start_date']."</td>
		<td align=center style=\"mso-number-format:'@';\">".$LIST[$i]['loan_end_date']."</td>
		<td align=center>".$BANK[$LIST[$i]['DCA_IP_BANK_ID']]."</td>
		<td align=center style=\"mso-number-format:'@';\">".$LIST[$i]['DCA_IP_ACCT_NB']."</td>
		<td align=center>".$ACCOUNT[$LIST[$i]['DCA_IP_ACCT_NB']]."</td>
		<td align=right>".number_format($LIST[$i]['DCA_IP_AMT'])."</td>
		<td align=center>".$LIST[$i]['IP_DATE']."</td>
	</tr>\n";
}

echo "</table>";


sql_close();
exit;

?>