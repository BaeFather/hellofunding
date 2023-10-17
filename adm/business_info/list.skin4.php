
<table class="table table-striped table-bordered table-hover">
	<colgroup>
		<col width="6%">
		<col width="12%">
		<col width="12%">
		<col width="12%">
		<col width="12%">
		<col width="12%">
		<col width="12%">
		<col width="12%">
		<col width="%">
	</colgroup>
	<thead>
		<tr style="background:#F8F8EF">
			<th rowspan="2" class="text-center">NO.</th>
			<th rowspan="2" class="text-center">상품유형</th>
			<th rowspan="2" class="text-center">상품명</th>
			<th colspan="2" class="text-center">채권 원금</th>
			<th rowspan="2" class="text-center">매각 금액</th>
			<th rowspan="2" class="text-center">매각처</th>
			<th rowspan="2" class="text-center">매각일자</th>
			<th rowspan="2" class="text-center">관리</th>
		</tr>
		<tr style="background:#F8F8EF">
			<th class="text-center">실제</th>
			<th class="text-center">출력용</th>
		</tr>
	</thead>
	<tbody id="emp_list">
<?
for($i=0;$i<count($strList[1]);$i++) {

	$print_title = '';
	if($strList[1][$i]["product_mask_title"]) {
		$print_title = $strList[1][$i]["product_mask_title"];
	}
	else {
		if($strList[1][$i]["start_num"]) $print_title = '제' . $strList[1][$i]["start_num"] . '호';
	}

?>
		<tr class="odd">
			<td align="center"><?=$strList[1][$i]["idx"];?></td>
			<td align="center"><?=$Business_Info->fn_category_txt($strList[1][$i]["category"],$strList[1][$i]["mortgage_guarantees"]);?></td>
			<td align="center"><?=$print_title?></td>
			<td align="right"><?=@number_format($strList[1][$i]["recruit_amount"]);?></td>
			<td align="right"><?=($strList[1][$i]["mask_recruit_amount"] > 0)?@number_format($strList[1][$i]["mask_recruit_amount"]) : '';?></td>
			<td align="right"><?=@number_format($strList[1][$i]["sale_amount"]);?></td>
			<td align="center"><?=$strList[1][$i]["sale_place"];?></td>
			<td align="center"><?=$strList[1][$i]["sale_date"];?></td>
			<td align="center">
				<button type="button" class="btn btn-sm btn-primary" data-section="<?=$SD;?>" data-num="<?=$strList[1][$i]["idx"];?>" id="mod_btn">수정</button>
				<button type="button" class="btn btn-sm btn-danger" data-num="<?=$strList[1][$i]["idx"];?>" id="del_btn">삭제</button>
			</td>
		</tr>
<?
}
?>
	</tbody>
</table>
