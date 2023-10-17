<?
###############################################################################
##  투자수익 지급내역 통계 - 엑셀다운로드
###############################################################################

set_time_limit(300);

include_once('./_common.php');
include_once(G5_LIB_PATH."/PHPExcel_1.8.0/Classes/PHPExcel.php");

if($is_admin != 'super' && $w == '') { echo "ERROR-LOGIN"; exit; }

foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }

if($syear=='') $syear = date('Y');

$sdate = $syear;
$sdate.= ($smonth) ?  "-" . $smonth : "";
$sdate.= ($sday) ?  "-" . $sday : "";


$sql = "
	SELECT
		A.product_idx, A.turn,
		B.start_num, B.title, B.invest_return
	FROM
		cf_product_give A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	WHERE 1
		AND banking_date BETWEEN '".$sdate." 00:00:00' AND '".$sdate." 23:59:59'
	GROUP BY
		A.product_idx, A.turn
	ORDER BY
		B.start_num,
		B.open_datetime,
		B.idx";
//echo "<pre>".$sql."</pre>";
$result = sql_query($sql);
$rcount = $result->num_rows;

$LIST  = array();

$TYPE = array(
	'1N' => '개인-일반',
	'2N' => '기업-일반',
	'1C' => '개인-대부',
	'2C' => '기업-대부'
);

$TYPE_KEY = array_keys($TYPE);


if(!$rcount) {
	echo "DATA-EMPTY"; exit;
}

for($i=0; $i<$rcount; $i++) {

	$LIST[$i] = sql_fetch_array($result);

	$TMP = sql_fetch("SELECT COUNT(idx) AS give_count FROM cf_product_give WHERE product_idx='".$LIST[$i]['product_idx']."' AND turn='".$LIST[$i]['turn']."'");
	$LIST[$i]['give_count'] = $TMP['give_count'];

	$LIST[$i]['start_num_title'] = "헬로펀딩 상품 " . $LIST[$i]['start_num'] . "호";

	$sql2 = "
		SELECT
			A.idx, A.member_idx, A.interest, A.principal, A.interest_tax, A.local_tax, A.fee, A.is_creditor, A.receive_method, A.bank_name, A.bank_private_name, A.account_num,
			B.mb_id, B.member_type, B.mb_co_name, B.mb_co_reg_num, B.mb_name, B.mb_co_name
		FROM
			cf_product_give A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE 1
			AND A.product_idx='".$LIST[$i]['product_idx']."'
			AND A.turn='".$LIST[$i]['turn']."'
		ORDER BY
			A.product_idx, A.turn ASC";
	$res2 = sql_query($sql2);

	$x = 0;
	while( $ROW = sql_fetch_array($res2) ) {

		$invest_interest    = $ROW['interest'] + $ROW['interest_tax'] + $ROW['local_tax'] + $ROW['fee'];
		$after_tax_interest = $ROW['interest'] + $ROW['fee'];

		$interest_tax       = $ROW['interest_tax'];
		$local_tax          = $ROW['local_tax'];
		$tax                = $ROW['interest_tax'] + $ROW['local_tax'];

		$fee                = $ROW['fee'];
		$fee_supply         = ceil($fee / 1.1);											// 공급가액
		$fee_vat            = $fee - $fee_supply;									// 부가세

		$last_interest      = $ROW['interest'];
		$last_amount        = $ROW['interest'] + $ROW['principal'];

		if($ROW['member_type']=='2') {
			$mb_gubun = ($ROW['is_creditor']=='Y') ? "2C" : "2N";
		}
		else {
			$mb_gubun = ($ROW['is_creditor']=='Y') ? "1C" : "1N";
		}


		// 상품.차수별 지급내역 합계
		$LIST[$i]['idx']                 = $ROW['idx'];
		$LIST[$i]['principal']          += $ROW['principal'];
		$LIST[$i]['invest_interest']    += $invest_interest;
		$LIST[$i]['after_tax_interest'] += $after_tax_interest;
		$LIST[$i]['interest_tax']       += $interest_tax;
		$LIST[$i]['local_tax']          += $local_tax;
		$LIST[$i]['tax']                += $tax;
		$LIST[$i]['fee_supply']         += $fee_supply;
		$LIST[$i]['fee_vat']            += $fee_vat;
		$LIST[$i]['fee']                += $fee;
		$LIST[$i]['last_interest']      += $last_interest;
		$LIST[$i]['last_amount']        += $last_amount;

		// 상품.차수별 지급상세내역 리스트
		$LIST[$i]['DETAIL'][$x]['idx']                = $ROW['idx'];
		$LIST[$i]['DETAIL'][$x]['member_idx']         = $ROW['member_idx'];
		$LIST[$i]['DETAIL'][$x]['mb_name']            = ($ROW['member_type']=='2') ? $ROW['mb_co_name'] : $ROW['mb_name'];
		$LIST[$i]['DETAIL'][$x]['reg_num']            = ($ROW['member_type']=='2') ? preg_replace("/-/", "", $ROW['mb_co_reg_num']) : getJumin($ROW['member_idx']);
		$LIST[$i]['DETAIL'][$x]['gubun']              = $mb_gubun;
		$LIST[$i]['DETAIL'][$x]['principal']          = $ROW['principal'];
		$LIST[$i]['DETAIL'][$x]['invest_interest']    = $invest_interest;
		$LIST[$i]['DETAIL'][$x]['after_tax_interest'] = $after_tax_interest;
		$LIST[$i]['DETAIL'][$x]['interest_tax']       = $interest_tax;
		$LIST[$i]['DETAIL'][$x]['local_tax']          = $local_tax;
		$LIST[$i]['DETAIL'][$x]['tax']                = $tax;
		$LIST[$i]['DETAIL'][$x]['fee_supply']         = $fee_supply;
		$LIST[$i]['DETAIL'][$x]['fee_vat']            = $fee_vat;
		$LIST[$i]['DETAIL'][$x]['fee']                = $fee;
		$LIST[$i]['DETAIL'][$x]['last_interest']      = $last_interest;
		$LIST[$i]['DETAIL'][$x]['last_amount']        = $last_amount;

		$LIST[$i]['DETAIL'][$x]['bank_name']          = $ROW['bank_name'];
		$LIST[$i]['DETAIL'][$x]['account_num']        = $ROW['account_num'];
		$LIST[$i]['DETAIL'][$x]['bank_private_name']  = $ROW['bank_private_name'];
		$LIST[$i]['DETAIL'][$x]['member_type']        = $ROW['member_type'];
		$LIST[$i]['DETAIL'][$x]['is_creditor']        = $ROW['is_creditor'];

		// 상품.차수별/회원타입별 지급내역
		$LIST[$i]['TYPELIST'][$mb_gubun]['give_count']         += 1;
		$LIST[$i]['TYPELIST'][$mb_gubun]['principal']          += $ROW['principal'];
		$LIST[$i]['TYPELIST'][$mb_gubun]['invest_interest']    += $invest_interest;
		$LIST[$i]['TYPELIST'][$mb_gubun]['after_tax_interest'] += $after_tax_interest;
		$LIST[$i]['TYPELIST'][$mb_gubun]['interest_tax']       += $interest_tax;
		$LIST[$i]['TYPELIST'][$mb_gubun]['local_tax']          += $local_tax;
		$LIST[$i]['TYPELIST'][$mb_gubun]['tax']                += $tax;
		$LIST[$i]['TYPELIST'][$mb_gubun]['fee_supply']         += $fee_supply;
		$LIST[$i]['TYPELIST'][$mb_gubun]['fee_vat']            += $fee_vat;
		$LIST[$i]['TYPELIST'][$mb_gubun]['fee']                += $fee;
		$LIST[$i]['TYPELIST'][$mb_gubun]['last_interest']      += $last_interest;
		$LIST[$i]['TYPELIST'][$mb_gubun]['last_amount']        += $last_amount;

		// 상품.차수별/회원타입별 지급내역 합계
		$LIST[$i]['TYPELIST_SUM']['give_count']         = ($LIST[$i]['TYPELIST']['1N']['give_count']         + $LIST[$i]['TYPELIST']['2N']['give_count']         + $LIST[$i]['TYPELIST']['1C']['give_count']         + $LIST[$i]['TYPELIST']['2C']['give_count']);
		$LIST[$i]['TYPELIST_SUM']['principal']          = ($LIST[$i]['TYPELIST']['1N']['principal']          + $LIST[$i]['TYPELIST']['2N']['principal']          + $LIST[$i]['TYPELIST']['1C']['principal']          + $LIST[$i]['TYPELIST']['2C']['principal']);
		$LIST[$i]['TYPELIST_SUM']['invest_interest']    = ($LIST[$i]['TYPELIST']['1N']['invest_interest']    + $LIST[$i]['TYPELIST']['2N']['invest_interest']    + $LIST[$i]['TYPELIST']['1C']['invest_interest']    + $LIST[$i]['TYPELIST']['2C']['invest_interest']);
		$LIST[$i]['TYPELIST_SUM']['after_tax_interest'] = ($LIST[$i]['TYPELIST']['1N']['after_tax_interest'] + $LIST[$i]['TYPELIST']['2N']['after_tax_interest'] + $LIST[$i]['TYPELIST']['1C']['after_tax_interest'] + $LIST[$i]['TYPELIST']['2C']['after_tax_interest']);
		$LIST[$i]['TYPELIST_SUM']['interest_tax']       = ($LIST[$i]['TYPELIST']['1N']['interest_tax']       + $LIST[$i]['TYPELIST']['2N']['interest_tax']       + $LIST[$i]['TYPELIST']['1C']['interest_tax']       + $LIST[$i]['TYPELIST']['2C']['interest_tax']);
		$LIST[$i]['TYPELIST_SUM']['local_tax']          = ($LIST[$i]['TYPELIST']['1N']['local_tax']          + $LIST[$i]['TYPELIST']['2N']['local_tax']          + $LIST[$i]['TYPELIST']['1C']['local_tax']          + $LIST[$i]['TYPELIST']['2C']['local_tax']);
		$LIST[$i]['TYPELIST_SUM']['tax']                = ($LIST[$i]['TYPELIST']['1N']['tax']                + $LIST[$i]['TYPELIST']['2N']['tax']                + $LIST[$i]['TYPELIST']['1C']['tax']                + $LIST[$i]['TYPELIST']['2C']['tax']);
		$LIST[$i]['TYPELIST_SUM']['fee_supply']         = ($LIST[$i]['TYPELIST']['1N']['fee_supply']         + $LIST[$i]['TYPELIST']['2N']['fee_supply']         + $LIST[$i]['TYPELIST']['1C']['fee_supply']         + $LIST[$i]['TYPELIST']['2C']['fee_supply']);
		$LIST[$i]['TYPELIST_SUM']['fee_vat']            = ($LIST[$i]['TYPELIST']['1N']['fee_vat']            + $LIST[$i]['TYPELIST']['2N']['fee_vat']            + $LIST[$i]['TYPELIST']['1C']['fee_vat']            + $LIST[$i]['TYPELIST']['2C']['fee_vat']);
		$LIST[$i]['TYPELIST_SUM']['fee']                = ($LIST[$i]['TYPELIST']['1N']['fee']                + $LIST[$i]['TYPELIST']['2N']['fee']                + $LIST[$i]['TYPELIST']['1C']['fee']                + $LIST[$i]['TYPELIST']['2C']['fee']);
		$LIST[$i]['TYPELIST_SUM']['last_interest']      = ($LIST[$i]['TYPELIST']['1N']['last_interest']      + $LIST[$i]['TYPELIST']['2N']['last_interest']      + $LIST[$i]['TYPELIST']['1C']['last_interest']      + $LIST[$i]['TYPELIST']['2C']['last_interest']);
		$LIST[$i]['TYPELIST_SUM']['last_amount']        = ($LIST[$i]['TYPELIST']['1N']['last_amount']        + $LIST[$i]['TYPELIST']['2N']['last_amount']        + $LIST[$i]['TYPELIST']['1C']['last_amount']        + $LIST[$i]['TYPELIST']['2C']['last_amount']);

		// 회원타입별 정산내역 합계
		$TYPELIST[$mb_gubun]['give_count']         += 1;
		$TYPELIST[$mb_gubun]['principal']          += $ROW['principal'];
		$TYPELIST[$mb_gubun]['invest_interest']    += $invest_interest;
		$TYPELIST[$mb_gubun]['after_tax_interest'] += $after_tax_interest;
		$TYPELIST[$mb_gubun]['interest_tax']       += $interest_tax;
		$TYPELIST[$mb_gubun]['local_tax']          += $local_tax;
		$TYPELIST[$mb_gubun]['tax']                += $tax;
		$TYPELIST[$mb_gubun]['fee_supply']         += $fee_supply;
		$TYPELIST[$mb_gubun]['fee_vat']            += $fee_vat;
		$TYPELIST[$mb_gubun]['fee']                += $fee;
		$TYPELIST[$mb_gubun]['last_interest']      += $last_interest;
		$TYPELIST[$mb_gubun]['last_amount']        += $last_amount;

		$x++;

	}

	$LIST_SUM['give_count']         += $LIST[$i]['give_count'];
	$LIST_SUM['principal']          += $LIST[$i]['principal'];
	$LIST_SUM['invest_interest']    += $LIST[$i]['invest_interest'];
	$LIST_SUM['after_tax_interest'] += $LIST[$i]['after_tax_interest'];
	$LIST_SUM['interest_tax']       += $LIST[$i]['interest_tax'];
	$LIST_SUM['local_tax']          += $LIST[$i]['local_tax'];
	$LIST_SUM['tax']                += $LIST[$i]['tax'];
	$LIST_SUM['fee_supply']         += $LIST[$i]['fee_supply'];
	$LIST_SUM['fee_vat']            += $LIST[$i]['fee_vat'];
	$LIST_SUM['fee']                += $LIST[$i]['fee'];
	$LIST_SUM['last_interest']      += $LIST[$i]['last_interest'];
	$LIST_SUM['last_amount']        += $LIST[$i]['last_amount'];

	// 회원타입별 정산 총합계
	$TYPELIST_SUM['give_count']         += $LIST[$i]['TYPELIST_SUM']['give_count'];
	$TYPELIST_SUM['principal']          += $LIST[$i]['TYPELIST_SUM']['principal'];
	$TYPELIST_SUM['invest_interest']    += $LIST[$i]['TYPELIST_SUM']['invest_interest'];
	$TYPELIST_SUM['after_tax_interest'] += $LIST[$i]['TYPELIST_SUM']['after_tax_interest'];
	$TYPELIST_SUM['interest_tax']       += $LIST[$i]['TYPELIST_SUM']['interest_tax'];
	$TYPELIST_SUM['local_tax']          += $LIST[$i]['TYPELIST_SUM']['local_tax'];
	$TYPELIST_SUM['tax']                += $LIST[$i]['TYPELIST_SUM']['tax'];
	$TYPELIST_SUM['fee_supply']         += $LIST[$i]['TYPELIST_SUM']['fee_supply'];
	$TYPELIST_SUM['fee_vat']            += $LIST[$i]['TYPELIST_SUM']['fee_vat'];
	$TYPELIST_SUM['fee']                += $LIST[$i]['TYPELIST_SUM']['fee'];
	$TYPELIST_SUM['last_interest']      += $LIST[$i]['TYPELIST_SUM']['last_interest'];
	$TYPELIST_SUM['last_amount']        += $LIST[$i]['TYPELIST_SUM']['last_amount'];

}

$list_count = count($LIST);


$objPHPExcel = new PHPExcel();

$title0 = $sdate." 투자수익지급내역";

// Excel 문서 속성을 지정해주는 부분이다. 적당히 수정하면 된다.
$objPHPExcel->getProperties()->setCreator("헬로펀딩 정산시스템")
                             ->setLastModifiedBy("헬로펀딩 정산시스템")
                             ->setTitle($title0)
                             ->setSubject($title0)
                             ->setDescription($title0)
                             ->setKeywords($title0)
                             ->setCategory("(주)헬로핀테크");

// 제목줄 ------------------------------------------------------------------------
// 셀병합(Col) 및 제목삽입
$objPHPExcel->setActiveSheetIndex(0)->mergeCells("A1:P1")->setCellValue("A1", preg_replace("/-/",".",$sdate)." 헬로펀딩 투자수익 지급내역");
//가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// 제목줄 ------------------------------------------------------------------------


// Excel 파일의 각 셀의 타이틀을 정해준다.
$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue("A2", "NO")
            ->setCellValue("B2", "펀딩상품")
            ->setCellValue("C2", "회차")
            ->setCellValue("D2", "이자율")
            ->setCellValue("E2", "지급건수")
            ->setCellValue("F2", "원금")
						->setCellValue("G2", "투자수익")
            ->setCellValue("H2", "이자소득세")
            ->setCellValue("I2", "지방소득세")
            ->setCellValue("J2", "원천세계")
            ->setCellValue("K2", "차감지급액")
            ->setCellValue("L2", "플랫폼이용료")
            ->setCellValue("M2", "부가세")
            ->setCellValue("N2", "플랫폼이용료계")
            ->setCellValue("O2", "세후금액")
            ->setCellValue("P2", "실지급액");

//셀너비
$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(7.86);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(22.29);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(6.43);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(9.29);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(9.29);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("M")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("N")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("O")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("P")->setWidth(15);


//가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle("A2:P2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//배경색 변경
$objPHPExcel->getActiveSheet()->getStyle("A2:P2")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFDCE6F1");


//루프시작
for($i=0,$j=1,$line_num=3; $i<$list_count; $i++,$j++,$line_num++) {

	$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue("A{$line_num}", $j)
	            ->setCellValue("B{$line_num}", $LIST[$i]['start_num_title'])
	            ->setCellValue("C{$line_num}", $LIST[$i]['turn'])
	            ->setCellValue("D{$line_num}", $LIST[$i]['invest_return']/100)
	            ->setCellValue("E{$line_num}", $LIST[$i]['give_count'])
	            ->setCellValue("F{$line_num}", $LIST[$i]['principal'])
	            ->setCellValue("G{$line_num}", $LIST[$i]['invest_interest'])
	            ->setCellValue("H{$line_num}", $LIST[$i]['interest_tax'])
	            ->setCellValue("I{$line_num}", $LIST[$i]['local_tax'])
	            ->setCellValue("J{$line_num}", $LIST[$i]['tax'])
	            ->setCellValue("K{$line_num}", $LIST[$i]['after_tax_interest'])
	            ->setCellValue("L{$line_num}", $LIST[$i]['fee_supply'])
	            ->setCellValue("M{$line_num}", $LIST[$i]['fee_vat'])
	            ->setCellValue("N{$line_num}", $LIST[$i]['fee'])
	            ->setCellValue("O{$line_num}", $LIST[$i]['last_interest'])
	            ->setCellValue("P{$line_num}", $LIST[$i]['last_amount']);

	//퍼센티지 포멧
	$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}")->getNumberFormat()->applyFromArray(["code" => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE]);
	//가운데 정렬
	$objPHPExcel->getActiveSheet()->getStyle("A{$line_num}:D{$line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

}

// 셀병합(Col)
$objPHPExcel->setActiveSheetIndex(0)->mergeCells("A{$line_num}:D{$line_num}")->setCellValue("A{$line_num}", "합계");

// 합산 데이터 출력(A)
$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue("E{$line_num}", $LIST_SUM['give_count'])
						->setCellValue("F{$line_num}", $LIST_SUM['principal'])
						->setCellValue("G{$line_num}", $LIST_SUM['invest_interest'])
						->setCellValue("H{$line_num}", $LIST_SUM['interest_tax'])
						->setCellValue("I{$line_num}", $LIST_SUM['local_tax'])
						->setCellValue("J{$line_num}", $LIST_SUM['tax'])
						->setCellValue("K{$line_num}", $LIST_SUM['after_tax_interest'])
						->setCellValue("L{$line_num}", $LIST_SUM['fee_supply'])
						->setCellValue("M{$line_num}", $LIST_SUM['fee_vat'])
						->setCellValue("N{$line_num}", $LIST_SUM['fee'])
						->setCellValue("O{$line_num}", $LIST_SUM['last_interest'])
						->setCellValue("P{$line_num}", $LIST_SUM['last_amount']);

//가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle("A{$line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//배경색 변경
$objPHPExcel->getActiveSheet()->getStyle("A{$line_num}:P{$line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFF6F6F6");


//회원 유형별 지급내역
$line_num += 1;
$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue("D{$line_num}", "구분")
						->setCellValue("E{$line_num}", "지급건수")
						->setCellValue("F{$line_num}", "원금")
						->setCellValue("G{$line_num}", "투자수익")
						->setCellValue("H{$line_num}", "이자소득세")
						->setCellValue("I{$line_num}", "지방소득세")
						->setCellValue("J{$line_num}", "원천세계")
						->setCellValue("K{$line_num}", "차감지급액")
						->setCellValue("L{$line_num}", "플랫폼이용료")
						->setCellValue("M{$line_num}", "부가세")
						->setCellValue("N{$line_num}", "플랫폼이용료계")
						->setCellValue("O{$line_num}", "세후금액")
						->setCellValue("P{$line_num}", "실지급액");

//가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}:P{$line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//배경색 변경
$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}:P{$line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFDCE6F1");


$type_count = count($TYPE);
$line_num += 1;
for($k=0; $k<$type_count; $k++,$line_num++) {

	$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue("D{$line_num}", $TYPE[$TYPE_KEY[$k]])
	            ->setCellValue("E{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['give_count'])
	            ->setCellValue("F{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['principal'])
	            ->setCellValue("G{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['invest_interest'])
	            ->setCellValue("H{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['interest_tax'])
	            ->setCellValue("I{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['local_tax'])
	            ->setCellValue("J{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['tax'])
	            ->setCellValue("K{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['after_tax_interest'])
	            ->setCellValue("L{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['fee_supply'])
	            ->setCellValue("M{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['fee_vat'])
	            ->setCellValue("N{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['fee'])
	            ->setCellValue("O{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['last_interest'])
	            ->setCellValue("P{$line_num}", (int)$TYPELIST[$TYPE_KEY[$k]]['last_amount']);

	$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

}

$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue("D{$line_num}", "합계")
						->setCellValue("E{$line_num}", (int)$TYPELIST_SUM['give_count'])
						->setCellValue("F{$line_num}", (int)$TYPELIST_SUM['principal'])
						->setCellValue("G{$line_num}", (int)$TYPELIST_SUM['invest_interest'])
						->setCellValue("H{$line_num}", (int)$TYPELIST_SUM['interest_tax'])
						->setCellValue("I{$line_num}", (int)$TYPELIST_SUM['local_tax'])
						->setCellValue("J{$line_num}", (int)$TYPELIST_SUM['tax'])
						->setCellValue("K{$line_num}", (int)$TYPELIST_SUM['after_tax_interest'])
						->setCellValue("L{$line_num}", (int)$TYPELIST_SUM['fee_supply'])
						->setCellValue("M{$line_num}", (int)$TYPELIST_SUM['fee_vat'])
						->setCellValue("N{$line_num}", (int)$TYPELIST_SUM['fee'])
						->setCellValue("O{$line_num}", (int)$TYPELIST_SUM['last_interest'])
						->setCellValue("P{$line_num}", (int)$TYPELIST_SUM['last_amount']);

//가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//배경색 변경
$objPHPExcel->getActiveSheet()->getStyle("D{$line_num}:P{$line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFF6F6F6");

// 셀병합(Row)
$start_line_num = $line_num-5;
$objPHPExcel->setActiveSheetIndex(0)->mergeCells("A{$start_line_num}:C{$line_num}")->setCellValue("A{$start_line_num}", "");
//배경색 변경
$objPHPExcel->getActiveSheet()->getStyle("A{$start_line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFDDDDDD");


//글자스타일
$objPHPExcel->getActiveSheet()->getStyle("A2:P{$line_num}")->getFont()->setName("맑은 고딕");
$objPHPExcel->getActiveSheet()->getStyle("A2:P{$line_num}")->getFont()->setSize(10);

//숫자형 변환 및 콤마
$objPHPExcel->getActiveSheet()->getStyle("E2:P{$line_num}")->getNumberFormat()->setFormatCode("#,##0");

//보더
$objPHPExcel->getActiveSheet()->getStyle("A2:P{$line_num}")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

//시트 타이틀 변경
$objPHPExcel->getActiveSheet()->setTitle($title0);

//제목 글자스타일
$objPHPExcel->getActiveSheet()->getStyle("A1:P1")->getFont()->setName("맑은 고딕");
$objPHPExcel->getActiveSheet()->getStyle("A1:P1")->getFont()->setSize(20);


//-------▶▶ 메인 시트 종료 ◀◀------------------------------------------------------//


//-------▶▶ 상품별 상세보기 생성 시작 ◀◀-------------------------------------------//

for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	$objWorkSheet = $objPHPExcel->createSheet();


	// 제목줄 ------------------------------------------------------------------------
	// 셀병합(Col) 및 제목삽입
	$objWorkSheet->mergeCells("A1:Q1")->setCellValue("A1", preg_replace("/-/",".",$sdate)." 헬로펀딩 투자수익 지급 상세내역 - ". $LIST[$i]['start_num']."호 상품 ".$LIST[$i]['turn']."회차");
	//가운데 정렬
	$objWorkSheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	// 제목줄 ------------------------------------------------------------------------

	$sheet_title = $LIST[$i]['start_num']."호 ".$LIST[$i]['turn']."회차";
	$objWorkSheet->setTitle($sheet_title);


	// 셀타이틀
	$objWorkSheet->setCellValue("E2", "구분")
	             ->setCellValue("F2", "투자건수")
	             ->setCellValue("G2", "원금")
	             ->setCellValue("H2", "투자수익")
	             ->setCellValue("I2", "이자소득세")
	             ->setCellValue("J2", "지방소득세")
	             ->setCellValue("K2", "원천세계")
	             ->setCellValue("L2", "차감지급액")
	             ->setCellValue("M2", "플랫폼이용료")
	             ->setCellValue("N2", "부가세")
	             ->setCellValue("O2", "플랫폼이용료계")
	             ->setCellValue("P2", "세후금액")
	             ->setCellValue("Q2", "실지급액");

	//배경색 변경
	$objWorkSheet->getStyle("E2:Q2")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFFCE4D6");
	//가운데 정렬
	$objWorkSheet->getStyle("E2:Q2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	for($k=0,$tab_line_num=3; $k<$type_count; $k++,$tab_line_num++) {

		$objWorkSheet->setCellValue("E{$tab_line_num}", $TYPE[$TYPE_KEY[$k]])
		             ->setCellValue("F{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['give_count'])
		             ->setCellValue("G{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['principal'])
		             ->setCellValue("H{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['invest_interest'])
		             ->setCellValue("I{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['interest_tax'])
		             ->setCellValue("J{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['local_tax'])
		             ->setCellValue("K{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['tax'])
		             ->setCellValue("L{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['after_tax_interest'])
		             ->setCellValue("M{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['fee_supply'])
		             ->setCellValue("N{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['fee_vat'])
		             ->setCellValue("O{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['fee'])
		             ->setCellValue("P{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['last_interest'])
		             ->setCellValue("Q{$tab_line_num}", (int)$LIST[$i]['TYPELIST'][$TYPE_KEY[$k]]['last_amount']);

		//가운데 정렬
		$objWorkSheet->getStyle("E{$tab_line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	}


	//글자스타일
	$objWorkSheet->getStyle("E2:Q{$tab_line_num}")->getFont()->setName("맑은 고딕");
	$objWorkSheet->getStyle("E2:Q{$tab_line_num}")->getFont()->setSize(10);


	$objWorkSheet->setCellValue("E{$tab_line_num}", "합계")
							 ->setCellValue("F{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['give_count'])
							 ->setCellValue("G{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['principal'])
							 ->setCellValue("H{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['invest_interest'])
							 ->setCellValue("I{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['interest_tax'])
							 ->setCellValue("J{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['local_tax'])
							 ->setCellValue("K{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['tax'])
							 ->setCellValue("L{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['after_tax_interest'])
							 ->setCellValue("M{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['fee_supply'])
							 ->setCellValue("N{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['fee_vat'])
							 ->setCellValue("O{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['fee'])
							 ->setCellValue("P{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['last_interest'])
							 ->setCellValue("Q{$tab_line_num}", (int)$LIST[$i]['TYPELIST_SUM']['last_amount']);

	$objWorkSheet->getStyle("E3:Q{$tab_line_num}")->getNumberFormat()->setFormatCode("#,##0");

	//배경색 변경
	$objWorkSheet->getStyle("E{$tab_line_num}:Q{$tab_line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFDDDDDD");
	//가운데 정렬
	$objWorkSheet->getStyle("E{$tab_line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	//보더
	$objWorkSheet->getStyle("E2:Q{$tab_line_num}")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);


	$tab_line_num = $tab_line_num + 1;



	// 셀타이틀
	$objWorkSheet->setCellValue("A9", "회원번호")
							 ->setCellValue("B9", "주민.사업자번")
							 ->setCellValue("C9", "은행")
	             ->setCellValue("D9", "계좌번호")
							 ->setCellValue("E9", "예금주")
	             ->setCellValue("F9", "구분")
	             ->setCellValue("G9", "원금")
	             ->setCellValue("H9", "투자수익")
	             ->setCellValue("I9", "이자소득세")
	             ->setCellValue("J9", "지방소득세")
	             ->setCellValue("K9", "원천세계")
	             ->setCellValue("L9", "차감지급액")
	             ->setCellValue("M9", "플랫폼이용료")
	             ->setCellValue("N9", "부가세")
	             ->setCellValue("O9", "플랫폼이용료계")
	             ->setCellValue("P9", "세후금액")
	             ->setCellValue("Q9", "실지급액")
				 ->setCellValue("R9", "성명");

	//가운데 정렬
	$objWorkSheet->getStyle("A9:R9")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	//배경색 변경
	$objWorkSheet->getStyle("A9:R9")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFFCE4D6");


	$detail_count = count($LIST[$i]['DETAIL']);

	for($x=0,$tab_line_num=10; $x<$detail_count; $x++,$tab_line_num++) {

		$objWorkSheet->setCellValue("A{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['member_idx'])
								 ->setCellValueExplicit("B{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['reg_num'],        PHPExcel_Cell_DataType::TYPE_STRING)
								 ->setCellValue("C{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['bank_name'])
								 ->setCellValueExplicit("D{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['account_num'],        PHPExcel_Cell_DataType::TYPE_STRING)
								 ->setCellValue("E{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['bank_private_name'])
								 ->setCellValue("F{$tab_line_num}", $TYPE[$LIST[$i]['DETAIL'][$x]['gubun']])
								 ->setCellValue("G{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['principal'])
								 ->setCellValue("H{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['invest_interest'])
		             ->setCellValue("I{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['interest_tax'])
		             ->setCellValue("J{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['local_tax'])
		             ->setCellValue("K{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['tax'])
		             ->setCellValue("L{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['after_tax_interest'])
		             ->setCellValue("M{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['fee_supply'])
		             ->setCellValue("N{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['fee_vat'])
		             ->setCellValue("O{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['fee'])
		             ->setCellValue("P{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['last_interest'])
		             ->setCellValue("Q{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['last_amount'])
					 ->setCellValue("R{$tab_line_num}", $LIST[$i]['DETAIL'][$x]['mb_name']);

		//가운데 정렬
		$objWorkSheet->getStyle("A{$tab_line_num}:F{$tab_line_num}:")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objWorkSheet->getStyle("R{$tab_line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		//숫자형 변환 및 콤마
		$objWorkSheet->getStyle("G{$tab_line_num}:Q{$tab_line_num}")->getNumberFormat()->setFormatCode("#,##0");

	}

	$objWorkSheet->mergeCells("A{$tab_line_num}:F{$tab_line_num}")->setCellValue("A{$tab_line_num}", "합계")
							 ->setCellValue("G{$tab_line_num}", $LIST[$i]['principal'])
							 ->setCellValue("H{$tab_line_num}", $LIST[$i]['invest_interest'])
							 ->setCellValue("I{$tab_line_num}", $LIST[$i]['interest_tax'])
							 ->setCellValue("J{$tab_line_num}", $LIST[$i]['local_tax'])
							 ->setCellValue("K{$tab_line_num}", $LIST[$i]['tax'])
							 ->setCellValue("L{$tab_line_num}", $LIST[$i]['after_tax_interest'])
							 ->setCellValue("M{$tab_line_num}", $LIST[$i]['fee_supply'])
							 ->setCellValue("N{$tab_line_num}", $LIST[$i]['fee_vat'])
							 ->setCellValue("O{$tab_line_num}", $LIST[$i]['fee'])
							 ->setCellValue("P{$tab_line_num}", $LIST[$i]['last_interest'])
							 ->setCellValue("Q{$tab_line_num}", $LIST[$i]['last_amount'])
							 ->setCellValue("R{$tab_line_num}", '');

	//숫자형 변환 및 콤마
	$objWorkSheet->getStyle("G{$tab_line_num}:R{$tab_line_num}")->getNumberFormat()->setFormatCode("#,##0");
	//가운데 정렬
	$objWorkSheet->getStyle("A{$tab_line_num}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	//배경색 변경
	$objWorkSheet->getStyle("A{$tab_line_num}:R{$tab_line_num}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FFDDDDDD");

	//보더
	$objWorkSheet->getStyle("A9:R{$tab_line_num}")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

	//글자스타일
	$objWorkSheet->getStyle("A9:R{$tab_line_num}")->getFont()->setName("맑은 고딕");
	$objWorkSheet->getStyle("A9:R{$tab_line_num}")->getFont()->setSize(10);


	//셀너비
	$objWorkSheet->getColumnDimension("A")->setWidth(12.14);
	$objWorkSheet->getColumnDimension("B")->setWidth(17.86);
	$objWorkSheet->getColumnDimension("C")->setWidth(17.86);
	$objWorkSheet->getColumnDimension("D")->setWidth(17.86);
	$objWorkSheet->getColumnDimension("E")->setWidth(17.86);
	$objWorkSheet->getColumnDimension("F")->setWidth(12.14);
	$objWorkSheet->getColumnDimension("G")->setWidth(15);
	$objWorkSheet->getColumnDimension("H")->setWidth(15);
	$objWorkSheet->getColumnDimension("I")->setWidth(15);
	$objWorkSheet->getColumnDimension("J")->setWidth(15);
	$objWorkSheet->getColumnDimension("K")->setWidth(15);
	$objWorkSheet->getColumnDimension("L")->setWidth(15);
	$objWorkSheet->getColumnDimension("M")->setWidth(15);
	$objWorkSheet->getColumnDimension("N")->setWidth(15);
	$objWorkSheet->getColumnDimension("O")->setWidth(15);
	$objWorkSheet->getColumnDimension("P")->setWidth(15);
	$objWorkSheet->getColumnDimension("Q")->setWidth(15);
	$objWorkSheet->getColumnDimension("R")->setWidth(17.86);

	//제목 글자스타일
	$objWorkSheet->getStyle("A1:R1")->getFont()->setName("맑은 고딕");
	$objWorkSheet->getStyle("A1:R1")->getFont()->setSize(20);

}



// 활성 시트 색인을 첫 번째 시트로 설정하면 Excel이 이를 첫 번째 시트로 엽니다.
$objPHPExcel->setActiveSheetIndex(0);


$file_subject = "헬로펀딩_투자수익지급내역(지급일." . $sdate .")";
// 파일의 저장형식이 utf-8일 경우 한글파일 이름은 깨지므로 euc-kr로 변환해준다.
$filename = iconv("UTF-8", "EUC-KR", $file_subject);


// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


exit;

?>