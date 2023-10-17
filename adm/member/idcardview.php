<?
include_once('./_common.php');


while(list($key, $value) = each($_GET)) { ${$key} = trim($value); }

if( !preg_match("/183\.98\.101\.114/", $_SERVER['REMOTE_ADDR']) ) { header("HTTP/1.0 404 Not Found"); exit; }
if(!$is_admin) { header("HTTP/1.0 404 Not Found"); exit; }
if(!$mb_no) { header("HTTP/1.0 404 Not Found"); exit; }


$sql ="SELECT id_card FROM g5_member WHERE mb_no = '".$mb_no."'";
$row = sql_fetch($sql);

if($row['id_card']){
	$file_name  = $row['id_card'];
}
else{
	header("HTTP/1.0 404 Not Found"); exit;
}

$upload_folder = G5_DATA_PATH."/member/id_card";
$file_path = $upload_folder."/".$file_name;
//echo $file_path; exit;

if($file_name && $file_path) {
	if(file_exists($file_path)) {

		$FILE = pathinfo($file_path);

		if(preg_match("/(jpg|jpeg)/i", $FILE['extension'])) {

			header("Content-type:image/jpeg");
			echo file_get_contents($file_path);

		}
		else if(preg_match("/(png|gif)/i", $FILE['extension'])) {

			header("Content-type:image/".$FILE['extension']);
			echo file_get_contents($file_path);

		}
		else if(preg_match("/pdf/i", $FILE['extension'])) {

			header("Content-type:application/pdf");
			echo file_get_contents($file_path);

		}
		else {

			header("Content-Type: doesn/matter");
			header("content-length: ". filesize($file_path));
			header("Content-Disposition: attachment; filename=$file_name");
			header("Content-Transfer-Encoding: binary");
			header("Pragma: no-cache");
			header("Expires: 0");

			if(is_file($file_path)) {
				$fp = fopen($file_path, "r");
				if(!fpassthru($fp)) {
					fclose($fp);
				}
			}

		}

	}
	else {
		header("HTTP/1.0 404 Not Found"); exit;
	}
}
else {
	header("HTTP/1.0 404 Not Found"); exit;
}

?>