<?
include_once("_common.php");

while(list($key, $value)=each($_REQUEST)) { ${$key} = trim($value); }

switch($ca) {
	/////////////////////////////////////////////////////////////////////////////////////////////////
	// 수익현황
	/////////////////////////////////////////////////////////////////////////////////////////////////
	case "total_status" :

		///////////////////////////////
		// 이벤트 상품 수익현황 추출
		///////////////////////////////
		$event_sql = "
				SELECT
					(SELECT IFNULL(SUM(amount),0) FROM cf_event_product_invest WHERE epd.idx = product_idx AND epi.invest_state='Y') AS total_invest_amount,
					epi.idx, epi.amount, epi.member_idx, epi.product_idx, epi.invest_state,
					epd.title, epd.invest_period, epd.recruit_period_start, epd.recruit_period_end, epd.recruit_amount,
					epd.start_date, epd.end_date, epd.invest_return, epd.invest_usefee, epd.open_datetime, epd.start_datetime, epd.end_datetime ,
					epd.start_hour, epd.start_minute, epd.start_second, epd.end_hour, epd.end_minute, epd.end_second, epd.state, epd.invest_end_date
				FROM
					cf_event_product_invest epi
				INNER JOIN
					cf_event_product epd ON epi.product_idx=epd.idx
				WHERE
					epi.member_idx='{$mb_no}'
				ORDER BY
					epi.idx DESC";
		$event_res  = sql_query($event_sql);
		$event_rows = sql_num_rows($event_res);

		$EVENT_TOTAL['invest_count']    = 0;
		$EVENT_TOTAL['amount']          = 0;    // 투자금액합계
		$EVENT_TOTAL['repay_principal'] = 0;		// 상환원금
		$EVENT_TOTAL['repay_profit']    = 0;		// 상환수익금
		$event_profit_rate = 0;

		if($event_rows) {
			for($i=0; $i<$event_rows; $i++) {

				$EVENT_LIST = sql_fetch_array($event_res);

				if($EVENT_LIST["invest_state"]=="Y") {

					//이자 지급내역
					$EVENT_GIVE = sql_fetch("SELECT SUM(invest_amount) AS invest_amount FROM cf_event_product_give WHERE invest_idx='".$EVENT_LIST['idx']."' AND product_idx='".$EVENT_LIST['product_idx']."'");

					$EVENT_TOTAL['invest_count']  += 1;
					$EVENT_TOTAL['amount'] += $EVENT_LIST["amount"];  // 투자금액합계

					if($EVENT_LIST["state"]==2) {
						$event_repay_profit    = $EVENT_GIVE['invest_amount'] - $EVENT_LIST["amount"];		//수익금 (지급액 - 투자금액)
						$event_repay_principal = $EVENT_GIVE['invest_amount'] - $event_repay_profit;			//원금 (지급액 - 상환수익금)
					}

					$EVENT_TOTAL['repay_profit']    += $event_repay_profit;
					$EVENT_TOTAL['repay_principal'] += $event_repay_principal;
					$EVENT_TOTAL['repay_sum']       += $EVENT_GIVE['invest_amount'];

					$event_invest_return_total += $EVENT_LIST["invest_return"];

				}
				$EVENT_TOTAL['invest_count'] = $EVENT_TOTAL['invest_count'] + 1;
			}

			$event_profit_rate = ($EVENT_TOTAL['repay_profit'] / $EVENT_TOTAL['amount']) * 100;
		}


		///////////////////////////////
		// 일반 상품 수익현항 추출
		///////////////////////////////
		$sql = "
				SELECT
					(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE B.idx = product_idx AND invest_state='Y') AS total_invest_amount,
					A.idx, A.amount, A.member_idx, A.product_idx, A.invest_state,
					B.title, B.loan_interest_rate, B.loan_usefee, invest_period, B.recruit_period_start, B.recruit_period_end, B.recruit_amount,
					B.start_date, B.end_date, B.invest_return, B.invest_usefee, B.open_datetime, B.start_datetime, B.end_datetime , B.loan_start_date, B.loan_end_date,
					B.start_hour, B.start_minute, B.start_second, B.end_hour, B.end_minute, B.end_second, B.state, B.invest_end_date
				FROM
					cf_product_invest A
				INNER JOIN
					cf_product B  ON A.product_idx=B.idx
				WHERE 1
					AND A.member_idx='{$mb_no}'
					AND A.invest_state='Y'
				ORDER BY
					A.idx DESC";
		$res  = sql_query($sql);
		$rows = sql_num_rows($res);

		$TOTAL['invest_count']       = 0;		// 투자횟수
		$TOTAL['amount']             = 0;		// 기상환 투자원금
		$TOTAL['invest_return']      = 0;		// 수익률(이자율)
		$TOTAL['repay_principal']    = 0;		// 상환원금
		$TOTAL['repay_interest']     = 0;		// 상환이자
		$TOTAL['repay_interest_sum'] = 0;		// 지급총액
		$invest_profit_rate = 0;

		if($rows) {
			for($i=0; $i<$rows; $i++) {

				$LIST[$i] = sql_fetch_array($res);

				if($LIST[$i]["invest_state"]=="Y") {

					$TOTAL['invest_count'] += 1;
					$TOTAL['amount']       += $LIST[$i]['amount'];

					//이자 지급내역
					$GIVE = sql_fetch("SELECT SUM(interest) AS interest FROM cf_product_give WHERE invest_idx='".$LIST[$i]['idx']."'");
					$TOTAL['repay_interest'] += $GIVE['interest'];

					//투자마감상품
					if( in_array($LIST[$i]["state"], array('2','5')) ) {
						$TOTAL['repay_principal'] += $LIST[$i]['amount'];
					}
					$TOTAL['repay_sum'] = $TOTAL['repay_principal'] + $TOTAL['repay_interest'];
				}

			}

			$invest_profit_rate = ($TOTAL['repay_interest'] / $TOTAL['amount']) * 100;

		}

?>
<table width="100%" style="margin-bottom:15px;">
	<tr>
		<th scope="col" class="text-center">구분</th>
		<th scope="col" class="text-center">전체투자금액</th>
		<th scope="col" class="text-center">전체상환원금</th>
		<th scope="col" class="text-center">전체지급이자</th>
		<th scope="col" class="text-center">지급합계</th>
		<th scope="col" class="text-center">투자수익률</th>
	</tr>
	<tr>
		<td class="text-center">일반투자</td>
		<td class="text-right"><?=number_format($TOTAL['amount'])?> 원</td>
		<td class="text-right"><?=number_format($TOTAL['repay_principal'])?> 원</td>
		<td class="text-right"><?=number_format($TOTAL['repay_interest'])?> 원</td>
		<td class="text-right"><?=number_format($TOTAL['repay_sum'])?> 원</td>
		<td class="text-right"><?=sprintf("%0.2f", $invest_profit_rate)?> %</td>
	</tr>
	<tr>
		<td class="text-center">이벤트투자</td>
		<td class="text-right"><?=number_format($EVENT_TOTAL['amount'])?> 원</td>
		<td class="text-right"><?=number_format($EVENT_TOTAL['repay_principal'])?> 원</td>
		<td class="text-right"><?=number_format($EVENT_TOTAL['repay_profit'])?> 원</td>
		<td class="text-right"><?=number_format($EVENT_TOTAL['repay_sum'])?> 원</td>
		<td class="text-right"><?=sprintf("%0.2f", $event_profit_rate)?> %</td>
	</tr>
</table>
<?

	break;

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// 일반 상품 투자내역
	/////////////////////////////////////////////////////////////////////////////////////////////////
	case 'invest_log' :

		$sql = "
				SELECT
					COUNT(*) AS invest_count
				FROM
					cf_product_invest pi
				INNER JOIN
					cf_product pd  ON pi.product_idx = pd.idx
				WHERE 1
					AND pi.member_idx='$mb_no'
					-- AND pi.invest_state='Y'";
		$r = sql_fetch($sql);

		$total_count = $r['invest_count'];
		$page_rows = 10;
		$total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산
		if(!$page) $page = 1;
		$from_record = ($page - 1) * $page_rows; // 시작 열을 구함


		$sql = "
				SELECT
					(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE pd.idx = product_idx AND invest_state='Y') AS total_invest_amount,pi.invest_state,
					pi.idx, pi.amount, pi.member_idx, pi.product_idx, pi.insert_date,pi.syndi_id,
					pd.category, pd.mortgage_guarantees, pd.title, pd.loan_interest_rate, pd.loan_usefee, invest_period, pd.recruit_period_start, pd.recruit_period_end, pd.recruit_amount,
					pd.start_date, pd.end_date, pd.invest_return, pd.invest_usefee, pd.open_datetime, pd.start_datetime, pd.end_datetime ,
					pd.loan_start_date, pd.loan_end_date,
					pd.start_hour, pd.start_minute, pd.start_second, pd.end_hour, pd.end_minute, pd.end_second, pd.state, pd.invest_end_date
				FROM
					cf_product_invest pi
				INNER JOIN
					cf_product pd  ON pi.product_idx=pd.idx
				WHERE 1
					AND pi.member_idx='$mb_no'
					-- AND pi.invest_state='Y'
				ORDER BY
					pi.idx DESC LIMIT ".$from_record.", ".$page_rows;
		$res  = sql_query($sql);
		$rows = sql_num_rows($res);

		for($i=0; $i<$rows; $i++) {

			$LIST[$i] = sql_fetch_array($res);

			$sql2 = "SELECT amount, invest_state, insert_date, insert_time, IFNULL(cancel_date, 0) AS cancel_date,syndi_id FROM cf_product_invest_detail WHERE invest_idx='".$LIST[$i]['idx']."' ORDER BY idx DESC";
			$res2 = sql_query($sql2);
			$rows2 = sql_num_rows($res2);
			for($j=0; $j<$rows2; $j++) {
				$DLIST = sql_fetch_array($res2);
				$LIST[$i]['DETAIL'][$j] = $DLIST;
			}

		}

?>
<style>
div.overhidden {width:100%;height:16px;line-height:16px;overflow:hidden;}
</style>
<table width="100%" style="font-size:12px;margin-bottom:15px;">
	<colgroup>
		<col style="width:5%">
		<col style="width:9%">
		<col style="width:%">
		<col style="width:9%">
		<col style="width:9%">
		<col style="width:12%">
		<!--<col style="width:12%">//-->
		<col style="width:8%">
		<col style="width:6%">
		<col style="width:6%">
		<col style="width:6%">
		<col style="width:10%">
	</colgroup>
	<tr>
		<th scope="col" style="text-align:center">No</th>
		<th scope="col" style="text-align:center">카테고리</th>
		<th scope="col" style="text-align:center">상품명</th>
		<th scope="col" style="text-align:center">투자금액</th>
		<th scope="col" style="text-align:center">진행현황</th>
		<!--<th scope="col" style="text-align:center">모집기간</th>//-->
		<th scope="col" style="text-align:center">실 투자기간</th>
		<th scope="col" style="text-align:center">투자일자</th>
		<th scope="col" style="text-align:center">투자처</th>
		<th scope="col" style="text-align:center">연 이자율</th>
		<th scope="col" style="text-align:center">플랫폼 이용료율</th>
		<th scope="col" style="text-align:center"></th>
	</tr>
<?
		if($rows) {

			$list_num = $total_count - ($page - 1) * $page_rows;
			for($i=0; $i<$rows; $i++) {

				$print_category = "";
				if($LIST[$i]['category']=='3') {
					$print_category = "확정매출채권";
				}
				else if($LIST[$i]['category']=='2') {
					$print_category = "부동산";
					$print_category.= ($LIST[$i]['mortgage_guarantees']=='1') ? '(주택담보)' : '(PF)';
				}
				else {
					$print_category = "동산";
				}


				$product_state = get_product_state(
													 $LIST[$i]["recruit_period_start"],
													 $LIST[$i]["recruit_period_end"],
													 preg_replace("/-|:| /", "", $LIST[$i]['open_datetime']),
													 preg_replace("/-|:| /", "", $LIST[$i]['start_datetime']),
													 preg_replace("/-|:| /", "", $LIST[$i]['end_datetime']),
													 $LIST[$i]["state"],
													 $LIST[$i]["recruit_amount"],
													 $LIST[$i]["total_invest_amount"],
													 preg_replace("/-/", "", $LIST[$i]["invest_end_date"]));

				$loan_date = ($LIST[$i]['loan_start_date']=='0000-00-00' && $LIST[$i]['loan_end_date']=='0000-00-00') ? "" : preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']);

?>
	<tr>
		<td style="text-align:center"><?=$list_num?></td>
		<td style="text-align:center"><div class="overhidden"><?=$print_category?></div></td>
		<td style=""><div class="overhidden"><?=$LIST[$i]['title']?></div></td>
		<td style="text-align:right"><?=number_format($LIST[$i]['amount'])?>원</td>
		<td style="text-align:center"><?=($LIST[$i]['invest_state'] =="N") ? "<b style='color:#D9534F'>취소</b>" : "<b style='color:#337AB7'>".$product_state."</b>"; ?></td>
		<!--<td style="text-align:center"><?=preg_replace("/-/", ".", $LIST[$i]['recruit_period_start'])?> ~ <?=preg_replace("/-/", ".", $LIST[$i]['recruit_period_end'])?></td>//-->
		<td style="text-align:center"><?=$loan_date?></td>
		<td style="text-align:center"><?=$LIST[$i]['insert_date']?></td>
		<td style="text-align:center"><?=$LIST[$i]['syndi_id']?></td>
		<td style="text-align:center"><?=$LIST[$i]['invest_return']?>%</td>
		<td style="text-align:center"><?=$LIST[$i]['invest_usefee']?>%</td>
		<td style="text-align:center"><a id="btn_detail<?=$i?>" class="btn btn-sm btn-<?=($LIST[$i]['invest_state'] =="N")?'danger':'primary';?>" onClick="startToggle('#btn_detail<?=$i?>','#detail_view<?=$i?>');">상세내역 <span class="glyphicon glyphicon-list"></span></a></td>
	</tr>
	<!-- 투자내역 상세리스트 //-->
	<tr>
		<td colspan="12" style="padding:0;">

			<div id="detail_view<?=$i?>" style="width:100%;display:none;">
				<table style="width:100%">
					<colgroup>
						<col style="width:5%">
						<col style="width:20%">
						<col style="width:10%">
						<col style="width:9%">
						<col style="width:15%">
						<col style="width:15%">
						<col style="width:8%">
						<col style="width:8%">
						<col style="width:10%">
					</colgroup>
					<tr style='text-align:center'>
						<td class="tmp"></td>
						<td class="tmp"></td>
						<td class="tmp">세부금액</td>
						<td class="tmp">투입현황</td>
						<td class="tmp">투자일시</td>
						<td class="tmp">취소일자</td>
						<td class="tmp">투자처</td>
						<td class="tmp"></td>
						<td class="tmp"></td>
					</tr>
<?
				$detail_count = count($LIST[$i]['DETAIL']);
				for($j=0,$k=1; $j<$detail_count; $j++,$k++) {

					if($LIST[$i]['DETAIL'][$j]['invest_state']=='Y') {
						$tdClass      = "tmp1";
						$invest_state = "정상";
						$cancel_date  = "N/A";
					}
					else {
						$tdClass      = "tmp1_1";
						$invest_state = "취소";
						$cancel_date  = substr($LIST[$i]['DETAIL'][$j]['cancel_date'], 0, 16);
					}

					$tr_style = ($k < $detail_count) ? "border-bottom:1px dotted #CCCCCC" : "border-bottom:1px solid #FFFFFF";

					echo "
							<tr style='text-align:center;$tr_style'>
								<td class='$tdClass'></td>
								<td class='$tdClass'></td>
								<td class='$tdClass' style='text-align:right;'>".number_format($LIST[$i]['DETAIL'][$j]['amount'])."원</td>
								<td class='$tdClass'>".$invest_state."</td>
								<td class='$tdClass'>".preg_replace("/-/", ".", $LIST[$i]['DETAIL'][$j]['insert_date'])." ".substr($LIST[$i]['DETAIL'][$j]['insert_time'], 0, 5)."</td>
								<td class='$tdClass'>".$cancel_date."</td>
								<td class='$tdClass'>".$LIST[$i]['DETAIL'][$j]['syndi_id']."</td>
								<td class='$tdClass'></td>
								<td class='$tdClass'></td>
							</tr>\n";
				}
?>
				</table>
			</div>

		</td>
	</tr>
<?
				$list_num--;
			}
		}
		else {
			echo "					<tr><td colspan='10' style='text-align:center'>투자내역이 없습니다.</td></tr>\n";
		}
?>
</table>

<div id="paging_span" style="width:100%; text-align: center;">
	<? paging($total_count, $page, $page_rows); ?>
</div>
<?

	break;

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// 이벤트 상품 투자내역
	/////////////////////////////////////////////////////////////////////////////////////////////////
	case 'event_invest_log' :

		$event_sql = "
				SELECT
					(SELECT IFNULL(SUM(amount),0) FROM cf_event_product_invest WHERE epd.idx = product_idx AND epi.invest_state='Y') AS total_invest_amount,
					epi.idx, epi.amount, epi.member_idx, epi.product_idx, epi.invest_state,
					epd.title, epd.invest_period, epd.recruit_period_start, epd.recruit_period_end, epd.recruit_amount,
					epd.start_date, epd.end_date, epd.invest_return, epd.invest_usefee, epd.open_datetime, epd.start_datetime, epd.end_datetime ,
					epd.start_hour, epd.start_minute, epd.start_second, epd.end_hour, epd.end_minute, epd.end_second, epd.state, epd.invest_end_date
				FROM
					cf_event_product_invest epi
				INNER JOIN
					cf_event_product epd ON epi.product_idx=epd.idx
				WHERE
					epi.member_idx='{$mb_no}'
				ORDER BY
					epi.idx DESC";
		$event_res  = sql_query($event_sql);
		$event_rows = sql_num_rows($event_res);

		for($i=0; $i<$event_rows; $i++) {
			$EVENT_LIST[$i] = sql_fetch_array($event_res);
		}


?>
	<table width="100%" style="margin-bottom:15px;">
		<tr>
			<th scope="col" class="text-center">No</th>
			<th scope="col" class="text-center">상품명</th>
			<th scope="col" class="text-center">투자금액</th>
			<th scope="col" class="text-center">진행현황</th>
			<th scope="col" class="text-center">투자기간</th>
			<th scope="col" class="text-center">이자율(회)</th>
			<th scope="col" class="text-center">플랫폼 이용료율</th>
		</tr>
<?
		if($event_rows) {

			$list_num = $event_rows;
			for($i=0; $i<$event_rows; $i++) {

				$event_product_state = get_product_state(
																$EVENT_LIST[$i]["recruit_period_start"],
																$EVENT_LIST[$i]["recruit_period_end"],
																preg_replace("/-|:| /", "", $EVENT_LIST[$i]['open_datetime']),
																preg_replace("/-|:| /", "", $EVENT_LIST[$i]['start_datetime']),
																preg_replace("/-|:| /", "", $EVENT_LIST[$i]['end_datetime']),
																$EVENT_LIST[$i]["state"],
																$EVENT_LIST[$i]['recruit_amount'],
																$EVENT_LIST[$i]['total_invest_amount'],
																preg_replace("/-/", "", $EVENT_LIST[$i]["invest_end_date"]));

?>
		<tr>
			<td class="text-center"><?=$list_num?></td>
			<td class="text-center"><?=$EVENT_LIST[$i]['title']?></td>
			<td class="text-center"><?=number_format($EVENT_LIST[$i]['amount'])?>원</td>
			<td class="text-center"><?=($EVENT_LIST[$i]['invest_state'] =="N") ? "<b style='color:#D9534F'>취소</b>" : "<b style='color:#337AB7'>".$event_product_state."</b>"; ?></td>
			<td class="text-center"><?=preg_replace("/-/", ".", $EVENT_LIST[$i]['recruit_period_start'])?> ~ <?=preg_replace("/-/", ".", $EVENT_LIST[$i]['recruit_period_end'])?></td>
			<td class="text-center"><?=$EVENT_LIST[$i]['invest_return'] ?>%</td>
			<td class="text-center"><?=$EVENT_LIST[$i]['invest_usefee'] ?>%</td>
		</tr>
<?
				$list_num--;
			}
		}
		else {
			echo "					<tr><td colspan='8' style='text-align:center'>투자내역이 없습니다.</td></tr>\n";
		}
?>
	</table>
<?

	break;
}

?>