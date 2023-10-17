<?
include_once("_common.php");
?>
<center>
<br/><br/>
<table style="width:90%; border-collapse: collapse; border-width: 1px 1px 0px; border-style: solid; border-color: black;";>
	<tr>
		<th style="border:solid 1px black;">No</th>
		<th style="border:solid 1px black;">품번</th>
		<th style="border:solid 1px black;">상태</th>
		<th style="border:solid 1px black;">상품명</th>
		<th style="border:solid 1px black;">차주번호</th>
		<th style="border:solid 1px black;">차주명</th>
		<th style="border:solid 1px black;">CI</th>
	</tr>
<?
$sql = "SELECT A.idx, A.title, A.state, A.loan_mb_no,
			   B.mb_id, B.mb_name, B.member_type, B.mb_ci
		  FROM cf_product A
	 LEFT JOIN g5_member B ON(A.loan_mb_no=B.mb_no)
		 WHERE A.state IN (1, 8, 9)
		   AND B.member_type='1'
		 ORDER BY  A.idx DESC";
$res = sql_query($sql);
$cnt = sql_num_rows($res);
$no = $cnt;

for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);
	if ($row["state"]=="1") $state_txt = "상환중";
	else if ($row["state"]=="8") $state_txt = "연체";
	else if ($row["state"]=="9") $state_txt = "부도";
	else $state_txt=$row["state"];
	?>
	<tr>
		<td style="text-align:center;border-width: 1px 1px 1px; border-style: solid; border-color: black; height:30px;"><?=$no--?></td>
		<td style="text-align:center;border-width: 1px 1px 1px; border-style: solid; border-color: black;"><?=$row["idx"]?></td>
		<td style="text-align:center;border-width: 1px 1px 1px; border-style: solid; border-color: black;"><?=$state_txt?></td>
		<td style="text-align:left;border-width: 1px 1px 1px; border-style: solid; border-color: black; padding-left:10px;">
			<?=$row["title"]?></td>
		<td style="text-align:center;border-width: 1px 1px 1px; border-style: solid; border-color: black;"><?=$row["loan_mb_no"]?></td>
		<td style="text-align:center;border-width: 1px 1px 1px; border-style: solid; border-color: black;"><?=$row["mb_name"]?></td>
		<td style="text-align:left;border-width: 1px 1px 1px; border-style: solid; border-color: black; padding-left:5px;">
			<?=$row["mb_ci"]?><?//=substr($row["mb_ci"],0,7)?><?//=$row["mb_ci"]?"...":""?><?//=substr($row["mb_ci"],-7)?></td>
	</tr>
	<?
}
?>
</table>
<br/><br/>
</center>