<?
include_once('./_common.php');

while(list($key, $value) = each($_REQUEST)) { ${$key} = trim($value); }

if(!$is_admin || !$mb_no) { alert("올바른 경로가 아닙니다."); exit; }
if( !in_array($orderFile, array('business_license','loan_co_license','id_card','business_license','bankbook','loan_co_license','junior_doc1','junior_doc2','junior_doc3','corp_deungibu_doc','corp_owner_id_card_doc','corp_owner_quest_doc','corp_stockholders_doc','corp_ingam_doc','corp_noneprofit_policy_doc','indi_etc_identfy_doc1','indi_etc_identfy_doc2','identify_zip_file')) ) { alert("올바른 요청이 아닙니다."); exit; }


$UFILE = sql_fetch("
	SELECT
		mb_id, mb_name, mb_co_name, member_type, $orderFile
	FROM
		g5_member
	WHERE
		mb_no = '$mb_no'");

if(!$UFILE[$orderFile]) { alert("데이터가 없습니다."); exit; }

$upload_folder = G5_DATA_PATH . '/member';

if($orderFile=='business_license')                $doc_title = "사업자등록증사본";
else if($orderFile=='loan_co_license')            $doc_title = "대부업등록증사본";
else if($orderFile=='id_card')                    $doc_title = "신분증사본";
else if($orderFile=='bankbook')                   $doc_title = "통장사본";
else if($orderFile=='junior_doc1')                $doc_title = "법정대리인동의서";
else if($orderFile=='junior_doc2')                $doc_title = "가족관계증명서";
else if($orderFile=='junior_doc3')                $doc_title = "법정대리인신분증사본";
else if($orderFile=='corp_deungibu_doc')          $doc_title = "법인등기부등본";
else if($orderFile=='corp_owner_id_card_doc')     $doc_title = "법인대표자신분증";
else if($orderFile=='corp_owner_quest_doc')       $doc_title = "법인실소유자 정보양식";
else if($orderFile=='corp_stockholders_doc')      $doc_title = "주주명부";
else if($orderFile=='corp_ingam_doc')             $doc_title = "법인인감증명서";
else if($orderFile=='corp_noneprofit_policy_doc') $doc_title = "비영리단체정관";
else if($orderFile=='indi_etc_identfy_doc1')      $doc_title = "기타본인확인서류1";
else if($orderFile=='indi_etc_identfy_doc2')      $doc_title = "기타본인확인서류2";
else if($orderFile=='identify_zip_file')          $doc_title = "문서묶음첨부파일";


$upload_folder.= '/'.$orderFile;

$file_name = $UFILE[$orderFile];
$file_path = $upload_folder . "/" . $file_name;
$FILE_NAME = explode(".", $file_path);

$extension = $FILE_NAME[count($FILE_NAME)-1];

$name = ($UFILE['member_type']=='2') ? $UFILE['mb_co_name'] : $UFILE['mb_name'];
$download_name = $doc_title . "_" . $UFILE['mb_id'] . "(".$name.")" . ".". $extension;

if($file_name && $file_path) {

	if(file_exists($file_path)) {

		header("Content-Type: doesn/matter");
		header("content-length: ". filesize($file_path));
		header("Content-Disposition: attachment; filename=$download_name");
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
	else { alert("존재하지 않습니다."); exit; }
}
else { alert("존재하지 않습니다."); exit; }

exit;

?>