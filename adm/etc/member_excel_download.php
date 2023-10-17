<?
################################################################################
## 회원리스트: 2017-06-12 이상규 요청분, 주소 및 주민번호 출력
################################################################################

include_once("_common.php");

auth_check($auth[$sub_menu], "w");

include_once('../secure_data_connect_log.php');


$sql = "
	SELECT
		mb_no, mb_id, mb_name, mb_co_name, mb_addr1, member_type, mb_co_reg_num,
		(SELECT COUNT(idx) AS cnt_idx FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS mb_invest_count,
		(SELECT SUM(amount) AS sum_amount FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS mb_invest_money
	FROM
		g5_member A
	WHERE
		mb_level=1
	ORDER BY
		member_type DESC, mb_invest_money DESC, mb_no DESC";
$res  = sql_query($sql);
$rows = $res->num_rows;


$now_date  = date('Ymd');
$file_name = "회원목록_".$now_date.".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP4 Generated Data" );

debug_flush('<table border=1 style="font-size:10pt">');

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);

	$mb_name = ($LIST[$i]['member_type']==2) ? $LIST[$i]['mb_co_name'] : $LIST[$i]['mb_name'];
	$mb_jumin = ($LIST[$i]['member_type']==2) ? $LIST[$i]['mb_co_reg_num'] : getJumin($LIST[$i]['mb_no']);

	debug_flush('<tr align="center">
			<td>'.$LIST[$i]['mb_id'].'</td>
			<td>'.$mb_name.'</td>
			<td>'.$LIST[$i]['mb_addr1'].'</td>
			<td style="mso-number-format:\'@\';">'.$mb_jumin.'</td>
			<td align="right">'.$LIST[$i]['mb_invest_count'].'</td>
			<td align="right">'.number_format($LIST[$i]['mb_invest_money']).'</td>
		</tr>');

}

debug_flush('</table>');

?>