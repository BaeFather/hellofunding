<?php
include_once("/home/crowdfund/public_html/common.cli.php");

$dir_db_obj = array();
$dir_web_obj = array();

$backup_dir = '/home/crowdfund/BACKUP/dump.daily';
$count = 0;
if ($handle = opendir($backup_dir)) {
    $file_object = array();
    while ($object = readdir($handle)) {
        if (($object <> ".") && ($object <> "..")) {
			$full_filename = $backup_dir .'/'. $object;
			$file_object = array(
				'name' => $object,
				'size1' => filesize($full_filename),
				'size2' => filesize_formatted($full_filename),
				'time' => date("Y-m-d H:i:s", filemtime($full_filename)),
				'type' => filetype($full_filename)
			);

			if ($file_object['type']=="file") {
				if (strpos($object,'_db_')) {
					$dir_db_obj[] = $file_object;
					$count_db = $count_db + 1;
				} else if (strpos($object,'_web_')) {
					$dir_web_obj[] = $file_object;
					$count_web = $count_web + 1;
				}

			}
		}
    }

    closedir($handle);
}


if (count($dir_db_obj))  insert_db($dir_db_obj, "DB 일자별 백업 06시", "DB", "LIVE", "/home/crowdfund/BACKUP/dump.daily/", "ED06DB");
if (count($dir_web_obj)) insert_db($dir_web_obj, "WEB 일자별 백업 06시", "PRG", "LIVE", "/home/crowdfund/BACKUP/dump.daily/", "ED06PG");

?>


<?
function insert_db($obj, $backup_gubun, $src_gubun, $svr, $path, $bu_code) {

	global $connect_db_real;

	$ins_cnt = 0;

	for ($i=0 ; $i<count($obj); $i++) {

		$chk_sql = "SELECT count(idx) cnt FROM cf_backup_file_check WHERE backup_file_name='".$obj[$i]['name']."'";
		$chk_res = sql_query($chk_sql, G5_DISPLAY_SQL_ERROR, $connect_db_real);
		$chk_row = sql_fetch_array($chk_res);
		$chk_cnt = $chk_row["cnt"];

		if (!$chk_cnt) {

			$sql = "INSERT INTO cf_backup_file_check SET
						bu_code = '$bu_code',
						backup_gubun = '$backup_gubun',
						src_gubun = '$src_gubun',
						backup_file_location = '$svr',
						backup_file_path = '$path',
						backup_file_name='".$obj[$i]['name']."',
						backup_file_datetime = '".$obj[$i]['time']."',
						backup_file_size1 = '".$obj[$i]['size1']."',
						backup_file_size2 = '".$obj[$i]['size2']."',
						input_datetime=NOW() ";
			//echo "$sql\n";
			sql_query($sql, G5_DISPLAY_SQL_ERROR, $connect_db_real);
			$ins_cnt++;
		}

	}

	echo "$ins_cnt 건 입력 ($backup_gubun) \n";
}

sql_close();
?>
<?
function filesize_formatted($file)
{
    $bytes = filesize($file);

    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>