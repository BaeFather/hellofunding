<?
include_once('./_common.php');

$member_idx = $_REQUEST["member_idx"];

if (!$member_idx) die("nojumin");

$ci_sql = "SELECT mb_ci FROM g5_member WHERE mb_no='$member_idx'";
$ci_row = sql_fetch($ci_sql);
if ($ci_row["mb_ci"]) {
	$ret_arr = array();
	$ret_arr["ci"]=$ci_row["mb_ci"];
	echo json_encode($ret_arr, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE );
	EXIT;
}

$jm = getJumin($member_idx);

//echo $jm;die();

$hmac_key = "ASDFSDFEWRSDSESFSDFSERSDFSDFSDFC";
$cipher = "aes-256-cbc";
$sym_key = "ASDFGHJKLQWERTYUASDFGHJKLQWERTYU";
$iv = "QWERTYUJHGFDSAZX";

$output=null;
$retval=null;


$json_data = json_encode(array("jm",$jm),JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRESERVE_ZERO_FRACTION);
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_URL, "https://www.hellofunding.co.kr/mydata/nice_ci/get_ci_re.php");
$result = curl_exec($ch);
curl_close($ch);
echo $result; 
die();

//exec('/bin/php /home/crowdfund/public_html/mydata/nice_ci/get_ci_cli.php '.$jm, $output, $retval);
//$output[0] = '{"dataHeader":{"CNTY_CD":"kr","GW_RSLT_CD":"1200","GW_RSLT_MSG":"오류 없음"},"dataBody":{"rsp_cd":"P000","enc_data":"iw6FcBoZEM6m8H9bGpon77T0CUjfIbfMv0JC5zZhMebI3Y5JwHDQ/5foJ0zfFT55c9Fyg/3IV6gfNLh99v1/WbvYj9qjcESSIHYtH+qWg+2p3UWtYtZc2px+rIHG8v417LKPg8YQIfZmjvCvNjPCqfS4wE6iYne9TKWL0l6Jup27mQaAklkuvWn6Ny0kHaPN/D6Ks2a0Di7Vp3YTAzQ97FyyTO4Zyb3P1FaGTO8Dj/o=","integrity_value":"9ZdUqLtM3n9VoX2toekNPOy7IP0Bb+0b5yL0x+COWVw=","result_cd":"0000"}}';


$nice_res = json_decode($output[0],true);

if ($nice_res["dataHeader"]["GW_RSLT_CD"]=="1200") {

	if ($nice_res["dataBody"]["rsp_cd"]=="P000") {

		if ($nice_res["dataBody"]["result_cd"]=="0000") {

			$res_hmac = base64_encode(hash_hmac('sha256', $nice_res["dataBody"]["enc_data"], $hmac_key, true));

			if ($res_hmac == $nice_res["dataBody"]["integrity_value"]) {
				$res_dec = openssl_decrypt($nice_res["dataBody"]["enc_data"] ,$cipher , $sym_key, $options=0, $iv);
				$res_arr = json_decode($res_dec, true);
				$ci = $res_arr["ci1"];

				$up_sql = "UPDATE g5_member SET mb_ci='".$ci."' WHERE mb_no='$member_idx' AND mb_ci=''";
				sql_query($up_sql);

				$ret_arr = array();
				$ret_arr["ci"]=$ci;
				$ret_arr["up_sql"]=$up_sql;
				echo json_encode($ret_arr, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE );

			}

		}

	}
}
?>