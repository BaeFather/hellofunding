<?
///////////////////////////////////////////////////////////////////////////////
// 파일 출력
// /adm/member/fileView.php?mb_no=회원번호&gbn=필드명
///////////////////////////////////////////////////////////////////////////////

include_once('./_common.php');


while(list($key, $value) = each($_GET)) { ${$key} = trim($value); }


if( !preg_match("/183\.98\.101/", $_SERVER['REMOTE_ADDR']) ) { header("HTTP/1.0 404 Not Found"); exit; }
if( !$is_admin ) { header("HTTP/1.0 404 Not Found"); exit; }
if( !in_array($member['mb_id'], array('admin_sori9th','admin_hellosiesta','admin_sundol4','admin_foolish34')) ) { header("HTTP/1.0 404 Not Found"); exit; }
if( !$mb_no || !$gbn ) { header("HTTP/1.0 404 Not Found"); exit; }

// $gbn 은 DB 필드명이며, 디렉토리명임.
$GBN = array(
	'business_license',
	'loan_co_license',
	'id_card',
	'bankbook',
	'junior_doc1',
	'junior_doc2',
	'junior_doc3',
	'corp_deungibu_doc',
	'corp_owner_id_card_doc',
	'corp_owner_quest_doc',
	'corp_stockholders_doc',
	'corp_ingam_doc',
	'corp_noneprofit_policy_doc',
	'indi_etc_identfy_doc1',
	'indi_etc_identfy_doc2',
	'identify_zip_file',
	'etcfile1',
	'etcfile2'
);

if(!in_array($gbn, $GBN)) { header("HTTP/1.0 404 Not Found"); exit; }


$sql ="SELECT $gbn FROM g5_member WHERE mb_no = '".$mb_no."'";
$row = sql_fetch($sql);

if($row[$gbn]){
	$file_name  = $row[$gbn];
}
else{
	header("HTTP/1.0 404 Not Found"); exit;
}

if($gbn=='etcfile1' || $gbn=='etcfile2') {
	$upload_folder = G5_DATA_PATH."/member/etc";
}
else {
	$upload_folder = G5_DATA_PATH."/member/" . $gbn;
}

$file_path = $upload_folder."/".$file_name;


if($file_name && $file_path) {
	if(file_exists($file_path)) {

		$FILE = pathinfo($file_path);

		if(preg_match("/(jpg|jpeg)/i", $FILE['extension'])) {

			header("Pragma: no-cache");
			header("Expires: 0");
			header("Content-type:image/jpeg");
			echo file_get_contents($file_path);

		}
		else if(preg_match("/(png|gif)/i", $FILE['extension'])) {

			header("Pragma: no-cache");
			header("Expires: 0");
			header("Content-type:image/".$FILE['extension']);
			echo file_get_contents($file_path);

		}
		else if(preg_match("/pdf/i", $FILE['extension'])) {

			header("Pragma: no-cache");
			header("Expires: 0");
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


sql_close();
exit;

?>