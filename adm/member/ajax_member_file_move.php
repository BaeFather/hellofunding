<?

// etcfile1, etcfile2 에 등록된 파일을 원하는 곳으로 이동하고 DB에 기록하기

include_once('_common.php');

/*
Array
(
    [mb_no] => 817
    [file_field] => etcfile1            <---- 이동대상파일 etcfile1 또는 etcfile2
    [move_field] => id_card             <---- 이쪽으로 파일 이동 및 DB필드값 변경
)
*/

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

$MB = sql_fetch("SELECT etcfile1, etcfile2, id_card, bankbook, junior_doc1, junior_doc2, junior_doc3, identify_zip_file, business_license, loan_co_license FROM g5_member WHERE mb_no = '".$mb_no."'");

if( $MB[$file_field] ) {

	$target_file_name = $MB[$file_field];

	$file_path = G5_DATA_PATH . '/member/etc/' . $target_file_name;										// 임시 업로드 경로
	$move_path = G5_DATA_PATH . '/member/' . $move_field . '/'. $target_file_name;		// 이동 목적 경로

	$o_file_name = $MB[$move_field];
	$o_file_path = G5_DATA_PATH . '/member/' . $move_field . '/' . $o_file_name;			// 기존파일 경로

	if( !is_dir($o_file_path) && is_file($o_file_path) ) {
		unlink($o_file_path);				// 기존파일 삭제
	}

	if( !is_dir($file_path) && is_file($file_path) ) {

		copy($file_path, $move_path);
		unlink($file_path);

		$sql = "
			UPDATE
				g5_member
			SET
				$file_field = '',
				$move_field = '".$target_file_name."'
			WHERE
				mb_no='".$mb_no."'";

		if( sql_query($sql) ) {
			member_edit_log($mb_no);

			$ARR = array('result'=>'SUCCESS', 'message'=>'');
			echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
		}
		else {
			$ARR = array('result'=>'FAIL', 'message'=>'DB QUERY ERROR');
			echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
		}

	}

}
else {
	$ARR = array('result'=>'FAIL', 'message'=>'임시 업로드된 데이터가 없습니다.');
	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
}


sql_close();
exit;

?>