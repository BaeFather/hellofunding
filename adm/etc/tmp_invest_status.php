<?
######################################
## 임의 투자통계 추출 (이상규 요청)
######################################

include_once("_common.php");

//$invest_idx_array = '127,130,134,135,142,144,145,146,149,151,157,160,168,169,170,171,172,173,174,175,176,177';
//$invest_idx_array = '171,174,184,185,187,191,192,193,195,197,198,201,202,205,207,212,222,224,226,227,228,230,231,232,233,243,259,262,270,272,279,281,286,289,290,291,293,294';		// 2018년 6월까지 투자내역
//$invest_idx_array = '421,445,449,359,392,417,428,458,483,502,496,469,473,400,405,478,523,198,443,491,512,509,462,437,444,475,481,482,486,499,514,516,195,485,506,290,299,321,233,262,328,364,367,389,507,524,357,378,380,396,402,332,508,493,464,460,457,448,442,434,416,409,387,375,372,363,342,336,281,279,426,476,489,492,232,503,526,528,529,527,530,532';		// 2018년 12월까지 투자내역
$invest_idx_array = '445,449,332,469,426,473,476,489,492,519,548,578,592,627,628,700,824,858,859,840,875,884,885,886,916,925,953,958,883,955,966,967,968,956,957,995,1003,1004,832,1005,1050,1051,1033,1052,1069,1068,1077,1014,1093,1132,1085,1124,1134,1142,1133,1161,1150,1173,1174,1175,1197,1196,1198,1199,1212,1218,1224,1238,1225,1226,1244,1247,1245,1263,1246,1264,1266,1268,1267,1265,1280,1282,1283,1289,1290,1269,987,1291,1232,1298,1299,1306,1309,1307,1300,1315,1316,1317,1318,1319,1329,1330,1326,1327,1328';


$sql = "
	SELECT
		A.idx, A.state, A.title, A.recruit_amount, A.loan_start_date,
		B.amount,
		C.mb_id, C.member_type, C.member_investor_type
	FROM
		cf_product A
	LEFT JOIN
		cf_product_invest B  ON A.idx=B.product_idx
	LEFT JOIN
		g5_member C  ON B.member_idx=C.mb_no
	WHERE (1)
		AND A.idx IN($invest_idx_array)
		AND B.invest_state='Y'
	ORDER BY
		A.idx ASC,
		C.member_type DESC,
		C.member_investor_type DESC,
		C.mb_no ASC";
$res = sql_query($sql);

echo '
<style>
td { padding:0 8px}
</style>

<div style="display:inline-block; width:100%; margin:30px 0; padding:0;">

	<table border="1" style="border-collapse:collapse;font-size:9pt;font-famile:gulim">
		<tr align="center">
			<th>품번</th>
			<th>대출상품명</th>
			<th>대출금액</th>
			<th>대출실행일</th>
			<th>상환현황</th>
			<th>투자자ID</th>
			<th>회원유형</th>
			<th>투자자유형</th>
			<th>투자금액</th>
		</tr>' . PHP_EOL;
while($row = sql_fetch_array($res)) {

	if($row['state']=='1')      $state = '이자상환중';
	else if($row['state']=='2') $state = '상환완료';
	else if($row['state']=='3') $state = '투자금모집실패';
	else if($row['state']=='4') $state = '부실';
	else if($row['state']=='5') $state = '중도상환';
	else if($row['state']=='6') $state = '대출취소(기표전)';
	else if($row['state']=='7') $state = '대출취소(기표후)';
	else $state = '투자자모집중';

	$member_type = ($row['member_type']=='2') ? '법인회원' : '개인회원';

	if($row['member_investor_type']=='1')      $member_investor_type = '일반개인투자자';
	else if($row['member_investor_type']=='2') $member_investor_type = '소득적격개인투자자';
	else if($row['member_investor_type']=='3') $member_investor_type = '전문투자자';
	else $member_investor_type = '';

	echo '
	<tr align="center">
		<td>'.$row['idx'].'</td>
		<td>'.$row['title'].'</td>
		<td>'.price_cutting($row['recruit_amount']).'</td>
		<td>'.$row['loan_start_date'].'</td>
		<td>'.$state.'</td>
		<td>'.$row['mb_id'].'</td>
		<td>'.$member_type.'</td>
		<td>'.$member_investor_type.'</td>
		<td align="right">'.number_format($row['amount']).'</td>
	</tr>' . PHP_EOL;
}
echo '	</table>' . PHP_EOL;


$sql = "
	SELECT
		C.mb_no, C.mb_id, C.member_type, C.member_investor_type, C.mb_co_reg_num, C.mb_addr1,
		COUNT(B.idx) AS invest_count,
		SUM(B.amount) AS invest_amount
	FROM
		cf_product_invest B
	LEFT JOIN
		g5_member C
	ON
		B.member_idx=C.mb_no
	WHERE (1)
		AND B.product_idx IN($invest_idx_array)
		AND B.invest_state='Y'
	GROUP BY
		B.member_idx
	ORDER BY
		B.idx ASC,
		C.member_type DESC,
		C.member_investor_type DESC,
		C.mb_no ASC";
$res = sql_query($sql);

echo '
	<br><br>
	<table border="1" style="border-collapse:collapse;font-size:9pt;font-famile:gulim">
		<tr align="center">
			<th>투자자ID</th>
			<th>주민.사업자</th>
			<th>주소</th>
			<th>회원유형</th>
			<th>투자자유형</th>
			<th>투자상품수</th>
			<th>투자금액</th>
		</tr>' . PHP_EOL;
while($row = sql_fetch_array($res)) {

	if($row['member_type']=='2') {
		$member_type = '법인회원';
		$ssn = $row['mb_co_reg_num'];
	}
	else {
		$member_type = '개인회원';
		$ssn = getJumin($row['mb_no']);
	}

	$member_type = ($row['member_type']=='2') ? '법인회원' : '개인회원';

	if($row['member_investor_type']=='1')      $member_investor_type = '일반개인투자자';
	else if($row['member_investor_type']=='2') $member_investor_type = '소득적격개인투자자';
	else if($row['member_investor_type']=='3') $member_investor_type = '전문투자자';
	else $member_investor_type = '';



	echo '
		<tr align="center">
			<td>'.$row['mb_id'].'</td>
			<td>'.$ssn.'</td>
			<td align="left">'.$row['mb_addr1'].'</td>
			<td>'.$member_type.'</td>
			<td>'.$member_investor_type.'</td>
			<td>'.$row['invest_count'].'</td>
			<td align="right">'.number_format($row['invest_amount']).'</td>
		</tr>' . PHP_EOL;
}
echo '	</table>' . PHP_EOL;
echo '</div>' . PHP_EOL;

?>