<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
//처리


$sub_menu = '300770';
include_once('./_common.php');
include_once('./business.class.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '사업정보고시 관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');
?>
<?php
	$Business_Info  =  new Business_Info();

	SWITCH($RD)
	{
		CASE "2" :

		  IF(!$SD)
		  {
				msg_replace("접근이 올바르지 않습니다.", "/adm/business_info2");
				exit;
			}
			$num_per_page = 20;
			IF(!$page) { $page = 1; }
			$intLimit = $num_per_page * ($page-1);

		  $strList		=	$Business_Info->Fn_List($SD, $intLimit, $num_per_page);

			$total_page = CEIL($strList[0] / $num_per_page);

			$strFile = "./list.php";
		BREAK;

		DEFAULT :

			$strSelectYear	=	$Business_Info->Fn_Date_Select();
			$strList		=	$Business_Info->Fn_Main_List();

			$strFile = "./list_main.php";

		BREAK;
	}

	INCLUDE_ONCE($strFile);
?>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
