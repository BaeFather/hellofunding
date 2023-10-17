<?
// 2021-03 전승찬 천재, 윤선미
// 신규 프로그램 작성이 선미씨의 경험과 달라 
// 서로 사맞디 아니할세
// 내 이를 가엽게 여겨 새로 방식을 변경하니
// 널리 이용토록 하라

include_once('./_common.php');

// 변수를 편하게 사용하기 위해 값에 지정
$idx = $_POST['idx'];
$hcseq = $_POST['hcseq'];

$mortgage_ranking = $_POST['mortgage_ranking'];
$mortgage_info = $_POST['mortgage_info'];
$pbirth = $_POST['pbirth'];
$pphone1 = $_POST['pphone1'];
$pphone2 = $_POST['pphone2'];
$pcp_name = $_POST['pcp_name'];
$pcp_addr = $_POST['pcp_addr'];
$pcp_contact = $_POST['pcp_contact'];
$pcredit_score = $_POST['pcredit_score'];
$dambo_pname = $_POST['dambo_pname'];
$dambo_pbirth = $_POST['dambo_pbirth'];
$dambo_pphone = $_POST['dambo_pphone'];
$dambo_cscore = $_POST['dambo_cscore'];
$brokerage_fee = $_POST['brokerage_fee'];
$brokerage_reamount = $_POST['brokerage_reamount'];
$middle_refee = $_POST['middle_refee'];
$middle_repayment_list = $_POST['middle_repayment_list'];
$seller = $_POST['seller'];
$sale_detail = $_POST['sale_detail'];
$hash_tag = $_POST['hash_tag'];


// 기존 저장된 데이터 select 후 원본 파일, 임시 파일명의 필드 값 변수 지정
$sql = "SELECT * FROM hloan_content WHERE hcseq='$hcseq'";
$res = sql_query($sql);
$old_data = sql_fetch_array($res);
$ori_file_names = $old_data["origin_file"];
$tmp_file_names = $old_data["temp_file"];


// 파일 업로드
if($_FILES['origin_file']['name'][0]) {
	// 파일명 초기화
	$ori_file_names = "";  
	$tmp_file_names = "";


	// hcseq 값이 있으면 원본 파일명, 임시 파일명의 값이 select 된 필드 값
	if($_REQUEST['hcseq']) {
		$row = sql_fetch("SELECT origin_file, temp_file FROM hloan_content WHERE hcseq = '".$_REQUEST['hcseq']."'");

		$ori_file_names = $row['origin_file'];
		$tmp_file_names = $row['temp_file'];
	}

	// 파일 업로드 관련 변수 지정
	$uploads_dir  = "uploads/";
	$allowed_ext = array('jpg','jpeg','png','gif','pdf','doc','docx','xlsx','xls','hwp','JPG','JPEG','PNG','GIF','PDF','DOC','DOCX','XLSX','XLS','HWP');

	$error = $_FILES['origin_file']['error'];
	$name = $_FILES['origin_file']['name'];

	$sw = true;


	// 원본 파일명을 가진 파일의 갯수만큼 루프
	for($i=0; $i<count($_FILES['origin_file']['name']); $i++) {

		$ext = substr($name[$i], strrpos($name[$i],'.') + 1);  // 확장자만 담는 변수 ex) jpg
		$ext = strtoupper($ext);
		//echo $ext; die();
		$uploadFile = $uploads_dir.basename($_FILES['origin_file']['tmp_name'][$i]);  // uploads/임시파일명의 basename 


		// 확장자 체크
		if(in_array($ext, $allowed_ext)) {  

			// 서버로 전송된 파일을 저장할 때 - move_uploaded_file(파일, 옮겨질 곳)
			if(move_uploaded_file($_FILES['origin_file']['tmp_name'][$i], $uploadFile)) {
				
				$ori_file_names .= $_FILES['origin_file']['name'][$i]. ";";
				$tmp_file_names .= basename($_FILES['origin_file']['tmp_name'][$i]). ";";
			
			} else {
				$sw = false;
			}

		
		} else {
			echo "<script>alert('허용되지 않는 확장자입니다.'); history.back();</script>"; 
			$sw = false;
		}
	}

	if (!$sw) {
		echo "<script>alert('파일 업로드 실패'); history.back();</script>"; 
		EXIT;
	}

}


// update sql
$sql = "UPDATE 
			hloan_content
		SET
			mortgage_ranking = '$mortgage_ranking',
			mortgage_info = '$mortgage_info',
			pbirth = '$pbirth',
			pphone1 = '$pphone1',
			pphone2 = '$pphone2',
			pcp_name = '$pcp_name',
			pcp_addr = '$pcp_addr',
			pcp_contact = '$pcp_contact',
			pcredit_score = '$pcredit_score',
			dambo_pname = '$dambo_pname',
			dambo_pbirth = '$dambo_pbirth',
			dambo_pphone = '$dambo_pphone',
			dambo_cscore = '$dambo_cscore',
			brokerage_fee = '$brokerage_fee',
			brokerage_reamount = '$brokerage_reamount',
			middle_refee = '$middle_refee',
			middle_repayment_list = '$middle_repayment_list',
			seller = '$seller',
			sale_detail = '$sale_detail',
			origin_file = '$ori_file_names',
			temp_file = '$tmp_file_names',
			hash_tag = '$hash_tag'
		WHERE
			hcseq = '$hcseq'
";

$result = sql_query($sql); 


goto_url('./mortgage_detail_form.php?save=Y&idx='.$idx, false);

?>
