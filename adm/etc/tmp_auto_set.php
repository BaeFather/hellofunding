<?
include_once('./_common.php');
?>
<?
$ss_sql = "select count(*) from cf_auto_invest_config_user_change";
$ss_res = sql_query($ss_sql);
$ss_cnt = $ss_res->num_rows;

if ($ss_cnt) $main_table = "cf_auto_invest_config_user_change";
else $main_table = "cf_auto_invest_config_user_test";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">

<style>
    .fixed-table-container {
        width: 100%;
        height: 750px;
        border: 1px solid #000;
        position: relative;
        padding-top: 60px; /* header-bg height값 */
    }
    .header-bg {
        background: skyblue;
        height: 60px; /* header-bg height값 */
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        border-bottom: 1px solid #000;
    }
    .table-wrapper {
        overflow-x: hidden;
        overflow-y: auto;
        height: 100%;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    td {
        border-bottom: 1px solid #ccc;
        padding: 5px;
    }
    td + td {
        border-left: 1px solid #ccc;
    }
    th {
        padding: 0px; /* reset */
    }
    .th-text {
        position: absolute;
        top: 0;
        width: inherit;
        line-height: 30px;  /* header-bg height값 */
        border-left: 1px solid #000;
		border-bottom: 1px solid #000;
		font-size:12px;
    }
    th:first-child .th-text {
        border-left: none;
    }
</style>
</head>
<body>
    <div class="fixed-table-container">
        <div class="header-bg"></div>
        <div class="table-wrapper">

<table border=1 style="width:100%;">
	<thead>
	<tr>
		<th width="40px" rowspan=2 ><div class="th-text" style="line-height:60px;">No.</div></th>
		<th width="40px" rowspan=2 ><div class="th-text" style="line-height:60px;">mNo</div></th>
		<th  rowspan=2 ><div class="th-text" style="line-height:60px;">이름</div></th>
		<th colspan=8 style="width:480px;"><div class="th-text" >변경전</div></th>
		<th colspan=8 style="width:480px;"><div class="th-text" >변경후</div></th>
		<th colspan=8 style="width:480px;"><div class="th-text" >현제</div></th>
	</tr>
	<tr>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;background-color:yellow;">확매</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;background-color:yellow;">동산</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;">확매A</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;background-color:yellow;">면세점</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;">확매B</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;background-color:yellow;">부동산</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;background-color:yellow;">주택담보</div></th>
		<th style="width:60px;"><div class="th-text" style="width:60px;top:30px;">error</div></th>

		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">확매</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">동산</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">확매A</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">면세점</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">확매B</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">부동산</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">주택담보</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">error</div></th>

		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">확매</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">동산</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">확매A</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">면세점</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">확매B</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">부동산</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;background-color:yellow;">주택담보</div></th>
		<th style="width:60px;"><div class="th-text" style="top:30px;">error</div></th>
	</tr>
	</thead>
	<tbody>
<?
if ($main_table=="cf_auto_invest_config_user_change") {
}

$msql = "SELECT member_idx, COUNT(idx) cnt FROM $main_table GROUP BY member_idx ORDER BY member_idx";
$mres = sql_query($msql);
$mcnt = $mres->num_rows;

for ($i=0 ; $i<$mcnt ; $i++) {

	$mrow = sql_fetch_array($mres);

	$mb_sql = "select mb_name from g5_member where mb_no='$mrow[member_idx]'";
	$mb_res = sql_query($mb_sql);
	$mb_row = sql_fetch_array($mb_res);

	$sql = "select * from $main_table where member_idx='$mrow[member_idx]'";
	$res = sql_query($sql);
	$cnt = $res->num_rows;

	$od_a6="";   $od_a7="";   $od_a8="";   $od_a9="";   $od_a10="";   $od_a11="";   $od_a12="";   $od_aa="";
	$od_a6_m=""; $od_a7_m=""; $od_a8_m=""; $od_a9_m=""; $od_a10_m=""; $od_a11_m=""; $od_a12_m=""; $od_aa_m="";
	$nw_a6="";   $nw_a7="";   $nw_a8="";   $nw_a9="";   $nw_a10="";   $nw_a11="";   $nw_a12="";   $nw_aa="";
	$nw_a6_m=""; $nw_a7_m=""; $nw_a8_m=""; $nw_a9_m=""; $nw_a10_m=""; $nw_a11_m=""; $nw_a12_m=""; $nw_aa_m="";

	for ($j=0 ; $j<$cnt ; $j++) {

		$a_row = sql_fetch_array($res);

		$a_row['setup_amount'] = $a_row['setup_amount']/10000;
		$a_row['setup_amount_new'] = $a_row['setup_amount_new']/10000;

		if ($a_row['ai_grp_idx']<>$a_row['ai_grp_idx_new']) $fc = "<font color=red>";
		else $fc="";

		if ($a_row['ai_grp_idx']=="6") {
			$od_a6 = $fc."Y";
			$od_a6_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="7") {
			$od_a7 = $fc."Y";
			$od_a7_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="8") {
			$od_a8 = $fc."Y";
			$od_a8_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="9") {
			$od_a9 = $fc."Y";
			$od_a9_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="10") {
			$od_a10 = $fc."Y";
			$od_a10_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="11") {
			$od_a11 = $fc."Y";
			$od_a11_m = $a_row['setup_amount'];
		} else if ($a_row['ai_grp_idx']=="12") {
			$od_a12 = $fc."Y";
			$od_a12_m = $a_row['setup_amount'];
		} else {
			$od_aa .= $a_row['ai_grp_idx_new']." ";
			$od_aa_m = $a_row['setup_amount'];
		}

		if ($a_row['ai_grp_idx_new']=="6") {
			$nw_a6 = $fc."Y";
			$nw_a6_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="7") {
			$nw_a7 = $fc."Y";
			$nw_a7_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="8") {
			$nw_a8 = $fc."Y";
			$nw_a8_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="9") {
			$nw_a9 = $fc."Y";
			$nw_a9_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="10") {
			$nw_a10 = $fc."Y";
			$nw_a11_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="11") {
			$nw_a11 = $fc."Y";
			$nw_a11_m = $a_row['setup_amount_new'];
		} else if ($a_row['ai_grp_idx_new']=="12") {
			$nw_a12 = $fc."Y";
			$nw_a12_m = $a_row['setup_amount_new'];
		} else {
			$nw_aa .= $a_row['ai_grp_idx_new']." ";
			$nw_aa_m = $a_row['setup_amount_new'];
		}
	}

	$sql = "select * from $main_table where member_idx='$mrow[member_idx]'";
	$res = sql_query($sql);
	$cnt = $res->num_rows;

	$a6="";   $a7="";   $a8="";   $a9="";   $a10="";   $a11="";   $a12="";   $aa="";
	$a6_m=""; $a7_m=""; $a8_m=""; $a9_m=""; $a10_m=""; $a11_m=""; $a12_m=""; $aa_m="";

	for ($j=0 ; $j<$cnt ; $j++) {
		$row = sql_fetch_array($res);
		$row['setup_amount'] = $row['setup_amount']/10000;

		if ($row['ai_grp_idx']=="6") {
			$a6 = "Y";
		} else if ($row['ai_grp_idx']=="7") {
			$a7 = "Y";
			$a7_m = $row['setup_amount'];
		} else if ($row['ai_grp_idx']=="8") {
			$a8 = "Y";
			$a8_m = $row['setup_amount'];
		} else if ($row['ai_grp_idx']=="9") {
			$a9 = "Y";
			$a9_m = $row['setup_amount'];
		} else if ($row['ai_grp_idx']=="10") {
			$a10 = "Y";
			$a10_m = $row['setup_amount'];
		} else if ($row['ai_grp_idx']=="11") {
			$a11 = "Y";
			$a11_m = $row['setup_amount'];
		} else if ($row['ai_grp_idx']=="12") {
			$a12 = "Y";
			$a12_m = $row['setup_amount'];
		} else {
			$aa .= $row['ai_grp_idx']." ";
			$aa_m = $row['setup_amount'];
		}

	}
	?>
	<tr>
		<td><?=$i+1?></td>
		<td><?=$mrow['member_idx']?></td>
		<td style="text-align:center;"><?=$mb_row['mb_name']?></td>
		<td style="text-align:center;"><?=$od_a6?><br/><?=$od_a6_m?></td>
		<td style="text-align:center;"><?=$od_a7?><br/><?=$od_a7_m?></td>
		<td style="text-align:center;"><?=$od_a8?><br/><?=$od_a8_m?></td>
		<td style="text-align:center;"><?=$od_a9?><br/><?=$od_a9_m?></td>
		<td style="text-align:center;"><?=$od_a10?><br/><?=$od_a10_m?></td>
		<td style="text-align:center;"><?=$od_a11?><br/><?=$od_a11_m?></td>
		<td style="text-align:center;"><?=$od_a12?><br/><?=$od_a12_m?></td>
		<td style="text-align:center;"><?=$od_aa?><br/><?=$od_aa_m?></td>

		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a6?><br/><?=$nw_a6_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a7?><br/><?=$nw_a7_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a8?><br/><?=$nw_a8_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a9?><br/><?=$nw_a9_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a10?><br/><?=$nw_a10_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a11?><br/><?=$nw_a11_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_a12?><br/><?=$nw_a12_m?></td>
		<td style="text-align:center;background-color:#F3F3F3;"><?=$nw_aa?><br/><?=$nw_aa_m?></td>

		<td style="text-align:center;"><?=$a6?><br/><?=$a6_m?></td>
		<td style="text-align:center;"><?=$a7?><br/><?=$a7_m?></td>
		<td style="text-align:center;"><?=$a8?><br/><?=$a8_m?></td>
		<td style="text-align:center;"><?=$a9?><br/><?=$a9_m?></td>
		<td style="text-align:center;"><?=$a10?><br/><?=$a10_m?></td>
		<td style="text-align:center;"><?=$a11?><br/><?=$a11_m?></td>
		<td style="text-align:center;"><?=$a12?><br/><?=$a12_m?></td>
		<td style="text-align:center;"><?=$aa?><br/><?=$aa_m?></td>
	</tr>
	<?
}
?>
	</tbody>
</table>
        </div>
    </div>
<?

?>
</body>
</html>

<?
die(" <br/><br/> ");
$sql = "select * from g5_member ";
$res = sql_query($sql);
$cnt = $res->num_rows;
echo "$cnt";
?>
<!--table border=1>
<tr>
	<th>--No--</th>
	<th>고객번호</th>
	<th>확정매출채권A</th>
	<th>확정매출채권B</th>
</tr-->
<?
$no=0;
for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);

	$a_sql = "select * from cf_auto_invest_config_user where member_idx='$row[mb_no]' and ai_grp_idx='8'";
	$a_res = sql_query($a_sql);
	$a_cnt = $a_res->num_rows;
	unset($a_row);
	if ($a_cnt) $a_row = sql_fetch_array($a_res);

	$b_sql = "select * from cf_auto_invest_config_user where member_idx='$row[mb_no]' and ai_grp_idx='10'";
	$b_res = sql_query($b_sql);
	$b_cnt = $b_res->num_rows;
	unset($b_row);
	if ($b_cnt) $b_row = sql_fetch_array($b_res);

	if (!$a_row["setup_amount"] and !$b_row["setup_amount"]) continue;

	$no++;
	?>
	<!--tr>
		<td><?=$no?></td>
		<td style="text-align:center;"><?=$row["mb_no"]?></td>
		<td style="text-align:right;"><?=number_format($a_row["setup_amount"])?></td>
		<td style="text-align:right;"><?=number_format($b_row["setup_amount"])?></td>
	</tr-->
	<?=$no?>,<?=$row["mb_no"]?>,<?=$a_row["setup_amount"]?>,<?=$b_row["setup_amount"]?><br/>

	<?
}
?>
<!--/table-->