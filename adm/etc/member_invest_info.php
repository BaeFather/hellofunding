<?
###############################################################################
## $mode==1 1억 이상 투자자
## $mode==2 100회 이상 투자자
###############################################################################

include_once("_common.php");

while( list($k, $v)=each($_REQUEST) ) { if(!is_array($$k)) $$k = trim($v); }

$sql = "
	SELECT
		A.member_idx, B.mb_id, B.member_type, B.mb_co_name, B.mb_name, B.mb_hp, B.mb_datetime, B.login_cnt,
		COUNT(A.idx) AS invest_count,
		IFNULL(SUM(A.amount),0) AS invest_amount,
		B.mb_point,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y' AND syndi_id='') AS total_invest_count,
		(SELECT IFNULL(SUM(amount),0)  FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y' AND syndi_id='') AS total_invest_amount
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1
		AND A.invest_state='Y'
		AND A.syndi_id=''
		AND A.insert_date BETWEEN '2016-08-05' AND '2019-08-05'";

if($mode=='1') {

	$sql.= "
		AND (SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y' AND syndi_id='' AND insert_date BETWEEN '2016-08-05' AND '2019-08-05') >= 100000000
	GROUP BY
		A.member_idx
	ORDER BY
		invest_amount DESC,
		A.member_idx ASC";

}
else if($mode=='2') {

	$sql.= "
		AND (SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y' AND syndi_id='' AND insert_date BETWEEN '2016-08-05' AND '2019-08-05') >= 100
	GROUP BY
		A.member_idx
	ORDER BY
		invest_count DESC,
		A.member_idx ASC";

}

$res = sql_query($sql);
$rows = $res->num_rows;
for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);
}
$list_count = count($LIST);

if($mode=='1') {
	$file_name = "헬로펀딩_1억이상_투자자_" . date('Ymd_Hi') . ".xls";
}
else if($mode=='2') {
	$file_name = "헬로펀딩_100회이상_투자자_" . date('Ymd_Hi') . ".xls";
}
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP5 Generated Data" );


echo "<table border='1' style='border-collapse:collapse;font-size:9pt'>
	<tr align='center'>
		<td bgcolor='#DDEBF7'>회원번호</td>
		<td bgcolor='#DDEBF7'>아이디</td>
		<td bgcolor='#DDEBF7'>회원구분</td>
		<td bgcolor='#DDEBF7'>업체명</td>
		<td bgcolor='#DDEBF7'>성명/담당자명</td>
		<td bgcolor='#DDEBF7'>연락처</td>
		<td bgcolor='#DDEBF7'>가입일</td>
		<td bgcolor='#DDEBF7'>로그인수</td>
		<td bgcolor='#DDEBF7'>투자건수</td>
		<td bgcolor='#DDEBF7'>투자금액</td>
		<td bgcolor='#DDEBF7'>예치금</td>
		<td bgcolor='#DDEBF7'>누적투자건수</td>
		<td bgcolor='#DDEBF7'>누적투자상품수</td>
	</tr>\n";

for($i=0; $i<$list_count; $i++) {

	$print_member_type = ($LIST[$i]['member_type']=='2') ? '법인' : '개인';
	$print_mb_hp = masterDecrypt($LIST[$i]['mb_hp'], false);

	echo "	<tr align='center'>
		<td>".$LIST[$i]['member_idx']."</td>
		<td>".$LIST[$i]['mb_id']."</td>
		<td>".$print_member_type."</td>
		<td>".$LIST[$i]['mb_co_name']."</td>
		<td>".$LIST[$i]['mb_name']."</td>
		<td style='mso-number-format:\"@\";'>".$print_mb_hp."</td>
		<td style='mso-number-format:\"@\";'>".substr($LIST[$i]['mb_datetime'], 0, 16)."</td>
		<td align='right'>".number_format($LIST[$i]['login_cnt'])."</td>
		<td align='right'>".number_format($LIST[$i]['invest_count'])."</td>
		<td align='right'>".number_format($LIST[$i]['invest_amount'])."</td>
		<td align='right'>".number_format($LIST[$i]['mb_point'])."</td>
		<td align='right'>".number_format($LIST[$i]['total_invest_count'])."</td>
		<td align='right'>".number_format($LIST[$i]['total_invest_amount'])."</td>
	</tr>\n";

}

echo "</table>";

sql_close();

?>