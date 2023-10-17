<?
/**
 * 주담대 문자발송현황
 */
$sub_menu = "920200";
include_once('./_common.php');
include_once('mortgage_common.php');

auth_check($auth[$sub_menu], 'w');
if ($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

$g5['title'] = $menu['menu920'][2][1];
include_once('../admin.head.php');
?>
<?
$this_ym = date("Y-m");
chk_repay();

function chk_repay() {
	global $this_ym;

	if (date("d")>="05") {
		$sql = "SELECT * FROM cf_product_turn WHERE ym<='$this_ym' AND repay_yn='N'";
	} else {
		$sql = "SELECT * FROM cf_product_turn WHERE ym<'$this_ym' AND repay_yn='N'";
	}
	$res = sql_query($sql);
	$cnt = $res->num_rows;

	for ($i=0 ; $i<$cnt ; $i++) {
		$row = sql_fetch_array($res);

		$chk_sql = "SELECT banking_date FROM cf_product_give WHERE product_idx='$row[product_idx]' AND turn='$row[turn]' AND interest>0";
		$chk_res = sql_query($chk_sql);
		$chk_cnt = $chk_res->num_rows;

		$repay_date = "";
		if ($chk_cnt) {
			$chk_row = sql_fetch_array($chk_res);
			$repay_date = $chk_row['banking_date'];
		}

		if ($repay_date) {
			$up_sql = "UPDATE cf_product_turn SET repay_yn='Y', repay_datetime='$repay_date' WHERE idx = '$row[idx]'";
			sql_query($up_sql);
		}
	}
}


?>
<?
$ymd = date("Y-m-d");


if (!$srch_y) $srch_y=date("Y");
if (!$srch_m) $srch_m=date("m");
$srch_ym = $srch_y."-".$srch_m;
$srch_ym2= $srch_y.$srch_m;
$srch_ym3 = " AND C.ym = '$srch_ym' ";
//if ($srch_ym=="-") $srch_ym  = date("Y-m");

$sql_order = " ORDER BY A.start_num DESC";
$sql_search = " A.category='2' AND A.mortgage_guarantees='1' AND A.loan_start_date<>'0000-00-00' ";
//$sql_search .= " AND A.state='1' ";
//$sql_search .= " AND A.loan_start_date<'$srch_ym-01' ";
//$sql_search .= " AND (A.state='1' or A.state='8') ";


// 검색 조건 설정
$wh1 = "";
if ($srch_tg_fld && $srch_keyword) {  // 상품 필드 및 검색어

	if ($srch_tg_fld=="prd_idx") {
		$wh1 = "AND A.idx='$srch_keyword' ";
	}
	if ($srch_tg_fld=="ho_num") {
		$wh1 = "AND A.start_num='$srch_keyword' ";
	}
	if ($srch_tg_fld=="prd_name") {
		$wh1 = "AND A.title LIKE '%".$srch_keyword."%' ";
	}
	if ($srch_tg_fld=="mb_name") {
		$wh1 = "AND B.mb_name='$srch_keyword' ";
	}
	if ($srch_tg_fld=="mb_phone") {
		$wh1 = "AND B.mb_hp='$srch_keyword' ";
	}
	if ($srch_tg_fld=="re_accnum") {
		$wh1 = "AND B.virtual_account2='$srch_keyword' ";
	}
	if ($srch_tg_fld=="tag") {
		echo "<script>alert('기능 개발 중');</script>";
	}
}

$wh2 = "";
$wh22 = "";
if($date_field && $sdate || $edate) {

	if($date_field=="loan_start_date") {  // 검색 조건이 대출 시작일이면
		if ($sdate) $wh2 = " AND A.loan_start_date >= '$sdate' ";  // 대출 시작일 시작 검색값이 있다면
		if ($edate) $wh2.= " AND A.loan_start_date <= '$edate' ";  // 대출 시작일 종료 검색값이 있다면 (점 주의)
	}
	if($date_field=="loan_end_date") {  // 대출 종료일
		if ($sdate) $wh2 = " AND A.loan_end_date >= '$sdate' ";
		if ($edate) $wh2.= " AND A.loan_end_date <= '$edate' ";
	}
	if($date_field=="pre_receipt_date") {  // 수취 예정일
		if($sdate) $wh22 = " AND CONCAT(C.ym,'-',C.dday) >= '$sdate' ";
		if($edate) $wh22.= " AND CONCAT(C.ym,'-',C.dday) <= '$edate' ";
	}
	if($date_field=="receipt_date") {  // 수취일
		$sdate = substr($sdate, 0, 4).substr($sdate, 5, 2).substr($sdate, 8, 2);
		$edate = substr($edate, 0, 4).substr($edate, 5, 2).substr($edate, 8, 2);

		$wh22 = "AND C.eja_in_date >= '$sdate' AND C.eja_in_date <= '$edate' ";
	}
	if($date_field=="eja_date") {  // 이자 지급일
		$wh22 = "AND C.repay_datetime >= '$sdate 00:00:00' AND C.repay_datetime <= '$edate 23:59:59' ";
	}
}

$wh3  = "";
$wh33 = "";
if($state1) {  // 상태 값(1: 이자상환중, 2: 정상상환, 5: 중도상환, 8: 연체)
	$wh3 .= " A.state = '1' ";
	$wh33 = "OR";
}
if($state2) {
	$wh3 .= "$wh33 A.state = '2' ";
	$wh33 = "OR";
}
if($state5) {
	$wh3 .= "$wh33 A.state = '5' ";
	$wh33 = "OR";
}
if($state8) {
	$wh3 .= "$wh33 A.state = '8' ";
}

if($wh3) {
	$wh3 = "AND (".$wh3.")";
}

$wh4 = "";
if ($srch_eja_end) {  // 수급상태(수취, 미수취)
	if($srch_eja_end == 'Y') {
		$wh4 = " AND C.eja_in_date != ''";
	}
	if($srch_eja_end == 'N') {
		$wh4 = " AND C.eja_in_date = ''";
	}
}
/*
$wh5 = "";
if($date_field == "pre_receipt_date") {


	$tmp_ym  =
	$tmp_day =
	$wh5 .= "AND (
				SELECT COUNT(idx)
				FROM cf_product_turn AA
				LEFT JOIN cf_product BB ON AA.product_idx = BB.idx
				WHERE 1 ";

	if($sdate && $edate) {

	} else {
		if($sdate) {
			$wh5 .= " AND ym <= '".substr($sdate,0,7)."' AND dday = '".substr($sdate,0,-2)."' ";
		}
		if($edate) {
			$wh5 .= " AND ym = '".substr($edate,0,7)."' AND dday = '".substr($edate,0,-2)."' ";
		}
	}

}
*/
$sql = "SELECT COUNT(A.idx) AS cnt
		FROM cf_product A
		LEFT JOIN cf_product_turn C ON (A.idx=C.product_idx)
		WHERE $sql_search $wh1 $wh4 $srch_ym3";

$row = sql_fetch($sql);

$total_count = $row['cnt'];
$rows = 150;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
$num = $total_count - $from_record; // 리스트에 표시할 순차 번호
//$num = 1;

echo $total_count;
echo "<pre>".$sql."</pre>";

$sql = "SELECT A.idx, A.title, A.loan_mb_no, A.loan_start_date, A.loan_end_date, A.recruit_amount,
			   A.loan_interest_rate, A.start_num, A.invest_usefee_type, A.loan_usefee, A.state,
			   B.mb_name, B.mb_hp, B.virtual_account2, B.mb_no,
			(SELECT max(turn) FROM cf_loaner_push_schedule WHERE product_idx=A.idx) AS max_turn,
			(SELECT C.ym FROM cf_product_turn C WHERE C.product_idx = A.idx $wh22 $wh4 $srch_ym3) AS ym
	   FROM cf_product A
	   LEFT JOIN g5_member B ON (A.loan_mb_no=B.mb_no)
	   WHERE $sql_search $wh1 $wh2 $wh3 $wh5
	   $sql_order
	   LIMIT $from_record, $rows";
/*
$sql = "SELECT A.idx, A.title, A.loan_mb_no, A.loan_start_date, A.loan_end_date, A.recruit_amount,
			   A.loan_interest_rate, A.start_num, A.invest_usefee_type, A.loan_usefee, A.state,
			   B.mb_name, B.mb_hp, B.virtual_account2, B.mb_no, C.eja_in_date, C.ym,
			(SELECT max(turn) FROM cf_loaner_push_schedule WHERE product_idx=A.idx) AS max_turn
	   FROM cf_product A
	   LEFT JOIN g5_member B ON (A.loan_mb_no = B.mb_no)
	   LEFT JOIN cf_product_turn C ON (C.product_idx = A.idx)
	   WHERE $sql_search $wh1 $wh4 $srch_ym3
	   $sql_order
	   LIMIT $from_record, $rows";
*/
echo "<pre>".$sql."</pre>";

$res = sql_query($sql);
$cnt = $res->num_rows;




?>

<style>

</style>

<div class="row">
	<div class="col-lg-12">
		<div class="panel-body" style="padding:0 1% 0 1%;">

			<!-- 검색영역 START -->
			<div style="line-height:28px;">
				<form id="frmSearchList" name="frmSearchList" method="get" class="form-horizontal">
				<input type="text" name="mode" value="" />
				<input type="text" name="srch_y" value="<?=$srch_y?>" />
				<input type="text" name="srch_m" value="<?=$srch_m?>" />
				<input type="text" name="srch_eja_end" value="<?=$srch_eja_end?>" />

				<ul class="col col-md-* list-inline" style="padding-left:0;margin-bottom:5px;float:left;">
					<span style="font-size: 13px; margin-left: 6px; color: #555;">대상 월 : </span>
					<li style="vertical-align: middle;">
						<select name="srch_y" class="form-control input-sm">
							<option value="">연도 선택</option>
							<option value="2021" <?=$srch_y=="2021"?"selected":""?> >2021</option>
							<option value="2020" <?=$srch_y=="2020"?"selected":""?> >2020</option>
						</select>
					</li>
					<li style="vertical-align: middle;">
						<select name="srch_m" class="form-control input-sm">
							<option value="">월 선택</option>
							<option value="01" <?=$srch_m=="01"?"selected":""?> >1</option>
							<option value="02" <?=$srch_m=="02"?"selected":""?> >2</option>
							<option value="03" <?=$srch_m=="03"?"selected":""?> >3</option>
							<option value="04" <?=$srch_m=="04"?"selected":""?> >4</option>
							<option value="05" <?=$srch_m=="05"?"selected":""?> >5</option>
							<option value="06" <?=$srch_m=="06"?"selected":""?> >6</option>
							<option value="07" <?=$srch_m=="07"?"selected":""?> >7</option>
							<option value="08" <?=$srch_m=="08"?"selected":""?> >8</option>
							<option value="09" <?=$srch_m=="09"?"selected":""?> >9</option>
							<option value="10" <?=$srch_m=="10"?"selected":""?> >10</option>
							<option value="11" <?=$srch_m=="11"?"selected":""?> >11</option>
							<option value="12" <?=$srch_m=="12"?"selected":""?> >12</option>
						</select>
					</li>
					<li style="vertical-align: middle;">
						<select name="srch_eja_end" class="form-control input-sm">
							<option value="">수급상태</option>
							<option value="Y" <?=$srch_eja_end=="Y"?"selected":""?> >수취</option>
							<option value="N" <?=$srch_eja_end=="N"?"selected":""?> >미수취</option>
						</select>
					</li>
					<li style="vertical-align: middle;">
						<button type="button" class="btn btn-sm btn-warning" id="ListSrch_button" onclick="goSearchList();">검색</button>
					</li>
				</ul>
				</form>
				<!--
				<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
					<li><label class="checkbox-inline"><input type="checkbox" name="state1" value="1" <?=$state1=="1"?"checked":""?> />이자상환중</label></li>
					<li><label class="checkbox-inline"><input type="checkbox" name="state2" value="2" <?=$state2=="2"?"checked":""?> />정상상환</label></li>
					<li><label class="checkbox-inline"><input type="checkbox" name="state5" value="5" <?=$state5=="5"?"checked":""?> />중도상환</label></li>
					<li><label class="checkbox-inline"><input type="checkbox" name="state0" value="0" />지연</label></li>
					<li><label class="checkbox-inline"><input type="checkbox" name="state8" value="8" <?=$state8=="8"?"checked":""?> />연체</label></li>
				</ul>
				-->
				<form id="frmSearch" name= "frmSearch" method="get" class="form-horizontal">
				<input type="text" name="mode" value="" />
				<input type="text" name="srch_tg_fld" value="<?=$srch_tg_fld?>" />
				<input type="text" name="srch_keyword" value="<?=$srch_keyword?>" />

				<ul class="col col-md-* list-inline" style="margin-bottom:10px;float:left;clear: both;margin-top: 5px;">
					<li style="float:left;">
						<select name="srch_tg_fld" class="form-control input-sm">
							<option value="">검색대상선택</option>
							<option value="prd_idx" <?=$srch_tg_fld=="prd_idx"?"selected":""?> >품번</option>
							<option value="ho_num" <?=$srch_tg_fld=="ho_num"?"selected":""?> >호번</option>
							<option value="prd_name" <?=$srch_tg_fld=="prd_name"?"selected":""?> >상품명</option>
							<option value="mb_name" <?=$srch_tg_fld=="mb_name"?"selected":""?> >차주명</option>
							<option value="mb_phone" <?=$srch_tg_fld=="mb_phone"?"selected":""?> >연락처</option>
							<option value="re_accnum" <?=$srch_tg_fld=="re_accnum"?"selected":""?> >상환용가상계좌번호</option>
							<option value="tag" <?=$srch_tg_fld=="tag"?"selected":""?> >태그</option>
						</select>
					</li>
					<li style="float:left;margin-left:4px;"><input type="text" name="srch_keyword" value="<?=$srch_keyword?>" onkeypress="JavaScript:press(this.form);" class="form-control input-sm"></li>
					<li style="vertical-align: middle;">
						<button type="button" class="btn btn-sm btn-warning" id="search_button" onclick="goSearch();">검색</button>
						<button type="button" onClick="location.replace('<?=$_SERVER['PHP_SELF']?>');" class="btn btn-sm btn-default" style="margin-left: 10px;">초기화</button>
					</li>
					<!--
					<li>
						<select name="date_field" class="form-control input-sm">
							<option value="">기간필드선택</option>
							<option value="loan_start_date" <?=$date_field=="loan_start_date"?"selected":""?> >대출시작일</option>
							<option value="loan_end_date" <?=$date_field=="loan_end_date"?"selected":""?> >대출종료일</option>
							<option value="pre_receipt_date" <?=$date_field=="pre_receipt_date"?"selected":""?> >수취예정일</option>
							<option value="receipt_date" <?=$date_field=="receipt_date"?"selected":""?> >수취일</option>
							<option value="eja_date" <?=$date_field=="eja_date"?"selected":""?> >이자지급일</option>
						</select>
					</li>
					<li><input type="text" id="sdate" name="sdate" value="<? echo $sdate;?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(시작)"></li>
					<li>~</li>
					<li><input type="text" id="edate" name="edate" value="<? echo $edate; ?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(종료)"></li>
				-->
				</ul>

				</form>
			</div>

			<div class="dataTable_wrapper">
				<table class="table table-striped table-bordered table-hover; " style="font-size:15px; max-width: 1600px; margin-top: 30px;">
					<thead>
						<tr>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;">NO.</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;">상품번호</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;">상품명</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;" colspan=2>대출정보</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;" colspan=2>차주정보</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;" colspan=2>이자내역</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;" colspan=2>수취내역</th>
							<th class="text-center" style="background-color:#F8F8EF; border:1px solid black;">문 자</th>
						</tr>
					</thead>
					<tbody>
<?
for ($i=0 ; $i<$cnt ; $i++) {

	$row = sql_fetch_array($res);

	$turn_sql = "SELECT * FROM cf_product_turn WHERE product_idx='$row[idx]' AND ym='$srch_ym'";
	$turn_row = sql_fetch($turn_sql);

	$ctn_sql = "SELECT commission_fee FROM cf_product_container WHERE product_idx=$row[idx]";
	$ctn_row = sql_fetch($ctn_sql);

	$brk_fee = $row['loan_usefee'] + $ctn_row['commission_fee'];

	if ($row['invest_usefee_type']=="A") $uf_type="후취";
	else if ($row['invest_usefee_type']=="B") $uf_type="선취";
	else $uf_type="-";

	$row['mb_hp'] = masterDecrypt($row['mb_hp'], false);
	$hp1 = substr($row['mb_hp'],0,3);
	$hp3 = substr($row['mb_hp'],-4);
	$hp2 = str_replace($hp1, "", $row['mb_hp']);
	$hp2 = str_replace($hp3, "", $hp2);

	$sqls = "SELECT * FROM cf_loaner_push_schedule WHERE product_idx='$row[idx]' AND SUBSTRING(send_date,1,7)='$srch_ym' AND send_date<'$srch_ym-15' ORDER BY send_date DESC, send_time DESC LIMIT 1";

	$ress = sql_query($sqls);
	$cnts = $ress->num_rows;
	if ($cnts) $rowss = sql_fetch_array($ress);
	else unset($rowss);

	$bal = 0;
	$bal = get_chaju_remain_amt($row['loan_mb_no'], $row['virtual_account2'], $row['idx']) ;
	$this_chul = get_chul_amt($row['idx'],$srch_ym);

	// 납입해야할 금액
	$pay = 0;
	//$pay = $rows['eja'] - $bal;
	$pay = $this_chul + $bal - $rowss['eja'];
	//if ($pay<0) $pay = 0;

	if ($srch_eja_end=="Y") {
		if ($pay<0 and $turn_row["sms_send_yn"]=="Y") continue;
	} else if ($srch_eja_end=="N") {
		if ($pay>=0) continue;
		if ($turn_row["sms_send_yn"]=="N") continue;
	}

	// 출금일
	//$in_sql = "SELECT * FROM IB_FB_P2P_IP WHERE acct_nb='$row[virtual_account2]' AND SUBSTRING(SR_DATE,1,6)='$srch_ym2' ORDER BY SR_DATE DESC LIMIT 1";
	$in_sql = "SELECT * FROM IB_FB_P2P_IP WHERE acct_nb='$row[virtual_account2]' ORDER BY SR_DATE DESC LIMIT 1";
	$in_row = sql_fetch($in_sql);
	if ($pay>=0) $indate = $in_row['SR_DATE'];
	else $indate = "";
	//$this_chul = $this_chul1["sum_out_amt"];
	//$this_chul_d = $this_chul1["banking_date"];

	$total_idx++;

	$hloan_sql = "SELECT pbirth, middle_refee FROM hloan_content WHERE product_idx='$row[idx]'";
	$hloan_row = sql_fetch($hloan_sql);

	$state_val = '';
	if($row['state'] == '5') {
		$state_val = '(중도상환)';
	}
	if($row['state'] == '2') {
		$state_val = '(정상상환)';
	}

	?>
						<tr class="odd" style="background-color:<?=$bgcolor?>;">
							<td align="center" style="border:1px solid black; border-bottom:1px solid black;" rowspan=4><?=$num--?></td>
							<td style="font-weight: bold;text-align:center; border:1px solid black;" rowspan=4><?=$row['idx']?>
								<p style="font-size: 13px;"><?=$state_val?></p>
							</td>
							<td style="font-weight: bold; border:1px solid black;" rowspan=4>
								<!--<button onclick="go_mng_set('<?=$row[idx]?>');" class='btn btn-default' style="margin-left:15px;">세팅</button>-->
								<a style="cursor: pointer; color: #295f98;" onclick="go_detail_form('<?=$row[idx]?>');"><?=$row['title']?></a>
							</td>
							<td style="padding:2px; text-align:center;">대출금</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=number_format($row['recruit_amount'])?></td>

							<td style="padding:2px; text-align:center;">차주명</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$row['mb_name']?> (<?=substr($hloan_row['pbirth'], 0, 6)?>)</td>

							<td style="padding:2px; text-align:center;">회차이자</td>
							<td style="padding:2px; text-align:right; padding-right:5px; border-right:1px solid black;"><?=$rowss['turn']?> / <?=number_format($rowss['eja'])?></td>

							<td style="padding:2px; text-align:center;">수취예정일</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=substr($rowss['dday'],5,5)?></td>

							<td style="font-weight: bold;text-align:center; border:1px solid black; vertical-align: middle;" rowspan=4>
								<?
								if ($turn_row["sms_send_yn"]=="Y") {
									$total_idx_send++;
									?>
									발송<br/><br/>
									<button class='btn btn-default' style="background-color:white;" onclick="go_change_msg('N', this,'<?=$row[idx]?>','<?=$srch_ym?>');">발송안함</button>
									<?
								} else if ($turn_row["sms_send_yn"]=="N") {
									?>
									<!--
									발송안함<br/><br/>
									<button class='btn btn-default' style="background-color:#44CD85;" onclick="go_change_msg('Y', this,'<?=$row[idx]?>','<?=$srch_ym?>');">발송</button>
									-->
									비대상
									<?
								}
								?>
							</td><!-- 발송결과 -->
						</tr>
						<tr class="odd" style="background-color:<?=$bgcolor?>">
							<td style="padding:2px; text-align:center;">금리</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$row['loan_interest_rate']?></td>

							<td style="padding:2px; text-align:center;">연락처</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$row['mb_hp']?><?//=$hp1?><?//=$hp2?><?//=$hp3?></td>

							<td style="padding:2px; text-align:center;">수취이자</td>
							<td style="padding:2px; text-align:right; padding-right:5px; border-right:1px solid black;"><?//=number_format($bal)?><?=number_format($this_chul)?></td>

							<td style="padding:2px; text-align:center;">수취일</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=substr($indate,4,2)?>-<?=substr($indate,6,2)?></td>
						</tr>
						<tr class="odd" style="background-color:<?=$bgcolor?>">
							<td style="padding:2px; text-align:center;">대출시작일</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$row['loan_start_date']?></td>

							<td style="padding:2px; text-align:center;">상환계좌번호</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$row['virtual_account2']?></td>

							<td style="padding:2px; text-align:center;">차액</td>
							<td style="padding:2px; text-align:right; padding-right:5px; border-right:1px solid black;">
								<?=number_format($pay)?></td>

							<td style="padding:2px; text-align:center;">대출수수료</td>
							<td style="padding:2px; text-align:center; border-right:1px solid black;"><?=$uf_type?></td>
						</tr>
						<tr class="odd" style="background-color:<?=$bgcolor?>">
							<td style="padding:2px; text-align:center; border-bottom:1px solid black;">대출만기일</td>
							<td style="padding:2px; text-align:center; border-bottom:1px solid black; border-right:1px solid black;"><?=$row['loan_end_date']?></td>
							<td style="padding:2px; text-align:center; border-bottom:1px solid black;">중도상환수수료율</td>
							<td style="padding:2px; text-align:center; border-bottom:1px solid black; border-right:1px solid black;"><?=$hloan_row['middle_refee'].'%'?></td>
							<td style="padding:2px; text-align:center; border-bottom:1px solid black;">연체일수/이자</td>
							<td style="padding:2px; text-align:right; border-right:1px solid black; border-bottom:1px solid black; padding-right:5px; <?=$pay<0?'color:red; font-weight: bold;':''?>"></td>

							<td style="padding:2px; text-align:center; border-bottom:1px solid black;">대출자수수료율</td>
							<td style="padding:2px; text-align:center; border-bottom:1px solid black; border-right:1px solid black;"><?=$brk_fee?></td>
						</tr>
	<?


}

?>
					</tbody>
				</table>
			</div>

<!--<?=$total_idx_send?> / 총 <?=$total_idx?> 명-->
			<div id="paging_span" style="width:100%; margin:10px 0 0 0; text-align:center;"><? paging($total_count, $page, $rows, 10); //$total_count, $page, $rows ?></div>


		</div>
		<!-- /.panel-body -->
	</div>
	<!-- /.col-lg-12 -->
</div>
<!-- /.row -->


<script type="text/javascript">

$(function(){
	$(".datepicker").datepicker({
		dateFormat      : 'yy-mm-dd',
		changeYear      : true,
		changeMonth     : true,
		monthNamesShort : ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
		dayNamesMin     : ['일' ,'월', '화', '수', '목', '금', '토']
	});
});

function go_detail_form(idx) {
	window.open('./mortgage_detail_form.php?idx='+idx, "_self", "mortgage_detail_form");
}

function go_mng_set(prd_idx) {
	window.open("./sett_mng_prd.php?prd_idx="+prd_idx, "sett_mng_prd", "left=10,top=10,width=500,height=400");
}

function go_change_msg(yn , obj, idx, srch_ym) {

	$(this).attr("disabled");

	if (yn=="Y") var wn_msg = "문자가 발송되게 변경하시겠습니까?";
	else var wn_msg = "문자가 발송되지 않게 변경하시겠습니까?";

	var go_yn = confirm(wn_msg);
	if (!go_yn) return;


	$.ajax({
		url: '/adm/mortgage/ajax_change.php',
		type: 'post',
		dataType: 'json',
		data:{
			yn: yn , idx: idx , srch_ym: srch_ym
		},
		success: function(data) {
			console.log(data);
			if (data.res_send_yn=="Y") {
				$(obj).parent().html("발송<br/><br/><button class='btn btn-default' style='background-color:white;' onclick=\"go_change_msg('N', this, '"+idx+"', '"+srch_ym+"');\">발송안함</button>");
			} else if (data.res_send_yn=="N") {
				$(obj).parent().html("발송안함<br/><br/><button class='btn btn-default' style='background-color:#44CD85;' onclick=\"go_change_msg('Y', this, '"+idx+"', '"+srch_ym+"');\">발송</button>");
			}
			/*
			if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
			else if(data.result=='ERROR') {
				if(data.message=='LOGIN_PLEASE') {
					window.location.replace('/');
				}
				else {
					alert(data.message);
				}
			}
			*/
		},
		beforeSend: function() { loading('on'); },
		complete: function() { loading('off'); },
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});

	/*
	if (v=="Y") {
		$(obj).parent().html("발송<br/><button class='btn btn-default' onclick=\"go_change_msg('N', this);\">발송안함</button>");
	} else if (v=="N") {
		$(obj).parent().html("발송안함<br/><button class='btn btn-default' onclick=\"go_change_msg('Y', this);\">발송</button>");
	}
	*/
}


$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>'
		        + '?page=' + $(this).attr('data-page')
				+ '&srch_eja_end=<?=$srch_eja_end?>'
				+ '&state1=<?=$state1?>'
				+ '&state2=<?=$state2?>'
				+ '&state5=<?=$state5?>'
				+ '&state8=<?=$state8?>'
				+ '&srch_tg_fld=<?=$srch_tg_fld?>'
				+ '&srch_keyword=<?=$srch_keyword?>'
				+ '&date_field=<?=$date_field?>'
				+ '&sdate=<?=$sdate?>'
				+ '&edate=<?=$edate?>'
				+ '&srch_y=<?=$srch_y?>'
				+ '&srch_m=<?=$srch_m?>';
		$(location).attr('href', url);
});

/*
function go_srch() {
	var f = document.frmSearch;
	f.submit();
}

*/

function view_schedule(prd_idx) {
	if (!prd_idx) return;
	window.open("./mortgage_sms_schedule.php?prd_idx="+prd_idx, "mortgage_sms_schedule", "left=10,top=10,width=700,height=700");
}


function press(f) {
	console.log(event.keyCode);
	if(event.keyCode == 13){    // 13이 enter키를 의미
		$("#search_button").click();
	}
}

$('#ListSrch_button').click(function() {
		var f = document.frmSearchList;
		f.method = 'get';
		f.target = '_self';
		//f.action = '<?=$_SERVER['PHP_SELF']?>';
		f.mode.value = 'searchList';
		f.submit();
});
/*
function goSearchList() {
		var f = document.frmSearch;
		f.target = '_self';
		f.mode.value = 'searchList';
		f.submit();
}
*/

function goSearch() {
		var f = document.frmSearch;
		f.target = '_self';
		f.mode.value = 'search';
		f.submit();
}

</script>


<? include_once ('../admin.tail.php'); ?>