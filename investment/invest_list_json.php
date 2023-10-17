<?
include_once('./_common.php');


while(list($key, $value)=each($_REQUEST)) { ${$key} = trim($value); }

$datetime = G5_TIME_YMDHIS;
//$datetime = preg_replace("/-|:| /i", "", G5_TIME_YMDHIS);  // YmdHis

$special_user = ($is_admin=='super' || in_array($member['mb_id'], array('yr4msp','hellosiesta','sori9th'))) ? true : false;

if($special_user) {
	$dp_query = "";
	$dp_query2 = "";
}
else {
	$dp_query  = " AND A.display = 'Y' ";
	$dp_query2 = " AND ASET.display = 'Y' ";
}

$where = "1=1";

switch($gubun) {

	## 투자 대기중
	case 'recruit_wait' :
		$where.= " AND (ASET.state = '' AND ASET.open_datetime > '$datetime') ";
		$where.= " OR ASET.title LIKE '[대기]%' ";
		$where.= ($mode=="success") ? " AND ASET.success_example='Y'" : "";
		$sub = true;
	break;

	## 진행중
	case 'recruit_started' :
		$where.= " AND ASET.state = '' ";
		$where.= " AND ASET.start_datetime <= '$datetime' ";
		$where.= " AND ASET.end_datetime >= '$datetime' ";
		$where.= " AND ASET.recruit_amount > total_invest_amount ";
		$where.= ($mode=="success") ? " AND ASET.success_example='Y' " : "";
		$where.= $dp_query2;
		$sub = true;
	break;

	## 투자 모집완료
	case 'recruit_finished' :
		$where.= " AND ASET.state = '' ";
		$where.= " AND ASET.end_datetime < '$datetime' ";
		$where.= " AND ASET.recruit_amount <= total_invest_amount ";
		$where.= ($mode=="success") ? " AND ASET.success_example='Y' " : "";
		$where.= $dp_query2;
		$sub = true;
	break;

	## 이자상환중
	case 'repay_started' :
		$where.= " AND A.state = '1' ";
		$where.= ($mode=="success") ? " AND A.success_example='Y' " : "";
		$where.= $dp_query;
		$sub = false;
	break;

	## 투자상환완료
	case 'repay_finished' :
		$where.= " AND A.state IN('2', '5') ";
		$where.= ($mode=="success") ? " AND A.success_example='Y' " : "";
		$where.= $dp_query;
		$sub = false;
	break;

	## 전체
	case 'all' :
	default :
		$where.= ($mode=="success") ? " AND A.success_example='Y' " : "";
		$where.= $dp_query;
		$sub = false;
	break;
}

if($sub) {
	$sub_sql = "(" .
	           "	SELECT " .
	           "    A.idx, A.open_datetime, A.start_datetime, A.end_datetime, A.state, A.recruit_amount, " .
	           "    A.category, A.title, A.invest_return, A.withhold_tax_rate, A.invest_usefee, A.invest_usefee_type, " .
	           "    A.invest_period, A.recruit_period_start, A.recruit_period_end, A.repay_type, " .
	           "    A.evaluate_score1, A.evaluate_score2, A.evaluate_score3, A.evaluate_score4, A.evaluate_star1, A.evaluate_star2, A.evaluate_star3, A.evaluate_star4, A.evaluate_grade1, A.evaluate_grade2, A.evaluate_grade3, A.evaluate_grade4, " .
	           "    A.main_image, A.display, A.purchase_guarantees, A.advanced_payment, A.success_example, A.popular_goods, A.start_date," .
	           "    (SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS total_invest_count, " .
	           "    (SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS total_invest_amount " .
	           "  FROM " .
	           "    cf_product A " .
	           "  WHERE " .
	           "    1=1 " . $dp_query .
	           ") AS ASET";
}
else {
	$sub_sql = " cf_product A ";
}

$sql = "SELECT COUNT(*) AS product_count FROM ".$sub_sql." WHERE ".$where;
$row = sql_fetch($sql);
$affect_num = $row['product_count'];
if(!$page) $page = 1;
if(!$size) $size = 5;

$total_page = ceil($affect_num / $size);

$DATA['query_string'] = $_SERVER['QUERY_STRING'];
$DATA['gubun']      = ($gubun) ? $gubun : 'all';
$DATA['page']       = $page;
if($total_page) {
	$DATA['total_page'] = $total_page;
	$DATA['next_page']  = ($page < $total_page) ? $page + 1 : '';
}
else {
	$DATA['total_page'] = 0;
	$DATA['next_page']  = 0;
}


if($page==1) {

	// 이벤트 상품 정보 추출
	$esql = "
		SELECT
			A.idx, A.state, A.category, A.title,
			A.invest_amount, A.invest_profit, A.invest_return, A.invest_period, A.invest_end_date,
			A.total_return_amount, A.recruit_period_start, A.recruit_period_end, A.recruit_amount, A.repay_type, repay_day,
			A.main_image, A.open_datetime, A.start_datetime, A.end_datetime, A.display,
			A.evaluate_score1, A.evaluate_score2, A.evaluate_score3, A.evaluate_score4,
			(SELECT COUNT(product_idx) AS total_invest_count FROM cf_event_product_invest WHERE A.idx = product_idx AND invest_state IN('Y','R')) AS total_invest_count,
			(SELECT IFNULL(SUM(amount),0) FROM cf_event_product_invest WHERE A.idx = product_idx AND invest_state IN('Y','R')) AS total_invest_amount
		FROM
			cf_event_product A
		WHERE
			1=1
			AND A.end_datetime > NOW()
			$dp_query
		ORDER BY
			A.start_date DESC, A.idx DESC";
	$eres  = sql_query($esql);
	$erows = $eres->num_rows;
	for($i=0; $i<$erows; $i++) {
		$EROW = sql_fetch_array($eres);
		//print_rr($EROW, 'font-size:11px;color:red;');

		$E['detail_url'] = "/event_invest/event_invest.php?prd_idx=".$EROW['idx'];

		if($EROW['main_image']) {
			$E['main_image'] = (is_file(G5_DATA_PATH."/product_special/".$EROW['main_image'])) ? G5_URL."/data/product_special/".$EROW['main_image'] : "";
		}
		else {
			$E['main_image'] = "";
		}

		$E['sub_link_display'] = ($E['main_image']) ? 'none' : 'block';
		$E['sub_link_display'] = (preg_match("/mirror\.hellofundin\.co\.kr/i", $_SERVER['HTTP_HOST'])) ? 'none' : 'block';

		if($EROW["recruit_amount"] > 0) {
			$E['invest_percent'] = ($EROW["total_invest_amount"] > 0) ? round((($EROW["total_invest_amount"]/$EROW["recruit_amount"])*100), 2) : 0;
		}
		else {
			$E['invest_percent'] = 0;
		}

		$E['progress_image_width'] = ($E['invest_percent']) ? $E['invest_percent'].'%' : '0.2%';

		$E['open_date']    = preg_replace("/-| |:/", "", $EROW["open_datetime"]);		//상점오픈 (투자시작가능)
		$E['invest_sdate'] = preg_replace("/-| |:/", "", $EROW["start_datetime"]);	//상품오픈 (투자시작가능)
		$E['invest_edate'] = preg_replace("/-| |:/", "", $EROW["end_datetime"]);		//상품종료 (투자모집완료)
		$E['end_date']     = preg_replace("/-| |:/", "", $EROW["invest_end_date"]);
		$E['state'] = get_product_state(
										 $EROW['recruit_period_start'],
										 $EROW['recruit_period_end'],
										 $E['open_date'],
										 $E['invest_sdate'],
										 $E['invest_edate'],
										 $EROW['state'],
										 $EROW['recruit_amount'],
										 $EROW['total_invest_amount'],
										 $E['end_date']
									 );

		$E['cover_display']         = "none";
		$E['cover_caption']         = "";
		$E['invest_button_caption'] = "상품상세보기";
		$E['invest_button_class']   = "btn_big_gray";

		if($E['invest_sdate']<=date("YmdHis") && $E['invest_edate']>=date("YmdHis")) {
			if($EROW["recruit_amount"] > $EROW["total_invest_amount"]) {
				$E['invest_button_caption'] = "상품상세보기";
				$E['invest_button_class']   = "btn_big_blue";
			}
			else {
				$E['cover_display']         = "block";
				$E['cover_caption']         = "펀딩성공";
				$E['invest_button_caption'] = "투자모집완료";
				$E['invest_button_class']   = "btn_big_gray";
			}
		}
		else {
			if($EROW["recruit_amount"] > $EROW["total_invest_amount"]) {
				if( preg_replace("/-/", "", $EROW["recruit_period_start"])>date("Ymd") ) {
					$E['invest_button_caption'] = "투자대기";
					$E['invest_button_class']   = "btn_big_blue";
				}
				else if( preg_replace("/-/", "", $EROW["recruit_period_end"])<date("Ymd") ) {
					$E['cover_display'] = "block";
					$E['cover_caption'] = "펀딩성공";
					$E['invest_button_caption'] = "투자모집완료";
					$E['invest_button_class']   = "btn_big_gray";
				}
			}
			else {
				$E['cover_display'] = "block";
				$E['cover_caption'] = "펀딩성공";
				$E['invest_button_caption'] = "투자모집완료";
				$E['invest_button_class']   = "btn_big_gray";
			}
		}

		$E['period_days'] = ceil(((strtotime($EROW["recruit_period_end"]) - strtotime($EROW["recruit_period_start"]))+86400) / 86400).'일';

		$E['opacity'] = ($special_user && $EROW['display']=='N') ? "0.5" : "1";

		$start_timestamp  = strtotime($EROW["start_datetime"]);
		$print_sdate = date('Y년 m월 d일', $start_timestamp);
		$print_sdate.= ' ' . get_yoil($EROW["start_datetime"]).'요일 ';
		$print_sdate.= (date(H, $start_timestamp) < 12) ? ' 오전' : ' 오후';
		$print_sdate.= date('H시', $start_timestamp);

		$DATA['list'][] = array(
												'opacity' => $E['opacity'],
												'detail_url' => $E['detail_url'],
												'sub_link_display' => $E['sub_link_display'],
												'grade_image' => '',
												'grade_display' => 'none',
												'main_image' => $E['main_image'],
												'purchase_guarantees_display' => 'none',
												'advanced_payment_display' =>	'none',
												'code_str' => '',
												'cover_caption' => $E['cover_caption'],
												'cover_display' => $E['cover_display'],
												'invest_button_caption' => $E['invest_button_caption'],
												'invest_button_class' => $E['invest_button_class'],
												'title' => $EROW['title'],
												'recruit_start_date' => date("Y년 m월 d일", strtotime($EROW["recruit_period_start"])),
												'invest_return_subject' => '투자자 수익률(일)',
												'invest_return' => (int)$EROW["invest_return"].'%',
												'invest_period' => $E['period_days'],
												'recruit_amount' => number_format($EROW["recruit_amount"]).'원',
												'total_invest_count' => $EROW["total_invest_count"],
												'total_invest_amount' => number_format($EROW["total_invest_amount"]).'원',
												'invest_percent' => $E['invest_percent'].'%',
												'progress_image_width' => $E['progress_image_width'],
												'simulation_url' => '',
												'sdate' => $print_sdate
											);

	}

	unset($E);

}


if($affect_num > 0) {

	if($page > ceil($affect_num / $size)) {
		$page = ceil($affect_num / $size);
	}
	$start_num = ($page - 1) * $size;

	// 일반 투자 상품 정보 추출

	if( preg_match("/(recruit_wait|recruit_started|recruit_finished)/i", $gubun) ) {
		$sql = "
			SELECT
				ASET.*
			FROM
				$sub_sql
			WHERE
				$where
			ORDER BY
				ASET.start_date DESC, ASET.idx DESC
			LIMIT
				$start_num, $size";
	}
	else {
		$sql = "
			SELECT
				A.idx, A.loan_start_date, A.open_datetime, A.start_datetime, A.end_datetime, A.state, A.recruit_amount,
				A.category, A.title, A.invest_return, A.withhold_tax_rate, A.invest_usefee, A.invest_usefee_type,
				A.invest_period, A.recruit_period_start, A.recruit_period_end, A.repay_type,
				A.evaluate_score1, A.evaluate_score2, A.evaluate_score3, A.evaluate_score4, A.evaluate_star1, A.evaluate_star2, A.evaluate_star3, A.evaluate_star4, A.evaluate_grade1, A.evaluate_grade2, A.evaluate_grade3, A.evaluate_grade4,
				A.main_image, A.display, A.purchase_guarantees, A.advanced_payment, A.success_example, A.popular_goods,
				(SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS total_invest_count,
				(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS total_invest_amount
			FROM
				$sub_sql
			WHERE
				$where
			ORDER BY
				A.start_date DESC, A.idx DESC
			LIMIT
				$start_num, $size";
	}

	$res  = sql_query($sql);
	$rows = $res->num_rows;
	for($i=0; $i<$rows; $i++) {
		$PROW = sql_fetch_array($res);
		//if($i==0) print_rr($PROW, 'font-size:11px');

		$P['detail_url'] = "/investment/investment.php?prd_idx=".$PROW['idx'];

		if($PROW['main_image']) {
			$P['main_image'] = (is_file(G5_DATA_PATH."/product/".$PROW['main_image'])) ? G5_URL."/data/product/".$PROW['main_image'] : "";
		}
		else {
			$P['main_image'] = "";
		}

		$P['sub_link_display'] = ($P['main_image']) ? 'none' : 'block';
		$P['sub_link_display'] = (preg_match("/mirror\.hellofundin\.co\.kr/i", $_SERVER['HTTP_HOST'])) ? 'none' : 'block';

		if($PROW['evaluate_score4']) {
			// 개정 등급 산정방식
			$level_score = round(($PROW["evaluate_score1"] + $PROW["evaluate_score2"] + $PROW["evaluate_score3"] + $PROW["evaluate_score4"]) / 5);
			$grade = $_gudge_grade_array[$level_score];
		}
		else if($PROW["evaluate_star1"] && $PROW["evaluate_star2"] && $PROW["evaluate_star3"]){
			// 기존 등급 산정방식
			$level_score = $PROW["evaluate_star1"] + $PROW["evaluate_star2"] + $PROW["evaluate_star3"];
			$grade = $_evaluation_grade_array[$level_score];
		}
		$P['grade_image']   = ($grade) ? G5_URL."/images/investment/level_".strtolower($grade).".png" : "";
		$P['grade_display'] = ($grade && preg_match("/mirror\.hellofunding\.co\.kr/i", $_SERVER['HTTP_HOST'])) ? 'block' : 'none';

		$P['purchase_guarantees_display'] = ($PROW['purchase_guarantees']=='Y') ? 'block' : 'none';
		$P['advanced_payment_display'] = ($PROW['advanced_payment']=='Y') ? 'block' : 'none';

		if($PROW["recruit_amount"] > 0) {
			$P['invest_percent'] = ($PROW["total_invest_amount"] > 0) ? round((($PROW["total_invest_amount"]/$PROW["recruit_amount"])*100), 2) : 0;
		}
		else{
			$P['invest_percent'] = 0;
		}

		$P['progress_image_width'] = ($P['invest_percent']) ? $P['invest_percent'].'%' : '0.2%';

		$PROW['invest_count']  = $PROW['total_invest_count'];
		$PROW['invest_amount'] = $PROW['total_invest_amount'];
		$PRDT_STATE = getProductStat($PROW['idx']);

		$P['invest_finished'] = (preg_match('/(B00|B01|B02)/', $PRDT_STATE['code']))  ? false : true;

		switch($PRDT_STATE['code']) {
			case "A01" :
				$P['invest_button_caption'] = $PRDT_STATE['code_str'];
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩성공";
				$P['cover_display']  = "block";
			break;
			case "A02" :
				$P['invest_button_caption'] = '원금상환완료';
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩성공";
				$P['cover_display']  = "block";
			break;
			case "A03" :
				$P['invest_button_caption'] = $PRDT_STATE['code_str'];
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩종료";
				$P['cover_display']  = "block";
			break;
			case "A04" :
				$P['invest_button_caption'] = $PRDT_STATE['code_str'];
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩종료";
				$P['cover_display']  = "block";
			break;
			case "A05" :
				$P['invest_button_caption'] = "원금상환완료";
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩성공";
				$P['cover_display']  = "block";
			break;
			case "A06" :
				$P['invest_button_caption'] = "투자금 반환 완료";
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩성공";
				$P['cover_display']  = "block";
			break;
			case "B00" :
				$P['invest_button_caption'] = '상품상세보기';
				$P['invest_button_class']   = 'btn_big_blue';
				$P['cover_caption']  = "";
				$P['cover_display']  = "none";
			break;
			case "B01" :
				$P['invest_button_caption'] = '상품상세보기';
				$P['invest_button_class']   = 'btn_big_green';
				$P['cover_caption']  = "";
				$P['cover_display']  = "none";
			break;
			case "B02" :
				$P['invest_button_caption'] = '상품상세보기';
				$P['invest_button_class']   = 'btn_big_blue';
				$P['cover_caption']  = "";
				$P['cover_display']  = "none";
			break;
			case "B03" :
				$P['invest_button_caption'] = $PRDT_STATE['code_str'];
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩성공";
				$P['cover_display']  = "block";
			break;
			case "B04" :
				$P['invest_button_caption'] = $PRDT_STATE['code_str'];
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "펀딩종료";
				$P['cover_display']  = "block";
			break;
			default    :
				$P['invest_button_caption'] = '상품상세보기';
				$P['invest_button_class']   = 'btn_big_gray';
				$P['cover_caption']  = "";
				$P['cover_display']  = "none";
			break;
		}

		$P['period_month'] = $PROW['invest_period'].'개월';

		$P['opacity'] = ($special_user && $PROW['display']=='N') ? "0.5" : "1";

		// 대출실행 완료건에 대하여 이자지급 차수 표시
		if($PROW['loan_start_date'] && $PROW['loan_start_date']!='0000-00-00') {
			$loan_start_date_day = (int)substr($PROW['loan_start_date'], -2);
			$total_repay_count = ((int)$loan_start_date_day < 5) ? $PROW['invest_period'] : $PROW['invest_period'] + 1; //총 지급횟수
			$PAIED = sql_fetch("SELECT MAX(turn) as max_turn FROM cf_product_success WHERE product_idx='".$PROW['idx']."' AND invest_give_state='Y'");
			$repay_count = ($PAIED['max_turn']) ? $PAIED['max_turn'] : 0;
			$repay_count_fcolor = ($repay_count) ? '#ff6633' : '#aaaaaa';

			$P['repay_count_tag'] = "<span style='color:$repay_count_fcolor'>$repay_count</span> / $total_repay_count";
		}

		$P['simulation_url'] = "/investment/simulation.php?prd_idx=".$PROW['idx'];

		$start_timestamp  = strtotime($PROW["start_datetime"]);
		$print_sdate = date('Y년 m월 d일', $start_timestamp);
		$print_sdate.= ' ' . get_yoil($PROW["start_datetime"]).'요일 ';
		$print_sdate.= (date(H, $start_timestamp) < 12) ? ' 오전' : ' 오후';
		$print_sdate.= date('H시', $start_timestamp);

		$DATA['list'][] = array(
												'opacity' => $P['opacity'],
												'detail_url' => $P['detail_url'],
												'sub_link_display' => $P['sub_link_display'],
												'grade_image' => $P['grade_image'],
												'grade_display' => $P['grade_display'],
												'main_image' => $P['main_image'],
												'purchase_guarantees_display' => $P['purchase_guarantees_display'],
												'advanced_payment_display' =>	$P['advanced_payment_display'],
												'code_str' => $PRDT_STATE['code_str'],
												'cover_caption' => $P['cover_caption'],
												'cover_display' => $P['cover_display'],
												'invest_button_caption' => $P['invest_button_caption'],
												'invest_button_class' => $P['invest_button_class'],
												'title' => $PROW['title'],
												'recruit_start_date' => date("Y년 m월 d일", strtotime($PROW["recruit_period_start"])),
												'invest_return_subject' => '투자자 수익률(연)',
												'invest_return' => (int)$PROW["invest_return"].'%',
												'invest_period' => $P['period_month'],
												'repay_count_tag' => $P['repay_count_tag'],
												'recruit_amount' => price_cutting($PROW["recruit_amount"]).'원',
												'total_invest_count' => $PROW["total_invest_count"],
												'total_invest_amount' => price_cutting($PROW["total_invest_amount"]).'원',
												'invest_percent' => $P['invest_percent'].'%',
												'progress_image_width' => $P['progress_image_width'],
												'simulation_url' => $P['simulation_url'],
												'sdate' => $print_sdate
											);

		unset($P);

	}

}


header('Cache-Control: no-cache');
header('Pragma: no-cache');

if($mode=='debug') {
	print_rr($DATA, 'font-size:11px;');
	exit;
}
else {

	header("Content-Type:application/json");

	if(!count($DATA['list'])) {
		$DATA = array(0 => 'empty');
	}

	$str = json_encode($DATA);
	echo $str;

}

exit;


?>