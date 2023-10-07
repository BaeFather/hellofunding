<?php

/*************************************************************************
**  SMS 함수 모음
*************************************************************************/

define('ADMIN_HP','15886760');

if( @!is_object($link3) ) {
	$link3      = sql_connect(G5_MYSQL_HOST3, G5_MYSQL_USER3, G5_MYSQL_PASSWORD3, G5_MYSQL_DB3);
	$select_db3 = sql_select_db(G5_MYSQL_DB3, $link3) or die('MySQL DB Error!!!');
	sql_set_charset('utf8', $link3);
}

// 관리자툴에 설정된 사용자/관리자발송 메세지 전송
function select_sms_send($type, $send_type_no, $replace_arr=array(), $to_hp, $from_hp=ADMIN_HP) {

	global $link3;

	if($type == 'admin') {		// 관리자 발송메세지일 경우
		$tbl_name = 'g5_sms_admininfo';
	}
	else {										// 사용자 발송메세지일 경우
		$tbl_name = 'g5_sms_userinfo';
	}

	$sms_sql = "SELECT msg FROM {$tbl_name} WHERE send_type_no='{$send_type_no}' AND use_yn='1'";
	$sms_r = sql_fetch($sms_sql);

	if($sms_r['msg'] && $sms_r['msg'] != '') {

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		// msg 치환
		if(count($replace_arr) > 0) {
			foreach($replace_arr as $k=>$v) {
				$sms_r['msg'] = str_replace($k, $v, $sms_r['msg']);
			}
		}

		$str_volume = mb_strwidth($sms_r['msg'], 'UTF-8');
		$msg_gubun = ($str_volume > 90) ? '1' : '0';		// : 0:SMS, 1:LMS

		$subject = ($msg_gubun=='1') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', $_SERVER['HTTP_HOST'])) ? 'dev' : '';

		$sql = "
			INSERT INTO
				agent_msgqueue
			SET
				kind='$msg_gubun',
				callbackNo='$from_hp',
				receiveNo='$to_hp',
				subject='$subject',
				message='".$sms_r['msg']."',
				registTime=NOW(),
				etc1='$etc1'";

		sql_query($send_sql, '', $link3);

		return 1;

	}
	else {

		return -1;		// 설정된 메세지가 없음

	}

}

/*카카오 알림톡 & 문자 Class 김성환 */
Class KaKao_Message_Send
{
		private $ndate;
		private $strTable;
		private $strQuery;
		private $strOrder;
		private $strLen;
		private $kakao_sendkey;
		private $Kakao_Sender_Key;
		private $Kakao_Nation_Code;
		private $kakao_Msg_Type;
		private $Kakao_ReSend;

		public $MEMBER;						// common.lib.php $member 변수
		public $DEPOSIT_MONEY;		// 입금액
		public $WIDTHDRW_MONEY;		// 출금액
		public $USER_DEPOSIT;			// 예치금 잔액
		public $PRODUCT_NAME;			// 상품명
		public $INVEST_MONEY;			// 투자금액
		public $USER_CONFIG;			// 자동투자 설정한 내용  - #{선택 카테고리 1} : #{최소 설정금액}원 ~ #{최대 설정금액}원 <br />
		public $PRODUCT_NUMBER;		// 상품호번

		Public Function __construct()
		{
				$this->ndate = DATE("Y-m-d H:i:s");
				$this->strTable = "g5_kakao_userinfo";
				$this->strQuery = "";
				$this->strOrder = "idx";
				$this->strLen = 10000;
				$this->Kakao_Sender_Key = "b44814856d9392d907284e022eefa19af3565c15";
				$this->Kakao_Nation_Code = "82";
				$this->kakao_Msg_Type = "7";
				$this->Kakao_ReSend = "Y";
		}

		Public Function __destruct()
		{
		}

		Public Function kakao_replace_key($strObj)
		{
			// 예치금 잔액 확인
			$retval =	preg_replace("/\{USER_NAME\}/", $this->MEMBER['mb_name'], $strObj);																										// 회원명
			$retval =	preg_replace("/\{ACCOUNT_NAME\}/", $this->MEMBER['va_private_name2'], $retval);																				// 신한은행 가상계좌 예금주
			$retval =	preg_replace("/\{ACCOUNT_NUMBER\}/", $this->MEMBER['virtual_account2'], $retval);																			// 신한은행 가상계좌 번호
			$retval =	preg_replace("/\{DEPOSIT_MONEY\}/", $this->f_number($this->DEPOSIT_MONEY), $retval);																	// 입금액
			$retval =	preg_replace("/\{WITHDRAW_ACCOUNT\}/", $this->MEMBER['bank_name']." ,  ".$this->MEMBER['account_num'], $retval);			// 출금계좌
			$retval =	preg_replace("/\{WIDTHDRW_MONEY\}/", $this->f_number($this->WIDTHDRW_MONEY), $retval);																// 출금액
			$retval =	preg_replace("/\{USER_DEPOSIT\}/", $this->f_number($this->MEMBER['mb_point']), $retval);															// 예치금 잔액
			$retval =	preg_replace("/\{PRODUCT_NAME\}/", $this->PRODUCT_NAME, $retval);																											// 상품명
			$retval =	preg_replace("/\{INVEST_MONEY\}/", $this->f_number($this->INVEST_MONEY), $retval);																		// 투자금액
			$retval =	preg_replace("/\{USER_CONFIG\}/", $this->USER_CONFIG, $retval);																												// 자동투자 설정내용
			$retval =	preg_replace("/\{PRODUCT_NUMBER\}/", $this->PRODUCT_NUMBER, $retval);																									// 상품호번

			return $retval;
		}

		Public Function kakao_message($tcode)
		{
			// 자동투자 예외처리
			IF(IN_ARRAY($tcode,ARRAY("hello012")))
			{
					$tcode = $this->kakao_message_auto_check();
			}

			$strColumn = ARRAY("idx","subject","content","turl");
			$strWhere  = " WHERE tcode='".$this->add_str($tcode)."'";
			$strLimit1 = 0;
			$strLimit2 = 1;

			$rowView = $this->fr_board_view($strColumn,$this->strTable,$this->strQuery,$strWhere,	$this->strOrder,$strLimit1,$strLimit2,$this->strLen,$connect_db);

			IF(@$rowView[0]["idx"])
			{
				$strRetTitle		= $rowView[0]["subject"];
				$strRetContent	=	$this->kakao_replace_key($rowView[0]["content"]);
				$strRetTurl		= $rowView[0]["turl"];
			}

			return ARRAY(
										"title" => $strRetTitle,
										"content" => $strRetContent,
										"turl"=>$strRetTurl,
										"tcode"=>$tcode
									);
		}

		Public Function kakao_message_auto_check()
		{
					$strColumn = ARRAY("ai_grp_idx","setup_amount","setup_amount2");
					$strWhere  = " WHERE member_idx='".$this->add_str($this->MEMBER['mb_no'])."'";
					$strLimit1 = 0;
					$strLimit2 = 100;
					$strTable = "cf_auto_invest_config_user";

					$strTarget = $this->auto_invert_config_target();

					$rowList = $this->fr_board_list($strColumn,$strTable,$this->strQuery,$strWhere,$this->strOrder,$strLimit1,$strLimit2,$this->strLen,$connect_db);

					IF($rowList[0] > 0) // 설정이 있다면
					{
						FOR($j=0;$j<COUNT($strTarget);$j++)
						{
							$strRetKind = false;
							FOR($i=0;$i<COUNT($rowList[1]);$i++)
							{
								IF($strTarget[$j][0] == $rowList[1][$i][0])
								{
									IF($retval)
									{
										$retval .= "\r\n";
									}
									$retval .= "-".$this->auto_invest_config_kind($rowList[1][$i][0])." : ".price_cutting($rowList[1][$i][1])."원 ~ ".price_cutting($rowList[1][$i][2])."원";
									$strRetKind = true;
									break;

								}
							}
							IF($strRetKind == false)
							{
								IF($retval)
								{
									$retval .= "\r\n";
								}
								$retval .= "-".$this->auto_invest_config_kind($strTarget[$j][0])." : 0만원 ~ 0만원";
							}
						}

						$this->USER_CONFIG = $retval; // 자동투자 내용 저장
					  return "hello012";	/*지정*/
					} ELSE {
						return "hello007";
					}
		}

		Public Function kakao_insert($tcode)
		{
				global $link3; // sms디비

				$strRet = $this->kakao_message($tcode);
				IF($strRet["tcode"] <> $tcode)
				{
					$tcode = $strRet["tcode"];
				}

				IF($strRet["title"])
				{
					$strJson = ARRAY(
											"name" => $strRet["title"],
											"type" => "WL",
											"url_mobile" => $strRet["turl"]
								);
					$strJson = json_encode($strJson,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				}

				$intContentLength = strlen(iconv("UTF-8","EUC-KR",$strRet["content"]));
				$Kakao_ReType = ($intContentLength <= 90) ? '4' : '6';

				$sql = "INSERT INTO Msg_Tran SET
							Phone_No = '".$this->MEMBER['mb_hp']."',
							Callback_No = '".ADMIN_HP."',
							Kakao_Sender_Key = '".$this->Kakao_Sender_Key."',
							Kakao_Template_Code = '".$tcode."',
							Kakao_Nation_Code = '".$this->Kakao_Nation_Code."',
							Message = '".$this->add_str($strRet["content"])."',
							Kakao_Button = '".$strJson."',
							Kakao_ReSend = '".$this->Kakao_ReSend."',
							Kakao_ReMessage ='".$this->add_str($strRet["content"])."',
							Kakao_ReType ='".$Kakao_ReType."',
							Send_Time = '".$this->ndate."',
							Save_Time = '".$this->ndate."',
							Msg_Type = '".$this->kakao_Msg_Type."'
				";
				sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
				//echo $sql;
		}

		Public Function auto_invert_config_target()
		{
			/*
			$retval = ARRAY(
											ARRAY("13","부동산"),
											ARRAY("14","주택담보"),
											ARRAY("15","동산담보"),
											ARRAY("16","헬로페이(소상공인)"),
											ARRAY("17","헬로페이(면세점 등)")
										);
			*/
			$retval = ARRAY(
											ARRAY("13","부동산"),
											ARRAY("14","주택담보"),
											ARRAY("15","동산담보"),
											ARRAY("16","헬로페이(소상공인)")
										);
			return $retval;

		}

		Public Function auto_invest_config_kind($obj)
		{
			$retval = $this->auto_invert_config_target();

			FOR($i=0;$i<COUNT($retval);$i++)
			{
				 IF($retval[$i][0] == $obj)
				 {
					 	$retobj = $retval[$i][1];
						break;
				 }
			}

			return $retobj;
		}

		Public FUNCTION f_number($obj)
	  {
			$retval = "";
			IF($obj)
			{
				$retval = NUMBER_FORMAT($obj);
			} ELSE {
				$retval = "0";
			}
			return $retval;
		}

		Public FUNCTION fr_board_view($frField,$frTable,$frQuery,$frWhere,$frorder,$frlimit1,$frlimit2,$strLen,$connect)
		{
			global $page;

			IF($frQuery)
			{
				$Query = "SELECT ".$frQuery." FROM ".$frTable." ".$frWhere." ORDER BY ".$frorder." LIMIT ".$frlimit1.",".$frlimit2;

			} ELSE {

				FOR($fri=0;$fri<COUNT($frField);$fri++)
				{
					IF($fri > 0)
					{
						$frFieldVal .= ",";
					}
					$frFieldVal .= $frField[$fri];
				}

				$Query = "SELECT ".$frFieldVal." FROM ".$frTable." ".$frWhere." ORDER BY ".$frorder." LIMIT ".$frlimit1.",".$frlimit2;
			}

			$Result = sql_query($Query,$connect);
			$retVal = "";

			$i = 0;
			$FR			=	ARRAY();
			WHILE($Row=sql_fetch_array($Result))
			{
				FOR($fri=0;$fri<COUNT($frField);$fri++)
				{
					if($frField[$fri] == "title")
					{
						IF($strLen)
						{
							$FR[$i][$frField[$fri]] = strcut_utf8(strip_str($Row[$frField[$fri]]),$strLen,"","..");
						} ELSE {
							$FR[$i][$frField[$fri]] = strip_str($Row[$frField[$fri]]);
						}

					} elseif($frField[$fri] == "reg_date") {
						$FR[$i][$frField[$fri]] = SUBSTR($Row[$frField[$fri]],0,10);
					} ELSE {
						$FR[$i][$frField[$fri]] = $Row[$frField[$fri]];
					}

				}
				$i++;
			}
			IF($i > 0)
			{
				sql_free_result($Result);
			} ELSE {
				$FR = ARRAY("","");
			}
			return $FR;
		}

		FUNCTION fr_board_list($frField,$frTable,$frQuery,$frWhere,$frorder,$frlimit1,$frlimit2,$strlen,$connect)
		{
			IF(!$strlen)
			{
				$strlen = 25;
			}
			$tQuery = "SELECT COUNT(*) as CNT FROM ".$frTable." ".$frWhere;
			$tResult = sql_query($tQuery,$connect);
			IF($Row=sql_fetch_array($tResult))
			{
				$frTotal = $Row["CNT"];
				sql_free_result($tResult);
			}

			IF($frQuery)
			{
				$Query = "SELECT ".$frQuery." FROM ".$frTable." ".$frWhere." ORDER BY ".$frorder." LIMIT ".$frlimit1.",".$frlimit2;

			} ELSE {

				FOR($fri=0;$fri<COUNT($frField);$fri++)
				{
					IF($fri > 0)
					{
						$frFieldVal .= ",";
					}
					$frFieldVal .= $frField[$fri];
				}

				$Query = "SELECT ".$frFieldVal." FROM ".$frTable." ".$frWhere." ORDER BY ".$frorder." LIMIT ".$frlimit1.",".$frlimit2;
			}

			$Result = sql_query($Query,$connect);

			$i = 0;

			$FR			 = ARRAY();

			WHILE($Row=sql_fetch_array($Result))
			{
				FOR($fri=0;$fri<COUNT($frField);$fri++)
				{
					UNSET($frFieldArr);

					$frFieldArr	=	EXPLODE(".",$frField[$fri]);

					if(COUNT($frFieldArr) == 1)
					{
						$frFieldArr[1] = $frField[$fri];
					}

					if($frFieldArr[1] == "title")
					{
						IF($Row[$frFieldArr[1]])
						{
							if($strlen2)
							{
								$FR[$i][$fri] = strcut_utf8(strip_tags(strip_str($Row[$frFieldArr[1]])),($strlen2),"","");
							} else {
								$FR[$i][$fri] = strcut_utf8(strip_str($Row[$frFieldArr[1]]),$strlen,"","");
							}
						} ELSE {
							$FR[$i][$fri] = $Row[$frFieldArr[1]];
						}

					} elseif($frField[$fri] == "reg_date") {
						$FR[$i][$fri] = SUBSTR($Row[$frFieldArr[1]],0,10);
					} ELSE {
						$FR[$i][$fri] = $this->strip_str($Row[$frFieldArr[1]]);
					}
				}
				$i++;
			}

			IF($i > 0)
			{
				sql_free_result($Result);
			} ELSE {
				$FR = "";
			}
			return ARRAY($frTotal,$FR);
		}

		FUNCTION add_str($strVal)
		{
			$strVal = addslashes(trim($strVal));
			return $strVal;
		}

		FUNCTION strip_str($strVal)
		{
			$strVal = stripslashes($strVal);
			return $strVal;
		}
}


// 문자전송
function unit_sms_send_smtnt($from_hp, $to_hp, $send_msg, $send_date=null, $send_id=null) {

	global $link3;

	$send_msg = trim(preg_replace("/․/",".", $send_msg));
	//$send_msg = addSlashes($send_msg);

	//$subject = addSlashes($subject);

	if($send_msg && $send_msg != '') {

		$send_id = ($send_id > 0) ? $send_id : '0';

		$str_volume = strlen(iconv("UTF-8", "EUC-KR", $send_msg));				// $str_volume = mb_strlen($send_msg, 'UTF-8');
		$msg_gubun  = ($str_volume <= 90) ? '4' : '6';										// : 4:SMS, 6:LMS		// $msg_gubun = ($str_volume <= 86) ? '4' : '6';

		if(strlen($to_hp) > 13) $to_hp = masterDecrypt($to_hp, false);		// 전화번호 유효범위의 텍스트길이를 초과할 경우 암호화 된것으로 간주하고 복호화 시도

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		$subject = ($msg_gubun=='6') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', @$_SERVER['HTTP_HOST'])) ? 'dev' : '';
		$isReserved = ($send_date=='') ? 'N' : 'Y';

		if($isReserved=='Y') {

			$sql = "
				INSERT INTO
					cf_Msg_Tran
				SET
					send_id     = '$send_id',
					Phone_No    = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type    = '$msg_gubun',
					Send_Time   = '$send_date',
					Save_Time   = NOW(),
					Subject     = '$subject',
					Message     = '$send_msg'";
			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		}
		else {

			$sql = "
				INSERT INTO
					Msg_Tran
				SET
					Phone_No    = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type    = '$msg_gubun',
					Send_Time   = NOW(),
					Save_Time   = NOW(),
					Subject     = '$subject',
					Message     = '$send_msg'";
			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		}

		//if($_SERVER['REMOTE_ADDR']=='183.98.101.114') { print_r($sql); }

		return 1;

	}
	else {

		return -1;		// 설정된 메세지가 없음

	}

}

// 문자전송
function unit_sms_send($from_hp, $to_hp, $send_msg, $send_date=null, $send_id=null) {

	global $link3;

	$send_msg = trim(preg_replace("/․/",".", $send_msg));

	if($send_msg && $send_msg != '') {

		$str_volume = strlen(iconv("UTF-8", "EUC-KR", $send_msg));				// $str_volume = mb_strlen($send_msg, 'UTF-8');
		$msg_gubun  = ($str_volume <= 90) ? '4' : '6';										// : 4:SMS, 6:LMS		// $msg_gubun = ($str_volume <= 86) ? '4' : '6';

		if(strlen($to_hp) > 13) $to_hp = masterDecrypt($to_hp, false);		// 전화번호 유효범위의 텍스트길이를 초과할 경우 암호화 된것으로 간주하고 복호화 시도

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		$subject = ($msg_gubun=='6') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', $_SERVER['HTTP_HOST'])) ? 'dev' : '';
		$isReserved = ($send_date=='') ? 'N' : 'Y';

		if($isReserved=='Y') {

			$sql = "
				INSERT INTO
					cf_Msg_Tran
				SET
					send_id     = '$send_id',
					Phone_No    = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type    = '$msg_gubun',
					Send_Time   = '$send_date',
					Save_Time   = NOW(),
					Subject     = '$subject',
					Message     = '$send_msg'";
			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		}
		else {

			$sql = "
				INSERT INTO
					Msg_Tran
				SET
					Phone_No    = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type    = '$msg_gubun',
					Send_Time   = NOW(),
					Save_Time   = NOW(),
					Subject     = '$subject',
					Message     = '$send_msg'";
			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		}

		return 1;

	}
	else {

		return -1;		// 설정된 메세지가 없음

	}

}

// 문자전송 (입력 테스트용)
function unit_sms_send_test($from_hp, $to_hp, $send_msg, $send_date=null,$send_id=null) {

	global $link3;

	$send_msg = trim($send_msg);

	if($send_msg && $send_msg != '') {

		$str_volume = mb_strlen($send_msg, 'UTF-8');
		$msg_gubun = ($str_volume <= 86) ? '4' : '6';		// : 4:SMS, 6:LMS

		if(strlen($to_hp) > 13) $to_hp = masterDecrypt($to_hp, false);		// 전화번호 유효범위의 텍스트길이를 초과할 경우 암호화 된것으로 간주하고 복호화 시도

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		$subject = ($msg_gubun=='6') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', $_SERVER['HTTP_HOST'])) ? 'dev' : '';
		$isReserved = ($send_date=='') ? 'N' : 'Y';

		if($isReserved=='Y') {

			$sql = "
				INSERT INTO
					cf_Msg_Tran_test
				SET
					send_id='".$send_id."',
					Phone_No = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type = '$msg_gubun',
					Send_Time = '$send_date',
					Save_Time = NOW(),
					Subject = '$subject',
					Message = '".$send_msg."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		} else {

			$sql = "
				INSERT INTO
					Msg_Tran_test
				SET
					Phone_No = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type = '$msg_gubun',
					Send_Time = NOW(),
					Save_Time = NOW(),
					Subject = '$subject',
					Message = '".$send_msg."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
			$insert_id = sql_insert_id($link3);

			return $insert_id;
		}

		return 1;

	} else {
		return -1;		// 설정된 메세지가 없음
	}

}

// 문자전송
function unit_sms_send_v2($from_hp, $to_hp, $send_msg, $send_date=null,$send_id=null) {

	global $link3;

	$send_msg = trim($send_msg);

	if($send_msg && $send_msg != '') {

		$str_volume = mb_strlen($send_msg, 'UTF-8');
		$msg_gubun = ($str_volume <= 86) ? '4' : '6';		// : 4:SMS, 6:LMS

		if(strlen($to_hp) > 13) $to_hp = masterDecrypt($to_hp, false);		// 전화번호 유효범위의 텍스트길이를 초과할 경우 암호화 된것으로 간주하고 복호화 시도

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		$subject = ($msg_gubun=='6') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', $_SERVER['HTTP_HOST'])) ? 'dev' : '';
		$isReserved = ($send_date=='') ? 'N' : 'Y';

		if($isReserved=='Y') {

			$sql = "
				INSERT INTO
					cf_Msg_Tran
				SET
					send_id='".$send_id."',
					Phone_No = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type = '$msg_gubun',
					Send_Time = '$send_date',
					Save_Time = NOW(),
					Subject = '$subject',
					Message = '".$send_msg."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		} else {

			$sql = "
				INSERT INTO
					Msg_Tran
				SET
					Phone_No = '$to_hp',
					Callback_No = '$from_hp',
					Msg_Type = '$msg_gubun',
					Send_Time = NOW(),
					Save_Time = NOW(),
					Subject = '$subject',
					Message = '".$send_msg."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
			$insert_id = sql_insert_id($link3);

			return $insert_id;
		}

		return 1;

	} else {
		return -1;		// 설정된 메세지가 없음
	}

}

// 문자전송
function unit_sms_send_back($from_hp, $to_hp, $send_msg, $send_date=null,$send_id=null) {

	global $link3;

	$send_msg = trim($send_msg);

	if($send_msg && $send_msg != '') {

		//$str_volume = mb_strwidth($send_msg, 'UTF-8');
		$str_volume = mb_strlen($send_msg, 'EUC-KR');
		$msg_gubun = ($str_volume <= 86) ? '0' : '1';		// : 0:SMS, 1:LMS

		/*if($_SERVER['REMOTE_ADDR']=="220.117.134.164") {
			echo $send_msg."\n\n";
			echo $str_volume."byte\n\n";
			return;
		}*/


		if(strlen($to_hp) > 13) $to_hp = masterDecrypt($to_hp, false);		// 전화번호 유효범위의 텍스트길이를 초과할 경우 암호화 된것으로 간주하고 복호화 시도

		$to_hp   = preg_replace('/[^0-9]*/s', '', $to_hp);
		$from_hp = preg_replace('/[^0-9]*/s', '', $from_hp);

		$subject = ($msg_gubun=='1') ? '헬로펀딩 메세지' : '';
		$etc1 = (preg_match('/dev\.hello/i', $_SERVER['HTTP_HOST'])) ? 'dev' : '';
		$isReserved = ($send_date=='') ? 'N' : 'Y';

		// 2018.8.7 sms 취소추가를 위해 이부분 주석처리하고 밑에 cf_agent_msgqueue 로 들어가도록 수정
		/*
		$sql = "
			INSERT INTO
				agent_msgqueue
			SET
				kind='".$msg_gubun."',
				callbackNo='".$from_hp."',
				receiveNo='".$to_hp."',
				subject='".$subject."',
				message='".$send_msg."',
				isReserved='".$isReserved."',
				registTime=NOW(),
				etc1='".$etc1."'";
		if($isReserved=='Y') $sql.=", reservedTime='".$send_date."'";

		sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
		$insert_id = sql_insert_id($link3);
		*/
		// --------------------------------------------------------------------------

		// 2018.8.7 전승찬 추가 imsi ---------------------------------------------------
		if($isReserved=='Y') {

			//$send_id = get_sms_send_id();

			$sql = "
				INSERT INTO
					cf_agent_msgqueue
				SET
					send_id='".$send_id."',
					kind='".$msg_gubun."',
					callbackNo='".$from_hp."',
					receiveNo='".$to_hp."',
					subject='".$subject."',
					message='".$send_msg."',
					isReserved='".$isReserved."',
					registTime=NOW(),
					etc1='".$etc1."'";

			if($isReserved=='Y') $sql.=", reservedTime='".$send_date."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);

		}
		else {

			$sql = "
				INSERT INTO
					agent_msgqueue
				SET
					kind='".$msg_gubun."',
					callbackNo='".$from_hp."',
					receiveNo='".$to_hp."',
					subject='".$subject."',
					message='".$send_msg."',
					isReserved='".$isReserved."',
					registTime=NOW(),
					etc1='".$etc1."'";
			if($isReserved=='Y') $sql.=", reservedTime='".$send_date."'";

			sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
			$insert_id = sql_insert_id($link3);

		}

		//if($_SERVER['REMOTE_ADDR']=='220.117.134.164') echo $sql."<br/>\n";

		// ------------------------------------------------------------------------

		return 1;

	}
	else {
		return -1;		// 설정된 메세지가 없음
	}

}


function get_sms_send_id() {
	global $link3;

	$sql = "select max(send_id) old_send_id from cf_agent_msgqueue";
	$res = sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
	$row = sql_fetch_array($res);

	$old_send_id = $row['old_send_id'];

	$new_send_id = $old_send_id + 1;

	return $new_send_id;
}

function get_sms_send_id_smtnt() {
	global $link3;

	$sql = "select max(send_id) old_send_id from cf_Msg_Tran";
	$res = sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
	$row = sql_fetch_array($res);

	$old_send_id = $row['old_send_id'];

	$new_send_id = $old_send_id + 1;

	return $new_send_id;
}

function get_sms_res($msg_id , $all) {

	global $link3;
	$res_msg = "";
	$LIST = array();

	$sql = "SELECT * FROM Msg_Tran WHERE Msg_Id='$msg_id'";
	$res = sql_query($sql, G5_DISPLAY_SQL_ERROR, $link3);
	$cnt = sql_num_rows($res);


	if (!$cnt) {
		$sms_log_tbl = "Msg_Log_".date("Ym");
		$sql = "SELECT * FROM $sms_log_tbl WHERE Msg_Id='$msg_id'";
		$res = sql_query($sql, G5_DISPLAY_SQL_ERROR , $link3);
		$cnt = sql_num_rows($res);

		if ($cnt) {
			$row = sql_fetch_array($res);
			$res_msg = $row["Result"];
		}

	} else {
		$row = sql_fetch_array($res);
		$res_msg = $row["Result"];
	}

	$LIST = $row;
	if ($all=="Y") return $LIST;

	return $res_msg;
}
?>
