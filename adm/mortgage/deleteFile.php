<?
// 2021-03-25 전승찬, 윤선미

include_once('./_common.php');


// get으로 넘어온 필드 항목을 변수로 지정
$idx   = $_GET['idx'];
$hcseq = $_GET['hcseq'];
$oname = $_GET['oname'];
$tname = $_GET['tname'];

if (!$hcseq) die("hcseq 오류");

// 해당 hcseq의 모든 필드를 select (기존 저장된 값) 
$sql = "SELECT * FROM hloan_content WHERE hcseq='$hcseq'";
$res = sql_query($sql);
$old_data = sql_fetch_array($res);


$del_file_res = unlink("uploads/".$tname);
 

// 원본 파일, 임시 파일
$ori_file_names = $old_data["origin_file"];  
$tmp_file_names = $old_data["temp_file"];


// 해당 파일 삭제될 때 sql update
$new_ori_file_name = str_replace($oname.";", "", $ori_file_names); 
$new_tmp_file_name = str_replace($tname.";", "", $tmp_file_names); // str_replace(find, replace, string) : 문자열 치환 함수 ex) adfie;eiane;fnaee; 에서 fnaee 삭제시 adfie;eiane;


$sql = "UPDATE 
			hloan_content
		SET
			origin_file = '$new_ori_file_name',
			temp_file = '$new_tmp_file_name'
		WHERE
			hcseq = '$hcseq'
";

// 삭제된 파일은 지워지고 update가 되어야 함
$result = sql_query($sql); 


goto_url('./mortgage_detail_form.php?save=Y&idx='.$idx, false);

?>