<?
include_once('./_common.php');

$g5['title'] = "상품 리스트";

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<?
$sql = "SELECT A.product_idx, SUM(A.principal) sum_prin,
			   MAX(total_turn) max_turn,
			   B.title, B.recruit_amount
		  FROM cf_product_turn_sum A
		  LEFT JOIN cf_product B ON(B.idx=A.product_idx)
		 GROUP BY A.product_idx
		 ORDER BY A.idx DESC";
$res = sql_query($sql);
$cnt = $res->num_rows;
$no = $cnt;
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
					<tr>
						<th>No</th>
						<th>품번</th>
						<th>상품명</th>
						<th>대출금액</th>
						<th>총회차</th>
					</tr>
<? 
for ($i=0 ; $i<$cnt ; $i++) {

	$row = sql_fetch_array($res);
	?>
					<tr>
						<td style="text-align:center;"><?=$no--?></td>
						<td style="text-align:center;"><?=$row["product_idx"]?></td>
						<td style="text-align:left;padding-left:10px;"><?=$row["title"]?></td>
						<td style="text-align:right;padding-right:10px;"><?=number_format($row["recruit_amount"])?></td>
						<td style="text-align:right;padding-right:10px;"><?=number_format($row["max_turn"])?></td>
					</tr>
	<?
}
?>
				</table>
			</div>
		</div>
	</div>
</div>