<?
include_once('./_common.php');

$g5['title'] = "상환 현황";

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<?
$sum_sql = "SELECT * FROM cf_product_turn_sum WHERE product_idx='$product_idx' ORDER BY total_turn";
$sum_res = sql_query($sum_sql);
$sum_cnt = $sum_res->num_rows;

$RP_LIST = array();

for ($i=0 ; $i<$sum_cnt ; $i++) {

	$sum_row = sql_fetch_array($sum_res);
	$RP_LIST[$i]["total_turn"] = $sum_row["total_turn"];
	$RP_LIST[$i]["turn"] = $sum_row["turn"];
	$RP_LIST[$i]["turn_sno"] = $sum_row["turn_sno"];
	$RP_LIST[$i]["repay_date"] = $sum_row["repay_date"];
	$RP_LIST[$i]["repay_yn"] = $sum_row["repay_yn"];
	$RP_LIST[$i]["principal"] = $sum_row["principal"];
	$RP_LIST[$i]["total_interest"] = $sum_row["total_interest"];
	$RP_LIST[$i]["interest"] = $sum_row["interest"];
	$RP_LIST[$i]["tax"] = $sum_row["interest_tax"]+$sum_row["local_tax"];
	$RP_LIST[$i]["fee"] = $sum_row["fee"];

}
?>

<style>
.table th.border_r { border-right:1px solid #999; }
.table td.border_r { border-right:1px solid #999; }
input::placeholder { text-align:center; }

ul.statusbar > li { padding:4px 0; }
</style>

<div class="row" style="width:99.9%; min-width:1500px;">
	<div class="col-lg-12">
		<div class="panel-body">
			<div class="dataTable_wrapper">
				<table class="table table-bordered">
					<tr style="background-color:#969CA7;">
						<th>전체회차</th>
						<th>회차1</th>
						<th>회차2</th>
						<th>지급(예정)일</th>
						<th>상환원금</th>
						<th>세전이자</th>
						<th>세후이자</th>
						<th>세액</th>
						<th>이용료</th>
						<th>지급</th>
					</tr>

<? for ($i=0 ; $i<COUNT($RP_LIST) ; $i++) { ?>
					<tr>
						<td style="text-align:center;"><?=$RP_LIST[$i]["total_turn"]?></td>
						<td style="text-align:center;"><?=$RP_LIST[$i]["turn"]?></td>
						<td style="text-align:center;"><?=$RP_LIST[$i]["turn_sno"]?></td>
						<td style="text-align:center;"><?=$RP_LIST[$i]["repay_date"]?></td>
						<td style="text-align:right;"><?=number_format($RP_LIST[$i]["principal"])?></td>
						<td style="text-align:right;"><?=number_format($RP_LIST[$i]["total_interest"])?></td>
						<td style="text-align:right;"><?=number_format($RP_LIST[$i]["interest"])?></td>
						<td style="text-align:right;"><?=number_format($RP_LIST[$i]["tax"])?></td>
						<td style="text-align:right;"><?=number_format($RP_LIST[$i]["fee"])?></td>
						<td style="text-align:center;"><?=$RP_LIST[$i]["repay_yn"]=="Y"?"Y":""?></td>
					</tr>
<? } ?>

				</tabel>
			</div>
		</div>
	</div>
</div>