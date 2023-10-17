<?

// 2019.08.05 ~ 2019.08.31 첫투자자(누적 50만원이상) 리스트

set_time_limit(0);

include_once("_common.php");


$sql = "
	SELECT
		B.mb_id,
		IF(B.member_type='2','법인','개인') AS _member_type,
		IF(B.member_type='2',B.mb_co_name, B.mb_name) AS _mb_name,
		IF(B.member_type='1',IF(B.member_investor_type=3,'전문', IF(B.member_investor_type=2,'소득적격','일반')),'') AS _investor_type,
		LEFT(B.mb_datetime,10) AS _mb_datetime,
		B.mb_hp,
		( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' AND insert_date BETWEEN '2019-08-05' AND '2019-08-31' ) AS _invest_amount,
		( SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' AND insert_date BETWEEN '2019-08-05' AND '2019-08-31' ) AS _invest_count,
		( SELECT insert_date FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' ORDER BY idx ASC LIMIT 1) AS first_invest_date,
		( SELECT insert_date FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' ORDER BY idx DESC LIMIT 1) AS last_invest_date,
		B.login_cnt,
		LEFT(B.mb_today_login, 16) AS _last_login,
		B.bank_code, B.account_num, B.bank_private_name
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE (1)
		AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' AND insert_date < '2019-08-05'  ) = 0
		AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' AND insert_date BETWEEN '2019-08-05' AND '2019-08-31'  ) > 0
		AND ( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx= A.member_idx AND invest_state='Y' AND insert_date BETWEEN '2019-08-05' AND '2019-08-31' ) >= 500000
	GROUP BY
		A.member_idx
	ORDER BY
		_invest_amount DESC, mb_no ASC";
$res = sql_query($sql);

$file_name = "2019.08.05~2019.08.31 첫투자자(누적 50만원이상) 리스트 " . date('Ymd') . ".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP5 Generated Data" );

debug_flush("<table border='1' style='font-size:10pt;border-collapse:collapse;'>
	<tr>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>아이디</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>회원구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>업체명.성명</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>투자자격구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>회원가입일</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>이벤트기간내</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>최초투자일</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>최종투자일</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>로그인수</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>최종로그인</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>연락처</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>은행명</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>계좌번호</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>예금주</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>통장표시내용</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#EEEEEE'>투자건수</td>
		<td align='center' bgcolor='#EEEEEE'>투자금액</td>
	</tr>\n");

while( $LIST = sql_fetch_array($res) ) {
	$LIST['mb_hp']       = masterDecrypt($LIST['mb_hp'], false);
	$LIST['account_num'] = masterDecrypt($LIST['account_num'], false);

debug_flush("
	<tr>
		<td align='center'>".$LIST['mb_id']."</td>
		<td align='center'>".$LIST['_member_type']."</td>
		<td align='center'>".$LIST['_mb_name']."</td>
		<td align='center'>".$LIST['_investor_type']."</td>
		<td align='center'>".$LIST['_mb_datetime']."</td>
		<td align='right'>".number_format($LIST['_invest_count'])."</td>
		<td align='right'>".number_format($LIST['_invest_amount'])."</td>
		<td align='center'>".$LIST['first_invest_date']."</td>
		<td align='center'>".$LIST['last_invest_date']."</td>
		<td align='right'>".$LIST['login_cnt']."</td>
		<td align='center' style='mso-number-format:\"@\";'>".$LIST['_last_login']."</td>
		<td align='center' style='mso-number-format:\"@\";'>".$LIST['mb_hp']."</td>
		<td align='center'>".$BANK[$LIST['bank_code']]."</td>
		<td align='center' style='mso-number-format:\"@\";'>".$LIST['account_num']."</td>
		<td align='center'>".$LIST['bank_private_name']."</td>
		<td align='center'></td>
	</tr>\n");

}

debug_flush("</table>");

sql_close();
exit;


?>