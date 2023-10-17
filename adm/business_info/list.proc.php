<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

include_once('./_common.php');
include_once('./business.class.php');

$strPost  = ARRAY("section","SE");

FOR($i=0;$i<COUNT($strPost);$i++)
{
    ${$strPost[$i]} = $_POST[$strPost[$i]];
}

$retVal = ARRAY();
IF($SE)
{
  $Business_Info  =  new Business_Info();
  $retVal = $Business_Info->Fn_View($section, $SE);
}

$product_idx = "";

SWITCH($section)
{
  CASE "1" :
    $retData = "
    <div class='form-group'>
        <label class='col-sm-1 control-label'>년도</label>
        <div class='col-sm-10'>
            <input type='text' name='biz_year' value='".$retVal["biz_year"]."' class='form-control' required>
        </div>
    </div>

    <div class='form-group'>
        <label class='col-sm-1 control-label'>내용</label>
        <div class='col-sm-10'>
            <input type='text' name='fin_contents' value='".$retVal["fin_contents"]."' class='form-control' required>
        </div>
    </div>

    <div class='form-group'>
        <label class='col-sm-1 control-label'>URL</label>
        <div class='col-sm-10'>
            <input type='text' name='fin_url' value='".$retVal["fin_url"]."' class='form-control' required>
        </div>
    </div>
    ";

  BREAK;

  CASE "2" :
  $retData = "
  <div class='form-group'>
      <label class='col-sm-1 control-label'>년도</label>
      <div class='col-sm-10'>
          <input type='text' name='biz_year' value='".$retVal["biz_year"]."' class='form-control' required>
      </div>
  </div>

  <div class='form-group'>
      <label class='col-sm-1 control-label'>임직원</label>
      <div class='col-sm-10'>
          <input type='text' name='emp_member' value='".$retVal["emp_member"]."' class='form-control' required>
      </div>
  </div>

  <div class='form-group'>
      <label class='col-sm-1 control-label'>여신심사역</label>
      <div class='col-sm-10'>
          <input type='text' name='emp_simsa' value='".$retVal["emp_simsa"]."' class='form-control' required>
      </div>
  </div>

  <div class='form-group'>
      <label class='col-sm-1 control-label'>전문인력</label>
      <div class='col-sm-10'>
          <input type='text' name='emp_professional' value='".$retVal["emp_professional"]."' class='form-control' required>
      </div>
  </div>
  ";
  BREAK;

  CASE "3" :
  $retData = "
  <div class='form-group'>
      <label class='col-sm-1 control-label'>년도</label>
      <div class='col-sm-10'>
          <input type='text' name='biz_year' value='".$retVal["biz_year"]."' class='form-control' required>
      </div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>대주주현황</label>
      <div class='col-sm-10'>
          <input type='text' name='major_shareholder' value='".$retVal["major_shareholder"]."' class='form-control' required>
      </div>
  </div>
  ";
  BREAK;

  CASE "4" :
  $retData = "
  <div class='form-group'>
      <label class='col-sm-1 control-label'>상품 고유번호</label>
      <div class='col-sm-10'>
				<ul style='width:100%;list-style:none;display:inline-block; padding:0; margin:0;'>
					<li style='float:left;'><input type='text' name='product_idx' id='product_idx' value='".$retVal["product_idx"]."' class='form-control' required></li>
					<li style='float:left;margin-left:8px;'><button type='button' id='load_product_info' class='btn btn-primary'>상품정보호출</button></li>
				</ul>
			</div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>상품유형</label>
      <div class='col-sm-10'>
           <input type='text' name='category' id='category' class='form-control'>
      </div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>상품 호번</label>
      <div class='col-sm-10'>
          <input type='text' name='start_num' id='start_num' class='form-control'>
      </div>
  </div>
	<div class='form-group'>
      <label class='col-sm-1 control-label'>채권 원금</label>
      <div class='col-sm-10'>
        <ul style='width:100%;list-style:none;display:inline-block; padding:0; margin:0;'>
					<li style='float:left;padding:8px'>실제</li>
					<li style='float:left;'><input type='text' name='recruit_amount' id='recruit_amount' class='form-control' readonly></li>
					<li style='float:left;padding:8px;margin-left:8px'>출력용</li>
					<li style='float:left;'><input type='text' name='mask_recruit_amount' id='mask_recruit_amount' value='".$retVal['mask_recruit_amount']."' class='form-control'></li>
				</ul>
			</div>
	</div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>매각 금액</label>
      <div class='col-sm-10'>
          <input type='text' name='sale_amount' value='".$retVal["sale_amount"]."' class='form-control' required>
      </div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>매각처</label>
      <div class='col-sm-10'>
          <input type='text' name='sale_place' value='".$retVal["sale_place"]."' class='form-control' required>
      </div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>매각일자</label>
      <div class='col-sm-10'>
          <input type='text' name='sale_date' value='".$retVal["sale_date"]."' class='form-control' required>
      </div>
  </div>
  <div class='form-group'>
      <label class='col-sm-1 control-label'>상품명</label>
      <div class='col-sm-10'>
          <input type='text' name='product_mask_title' value='".$retVal["product_mask_title"]."' class='form-control' placeholder='입력시에만 본 내용으로 출력'>
      </div>
  </div>

	";
  $product_idx = $retVal["product_idx"];
  BREAK;
}
/*
IF($retData)
{
    ECHO $retData;
}
*/
$ARR = array('lbody'=>$retData, 'product_idx'=>$product_idx);
echo json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);


sql_close($connect_for);

?>