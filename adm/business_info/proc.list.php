<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

include_once('./_common.php');
include_once('./business.class.php');

$strPost  = ARRAY("ldx");

FOR($i=0;$i<COUNT($strPost);$i++)
{
    ${$strPost[$i]} = $_POST[$strPost[$i]];
}

IF(!$ldx)
{

	$objval = ARRAY(
		"retcode"  => "X",
		"retalert" => STR_REPLACE("+"," ",urlencode("정상적인 접근이 아닙니다")),
		"retval"   => ""
	);

	ECHO json_encode($objval);
  //EXIT;

}
ELSE {

	$Business_Info  =  new Business_Info();
  $objval = $Business_Info->Fn_Category_Sum($ldx);

  $retval = ARRAY(
		"retcode"  => "OK",
		"retalert" => STR_REPLACE("+"," ",urlencode("정상 적으로 반영 완료되었습니다")),
		"retval"   => $objval
	);

	ECHO json_encode($retval);

}

sql_close($connect_for);

?>