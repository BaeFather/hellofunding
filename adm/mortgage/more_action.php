<?

include_once('./_common.php');

//$last_list = $_POST['last_list'];
$idx = $_POST['idx'];

$sql = "SELECT * FROM cf_product_turn WHERE turn > '13' and product_idx = '4517' ORDER BY turn ASC";
$res = sql_query($sql);
$cnt = $res->num_rows;

$idx = 0;  // 인덱스 번호 초기화

while($row = sql_fetch_array($res)) {
	$list[$idx] = $row;  // 배열
	$idx = $idx + 1;     // idx 값 1씩 증가

}

//echo $sql;
echo json_encode($list);  // 배열을 json으로 변환
?>