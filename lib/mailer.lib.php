<?php

include_once(G5_PHPMAILER_PATH.'/PHPMailerAutoload.php');
include_once(G5_PHPMAILER_PATH.'/class.smtp.php');

// 메일 보내기 (파일 여러개 첨부 가능)
// $type : text=0, html=1, text+html=2

function mailer($fromName, $fromMail, $toMail, $toName, $subject, $content, $type=0, $file='', $cc='', $bcc='') {

	global $config;
	global $g5;

	// 메일발송 사용을 하지 않는다면
	if(!$config['cf_email_use']) return;

	if($type != 1) $content = nl2br($content);

	$mail = new PHPMailer();							// defaults to using php "mail()"

	if(defined('G5_SMTP_PORT') && G5_SMTP_PORT) $mail->Port = G5_SMTP_PORT;

	///////////////////////////////////////////////////////////////////
	// Gmail 또는 외부 smtp 서버 사용할때, G5_SMTP_USE_EXT 1 로 세팅
	///////////////////////////////////////////////////////////////////
	if(defined('G5_SMTP_USE_EXT') && G5_SMTP_USE_EXT=='1') {
		$mail->IsSMTP();										// telling the class to use SMTP
		$mail->Host = G5_SMTP;							// SMTP server

		if(G5_SMTP=='smtp.naver.com') {
			$fromMail = "hellofunding@naver.com";
		}
		else if(G5_SMTP=='smtp.gmail.com') {
			$fromMail = "hellofunding@gmail.com";
		}
		$mail->SMTPAuth   = G5_SMTP_USEAUTH;
		$mail->SMTPSecure = G5_SMTP_USESECURE;
		$mail->Username   = G5_SMTP_USER;
		$mail->Password   = G5_SMTP_PASS;
	}
	else {
		$mail->Host = G5_SMTP;
	}

	$mail->CharSet     = "UTF-8";													// class.phpmailer.php 의 기본값이 iso-8859-1 이므로, UTF-8 로 변경함.
	$mail->Debugoutput = "html";
	$mail->Encoding    = "base64";												// 기본값이 8bit 이므로, base64로 변경함.
	$mail->AddAddress($toMail, $toName);									// 수신자
	$mail->SetFrom($fromMail, $fromName);									// 발신자
	$mail->AddReplyTo($fromMail, $fromName);
	$mail->AddReturnPath("mailuser@hellofunding.kr", "메일관리자");

	$mail->Subject = $subject;														// 제목
	//$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
	$mail->MsgHTML(stripSlashes($content));

	if($cc)  $mail->AddCC($cc);														// 참조자 이름 및 계정 정보
	if($bcc) $mail->AddBCC($bcc);													// 숨은 참조자 이름 및 계정 정보
	if($file != "") {
		foreach ($file as $f) {
			$mail->AddAttachment($f['path'], $f['name']);
		}
	}

	/*
	// KISA화이트도메인 결과가 오기 전까지 GMAIL 발송 금지
	if( !preg_match("/gmail\.com/i", $toMail) ) {
		return $mail->Send();
	}
	// 2018-08-23 화이트 도메인 등록 완료
	*/

	return $mail->Send();

}

// 파일을 첨부함. 서버에 업로드 되는 파일은 확장자를 주지 않는다.(보안 취약점)
function attach_file($filename, $tmp_name) {
	$dest_file = G5_DATA_PATH.'/tmp/'.str_replace('/', '_', $tmp_name);
	move_uploaded_file($tmp_name, $dest_file);
	$tmpfile = array("name" => $filename, "path" => $dest_file);
	return $tmpfile;
}

?>
