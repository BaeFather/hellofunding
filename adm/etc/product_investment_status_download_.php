<?

set_time_limit(300);

include_once('./_common.php');
include_once(G5_LIB_PATH."/PHPExcel_1.8.0/Classes/PHPExcel.php");

foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }

$prd_idx = $_REQUEST['idx'];

$PRDT = sql_fetch("SELECT start_num, title, recruit_amount, invest_return, recruit_period_start, loan_start_date, loan_end_date, start_datetime FROM cf_product WHERE idx='".$prd_idx."'");

// 투자소요시간 측정
$LAST_INVEST = sql_fetch("SELECT insert_date, insert_time FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y' ORDER BY idx DESC LIMIT 1");
$last_invest_datetime = $LAST_INVEST['insert_date']." ".$LAST_INVEST['insert_time'];
$interval = getDateInterval($PRDT['start_datetime'], $last_invest_datetime);

$sql = "
	SELECT
		B.mb_id, B.mb_name, B.mb_co_name, B.member_type, B.member_investor_type,
		A.member_idx, A.amount, A.is_advance_invest, A.syndi_id AS flatform_id,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_count,
		(SELECT SUM(amount) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_amount
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B
	ON
		A.member_idx = B.mb_no
	WHERE (1)
		AND A.product_idx='".$prd_idx."'
		AND A.invest_state='Y'
		$where_plus
	ORDER BY
		A.amount DESC";
//echo $sql;
$res  = sql_query($sql);
$rows = $res->num_rows;

$TOTAL = array(
					'COUNT'      => 0,
					'AMOUNT'     => 0,
					'M1_COUNT'   => 0,
					'M1_AMOUNT'  => 0,
					'M11_COUNT'  => 0,
					'M11_AMOUNT' => 0,
					'M12_COUNT'  => 0,
					'M12_AMOUNT' => 0,
					'M13_COUNT'  => 0,
					'M13_AMOUNT' => 0,
					'M2_COUNT'   => 0,
					'M2_AMOUNT'  => 0,
					'M3_COUNT'   => 0,
					'M3_AMOUNT'  => 0,
					'M32_COUNT'   => 0,
					'M32_AMOUNT'  => 0,
					'M33_COUNT'   => 0,
					'M33_AMOUNT'  => 0,
				);

$TOTAL_A = array(
						'COUNT'      => 0,
						'AMOUNT'     => 0,
						'M1_COUNT'   => 0,
						'M1_AMOUNT'  => 0,
						'M11_COUNT'  => 0,
						'M11_AMOUNT' => 0,
						'M12_COUNT'  => 0,
						'M12_AMOUNT' => 0,
						'M13_COUNT'  => 0,
						'M13_AMOUNT' => 0,
						'M2_COUNT'   => 0,
						'M2_AMOUNT'  => 0,
						'M3_COUNT'   => 0,
						'M3_AMOUNT'  => 0,
						'M32_COUNT'   => 0,
						'M32_AMOUNT'  => 0,
						'M33_COUNT'   => 0,
						'M33_AMOUNT'  => 0,
					);

$TOTAL_B = array(
						'COUNT'      => 0,
						'AMOUNT'     => 0,
						'M1_COUNT'   => 0,
						'M1_AMOUNT'  => 0,
						'M11_COUNT'  => 0,
						'M11_AMOUNT' => 0,
						'M12_COUNT'  => 0,
						'M12_AMOUNT' => 0,
						'M13_COUNT'  => 0,
						'M13_AMOUNT' => 0,
						'M2_COUNT'   => 0,
						'M2_AMOUNT'  => 0,
						'M3_COUNT'   => 0,
						'M3_AMOUNT'  => 0,
						'M32_COUNT'   => 0,
						'M32_AMOUNT'  => 0,
						'M33_COUNT'   => 0,
						'M33_AMOUNT'  => 0,
					);


for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);

	////////////////////////////////////
	// 전체 현황
	////////////////////////////////////
	$TOTAL['COUNT'] += 1;
	$TOTAL['AMOUNT'] += $LIST[$i]['amount'];

	if($LIST[$i]['member_type']=='2') {
		$TOTAL['M2_COUNT'] += 1;
		$TOTAL['M2_AMOUNT'] += $LIST[$i]['amount'];
	}
	else {
		$TOTAL['M1_COUNT'] += 1;
		$TOTAL['M1_AMOUNT'] += $LIST[$i]['amount'];

		if($LIST[$i]['member_investor_type']=='2') {
			$TOTAL['M12_COUNT'] += 1;
			$TOTAL['M12_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['member_investor_type']=='3') {
			$TOTAL['M13_COUNT'] += 1;
			$TOTAL['M13_AMOUNT'] += $LIST[$i]['amount'];
		}
		else {
			$TOTAL['M11_COUNT'] += 1;
			$TOTAL['M11_AMOUNT'] += $LIST[$i]['amount'];
		}
	}

	if($LIST[$i]['flatform_id']=='finnq') {
		$TOTAL['M3_COUNT'] += 1;
		$TOTAL['M3_AMOUNT'] += $LIST[$i]['amount'];
	}
	else if($LIST[$i]['flatform_id']=='hktvwowstar') {
		$TOTAL['M32_COUNT'] += 1;
		$TOTAL['M32_AMOUNT'] += $LIST[$i]['amount'];
	}
	else if($LIST[$i]['flatform_id']=='chosun') {
		$TOTAL['M33_COUNT'] += 1;
		$TOTAL['M33_AMOUNT'] += $LIST[$i]['amount'];
	}

	////////////////////////////////////
	// 최초 투자자 현황 데이터
	////////////////////////////////////
	if($LIST[$i]['total_invest_count']==1) {

		$TOTAL_A['COUNT'] += 1;
		$TOTAL_A['AMOUNT'] += $LIST[$i]['amount'];

		if($LIST[$i]['member_type']=='2') {
			$TOTAL_A['M2_COUNT'] += 1;
			$TOTAL_A['M2_AMOUNT'] += $LIST[$i]['amount'];
		}
		else {
			$TOTAL_A['M1_COUNT'] += 1;
			$TOTAL_A['M1_AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_investor_type']=='2') {
				$TOTAL_A['M12_COUNT'] += 1;
				$TOTAL_A['M12_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['member_investor_type']=='3') {
				$TOTAL_A['M13_COUNT'] += 1;
				$TOTAL_A['M13_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_A['M11_COUNT'] += 1;
				$TOTAL_A['M11_AMOUNT'] += $LIST[$i]['amount'];
			}
		}

		if($LIST[$i]['flatform_id']=='finnq') {
			$TOTAL_A['M3_COUNT'] += 1;
			$TOTAL_A['M3_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='hktvwowstar') {
			$TOTAL_A['M32_COUNT'] += 1;
			$TOTAL_A['M32_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='chosun') {
			$TOTAL_A['M33_COUNT'] += 1;
			$TOTAL_A['M33_AMOUNT'] += $LIST[$i]['amount'];
		}

	}

	////////////////////////////////////
	// 기존 투자자 현황 데이터
	////////////////////////////////////
	else {

		$TOTAL_B['COUNT'] += 1;
		$TOTAL_B['AMOUNT'] += $LIST[$i]['amount'];

		if($LIST[$i]['member_type']=='2') {
			$TOTAL_B['M2_COUNT'] += 1;
			$TOTAL_B['M2_AMOUNT'] += $LIST[$i]['amount'];
		}
		else {
			$TOTAL_B['M1_COUNT'] += 1;
			$TOTAL_B['M1_AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_investor_type']=='2') {
				$TOTAL_B['M12_COUNT'] += 1;
				$TOTAL_B['M12_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['member_investor_type']=='3') {
				$TOTAL_B['M13_COUNT'] += 1;
				$TOTAL_B['M13_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_B['M11_COUNT'] += 1;
				$TOTAL_B['M11_AMOUNT'] += $LIST[$i]['amount'];
			}
		}

		if($LIST[$i]['flatform_id']=='finnq') {
			$TOTAL_B['M3_COUNT'] += 1;
			$TOTAL_B['M3_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='hktvwowstar') {
			$TOTAL_B['M32_COUNT'] += 1;
			$TOTAL_B['M32_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='chosun') {
			$TOTAL_B['M33_COUNT'] += 1;
			$TOTAL_B['M33_AMOUNT'] += $LIST[$i]['amount'];
		}

	}

}


$title0 = "헬로펀딩 제{$PRDT['start_num']}호 상품 투자 요약보고";

echo "
	<style>
	table { font-family:'맑은 고딕'; font-size:10pt; }:
	</style>
	<table border='2'>
		<tr>
			<th colspan='7' style='font-size:16pt'>".$title0."</th>
		</tr>
	</table>

	<br/>

	<table border='1'>
		<tr>
			<td colspan='7' style='background:#808080;text-align:center;font-size:11pt;font-weight:bold;color:#fff;'>".$PRDT['title']."</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>모집금액</td>
			<td colspan='2' style='background:#F6F6F6;text-align:center;'>".price_cutting($PRDT['recruit_amount'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>투자소요시간</td>
			<td colspan='2' style='background:#F6F6F6;text-align:center;'>".$interval."</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>전체투자현황</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL['COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL['AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>법인투자</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL['M2_COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL['M2_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>개인투자</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL['M1_COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL['M1_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>최초투자자</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL_A['COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL_A['AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>기존투자자</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL_B['COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL_B['AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>개인-소득적격</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL['M12_COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL['M12_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>개인-전문</td>
			<td style='background:#F6F6F6;text-align:right;'>".number_format($TOTAL_B['M13_COUNT'])."건</td>
			<td style='background:#F6F6F6;text-align:right;'>".price_cutting($TOTAL_B['M13_AMOUNT'])."원</td>
		</tr>
	</table>

	<br/>

	<table>
		<tr>
			<td colspan='7' style='font-size:11pt;'>신디케이션 서비스별 투자 발생내역</td>
		</tr>
	</table>
	<table border='1'>
		<tr>
			<td colspan='5' style='background:#DCE6F1;text-align:center;'>서비스명</td>
			<td style='background:#DCE6F1;text-align:center;'>투자건수</td>
			<td style='background:#DCE6F1;text-align:center;'>투자금액</td>
		</tr>
		<tr>
			<td colspan='5' style='text-align:center;'>핀크</td>
			<td style='text-align:right;'>".number_format($TOTAL['M3_COUNT'])."건</td>
			<td style='text-align:right;'>".price_cutting($TOTAL['M3_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='text-align:center;'>한경</td>
			<td style='text-align:right;'>".number_format($TOTAL['M32_COUNT'])."건</td>
			<td style='text-align:right;'>".price_cutting($TOTAL['M32_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td colspan='5' style='text-align:center;'>조선</td>
			<td style='text-align:right;'>".number_format($TOTAL['M33_COUNT'])."건</td>
			<td style='text-align:right;'>".price_cutting($TOTAL['M33_AMOUNT'])."원</td>
		</tr>
	</table>

	<br/>

	<table>
		<tr>
			<td colspan='7' style='font-size:11pt;'>투자 상세내역</td>
		</tr>
	</table>
	<table border='1'>
		<tr>
			<td style='background:#DCE6F1;text-align:center;'>NO</td>
			<td style='background:#DCE6F1;text-align:center;'>업체명/성명</td>
			<td style='background:#DCE6F1;text-align:center;'>투자자유형</td>
			<td style='background:#DCE6F1;text-align:center;'>투자처</td>
			<td style='background:#DCE6F1;text-align:center;'>투자금액</td>
			<td style='background:#DCE6F1;text-align:center;'>누적투자수</td>
			<td style='background:#DCE6F1;text-align:center;'>누적투자액</td>
		</tr>
";

for($i=0,$j=1,$lnum=21; $i<$rows; $i++,$j++,$lnum++) {

	$name = ($LIST[$i]['member_type']=='2') ? $LIST[$i]['mb_co_name'] : $LIST[$i]['mb_name'];

	if($LIST[$i]['member_type']=='2') {
		$member_type = "법인";
	}
	else {
		if($LIST[$i]['member_investor_type']=='3')  $member_type = "전문";
		else if($LIST[$i]['member_investor_type']=='2') $member_type = "전문";
		else $member_type = "개인";
	}

	if($LIST[$i]['flatform_id']=='finnq') {
		$flatform = "핀크";
	}
	else if($LIST[$i]['flatform_id']=='finnq') {
		$flatform = "한경";
	}
	else {
		$flatform = "헬로";
	}

	echo "		<tr>
			<td style='text-align:center;'>".$j."</td>
			<td style='text-align:center;'>".$name."</td>
			<td style='text-align:center;'>".$member_type."</td>
			<td style='text-align:center;'>".$flatform."</td>
			<td style='text-align:right;'>".price_cutting($LIST[$i]['amount'])."원</td>
			<td style='text-align:right;'>".number_format($LIST[$i]['total_invest_count'])."건</td>
			<td style='text-align:right;'>".price_cutting($LIST[$i]['total_invest_amount'])."원</td>
		</tr>\n";

}
echo "</table>\n";



$file_subject = $title0;
$filename = iconv("UTF-8", "EUC-KR", $file_subject);

// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

exit;

?>