<?

$prdtKwd = sql_real_escape_string($prdtKwd);
$mbKwd   = sql_real_escape_string($mbKwd);

$STATE['1A']	= " AND C.end_datetime > NOW() AND C.invest_end_date=''";	// 투자금 모집중
$STATE['1B']  = " AND C.state='' AND C.invest_end_date!=''";																					// 투자금 모집완료
$STATE['3']   = " AND (C.state='3' OR (C.end_datetime < NOW() AND C.invest_end_date=''))";						// 투자금 모집실패
$STATE['1']   = " AND C.state='1'";																																		// 이자상환중
$STATE['2']   = " AND C.state='2'";																																		// 상품마감(정상상환)
$STATE['5']   = " AND C.state='5'";																																		// 상품마감(중도상환)
$STATE['4']   = " AND C.state='4'";																																		// 부실
$STATE['6']   = " AND C.state='6'";																																		// 대출취소(기표전)
$STATE['7']   = " AND C.state='7'";																																		// 대출취소(기표후)
$STATE['2+5'] = " AND C.state IN('2','5')";																														// 상품마감(전체)
$STATE['6+7'] = " AND C.state IN('6','7')";																														// 대출취소(전체)
$STATE['8']   = " AND C.state='8'";																																		// 연체
$STATE['9']   = " AND C.state='9'";																																		// 부도(상환불가)

$CATEGORY['1']  = " AND C.category='1'";
$CATEGORY['2']  = " AND C.category='2'";
$CATEGORY['2A'] = " AND C.category='2' AND C.mortgage_guarantees=''";
$CATEGORY['2B'] = " AND C.category='2' AND C.mortgage_guarantees='1'";
$CATEGORY['3']  = " AND C.category='3'";
$CATEGORY['3A'] = " AND C.category='3' AND C.category2='1'";
$CATEGORY['3B'] = " AND C.category='3' AND C.category2='2'";


$where = "1=1 AND A.is_drop='' AND C.isTest='' AND C.ib_product_regist='Y' ";
if($state) $where.= $STATE[$state];
if($category) $where.= $CATEGORY[$category];
if($dateFld) {
	if($sdate && $edate) {
		$where.= " AND LEFT({$dateFld},10) BETWEEN '{$sdate}' AND '{$edate}'";
	}
	else {
		if($sdate) $where.= " AND LEFT({$dateFld},10) <='{$sdate}'";
		if($edate) $where.= " AND LEFT({$dateFld},10) >='{$edate}'";
	}
}
if($prdtFld && $prdtKwd) {
	if( in_array($prdtFld, array('idx','start_num')) ) {
		if( preg_match("/\,/", $prdtKwd) ) {
			$where.= " AND C.{$prdtFld} IN(".preg_replace("/( )/", "", $prdtKwd).") ";
		}
		else {
			$where.= " AND C.{$prdtFld} = '".$prdtKwd."' ";
		}
	}
	else if($prdtFld == 'title') $where.= " AND C.{$prdtFld} LIKE '%".$prdtKwd."%' ";
	else if($prdtFld == 'insert_date') $where.= " AND C.{$prdtFld} = '".$prdtKwd."') ";
}

if($mbFld && $mbKwd) {
	if( in_array($mbFld, array('D.mb_no','D.mb_id')) ) {
		if( preg_match("/\,/", $mbKwd) ) {
			$where.= " AND {$mbFld} IN(".preg_replace("/( )/", "", $mbKwd).") ";
		}
		else {
			$where.= " AND {$mbFld}='".$mbKwd."' ";
		}
	}
	else if($mbFld=='mb_title') {
		$where.= " AND (D.mb_name LIKE '%".$mbKwd."%' OR D.mb_co_name LIKE '%".$mbKwd."%') ";
	}
}

//투자기간 검색
if($invest_period) {
	if($invest_period=='short') {
		$where.= " AND (C.invest_period = '1' AND C.invest_days > 0) ";
	}
	else if($invest_period=='long') {
		$where.= " AND C.invest_period > '24'";
	}
	else {
		$where.= " AND C.invest_period = '".$invest_period."'";
	}
}

// 수취방식
if($loan_usefee_type) {
	$where.= " AND A.loan_usefee_type = '".$loan_usefee_type."'";
}

// 수취여부	( deposit_amt가 존재하고 collect_ok 가 1 인 데이터 )
if($receive_status) {
	if($receive_status=='B') {
		// 미수취
		$where.= " AND (A.collect_ok = '' AND A.deposit_amt = 0)";
	}
	if($receive_status=='A') {
		// 수취완료전체
		$where.= " AND A.collect_ok = '1'";
	}
	if($receive_status=='A0') {
		// 수취완료: 정상수취
		$where.= " AND (A.collect_ok = '1' AND A.deposit_amt = A.schedule_amt)";
	}
	else if($receive_status=='A1') {
		// 수취완료: 초과입금
		$where.= " AND (A.collect_ok = '1' AND A.deposit_amt > A.schedule_amt)";
	}
	else if($receive_status=='A2') {
		// 수취완료: 부족입금
		$where.= " AND (A.collect_ok = '1' AND A.deposit_amt < A.schedule_amt AND A.deposit_amt > 0)";
	}
}

// 계산서 밝브여부
if($taxInvoiceOk) {
	$where.= ($taxInvoiceOk=='Y') ? " AND A.mgtKey != ''" : " AND A.mgtKey = ''";
}


$sql = "
	SELECT
		COUNT(A.idx) AS cnt
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_loanerTaxinvoiceLog B  ON A.mgtKey=B.mgtKey
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	LEFT JOIN
		g5_member D  ON C.loan_mb_no=D.mb_no
	WHERE
		$where";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows        = ($_REQUEST['rows']) ? $_REQUEST['rows'] : 20;		// 페이지당 나열 수
$total_page  = ceil($total_count / $rows);							// 전체페이지
$page        = ($page > 0) ? $page : 1;
$from_record = ($page - 1) * $rows;											// 시작 열
$num         = $total_count - $from_record;

$sql = "
	SELECT
		A.idx, A.product_idx, A.loan_usefee_type, A.turn, A.schedule_date, A.bank_code, A.acct_no, A.depositor,
		A.schedule_amt, A.loan_usefee_amt, A.commission_fee_amt, A.deposit_amt, A.repay_amt, A.return_amt, A.return_ok, A.supply_price, A.tax,
		A.mgtKey, A.collect_ok, A.collect_date, A.rdate,
		B.req_date,
		C.category, C.category2, C.mortgage_guarantees, C.start_num, C.state, C.title, C.recruit_amount, C.loan_interest_rate, C.loan_start_date, C.loan_end_date, C.invest_period, C.invest_days,
		C.loan_usefee, C.loan_usefee_repay_count, C.ib_trust,
		(SELECT commission_fee FROM cf_product_container WHERE product_idx=A.product_idx) AS commission_fee,
		D.mb_no, D.mb_id, D.mb_name, D.mb_co_name, D.member_type, D.mb_hp
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_loanerTaxinvoiceLog B  ON A.mgtKey=B.mgtKey
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	LEFT JOIN
		g5_member D  ON C.loan_mb_no=D.mb_no
	WHERE
		$where
	ORDER BY
		C.loan_start_date DESC,
		A.idx DESC,
		A.turn ASC
	LIMIT
		$from_record, $rows";
//print_rr($sql);
$result = sql_query($sql);
$rcount = sql_num_rows($result);

$SUM = array(
	'schedule_amt'      => 0,
	'loan_usefee_amt'   => 0,
	'commission_fee_amt'=> 0,
	'deposit_amt'       => 0,
	'repay_amt'         => 0,
	'return_amt'        => 0,
	'supply_price'      => 0,
	'tax'               => 0
);

for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);
	$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'], false);

	$LIST[$i]['print_category'] = '';
	if($LIST[$i]['category']=='2') {
		$LIST[$i]['print_category'].= '부동산';
		$LIST[$i]['print_category'].= ($LIST[$i]['mortgage_guarantees']=='1') ? '>주택담보' : '>PF';
	}
	else if($LIST[$i]['category']=='3') {
		$LIST[$i]['print_category'].= '헬로페이';
		$LIST[$i]['print_category'].= ($LIST[$i]['category2']=='1') ? '>소상공인' : '>면세점';
	}
	else {
		$LIST[$i]['print_category'].= '동산';
	}

	$LIST[$i]['loan_started'] = ( in_array($LIST[$i]['state'], array('1','2','5','8','9')) ) ? 1 : 0;

	// 특별처리상품 플래그 (초기상품중 종료일이 5일 이전일때 이전회차와 최종상환회차를 동일회차로 처리한 상품 구분)
	$exceptionProduct = ($LIST[$i]['product_idx'] < 162  && $LIST[$i]['ib_trust']=='N' && substr($LIST[$i]['loan_end_date'],-2) <= '05') ? 1 : 0;
	$shortTermProduct = ($LIST[$i]['invest_days'] > 0) ? 1 : 0;

	$LIST[$i]['repay_count']  = ($LIST[$i]['loan_started']) ? repayTurnCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date'], $exception_product, $shortTermProduct) : '';
	$LIST[$i]['total_days']   = ($LIST[$i]['loan_started']) ? repayDayCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date']) : '';

	$LIST[$i]['diff_amt'] = max(0, ($LIST[$i]['deposit_amt']-$LIST[$i]['repay_amt']));


	$SUM['schedule_amt']       += $LIST[$i]['schedule_amt'];
	$SUM['loan_usefee_amt']    += $LIST[$i]['loan_usefee_amt'];
	$SUM['commission_fee_amt'] += $LIST[$i]['commission_fee_amt'];
	$SUM['deposit_amt']        += $LIST[$i]['deposit_amt'];
	$SUM['repay_amt']          += $LIST[$i]['repay_amt'];
	$SUM['return_amt']         += $LIST[$i]['return_amt'];
	$SUM['diff_amt']           += $LIST[$i]['diff_amt'];
	$SUM['supply_price']       += $LIST[$i]['supply_price'];
	$SUM['tax']                += $LIST[$i]['tax'];

}
$list_count = count($LIST);
//print_rr($LIST,'font-size:12px');

?>

<!-- 검색영역 START -->
<form name="frmSearch" method="get" class="form-horizontal">
	<ul style="list-style:none;display:inline-block; padding:0; margin:8px 0 4px 0;">
		<li style="float:left;">
			<select name="state" class="form-control input-sm">
				<option value="">::진행상태::</option>
				<!--<option value="1A" <?=($_REQUEST['state']=='1A') ? 'selected' : ''; ?>>투자금 모집중</option>-->
				<!--<option value="1B" <?=($_REQUEST['state']=='1B') ? 'selected' : ''; ?>>투자금 모집완료</option>-->
				<!--<option value="3" <?=($_REQUEST['state']=='3') ? 'selected' : ''; ?>>투자금 모집실패</option>-->
				<option value="1" <?=($_REQUEST['state']=='1') ? 'selected' : ''; ?>>이자상환중</option>
				<option value="2+5" <?=($_REQUEST['state']=='2+5') ? 'selected' : ''; ?>>상품마감(전체)</option>
				<option value="2" <?=($_REQUEST['state']=='2') ? 'selected' : ''; ?>>상품마감(정상상환)</option>
				<option value="5" <?=($_REQUEST['state']=='5') ? 'selected' : ''; ?>>상품마감(중도상환)</option>
				<option value="8" <?=($_REQUEST['state']=='8') ? 'selected' : ''; ?>>연체</option>
				<option value="4" <?=($_REQUEST['state']=='4') ? 'selected' : ''; ?>>부실</option>
				<option value="6+7" <?=($_REQUEST['state']=='6+7') ? 'selected' : ''; ?>>대출취소(전체)</option>
				<option value="6" <?=($_REQUEST['state']=='6') ? 'selected' : ''; ?>>대출취소(기표전)</option>
				<option value="7" <?=($_REQUEST['state']=='7') ? 'selected' : ''; ?>>대출취소(기표후)</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;">
			<select name="category" class="form-control input-sm">
				<option value="">::상품군::</option>
				<option value="2" <?=($category=='2') ? 'selected' : ''; ?>>부동산</option>
				<option value="2A" <?=($category=='2A') ? 'selected' : ''; ?>> - PF</option>
				<option value="2B" <?=($category=='2B') ? 'selected' : ''; ?>> - 주택담보</option>
				<option value="1" <?=($category=='1') ? 'selected' : ''; ?>>동산</option>
				<option value="3" <?=($category=='3') ? 'selected' : ''; ?>>헬로페이</option>
				<option value="3A" <?=($category=='3A') ? 'selected' : ''; ?>> - 소상공인</option>
				<option value="3B" <?=($category=='3B') ? 'selected' : ''; ?>> - 면세점</option>
			</select>
		</li>
		<!--
		<li style="float:left;margin-left:4px;">
			<select name="invest_period" class="form-control input-sm">
				<option value=''>::대출설정기간::</option>";
				<option value='short' <? if($invest_period=='short')echo 'selected'; ?>>1개월 미만</option>";
			<?
			for($i=1; $i<=24; $i++) {
				$selected = ($i==$invest_period) ? 'selected' : '';
				echo "<option value='".$i."' $selected>".$i."개월</option>\n";
			}
			?>
				<option value='long' <? if($invest_period=='long')echo 'selected'; ?>>24개월 초과</option>";
			</select>
		</li>
		//-->
		<li style="float:left;margin-left:4px;">
			<select name="loan_usefee_type" class="form-control input-sm">
				<option value=''>::수수료 수취방식::</option>";
				<option value='A' <? if($loan_usefee_type=='A')echo 'selected'; ?>>후취 (분할납부)</option>
				<option value='B' <? if($loan_usefee_type=='B')echo 'selected'; ?>>선취 (일시납부)</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;">
			<select name="receive_status" class="form-control input-sm">
				<option value=''>::수수료 수취여부::</option>";
				<option value='B' <? if($receive_status=='B')echo 'selected'; ?>>미수취</option>
				<option value='A' <? if($receive_status=='A')echo 'selected'; ?>>수취완료전체</option>
				<option value='A0' <? if($receive_status=='A0')echo 'selected'; ?>> - 정상수취</option>
				<option value='A1' <? if($receive_status=='A1')echo 'selected'; ?>> - 초과입금</option>
				<option value='A2' <? if($receive_status=='A2')echo 'selected'; ?>> - 부족입금</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;">
			<select name="taxInvoiceOk" class="form-control input-sm">
				<option value=''>::계산서 발급여부::</option>";
				<option value='Y' <? if($taxInvoiceOk=='Y')echo 'selected'; ?>>발급완료</option>
				<option value='N' <? if($taxInvoiceOk=='N')echo 'selected'; ?>>미발급</option>
			</select>
		</li>
	</ul><br>
	<ul style="list-style:none;display:inline-block; width:100%; padding:0; margin:0 0 4px 0">
		<li style="float:left;">
			<select name="dateFld" class="form-control input-sm">
				<option value="">::데이트필드선택::</option>
				<option value="A.schedule_date" <?=($dateFld=='A.schedule_date') ? 'selected' : '';?>>납입예정일</option>
				<option value="A.collect_date" <?=($dateFld=='A.collect_date') ? 'selected' : '';?>>수취일</option>
				<option value="C.loan_start_date" <?=($dateFld=='C.loan_start_date') ? 'selected' : '';?>>대출실행일</option>
				<option value="C.loan_end_date" <?=($dateFld=='C.loan_end_date') ? 'selected' : '';?>>대출종료일</option>
				<option value="B.req_date" <?=($dateFld=='B.req_date') ? 'selected' : '';?>>계산서발급요청일</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;"><input type="text" name="sdate" value="<?=($dateFld)?$sdate:'';?>" class="form-control input-sm datepicker" readonly></li>
		<li style="float:left;margin-left:4px;"> ~ </li>
		<li style="float:left;margin-left:4px;"><input type="text" name="edate" value="<?=($dateFld)?$edate:'';?>" class="form-control input-sm datepicker" readonly></li>
	</ul>

	<ul style="list-style:none;display:inline-block; width:100%; padding:0; margin:0 0 8px 0">
		<li style="float:left;">
			<select name="prdtFld" class="form-control input-sm">
				<option value="">::상품필드선택::</option>
				<option value="idx" <?=($prdtFld=='idx') ? 'selected' : ''; ?>>품번</option>
				<option value="start_num" <?=($prdtFld=='start_num') ? 'selected' : ''; ?>>호번</option>
				<option value="title" <?=($prdtFld=='title') ? 'selected' : ''; ?>>상품명</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;"><input type="text" name="prdtKwd" value="<?=($prdtKwd) ? $prdtKwd : '';?>" placeholder="상품검색어" class="form-control input-sm"></li>
		<li style="float:left;margin-left:20px;">
			<select name="mbFld" class="form-control input-sm">
				<option value="">::차주필드선택::</option>
				<option value="D.mb_no" <?=($mbFld=='D.mb_no') ? 'selected' : ''; ?>>차주번호</option>
				<option value="D.mb_id" <?=($mbFld=='D.mb_id') ? 'selected' : ''; ?>>아이디</option>
				<option value="mb_title" <?=($mbFld=='mb_title') ? 'selected' : ''; ?>>법인명.성명</option>
			</select>
		</li>
		<li style="float:left;margin-left:4px;"><input type="text" name="mbKwd" value="<?=($mbKwd) ? $mbKwd : '';?>" placeholder="차주검색어" class="form-control input-sm"></li>
		<li style="float:left;margin-left:4px;"><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
		<li style="float:left;margin-left:4px;"><button type="button" onClick="location.href='<?=$_SERVER['PHP_SELF']?>';" class="btn btn-sm btn-default">초기화</button></li>
		<li style="float:left;margin-left:8px;">
			<select name="rows" class="form-control input-sm" onChange="document.frmSearch.submit();">
				<option value="10" <?=($_REQUEST['rows']=='10') ? 'selected' : ''; ?>>10개씩</option>
				<option value="20" <?=($_REQUEST['rows']=='20') ? 'selected' : ''; ?>>20개씩</option>
				<option value="50" <?=($_REQUEST['rows']=='50') ? 'selected' : ''; ?>>50개씩</option>
				<option value="100" <?=($_REQUEST['rows']=="" || $_REQUEST['rows']=='100') ? 'selected' : ''; ?>>100개씩</option>
				<option value="300" <?=($_REQUEST['rows']=='300') ? 'selected' : ''; ?>>300개씩</option>
				<option value="500" <?=($_REQUEST['rows']=='500') ? 'selected' : ''; ?>>500개씩</option>
			</select>
		</li>
		<li style="float:left;margin-left:20px;"><button type="button" class="btn btn-sm btn-primary" onClick="location.href='?mode=new';">수취정보등록</button></li>
		<li style="float:left;margin-left:20px;"><button type='button' id='button_a' class='btn btn-sm btn-danger' onClick="loanerTaxinvoiceRequest();">세금계산서.현금영수증 일괄발행</button></li>
		<li style="float:left;margin-left:20px;"><button type="button" class="btn btn-sm btn-success" id="exelDownload">엑셀 다운로드</button></li>
	</ul>
</form>
<!-- 검색영역 E N D -->

<div style="float:right; display:inline-block; line-height:20px;width:100%;">
	<span style="float:left;margin-left:8px;">▣ 등록 : <?=number_format($total_count);?>건</span>
</div>

<table id="dataList" class="table-bordered" style="font-size:12px">
	<colgroup>
		<col style="width:3%" alt="체크박스">
		<col style="width:5%" alt="호번">
		<col style="width:5%" alt="차주번호">

		<col style="width:6%" alt="플랫폼이용료">
		<col style="width:6%" alt="중개수수료">
		<col style="width:4%" alt="수취방식">
		<col style="width:4%" alt="분납회수">

		<col style="width:4%" alt="납입회차">
		<col style="width:5%" alt="납입예정일">
		<col style="width:6.5%" alt="수취예정금액 수취금액">
		<col style="width:5%" alt="수취일">
		<col style="width:5.5%" alt="반환액">
		<col style="width:4%" alt="반환여부">
		<col style="width:5.5%" alt="정산차액">
		<col style="width:6.5%" alt="최종정산액">

		<col style="width:5%" alt="공급가">
		<col style="width:5%" alt="세액">
		<col style="width:5%" alt="발급구분">

		<col style="width:5%" alt="등록일">
		<col style="width:%" alt="버튼">
	</colgroup>
	<thead style="font-size:13px">
		<tr align="center" style="background:#F8F8EF">
			<td rowspan="2" style="padding:3px 0"><input type="checkbox" id="chkall" value="1"></td>
			<td rowspan="2" style="padding:3px 0">호번</td>
			<td rowspan="2" style="padding:3px 0">차주번호</td>

			<td colspan="4" style="padding:3px 0">수수료</td>
			<td colspan="8" style="padding:3px 0">수취정보</td>
			<td colspan="3" style="padding:3px 0">세무정보</td>
			<td rowspan="2" style="padding:3px 0">등록일</td>
			<td rowspan="2" style="padding:3px 0">PROC</td>
		</tr>
		<tr align="center" style="background:#F8F8EF">
			<td style="padding:3px 0">플랫폼이용료</td>
			<td style="padding:3px 0">중개수수료</td>
			<td style="padding:3px 0">수취방식</td>
			<td style="padding:3px 0">분납회수</td>

			<td style="padding:3px 0">납입회차</td>
			<td style="padding:3px 0">납입예정일</td>
			<td style="padding:3px 0">수취예정<br/>수취금액</td>
			<td style="padding:3px 0">수취일</td>
			<td style="padding:3px 0">반환액</td>
			<td style="padding:3px 0">반환여부</td>
			<td style="padding:3px 0">정산차액</td>
			<td style="padding:3px 0">최종정산액</td>

			<td style="padding:3px 0">공급가</td>
			<td style="padding:3px 0">세액</td>
			<td style="padding:3px 0">발급구분</td>
		</tr>
	</thead>
	<tbody>
		<form id="frmX02" name="frmX02" method="post">
			<input type="hidden" name="sql" value="<? echo $sql ?>" />
		</form>

		<form id="frmX01">
<?
if($list_count) {
	if($list_count > 1) {
?>
		<tr align="center" style="background:#EEEEFF;color:brown">
			<td>합계</td>
			<td></td>
			<td></td>

			<td align="right" alt="플랫폼이용료"><?=number_format($SUM['loan_usefee_amt'])?>원</td>
			<td align="right" alt="중개수수료"><?=number_format($SUM['commission_fee_amt'])?>원</td>
			<td></td>
			<td></td>

			<td alt="납입회차"></td>
			<td alt="납입예정일"></td>
			<td align="right" alt="수취예정금액 수취금액">
				<?=number_format($SUM['schedule_amt'])?>원<br/>
				<?=number_format($SUM['deposit_amt'])?>원
			</td>
			<td alt="수취일"></td>
			<td align="right" alt="반환액"><?=number_format($SUM['return_amt'])?>원</td>
			<td alt="반환여부"></td>
			<td align="right" alt="정산차액"><?=number_format($SUM['diff_amt'])?>원</td>
			<td align="right" alt="최종정산액"><?=number_format($SUM['repay_amt'])?>원</td>

			<td align="right" alt="공급가"><?=number_format($SUM['supply_price'])?>원</td>
			<td align="right" alt="세액"><?=number_format($SUM['tax'])?>원</td>
			<td></td>

			<td></td>
			<td></td>
		</tr>
<?
	}

	for($i=0,$num=$list_count; $i<$list_count; $i++,$$num--) {

		$PRINT = '';

		if($LIST[$i]['loan_started']) {
			$PRINT['invest_period'].= preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']);
			$PRINT['invest_period'].= ' ('.$LIST[$i]['total_days'].'일)';
		}

		$PRINT['loan_interest_rate'] = '(연) ' . floatRtrim($LIST[$i]['loan_interest_rate']) . '%';
		$PRINT['loan_date_range']    = ($LIST[$i]['loan_started']) ? preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']) : '';

		$PRINT['loan_usefee']    = floatRtrim($LIST[$i]['loan_usefee']).'%';
		$PRINT['commission_fee'] = floatRtrim($LIST[$i]['commission_fee']).'%';


		$PRINT['schedule_amt']       = ($LIST[$i]['schedule_amt'] <> 0) ? number_format($LIST[$i]['schedule_amt']) . '원' : '<span style="color:#CCC">0원</span>';
		$PRINT['loan_usefee_amt']    = ($LIST[$i]['loan_usefee_amt'] <> 0) ? number_format($LIST[$i]['loan_usefee_amt']) . '원' : '<span style="color:#CCC">0원</span>';
		$PRINT['commission_fee_amt'] = ($LIST[$i]['commission_fee_amt'] <> 0) ? number_format($LIST[$i]['commission_fee_amt']) . '원' : '<span style="color:#CCC">0원</span>';

		if($LIST[$i]['loan_usefee_type']=='A') {
			$PRINT['loan_usefee_type']         = '후취';
			$PRINT['loan_usefee_repay_count']  = $LIST[$i]['loan_usefee_repay_count'] . '회';
			$PRINT['loan_usefee_amt_month'] = number_format($LIST[$i]['loan_usefee_amt_month']) . '원';
		}
		else {
			$PRINT['loan_usefee_type']         = '선취';
			$PRINT['loan_usefee_repay_count']  = '-';
			$PRINT['loan_usefee_amt_month'] = '';
		}

		$PRINT['turn'] =  $LIST[$i]['turn'];

		$fcolor1 = ($LIST[$i]['schedule_amt']) ? '' : '#CCC';
		$fcolor1_1 = ($LIST[$i]['deposit_amt']) ? '' : '#CCC';
		$fcolor2 = ($LIST[$i]['repay_amt']) ? '' : '#CCC';
		if($LIST[$i]['diff_amt'] > 0) {
			$fcolor3 = '#FF2222';
		}
		else {
			$fcolor3 = ($LIST[$i]['diff_amt']) ? '' : '#CCC';
		}
		$fcolor4 = ($LIST[$i]['return_amt']) ? '' : '#CCC';
		$fcolor5 = ($LIST[$i]['supply_price']) ? '' : '#CCC';
		$fcolor6 = ($LIST[$i]['tax']) ? '' : '#CCC';

		if($LIST[$i]['collect_ok']) {
			$PRINT['collect_date'] = ($LIST[$i]['collect_date']) ? preg_replace("/-/", ".", $LIST[$i]['collect_date']) : '기록없음';
		}
		else {
			$PRINT['collect_date']= '';
		}

		$PRINT['schedule_amt']= '<span style="color:'.$fcolor1.'">' . number_format($LIST[$i]['schedule_amt']) . '원</span>';
		$PRINT['deposit_amt']= '<span style="color:'.$fcolor1_1.'">' . number_format($LIST[$i]['deposit_amt']) . '원</span>';
		$PRINT['repay_amt']  = '<span style="color:'.$fcolor2.'">' . number_format($LIST[$i]['repay_amt']) . '원</span>';
		$PRINT['diff_amt']   = '<span style="color:'.$fcolor3.'">' . number_format($LIST[$i]['diff_amt']) . '원</span>';
		$PRINT['return_amt'] = '<span style="color:'.$fcolor4.'">' . number_format($LIST[$i]['return_amt']) . '원</span>';
		$PRINT['return_ok']  = ($LIST[$i] > 0 && $LIST[$i]['return_ok']=='1') ? '반환' : '';

		$PRINT['schedule_date'] = preg_replace("/-/", ".", $LIST[$i]['schedule_date']);
		$PRINT['rdate'] = preg_replace("/-/", ".", substr($LIST[$i]['rdate'], 0, 10));

		$PRINT['supply_price'] = '<span style="color:'.$fcolor5.'">' . number_format($LIST[$i]['supply_price']) . '원</span>';
		$PRINT['tax'] = '<span style="color:'.$fcolor6.'">' . number_format($LIST[$i]['tax']) . '원</span>';

		$taxinvoicetype = ($LIST[$i]['member_type']=='2') ? '세금계산서' : '현금영수증';
		if($LIST[$i]['mgtKey']) {
			$PRINT['taxinvoice'] = '<a href="'.G5_URL.'/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL2.php?mgtKey='.$LIST[$i]['mgtKey'].'" target="_blank">'.$taxinvoicetype.'</a>';
		}
		else {
			$PRINT['taxinvoice'] = '<span style="color:#CCC">'.$taxinvoicetype.'</span>';
		}

		$PRINT['member_type'] = ($LIST[$i]['member_type']=='2') ? '법인회원' : '개인회원';

		if($LIST[$i]['member_type']=='2') {
			$PRINT['mb_title'] = $LIST[$i]['mb_co_name'];
			$PRINT['mb_hp']    = $LIST[$i]['mb_hp'];
		}
		else {
			$PRINT['mb_title'] = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);
			$PRINT['mb_hp']    = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_hp'] : substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****";
		}


		$PRINT['product_info_lnk'] = "/adm/product/product_list.php?field=A.idx&keyword=" . $LIST[$i]['product_idx'];

		$PRINT['member_info_lnk'] = "/adm/member/member_list.php?member_group=L&key_search=A.mb_no&keyword=" . $LIST[$i]['mb_no'];

		$PRINT['click_action'] = ($_SESSION['ss_accounting_admin']) ? "location.href='?{$qstr}&idx={$LIST[$i]['idx']}'" : "alert('접근권한이 없습니다.');";

?>
		<tr align="center" style="background:<?=($LIST[$i]['idx']==$idx)?'#FFFFCC':''?>">
			<td><?if($LIST[$i]['mgtKey']==''){?><input type="checkbox" name="chk[]" value="<?=$LIST[$i]['idx']?>"><?}?></td>

			<td>
				<div id="detail_<?=$i?>" style="position:absolute; z-index:<?=$num?>; border:1px solid #FF2222; border-radius:3px; background:#FFF; cursor:pointer; display:none;" onClick="fieldToggle('detail_<?=$i?>');">
					<dl style="font-size:12px; margin:8px; text-align:left;">
						<dd style='margin:0 8px'>품번. <a href="<?=$PRINT['product_info_lnk']?>" target="_blank"><?=$LIST[$i]['product_idx']?></a></dd>
						<dd style="margin:0 8px">상품군. <?=$LIST[$i]['print_category']?></strong>
						<dd style="margin:0 8px">상품명. <?=$LIST[$i]['title']?></strong>
						<dd style='margin:0 8px'>대출금액. <?=number_format($LIST[$i]['recruit_amount'])?>원</dd>
						<dd style='margin:0 8px'>대출이자. <?=$PRINT['loan_interest_rate']?></dd>
						<dd style='margin:0 8px'>대출기간. <?=$PRINT['invest_period']?></dd>
						<dd style='margin:0 8px'>대출자플랫폼이용료율. <?=$PRINT['loan_usefee']?></dd>
						<dd style='margin:0 8px'>중개수수료율. <?=$PRINT['commission_fee']?></dd>
					</dl>
				</div>
				<span onClick="fieldToggle('detail_<?=$i?>');" style="color:#3333cc;cursor:pointer;"><?=$LIST[$i]['start_num']?></span>
			</td>

			<td>
				<div id="mbdetail_<?=$i?>" style="position:absolute; z-index:<?=$num?>; border:1px solid #FF2222; border-radius:3px; background:#FFF; cursor:pointer; display:none;" onClick="fieldToggle('mbdetail_<?=$i?>');">
					<dl style="font-size:12px; margin:8px; text-align:left;">
						<dd style='margin:0 8px'>회원번호. <a href="<?=$PRINT['member_info_lnk']?>" target="_blank"><?=$LIST[$i]['mb_no']?></a></dd>
						<dd style='margin:0 8px'>가입구분. <?=$PRINT['member_type']?></dd>
						<dd style="margin:0 8px">아이디. <?=$LIST[$i]['mb_id']?></strong>
						<dd style="margin:0 8px"><?=($LIST[$i]['member_type']=='2')?'사업자명':'성명';?>. <?=$PRINT['mb_title']?></strong>
						<dd style='margin:0 8px'>연락처. <?=$PRINT['mb_hp']?></dd>
					</dl>
				</div>
				<span onClick="fieldToggle('mbdetail_<?=$i?>');" style="color:#3333cc;cursor:pointer;"><?=$LIST[$i]['mb_no']?></span>
			</td>

			<td align="right"><?=$PRINT['loan_usefee_amt']?></td>
			<td align="right"><?=$PRINT['commission_fee_amt']?></td>
			<td><?=$PRINT['loan_usefee_type']?></td>
			<td><?=$PRINT['loan_usefee_repay_count']?></td>

			<td><?=$PRINT['turn']?></td>
			<td><?=$PRINT['schedule_date']?></td>
			<td align="right">
				<?=$PRINT['schedule_amt']?><br/>
				<?=$PRINT['deposit_amt']?>
			</td>
			<td align="center"><?=$PRINT['collect_date']?></td>
			<td align="right"><?=$PRINT['return_amt']?></td>
			<td><?=$PRINT['return_ok']?></td>
			<td align="right"><?=$PRINT['diff_amt']?></td>
			<td align="right" style="color:#FF2222"><?=$PRINT['repay_amt']?></td>

			<td align="right"><?=$PRINT['supply_price']?></td>
			<td align="right" style="color:#3366FF"><?=$PRINT['tax']?></td>
			<td><?=$PRINT['taxinvoice']?></td>

			<td><?=$PRINT['rdate']?></td>
			<td><button type="button" onClick="<?=$PRINT['click_action']?>" class="btn btn-sm <?=($LIST[$i]['idx']==$idx)?'':'btn-default'?>" style="margin-top:2px;width:60px">보기</button></td>
		</tr>
<?
		$num--;
	}
}
else {
	echo "		<tr><td colspan='20' align='center'>데이터가 없습니다.</td></tr>\n";
}
?>
	</tbody>
</table>

<div id="paging_span" style="width:100%; margin:10px 0 10px 0; text-align:center;"><? paging($total_count, $page, $rows, 10); ?></div>

<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);
?>
<script type="text/javascript">
$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
});

$(document).ready(function() {
	$('#dataList').floatThead();
});

$("input[id=chkall]").click(function() {
	$("input:checkbox[name='chk[]']").prop('checked', this.checked);
});

fSubmit = function() {
	f= document.frmSearch;
	f.submit();
}

$('#exelDownload').on('click', function(){
	if(confirm('검색결과를 엑셀시트로 다운로드 받으시겠습니까?')) {
		var f = document.frmX02;
		f.method = 'post';
		f.action = 'loaner_usefee_repay.download.php';
		f.submit();
	}
});

loanerTaxinvoiceRequest = function() {
	checked_count = $("input[name='chk[]']:checked").length;
	if( checked_count > 0 ) {

		if( confirm('선택된 상품의 [세금계산서.현금영수증] 일괄발행을 시작합니다.\n실행 후에는 본 페이지를 이탈하여도 발급요청이 실행 됩니다.\n진행 하시겠습니까?') ) {

			var params = $("#frmX01").serialize();

			$.ajax({
				url : 'loaner_taxinvoice_proc.php',
				type : 'post',
				dataType : 'json',
				data : params,
				success:function(result) {
					if(result.code=='SUCCESS') {
						return;
					}
					else {
						alert(result.message);
					}
				},
				beforeSend: function() { loading('on'); },
				complete: function() { loading('off'); },
				error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
			});

			$('#button_a').attr('disabled','true');
		}

	}
	else {
		alert('목록에서 계산서 발행 대상을 선택하십시요.');
	}
}

fieldToggle = function(id) {
	if(id) $('#' + id).slideToggle();
}


$('#close_button').on('click', function() {
	$.unblockUI();
	return false;
});

</script>
