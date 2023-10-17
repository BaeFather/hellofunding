<?

include_once('./_common.php');

$action = $_REQUEST['action'];
$save_dir = '/home/crowdfund/public_html/data/member';

$sql = "
	SELECT
		mb_no, mb_id,
		business_license, loan_co_license, bankbook, junior_doc1, junior_doc2, junior_doc3
	FROM
		g5_member
	WHERE 1
		AND (business_license!='' OR loan_co_license!='' OR bankbook!='' OR junior_doc1!='' OR junior_doc2!='' OR junior_doc3)
	ORDER BY
		mb_no ASC";
//echo $sql;
$res = sql_query($sql);
$rows = $res->num_rows;

for($i=0; $i<$rows; $i++) {

	$R = sql_fetch_array($res);
	//print_rr($R);

	if($R['business_license']) {

		$o_save_path = $save_dir . '/' . $R['business_license'];
		$n_save_dir = $save_dir . '/business_license/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

	if($R['loan_co_license']) {

		$o_save_path = $save_dir . '/' . $R['loan_co_license'];
		$n_save_dir = $save_dir . '/loan_co_license/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

	if($R['bankbook']) {

		$o_save_path = $save_dir . '/' . $R['bankbook'];
		$n_save_dir = $save_dir . '/bankbook/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

	if($R['junior_doc1']) {

		$o_save_path = $save_dir . '/junior/' . $R['junior_doc1'];
		$n_save_dir = $save_dir . '/junior_doc1/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

	if($R['junior_doc2']) {

		$o_save_path = $save_dir . '/junior/' . $R['junior_doc2'];
		$n_save_dir = $save_dir . '/junior_doc2/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

	if($R['junior_doc3']) {

		$o_save_path = $save_dir . '/junior/' . $R['junior_doc3'];
		$n_save_dir = $save_dir . '/junior_doc3/';

		$commend = "mv $o_save_path $n_save_dir";
		if($action=='yes') shell_exec($commend);
		print_rr($commend);

	}

}

sql_close();
exit;

?>