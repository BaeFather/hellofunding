<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

include_once('./_common.php');
include_once('./business.class.php');

$strPost  = ARRAY("section","kind","SE","page","RD");


FOR($i=0;$i<COUNT($strPost);$i++) {
	${$strPost[$i]} = $_POST[$strPost[$i]];
}

IF(!$section) {

	$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("정상적인 접근이 아닙니다")),"retval"=>"");
  ECHO json_encode($objval);
  EXIT;

}
ELSE {

	// 데이터 삭제
	if($kind=='DELETE') {
		$sql = "DELETE FROM cf_biz_info_re WHERE idx='".$SE."'";
		sql_query($sql);
		$objval = ARRAY("retcode"  => "OK", "retalert" => "");
		echo json_encode($objval);
		exit;
	}
	else {

		$Business_Info  =  new Business_Info();

		$strPost2 = $Business_Info->Fn_Column($section);

		FOR($i=0;$i<COUNT($strPost2);$i++) {
				$strValues[]  = $_POST[$strPost2[$i]];
		}

		$SE = $Business_Info->Fn_Board_Process($kind,$section,$strValues, $SE);

		$objval = ARRAY(
			"retcode"  => "OK",
			"retalert" => STR_REPLACE("+"," ",urlencode("정상 적으로 반영 완료되었습니다")),
			"retval"   => "./?RD=".$RD."&SD=".$section."&page=".$page
		);

		ECHO json_encode($objval);

	}

}

sql_close($connect_for);
exit;

?>