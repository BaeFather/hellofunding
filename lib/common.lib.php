<?
###############################################################################
##	공통 라이브러리
##		2019-01-21 업데이트 : get_member() 수정, getJumin 추가
###############################################################################

if(!defined('_GNUBOARD_')) exit;

/*************************************************************************
 **
 **  일반 함수 모음
 **
 *************************************************************************/

// 마이크로 타임을 얻어 계산 형식으로 만듦
function get_microtime()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}


// 한페이지에 보여줄 행, 현재페이지, 총페이지수, URL
function get_paging($write_pages, $cur_page, $total_page, $url, $add="")
{
	//$url = preg_replace('#&amp;page=[0-9]*(&amp;page=)$#', '$1', $url);
	$url = preg_replace('#&amp;page=[0-9]*#', '', $url) . '&amp;page=';

	$str = '';
	if($cur_page > 1) {
		$str .= '<a href="'.$url.'1'.$add.'" class="pg_page pg_start">처음</a>'.PHP_EOL;
	}

	$start_page = ( ( (int)( ($cur_page - 1 ) / $write_pages ) ) * $write_pages ) + 1;
	$end_page = $start_page + $write_pages - 1;

	if($end_page >= $total_page) $end_page = $total_page;

	if($start_page > 1) $str .= '<a href="'.$url.($start_page-1).$add.'" class="pg_page pg_prev">이전</a>'.PHP_EOL;

	if($total_page > 1) {
		for($k=$start_page;$k<=$end_page;$k++) {
			if($cur_page != $k)
				$str .= '<a href="'.$url.$k.$add.'" class="pg_page">'.$k.'<span class="sound_only">페이지</span></a>'.PHP_EOL;
			else
				$str .= '<span class="sound_only">열린</span><strong class="pg_current">'.$k.'</strong><span class="sound_only">페이지</span>'.PHP_EOL;
		}
	}

	if($total_page > $end_page) $str .= '<a href="'.$url.($end_page+1).$add.'" class="pg_page pg_next">다음</a>'.PHP_EOL;

	if($cur_page < $total_page) {
		$str .= '<a href="'.$url.$total_page.$add.'" class="pg_page pg_end">맨끝</a>'.PHP_EOL;
	}

	if($str)
		return "<div class=\"pg_wrap\"><span class=\"pg\">{$str}</span></div>";
	else
		return "";
}

// 페이징 코드의 <nav><span> 태그 다음에 코드를 삽입
function page_insertbefore($paging_html, $insert_html)
{
	if(!$paging_html)
		$paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';

	return preg_replace("/^(<nav[^>]+><span[^>]+>)/", '$1'.$insert_html.PHP_EOL, $paging_html);
}

// 페이징 코드의 </span></nav> 태그 이전에 코드를 삽입
function page_insertafter($paging_html, $insert_html)
{
	if(!$paging_html)
		$paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';

	if(preg_match("#".PHP_EOL."</span></nav>#", $paging_html))
		$php_eol = '';
	else
		$php_eol = PHP_EOL;

	return preg_replace("#(</span></nav>)$#", $php_eol.$insert_html.'$1', $paging_html);
}


// 변수 또는 배열의 이름과 값을 얻어냄. print_r() 함수의 변형
function print_r2($var)
{
	ob_start();
	print_r($var);
	$str = ob_get_contents();
	ob_end_clean();
	$str = str_replace(" ", "&nbsp;", $str);
	echo nl2br("<span style='font-family:Tahoma, 굴림; font-size:9pt;'>$str</span>");
}


function get_head_title($title){
	global $g5;

	if( isset($g5['board_title']) && $g5['board_title'] ){
		$title = $g5['board_title'];
	}

	return $title;
}


// 메타태그를 이용한 URL 이동
// header("location:URL") 을 대체
function goto_url($url)
{
	$url = str_replace("&amp;", "&", $url);
	//echo "<script> location.replace('$url'); </script>";

	if(!headers_sent())
		header('Location: '.$url);
	else {
		echo '<script>';
		echo 'location.replace("'.$url.'");';
		echo '</script>';
		echo '<noscript>';
		echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
		echo '</noscript>';
	}
	exit;
}


// 세션변수 생성
function set_session($session_name, $value)
{
	if(PHP_VERSION < '5.3.0')
		session_register($session_name);
	// PHP 버전별 차이를 없애기 위한 방법
	$$session_name = $_SESSION[$session_name] = $value;
}


// 세션변수값 얻음
function get_session($session_name)
{
	return isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : '';
}


// 쿠키변수 생성
function set_cookie($cookie_name, $value, $expire)
{
	global $g5;

	$httpOnly = true;
	$secure = (@$_SERVER['REQUEST_SCHEME']=='https') ? true : false;
//$secure = true;

	setcookie(
		md5($cookie_name),
		base64_encode($value),
		G5_SERVER_TIME + $expire,
		'/',
		G5_COOKIE_DOMAIN,
		$httpOnly,
		$secure
	);
}


// 쿠키변수값 얻음
function get_cookie($cookie_name)
{
	$cookie = md5($cookie_name);
	if(array_key_exists($cookie, $_COOKIE))
		return base64_decode($_COOKIE[$cookie]);
	else
		return "";
}

// 경고메세지를 경고창으로
function alert($msg='', $url='', $error=true, $post=false)
{
	global $g5, $config, $member;
	global $is_admin;

	if(!$msg) $msg = '올바른 방법으로 이용해 주십시오.';

	$header = '';
	if(isset($g5['title'])) {
		$header = $g5['title'];
	}

	include_once(G5_BBS_PATH.'/alert.php');
	exit;
}

// 경고메세지 출력후 창을 닫음
function alert_close($msg, $error=true)
{
	global $g5;

	$header = '';
	if(isset($g5['title'])) {
		$header = $g5['title'];
	}
	include_once(G5_BBS_PATH.'/alert_close.php');
	exit;
}


// 2017-07-18 추가분 -------------------------------------------------------------
function msg_go($msg="", $href=0, $target="window")
{
	$go = $href ? "$target.location.href='$href';" : "history.go(-1);";
	$alert = $msg ? "window.alert('$msg');" : "";

	echo " <script> ";
	echo "  $alert ";
	echo "  $go ";
	echo " </script> ";
	exit;
}

//히스토리 빽 방지를 위한 함수
function msg_replace($msg="", $href=0, $target="window")
{
	$go = $href ? "$target.location.replace('$href');" : "$target.location.replace('about:blank');";
	$alert = $msg ? "window.alert('$msg');" : "";

	echo " <script> ";
	echo "  $alert ";
	echo "  $go ";
	echo " </script> ";
	exit;
}

function msg_reload($msg="", $target="window")
{
	$alert = $msg ? "window.alert('$msg');" : "";
	$reload = "{$target}.location.reload();";
	echo " <script> ";
	echo "  $alert ";
	echo "  $reload ";
	echo " </script> ";
	exit;
}

function msg_close($msg="")
{
	echo " <script> ";
	echo "  window.alert('$msg'); ";
	echo "  top.close(); ";
	echo " </script> ";
	exit;
}
// 2017-07-18 추가분 -------------------------------------------------------------


// confirm 창
function confirm($msg, $url1='', $url2='', $url3='')
{
	global $g5;

	if(!$msg) {
		$msg = '올바른 방법으로 이용해 주십시오.';
		alert($msg);
	}

	if(!trim($url1) || !trim($url2)) {
		$msg = '$url1 과 $url2 를 지정해 주세요.';
		alert($msg);
	}

	if(!$url3) $url3 = clean_xss_tags($_SERVER['HTTP_REFERER']);

	$msg = str_replace("\\n", "<br>", $msg);

	$header = '';
	if(isset($g5['title'])) {
		$header = $g5['title'];
	}
	include_once(G5_BBS_PATH.'/confirm.php');
	exit;
}


// way.co.kr 의 wayboard 참고
function url_auto_link($str)
{
	global $g5;
	global $config;

	// 140326 유창화님 제안코드로 수정
	// http://sir.kr/pg_lecture/461
	// http://sir.kr/pg_lecture/463
	$str = str_replace(array("&lt;", "&gt;", "&amp;", "&quot;", "&nbsp;", "&#039;"), array("\t_lt_\t", "\t_gt_\t", "&", "\"", "\t_nbsp_\t", "'"), $str);
	//$str = preg_replace("`(?:(?:(?:href|src)\s*=\s*(?:\"|'|)){0})((http|https|ftp|telnet|news|mms)://[^\"'\s()]+)`", "<A HREF=\"\\1\" TARGET='{$config['cf_link_target']}'>\\1</A>", $str);
	$str = preg_replace("/([^(href=\"?'?)|(src=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[가-힣\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,\(\)]+)/i", "\\1<A HREF=\"\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
	$str = preg_replace("/(^|[\"'\s(])(www\.[^\"'\s()]+)/i", "\\1<A HREF=\"http://\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
	$str = preg_replace("/[0-9a-z_-]+@[a-z0-9._-]{4,}/i", "<a href=\"mailto:\\0\">\\0</a>", $str);
	$str = str_replace(array("\t_nbsp_\t", "\t_lt_\t", "\t_gt_\t", "'"), array("&nbsp;", "&lt;", "&gt;", "&#039;"), $str);

	/*
	// 속도 향상 031011
	$str = preg_replace("/&lt;/", "\t_lt_\t", $str);
	$str = preg_replace("/&gt;/", "\t_gt_\t", $str);
	$str = preg_replace("/&amp;/", "&", $str);
	$str = preg_replace("/&quot;/", "\"", $str);
	$str = preg_replace("/&nbsp;/", "\t_nbsp_\t", $str);
	$str = preg_replace("/([^(http:\/\/)]|\(|^)(www\.[^[:space:]]+)/i", "\\1<A HREF=\"http://\\2\" TARGET='{$config['cf_link_target']}'>\\2</A>", $str);
	//$str = preg_replace("/([^(HREF=\"?'?)|(SRC=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,]+)/i", "\\1<A HREF=\"\\2\" TARGET='$config['cf_link_target']'>\\2</A>", $str);
	// 100825 : () 추가
	// 120315 : CHARSET 에 따라 링크시 글자 잘림 현상이 있어 수정
	$str = preg_replace("/([^(HREF=\"?'?)|(SRC=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[가-힣\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,\(\)]+)/i", "\\1<A HREF=\"\\2\" TARGET='{$config['cf_link_target']}'>\\2</A>", $str);

	// 이메일 정규표현식 수정 061004
	//$str = preg_replace("/(([a-z0-9_]|\-|\.)+@([^[:space:]]*)([[:alnum:]-]))/i", "<a href='mailto:\\1'>\\1</a>", $str);
	$str = preg_replace("/([0-9a-z]([-_\.]?[0-9a-z])*@[0-9a-z]([-_\.]?[0-9a-z])*\.[a-z]{2,4})/i", "<a href='mailto:\\1'>\\1</a>", $str);
	$str = preg_replace("/\t_nbsp_\t/", "&nbsp;" , $str);
	$str = preg_replace("/\t_lt_\t/", "&lt;", $str);
	$str = preg_replace("/\t_gt_\t/", "&gt;", $str);
	*/

	return $str;
}


// url에 http:// 를 붙인다
function set_http($url)
{
	if(!trim($url)) return;

	if(!preg_match("/^(http|https|ftp|telnet|news|mms)\:\/\//i", $url))
		$url = "http://" . $url;

	return $url;
}


// 파일의 용량을 구한다.
//function get_filesize($file)
function get_filesize($size)
{
	//$size = @filesize(addslashes($file));
	if($size >= 1048576) {
		$size = number_format($size/1048576, 1) . "M";
	} else if($size >= 1024) {
		$size = number_format($size/1024, 1) . "K";
	} else {
		$size = number_format($size, 0) . "byte";
	}
	return $size;
}


// 게시글에 첨부된 파일을 얻는다. (배열로 반환)
function get_file($bo_table, $wr_id)
{
	global $g5, $qstr;

	$file['count'] = 0;
	$sql = " select * from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '$wr_id' order by bf_no ";
	$result = sql_query($sql);
	while($row = sql_fetch_array($result))
	{
		$no = $row['bf_no'];
		$file[$no]['href'] = G5_BBS_URL."/download.php?bo_table=$bo_table&amp;wr_id=$wr_id&amp;no=$no" . $qstr;
		$file[$no]['download'] = $row['bf_download'];
		// 4.00.11 - 파일 path 추가
		$file[$no]['path'] = G5_DATA_URL.'/file/'.$bo_table;
		$file[$no]['size'] = get_filesize($row['bf_filesize']);
		$file[$no]['datetime'] = $row['bf_datetime'];
		$file[$no]['source'] = addslashes($row['bf_source']);
		$file[$no]['bf_content'] = $row['bf_content'];
		$file[$no]['content'] = get_text($row['bf_content']);
		//$file[$no]['view'] = view_file_link($row['bf_file'], $file[$no]['content']);
		$file[$no]['view'] = view_file_link($row['bf_file'], $row['bf_width'], $row['bf_height'], $file[$no]['content']);
		$file[$no]['file'] = $row['bf_file'];
		$file[$no]['image_width'] = $row['bf_width'] ? $row['bf_width'] : 640;
		$file[$no]['image_height'] = $row['bf_height'] ? $row['bf_height'] : 480;
		$file[$no]['image_type'] = $row['bf_type'];
		$file['count']++;
	}

	return $file;
}


// 폴더의 용량 ($dir는 / 없이 넘기세요)
function get_dirsize($dir)
{
	$size = 0;
	$d = dir($dir);
	while($entry = $d->read()) {
		if($entry != '.' && $entry != '..') {
			$size += filesize($dir.'/'.$entry);
		}
	}
	$d->close();
	return $size;
}


/*************************************************************************
 **
 **  그누보드 관련 함수 모음
 **
 *************************************************************************/


// 게시물 정보($write_row)를 출력하기 위하여 $list로 가공된 정보를 복사 및 가공
function get_list($write_row, $board, $skin_url, $subject_len=40)
{
	global $g5, $config;
	global $qstr, $page;

	//$t = get_microtime();

	// 배열전체를 복사
	$list = $write_row;
	unset($write_row);

	$board_notice = array_map('trim', explode(',', $board['bo_notice']));
	$list['is_notice'] = in_array($list['wr_id'], $board_notice);

	if($subject_len)
		$list['subject'] = conv_subject($list['wr_subject'], $subject_len, '…');
	else
		$list['subject'] = conv_subject($list['wr_subject'], $board['bo_subject_len'], '…');

	// 목록에서 내용 미리보기 사용한 게시판만 내용을 변환함 (속도 향상) : kkal3(커피)님께서 알려주셨습니다.
	if($board['bo_use_list_content'])
	{
		$html = 0;
		if(strstr($list['wr_option'], 'html1'))
			$html = 1;
		else if(strstr($list['wr_option'], 'html2'))
			$html = 2;

		$list['content'] = conv_content($list['wr_content'], $html);
	}

	$list['comment_cnt'] = '';
	if($list['wr_comment'])
		$list['comment_cnt'] = "<span class=\"cnt_cmt\">".$list['wr_comment']."</span>";

	// 당일인 경우 시간으로 표시함
	$list['datetime'] = substr($list['wr_datetime'],0,10);
	$list['datetime2'] = $list['wr_datetime'];
	if($list['datetime'] == G5_TIME_YMD)
		$list['datetime2'] = substr($list['datetime2'],11,5);
	else
		$list['datetime2'] = substr($list['datetime2'],5,5);
	// 4.1
	$list['last'] = substr($list['wr_last'],0,10);
	$list['last2'] = $list['wr_last'];
	if($list['last'] == G5_TIME_YMD)
		$list['last2'] = substr($list['last2'],11,5);
	else
		$list['last2'] = substr($list['last2'],5,5);

	$list['wr_homepage'] = get_text($list['wr_homepage']);

	$tmp_name = get_text(cut_str($list['wr_name'], $config['cf_cut_name'])); // 설정된 자리수 만큼만 이름 출력
	$tmp_name2 = cut_str($list['wr_name'], $config['cf_cut_name']); // 설정된 자리수 만큼만 이름 출력
	if($board['bo_use_sideview'])
		$list['name'] = get_sideview($list['mb_id'], $tmp_name2, $list['wr_email'], $list['wr_homepage']);
	else
		$list['name'] = '<span class="'.($list['mb_id']?'sv_member':'sv_guest').'">'.$tmp_name.'</span>';

	$reply = $list['wr_reply'];

	$list['reply'] = strlen($reply)*10;

	$list['icon_reply'] = '';
	if($list['reply'])
		$list['icon_reply'] = '<img src="'.$skin_url.'/img/icon_reply.gif" style="margin-left:'.$list['reply'].'px;" alt="답변글"/>';

	$list['icon_link'] = '';
	if($list['wr_link1'] || $list['wr_link2'])
		$list['icon_link'] = '<img src="'.$skin_url.'/img/icon_link.gif" alt="관련링크"/>';

	// 분류명 링크
	$list['ca_name_href'] = G5_BBS_URL.'/board.php?bo_table='.$board['bo_table'].'&amp;sca='.urlencode($list['ca_name']);

	$list['href'] = G5_BBS_URL.'/board.php?bo_table='.$board['bo_table'].'&amp;wr_id='.$list['wr_id'].$qstr;
	$list['comment_href'] = $list['href'];

	$list['icon_new'] = '';
	if($board['bo_new'] && $list['wr_datetime'] >= date("Y-m-d H:i:s", G5_SERVER_TIME - ($board['bo_new'] * 3600)))
		$list['icon_new'] = '<img src="'.$skin_url.'/img/icon_new.gif" alt="새글"/>';

	$list['icon_hot'] = '';
	if($board['bo_hot'] && $list['wr_hit'] >= $board['bo_hot'])
		$list['icon_hot'] = '<img src="'.$skin_url.'/img/icon_hot.gif" alt="인기글"/>';

	$list['icon_secret'] = '';
	if(strstr($list['wr_option'], 'secret'))
		$list['icon_secret'] = '<img src="'.$skin_url.'/img/icon_secret.gif" alt="비밀글"/>';

	// 링크
	for($i=1; $i<=G5_LINK_COUNT; $i++) {
		$list['link'][$i] = set_http(get_text($list["wr_link{$i}"]));
		$list['link_href'][$i] = G5_BBS_URL.'/link.php?bo_table='.$board['bo_table'].'&amp;wr_id='.$list['wr_id'].'&amp;no='.$i.$qstr;
		$list['link_hit'][$i] = (int)$list["wr_link{$i}_hit"];
	}

	// 가변 파일
	if($board['bo_use_list_file'] || ($list['wr_file'] && $subject_len == 255) /* view 인 경우 */) {
		$list['file'] = get_file($board['bo_table'], $list['wr_id']);
	} else {
		$list['file']['count'] = $list['wr_file'];
	}

	if($list['file']['count'])
		$list['icon_file'] = '<img src="'.$skin_url.'/img/icon_file.gif" alt="첨부파일"/>';

	return $list;
}

// get_list 의 alias
function get_view($write_row, $board, $skin_url)
{
	return get_list($write_row, $board, $skin_url, 255);
}


// set_search_font(), get_search_font() 함수를 search_font() 함수로 대체
function search_font($stx, $str)
{
	global $config;

	// 문자앞에 \ 를 붙입니다.
	$src = array('/', '|');
	$dst = array('\/', '\|');

	if(!trim($stx)) return $str;

	// 검색어 전체를 공란으로 나눈다
	$s = explode(' ', $stx);

	// "/(검색1|검색2)/i" 와 같은 패턴을 만듬
	$pattern = '';
	$bar = '';
	for($m=0; $m<count($s); $m++) {
		if(trim($s[$m]) == '') continue;
		// 태그는 포함하지 않아야 하는데 잘 안되는군. ㅡㅡa
		//$pattern .= $bar . '([^<])(' . quotemeta($s[$m]) . ')';
		//$pattern .= $bar . quotemeta($s[$m]);
		//$pattern .= $bar . str_replace("/", "\/", quotemeta($s[$m]));
		$tmp_str = quotemeta($s[$m]);
		$tmp_str = str_replace($src, $dst, $tmp_str);
		$pattern .= $bar . $tmp_str . "(?![^<]*>)";
		$bar = "|";
	}

	// 지정된 검색 폰트의 색상, 배경색상으로 대체
	$replace = "<b class=\"sch_word\">\\1</b>";

	return preg_replace("/($pattern)/i", $replace, $str);
}


// 제목을 변환
function conv_subject($subject, $len, $suffix='')
{
	return get_text(cut_str($subject, $len, $suffix));
}

// 내용을 변환
function conv_content($content, $html, $filter=true)
{
	global $config, $board;

	if($html)
	{
		$source = array();
		$target = array();

		$source[] = "//";
		$target[] = "";

		if($html == 2) { // 자동 줄바꿈
			$source[] = "/\n/";
			$target[] = "<br/>";
		}

		// 테이블 태그의 개수를 세어 테이블이 깨지지 않도록 한다.
		$table_begin_count = substr_count(strtolower($content), "<table");
		$table_end_count = substr_count(strtolower($content), "</table");
		for($i=$table_end_count; $i<$table_begin_count; $i++)
		{
			$content .= "</table>";
		}

		$content = preg_replace($source, $target, $content);

		if($filter)
			$content = html_purifier($content);
	}
	else // text 이면
	{
		// & 처리 : &amp; &nbsp; 등의 코드를 정상 출력함
		$content = html_symbol($content);

		// 공백 처리
		//$content = preg_replace("/  /", "&nbsp; ", $content);
		$content = str_replace("  ", "&nbsp; ", $content);
		$content = str_replace("\n ", "\n&nbsp;", $content);

		$content = get_text($content, 1);
		$content = url_auto_link($content);
	}

	return $content;
}


// http://htmlpurifier.org/
// Standards-Compliant HTML Filtering
// Safe  : HTML Purifier defeats XSS with an audited whitelist
// Clean : HTML Purifier ensures standards-compliant output
// Open  : HTML Purifier is open-source and highly customizable
function html_purifier($html)
{
	$f = file(G5_PLUGIN_PATH.'/htmlpurifier/safeiframe.txt');
	$domains = array();
	foreach($f as $domain) {
		// 첫행이 # 이면 주석 처리
		if(!preg_match("/^#/", $domain)) {
			$domain = trim($domain);
			if($domain)
				array_push($domains, $domain);
		}
	}
	// 내 도메인도 추가
	array_push($domains, $_SERVER['HTTP_HOST'].'/');
	$safeiframe = implode('|', $domains);

	include_once(G5_PLUGIN_PATH.'/htmlpurifier/HTMLPurifier.standalone.php');
	$config = HTMLPurifier_Config::createDefault();
	// data/cache 디렉토리에 CSS, HTML, URI 디렉토리 등을 만든다.
	$config->set('Cache.SerializerPath', G5_DATA_PATH.'/cache');
	$config->set('HTML.SafeEmbed', false);
	$config->set('HTML.SafeObject', false);
	$config->set('Output.FlashCompat', false);
	$config->set('HTML.SafeIframe', true);
	$config->set('URI.SafeIframeRegexp','%^(https?:)?//('.$safeiframe.')%');
	$config->set('Attr.AllowedFrameTargets', array('_blank'));
	$purifier = new HTMLPurifier($config);
	return $purifier->purify($html);
}


// 검색 구문을 얻는다.
function get_sql_search($search_ca_name, $search_field, $search_text, $search_operator='and')
{
	global $g5;

	$str = "";
	if($search_ca_name)
		$str = " ca_name = '$search_ca_name' ";

	$search_text = strip_tags(($search_text));
	$search_text = trim(stripslashes($search_text));

	if(!$search_text) {
		if($search_ca_name) {
			return $str;
		} else {
			return '0';
		}
	}

	if($str)
		$str .= " and ";

	// 쿼리의 속도를 높이기 위하여 ( ) 는 최소화 한다.
	$op1 = "";

	// 검색어를 구분자로 나눈다. 여기서는 공백
	$s = array();
	$s = explode(" ", $search_text);

	// 검색필드를 구분자로 나눈다. 여기서는 +
	$tmp = array();
	$tmp = explode(",", trim($search_field));
	$field = explode("||", $tmp[0]);
	$not_comment = "";
	if(!empty($tmp[1]))
		$not_comment = $tmp[1];

	$str .= "(";
	for($i=0; $i<count($s); $i++) {
		// 검색어
		$search_str = trim($s[$i]);
		if($search_str == "") continue;

		// 인기검색어
		insert_popular($field, $search_str);

		$str .= $op1;
		$str .= "(";

		$op2 = "";
		for($k=0; $k<count($field); $k++) { // 필드의 수만큼 다중 필드 검색 가능 (필드1+필드2...)

			// SQL Injection 방지
			// 필드값에 a-z A-Z 0-9 _ , | 이외의 값이 있다면 검색필드를 wr_subject 로 설정한다.
			$field[$k] = preg_match("/^[\w\,\|]+$/", $field[$k]) ? $field[$k] : "wr_subject";

			$str .= $op2;
			switch ($field[$k]) {
				case "mb_id" :
				case "wr_name" :
					$str .= " $field[$k] = '$s[$i]' ";
					break;
				case "wr_hit" :
				case "wr_good" :
				case "wr_nogood" :
					$str .= " $field[$k] >= '$s[$i]' ";
					break;
				// 번호는 해당 검색어에 -1 을 곱함
				case "wr_num" :
					$str .= "$field[$k] = ".((-1)*$s[$i]);
					break;
				case "wr_ip" :
				case "wr_password" :
					$str .= "1=0"; // 항상 거짓
					break;
				// LIKE 보다 INSTR 속도가 빠름
				default :
					if(preg_match("/[a-zA-Z]/", $search_str))
						$str .= "INSTR(LOWER($field[$k]), LOWER('$search_str'))";
					else
						$str .= "INSTR($field[$k], '$search_str')";
					break;
			}
			$op2 = " or ";
		}
		$str .= ")";

		$op1 = " $search_operator ";
	}
	$str .= " ) ";
	if($not_comment)
		$str .= " and wr_is_comment = '0' ";

	return $str;
}


// 게시판 테이블에서 하나의 행을 읽음
function get_write($write_table, $wr_id)
{
	return sql_fetch(" select * from $write_table where wr_id = '$wr_id' ");
}


// 게시판의 다음글 번호를 얻는다.
function get_next_num($table)
{
    // 가장 작은 번호를 얻어
    $sql = " select min(wr_num) as min_wr_num from $table ";
    $row = sql_fetch($sql);
    // 가장 작은 번호에 1을 빼서 넘겨줌
    return (int)($row['min_wr_num'] - 1);
}


// 그룹 설정 테이블에서 하나의 행을 읽음
function get_group($gr_id)
{
    global $g5;

    return sql_fetch("SELECT * FROM {$g5['group_table']} WHERE gr_id = '$gr_id' ");
}


// 회원 정보를 얻는다.
function get_member($mb_id, $fields='*')
{
	global $g5, $INDI_INVESTOR, $CONF;

	$row  = sql_fetch("SELECT $fields FROM g5_member WHERE mb_id = TRIM('$mb_id')");

	// 투자시 부하방지를 위하여 투자페이지 디렉토리에서는 자체 복호화 모듈 사용
	if( preg_match("/(\/investment\/|\/adm\/repayment)/", @$_SERVER['PHP_SELF']) ) {
		if($row['mb_hp'])       $row['mb_hp']       = masterDecrypt($row['mb_hp'], false);
		if($row['corp_phone'])  $row['corp_phone']  = masterDecrypt($row['corp_phone'], false);
		if($row['account_num']) $row['account_num'] = masterDecrypt($row['account_num'], false);
	}
	else {
		// 암호화된 내용 복호화
		if($row['mb_hp'] || $row['mb_hp_ineb']) {
			$row['mb_hp'] = ($row['mb_hp_ineb']) ? DGuardDecrypt($row['mb_hp_ineb']) : masterDecrypt($row['mb_hp'], false);
		}
		if($row['corp_phone'] || $row['corp_phone_ineb']) {
			$row['corp_phone']  = ($row['corp_phone_ineb']) ? DGuardDecrypt($row['corp_phone_ineb']) : masterDecrypt($row['corp_phone'], false);
		}
		if($row['account_num'] || $row['account_num_ineb']) {
			$row['account_num'] = ($row['account_num_ineb']) ? DGuardDecrypt($row['account_num_ineb']) : masterDecrypt($row['account_num'], false);
		}
	}


	// 개인 회원중 특별투자권한 자격정보 추출
	if($row['member_type']=='1' && $row['member_investor_type']>'1') {

		$row2 = sql_fetch("SELECT allow_date, rights_start_date, rights_end_date FROM investor_type_change_request WHERE idx='".$row['investor_judge_idx']."'");

		$row['special_investor'] = array();
		$row['special_investor']['allow_date']   = $row2['allow_date'];
		$row['special_investor']['rights_sdate'] = $row2['rights_start_date'];

		if( (empty($row2['rights_end_date']) || $row2['rights_end_date'] <= '2018-11-30') && G5_TIME_YMD <= '2018-12-31' ) {
			$row2['rights_end_date'] = "2018-11-30";		// 2018년 이전 가입자는 임의로 설정 (이정환 차장 요청)
		}

		$row['special_investor']['rights_edate'] = $row2['rights_end_date'];
		$row['special_investor']['valid_days'] = ceil((strtotime($row2['rights_end_date'])-time())/86400)+1;		// 자격 잔여일수

	}

	//  - state = 1:이자상환중|2:상환완료(투자종료)|3:투자금모집실패|4:부실|5:중도상환|6:대출취소(기표전)|7:대출취소(기표후)|8:연채|9:부도(상환불가)

	if( $row['member_group']=='F' && in_array($row['mb_level'], array('1','2','3','4','5')) ) {

		////////////////
		// 투자 정보
		////////////////

		$INV = array(
			// 누적 투자액
			'nujuk_amount_bds' => 0,	// 부동산
			'nujuk_amount_ds'  => 0,	// 동산
			'nujuk_amount'     => 0,

			// 투자 잔액
			'live_amount_bds'  => 0,
			'live_amount_ds'   => 0,
			'live_amount'      => 0,

			// 투자 가능액
			'able_amount_bds'  => 0,
			'able_amount_ds'   => 0,
			'able_amount'      => 0,
		);


		$invest_sql = "
			SELECT
				IFNULL(SUM(A.amount), 0) AS sum_amount
			FROM
				cf_product_invest A
			LEFT JOIN
				cf_product B  ON A.product_idx=B.idx
			WHERE (1)
				AND A.member_idx = '".$row['mb_no']."'
				AND A.invest_state = 'Y'
				AND B.state NOT IN('3','6','7')
				AND B.isTest = ''";

		// ▼ 누적 투자액 : P2P 가이드라인 적용 이전 데이터 ▼-----------------------------------------
		$INVESTED_OLD['BDS'] = sql_fetch($invest_sql . " AND B.category = '2'  AND A.product_idx <= '".$CONF['old_type_end_prdt_idx']."'");		// 부동산
		$INVESTED_OLD['DS']  = sql_fetch($invest_sql . " AND B.category <> '2' AND A.product_idx <= '".$CONF['old_type_end_prdt_idx']."'");		// 부동산외
		$INVESTED_OLD['sum_amount'] = $INVESTED_OLD['BDS']['sum_amount'] + $INVESTED_OLD['DS']['sum_amount'];


		// ▼ 누적 투자액 : P2P 가이드라인 적용 이후 (정상상품만) ▼	-----------------------------------------
		// 누적 투자액 - 부동산
		$INVESTED['BDS'] = sql_fetch($invest_sql . " AND B.category = '2'");
		$INVESTED['DS']  = sql_fetch($invest_sql . " AND B.category <> '2'");
		$INVESTED['sum_amount'] = $INVESTED['BDS']['sum_amount'] + $INVESTED['DS']['sum_amount'];


		// ▼ 상환금액 조회 --------------------------------------------------------------------------------
		$paid_sql = "
			SELECT
				IFNULL(SUM(principal), 0) AS sum_amount
			FROM
				cf_product_give A
			LEFT JOIN
				cf_product B  ON A.product_idx=B.idx
			WHERE (1)
				AND A.member_idx = '".$row['mb_no']."'
				AND B.isTest = ''";

		$PAID['BDS'] = sql_fetch($paid_sql . " AND B.category = '2'");		// 부동산
		$PAID['DS']  = sql_fetch($paid_sql . " AND B.category <> '2'");		// 부동산외
		$PAID['sum_amount'] = $PAID['BDS']['sum_amount'] + $PAID['DS']['sum_amount'];


		// ▼ 투자 잔액 추출 ▼ -----------------------------------------
		$INVESTING['BDS']['sum_amount'] = $INVESTED['BDS']['sum_amount'] - $PAID['BDS']['sum_amount'];		// 부동산
		$INVESTING['DS']['sum_amount']  = $INVESTED['DS']['sum_amount'] - $PAID['DS']['sum_amount'];			// 부동산외
		$INVESTING['sum_amount'] = $INVESTING['BDS']['sum_amount'] + $INVESTING['DS']['sum_amount'];

		/*
		if( preg_match("/\/member\/member_view\.php/", $_SERVER['PHP_SELF']) && ($_SERVER['REMOTE_ADDR']=='211.248.149.48') ) {
			echo "INVESTED_OLD : "; print_rr($INVESTED_OLD, 'font-size:12px; color:red;');
			echo "INVESTED : "; print_rr($INVESTED, 'font-size:12px; color:red;');
			echo "PAID : "; print_rr($PAID, 'font-size:12px; color:red;');
			echo "INVESTING : "; print_rr($INVESTING, 'font-size:12px; color:red;');
		}
		*/

		// ▼ 투자 가능금액 추출 ▼ -----------------------------------------
		if($row['member_type']=='1') {

			if($row['member_investor_type']=='1') {
				$INV['able_amount_bds'] = $INDI_INVESTOR['1']['prpt_limit'] - $INVESTING['BDS']['sum_amount'];
				$INV['able_amount_ds']  = $INDI_INVESTOR['1']['site_limit'] - $INVESTING['DS']['sum_amount'];
				$INV['able_amount']     = $INDI_INVESTOR['1']['site_limit'] - $INVESTING['sum_amount'];
			}
			else if($row['member_investor_type']=='2') {
				$INV['able_amount'] = $INDI_INVESTOR['2']['site_limit'] - $INVESTING['sum_amount'];
				$INV['able_amount_ds'] = $INV['able_amount_bds'] = $INV['able_amount'];
			}
			else {
				unset($INV['able_amount_bds']);
				unset($INV['able_amount_ds']);
				$INV['able_amount'] = $INDI_INVESTOR['3']['site_limit'];	// 전문투자자 무제한
			}

		}
		else {
			unset($INV['able_amount_bds']);
			unset($INV['able_amount_ds']);
			$INV['able_amount'] = $INDI_INVESTOR['3']['site_limit'];		// 법인 투자자 투자한도 무제한
		}


		$row['total_invest_amount']         = $INVESTED['sum_amount'];	              // 누적 투자금액(전체)
		$row['total_invest_amount_new']     = $INVESTED['sum_amount'];								// 누적 투자금액(가이드라인 이후)
		$row['total_invest_amount_new_prpt']= $INVESTED['BDS']['sum_amount'];					// 누적 투자금액(가이드라인 이후 부동산)
		$row['total_invest_amount_new_ds']  = $INVESTED['DS']['sum_amount'];					// 누적 투자금액(가이드라인 이후 동산)

		$row['ing_invest_amount']           = $INVESTING['sum_amount'];		            // 투자 잔액 (전체)
		$row['ing_invest_amount_new']       = $INVESTING['sum_amount'];								// 투자 잔액 (가이드라인 이후)
		$row['ing_invest_amount_new_prpt']  = $INVESTING['BDS']['sum_amount'];				// 투자 잔액 (가이드라인 이후 부동산)
		$row['ing_invest_amount_new_ds']    = $INVESTING['DS']['sum_amount'];					// 투자 잔액 (가이드라인 이후 동산)

		$row['invest_possible_amount']      = $INV['able_amount'];										// 투자 가능금액(사이트 투자제한 금액 기준)
		$row['invest_possible_amount_prpt'] = $INV['able_amount_bds'];								// 투자 가능금액(부동산 투자제한 금액 기준)
		$row['invest_possible_amount_ds']   = $INV['able_amount_ds'];									// 투자 가능금액(동산 투자제한 금액 기준)

		// 사이트전체투자가능금액 < 상품 카테고리별 투자가능금액 의 경우 투자가능금액 보정
		if($row['member_type']=='1' && $row['member_investor_type']=='1') {
			if($row['invest_possible_amount'] < $row['invest_possible_amount_prpt']) $row['invest_possible_amount_prpt'] = $row['invest_possible_amount'];
			if($row['invest_possible_amount'] < $row['invest_possible_amount_ds'])   $row['invest_possible_amount_ds'] = $row['invest_possible_amount'];
		}


		//--------------------------------------------------------------------------------------
		// 회원테이블상 투자가능금액 적용 : 2021-09-13
		//--------------------------------------------------------------------------------------
		$row['invest_possible_amount']      = $row['p2pctr_all_limit'];		// 투자 가능금액(사이트 투자제한 금액 기준)
		$row['invest_possible_amount_prpt'] = $row['p2pctr_imv_limit'];		// 투자 가능금액(부동산 투자제한 금액 기준)
		$row['invest_possible_amount_ds']   = $row['p2pctr_mv_limit'];		// 투자 가능금액(동산 투자제한 금액 기준)
		//--------------------------------------------------------------------------------------



		// 출금가능금액 산출 : 현재예치금 - 현재시각기준24시간전 입금된 총금액 2019-05-23 적용 2019-05-24 부터 시행
		$BEFORE_1DAY = sql_fetch("
			SELECT
				(SELECT IFNULL(SUM(TR_AMT), 0) AS insert_amt FROM IB_FB_P2P_IP WHERE CUST_ID='".$row['mb_no']."' AND ERP_TRANS_DT BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y%m%d%H%i%s') AND DATE_FORMAT(NOW(),'%Y%m%d%H%i%s')) AS insert_amt,
				(SELECT IFNULL(SUM(TR_AMT), 0) AS insert_cancel_amt FROM IB_FB_P2P_IP_CANCEL WHERE CUST_ID='".$row['mb_no']."' AND SR_DATE BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY),'%Y%m%d') AND DATE_FORMAT(NOW(),'%Y%m%d')) AS insert_cancel_amt,
				(SELECT IFNULL(SUM(amount), 0) AS invest_amt FROM cf_product_invest WHERE member_idx='".$row['mb_no']."' AND invest_state = 'Y' AND insert_datetime BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW()) AS invest_amt,
				(SELECT IFNULL(SUM(req_price), 0) AS withdrawal_amt FROM g5_withdrawal WHERE mb_id='".$row['mb_no']."' AND regdate BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW() AND state='2') AS withdrawal_amt
		");

	//$now_amt    = $row['mb_point'];		// 현재예치금
		$now_amt    = get_point_sum($mb_id);
		$lock_amt   = max(array(($BEFORE_1DAY['insert_amt'] - $BEFORE_1DAY['insert_cancel_amt'] - $BEFORE_1DAY['invest_amt']), 0));		// 잠긴금액 : max((24시간내 입금액 - 24시간내 입금취소액 - 24시간내 정상투자금액), 0)
		$unlock_amt = max(array(($now_amt - $lock_amt), 0));		// 출금가능금액	: max( (현재예치금-잠긴금액), 0 )		1160000-0, 0

		$row['lock_amount'] = (int)$lock_amt;
		$row['withdrawal_posible_amount'] = (int)$unlock_amt;

		//if ($mb_id=="romrom" or $mb_id=="hellosiesta") $row["p2pctr"] = get_p2pctr_limit($mb_id);   // 여기서 금결원 투자잔액을 가져오면 부하가 많을것 같은데요....

		/*
		if($_SERVER['REMOTE_ADDR']=='220.117.134.164') {
			echo "<div style='text-align:left;font-size:12px;margin-top:150px'>\n";
			echo "BEFORE_1DAY: "; print_rr($BEFORE_1DAY, "text-align:left;font-size:12px;");
			echo "now_amt : {$row['mb_point']}<br>\n";
			echo "lock_amt : max(array(({$BEFORE_1DAY['insert_amt']} - {$BEFORE_1DAY['insert_cancel_amt']} - {$BEFORE_1DAY['invest_amt']}), 0)) = {$lock_amt}<br>\n";
			echo "unlock_amt : max(array(($now_amt - $lock_amt), 0)) = {$unlock_amt}<br>\n";
			echo "</div>\n";
		}
		*/


		//$row['next_kyc_date'] = getNextKycDate($row['mb_no']);

	}

	if($row['mb_level'] >= 6 && $row['mb_level'] <= 9) {
		$subadmin_sql = "
			SELECT
				is_inspecter, is_editor, auth_info, privacy_auth, hp_auth, account_view_auth, member_control_auth, product_control_auth, account_auth, allow_location
			FROM
				g5_sub_admin
			WHERE 1
				AND mb_no = '".$row['mb_no']."' AND withdrawal = ''";
		$row2 = sql_fetch($subadmin_sql);
		$row['SUB_ADMIN'] = $row2;
	}

	return $row;

}

function getNextKycDate($member_idx) {
	global $member;

	if(!$member_idx) $member_idx = $member['mb_no'];

	$next_kyc_date = '';

	$KYCTABLE = 'g5_member_aml';
	$KYCTABLE.= ($member['member_type']=='2') ? '_corp' : '_indi';
	$NEXT_KYC_DD = sql_fetch("SELECT KYC_NEXT_EXEC_DD FROM {$KYCTABLE} WHERE mb_no = '".$member_idx."'")['KYC_NEXT_EXEC_DD'];

	if($NEXT_KYC_DD) {
		$next_kyc_date = trim(substr($NEXT_KYC_DD, 0, 4) . '-' . substr($NEXT_KYC_DD, 4, 2) . '-' . substr($NEXT_KYC_DD, 6));
	}

	return $next_kyc_date;

}

function getJumin($member_idx, $is_dropped_member=false) {

	global $member;

	if($is_dropped_member==false) {
		$member_table =  "g5_member";
		$jumin_table  = "member_private";
	}
	else {
		$member_table = "g5_member_drop";
		$jumin_table  = "member_private_drop";
	}

	$TARGET_MB = sql_fetch("SELECT mb_no, mb_id FROM {$member_table} WHERE mb_no='".$member_idx."'");


	//crypt.lib.php 가 필요함
	if( defined('G5_MYSQL_HOST2') && defined('G5_MYSQL_USER2') && defined('G5_MYSQL_PASSWORD2') && defined('G5_MYSQL_DB2') ) {

		$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('DB2 Connect Error!!!');

		$row = sql_fetch("SELECT * FROM {$jumin_table} WHERE mb_no = '".$member_idx."' ORDER BY idx DESC LIMIT 1", G5_DISPLAY_SQL_ERROR, $linkX);

		if( $row['regist_number'] || $row['regist_number_ineb'] ) {

			// 아이넵 엔코딩 데이터 우선
			if($row['regist_number_ineb'] && function_exists('DGuardDecrypt')) {
				$decryptJumin = DGuardDecrypt($row['regist_number_ineb']);
			}
			else if($row['regist_number']) {
				$decryptJumin = masterDecrypt($row['regist_number'], true);
			}

			if($member_idx != $member['mb_no']) {
				// 로그기록
				$req_url = G5_URL . @$_SERVER['REQUEST_URI'];
				$sql = "
					INSERT INTO
						connect_log
					SET
						mb_no = '".$member['mb_no']."',
						mb_id = '".$member['mb_id']."',
						target_mb_no = '".$TARGET_MB['mb_no']."',
						target_mb_id = '".$TARGET_MB['mb_id']."',
						request_url = '".@$req_url."',
						ip = '".@$_SERVER['REMOTE_ADDR']."',
						rdate = NOW()";

				sql_query($sql, G5_DISPLAY_SQL_ERROR, $linkX);
			}
		}
		else {

			//$row2 = sql_fetch("SELECT jumin FROM member_private_new WHERE mb_no='".$member_idx."'", '', $linkX);
			//$decryptJumin = DGuardDecrypt($row2['jumin']);

		}

		sql_close($linkX);

		return $decryptJumin;

	}
	else {
		return false;
	}

}


// 주요정보 복호화 함수 getPrivate('회원번호','hp|acct|jumin')
function getPrivate($member_idx, $arg='hp') {

	global $member;

	if($arg=='hp')         { $field = 'mb_hp'; }
	else if($arg=='acct')  { $field = 'account_num'; }
	else if($arg=='jumin') { $field = 'regist_num'; }
	else                   { return false; }

	$TARGET_MB = sql_fetch("SELECT mb_no, mb_id, mb_level FROM g5_member WHERE mb_no='".$member_idx."'");

	$private_table = ($TARGET_MB['mb_level'] > 0 && $TARGET_MB['mb_level'] <= 9) ? 'member_private_new' : 'member_private_drop_new';		// 정상회원:탈퇴회원

	//crypt.lib.php 가 필요함
	if( defined('G5_MYSQL_HOST2') && defined('G5_MYSQL_USER2') && defined('G5_MYSQL_PASSWORD2') && defined('G5_MYSQL_DB2') && function_exists('DGuardDecrypt') ) {

		$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('DB2 Connect Error!!!');

		$PRIV_DATA = sql_fetch("SELECT {$field} FROM {$private_table} WHERE mb_no = '".$member_idx."' AND {$field}!='' ORDER BY idx DESC LIMIT 1", G5_DISPLAY_SQL_ERROR, $linkX);

		if($PRIV_DATA[$field]) {

			$return_value = DGuardDecrypt($PRIV_DATA[$field]);

			// 본인 외 복호화 요청자 로그 기록
			if($member_idx <> $member['mb_no']) {

				$req_url = G5_URL . @$_SERVER['REQUEST_URI'];

				$sql = "
					INSERT INTO
						connect_log
					SET
						mb_no        = '".$member['mb_no']."',
						mb_id        = '".$member['mb_id']."',
						target_mb_no = '".$TARGET_MB['mb_no']."',
						target_mb_id = '".$TARGET_MB['mb_id']."',
						target_arg   = '".$arg."',
						request_url  = '".@$req_url."',
						ip           = '".@$_SERVER['REMOTE_ADDR']."',
						rdate        = NOW()";

				sql_query($sql, G5_DISPLAY_SQL_ERROR, $linkX);

			}

			return $return_value;

		}

	}
}

// 주민번호 유효성 검사 (외국인번호 판별기능 추가 : 2021-11-16)
function checkJumin($number, $foreigner=false) {

	$number = trim(preg_replace("/-/", "", $number));

	/*
		// 예외처리 주민번호 (정상적이나 윤달등의 이유로 비적합 판정이 뜬 경우이르모 성공사인 보내줌.
		$EXCEP_NUM = array('8002292548010');
		if( in_array($number, $EXCEP_NUM) && $_SERVER['REMOTE_ADDR']!='183.98.101.114') {
			return true;
		}
	*/
	// 유효한 13자리수인지 자릿수 체크, 7번째 항목의 숫자가 1~4가 맞는지 확인
	$year  = ('2' >= $number[6]) ? '19' : '20';
	$year.= substr($number, 0, 2);				// $year += substr($number, 0, 2);
	$month = substr($number, 2, 2);
	$day   = substr($number, 4, 2);

	// 유효한 날짜인지 체크
  if(!checkdate($month, $day, $year)) {
    return false;
	}

	for($i=0; $i<13; $i++) {
		$buf[$i] = (int)$number[$i];
	}

	if($foreigner) {

		///////////////////////
		// 외국인
		///////////////////////
		if(!ereg('^[[:digit:]]{6}[5-8][[:digit:]]{6}$', $number)) { return false; }

		if( ($buf[7]*10 + $buf[8])&1 ) { return false; }

		$total  = 0;
		$weight = 2;

		for($i=$total=0; $i<12; $i++) {
			$sum = $buf[$i] * $weight;
			$total += $sum;
			if(++$weight > 9) $weight = 2;
		}

		if(($total = 11 - ($total%11)) >= 10) $total -= 10;
		if(($total += 2) >= 10) $total -= 10;

		if($total != $buf[12]) { return false; }

	}
	else {

		///////////////////////
		// 내국인
		///////////////////////
		if(!ereg('^[[:digit:]]{6}[1-4][[:digit:]]{6}$', $number)) { return false; }

		$multipliers = array(2,3,4,5,6,7,8,9,2,3,4,5);

		for($i=$sum=0; $i<12; $i++) {
			$sum += ($buf[$i] *= $multipliers[$i]);
		}

		if((11 - ($sum % 11)) % 10 != $buf[12]) { return false; }

	}

  return true;		// 일치하면 올바른 주민등록번호로 검사 완료

}

// 주민번호에서 생년월일 및 성별 추출하여 배열로 반환
function getBirthGender($regist_number) {
	if(strlen($regist_number)==13) {
		$birthdate = ( in_array(substr($regist_number, 6, 1), array('1','2','5','6')) ) ? '19'.substr($regist_number, 0, 2) : '20'.substr($regist_number, 0, 2);
		if( $birthdate > date('Y') ) $birthdate = '19'.substr($regist_number, 0, 2);
		$birthdate.= '-' . substr($regist_number, 2, 2) . '-' . substr($regist_number, 4, 2);
		$gender = ( in_array(substr($regist_number, 6, 1), array('1','3','5','7')) ) ? 'm' : 'w';
		$genderNo = substr($regist_number, 6, 1);

		$arr = array($birthdate, $gender, $genderNo);
		return $arr;
	}
	else {
		return false;
	}
}


// 만나이 반환
function getFullAge($birthday) {
	$birthday = substr(preg_replace("/(-| |:)/", "", trim($birthday)), 0, 8);
	if(!$birthday) return 0;

	$birth_y = (int)substr($birthday, 0, 4);
	$age = date('Y') - $birth_y;
	$birth_md = substr($birthday, 4);
	if( $birth_md > date('md')) $age = $age - 1;
	return $age;
}


// 날짜, 조회수의 경우 높은 순서대로 보여져야 하므로 $flag 를 추가
// $flag : asc 낮은 순서 , desc 높은 순서
// 제목별로 컬럼 정렬하는 QUERY STRING
function subject_sort_link($col, $query_string='', $flag='asc')
{
	global $sst, $sod, $sfl, $stx, $page;

	$q1 = "sst=$col";
	if($flag == 'asc') {
		$q2 = 'sod=asc';
		if($sst == $col) {
			if($sod == 'asc') {
				$q2 = 'sod=desc';
			}
		}
	}
	else {
		$q2 = 'sod=desc';
		if($sst == $col) {
			if($sod == 'desc') {
				$q2 = 'sod=asc';
			}
		}
	}

	$arr_query = array();
	$arr_query[] = $query_string;
	$arr_query[] = $q1;
	$arr_query[] = $q2;
	$arr_query[] = 'sfl='.$sfl;
	$arr_query[] = 'stx='.$stx;
	$arr_query[] = 'page='.$page;
	$qstr = implode("&amp;", $arr_query);

	return "<a href=\"{$_SERVER['SCRIPT_NAME']}?{$qstr}\">";
}


// 관리자 정보를 얻음
function get_admin($admin='super', $fields='*')
{
	global $config, $group, $board;
	global $g5;

	$is = false;
	if($admin == 'board') {
		$mb = sql_fetch("SELECT {$fields} FROM {$g5['member_table']} WHERE mb_id IN ('{$board['bo_admin']}') LIMIT 1 ");
		$is = true;
	}

	if(($is && !$mb['mb_id']) || $admin == 'group') {
		$mb = sql_fetch("SELECT {$fields} FROM {$g5['member_table']} WHERE mb_id IN ('{$group['gr_admin']}') LIMIT 1 ");
		$is = true;
	}

	if(($is && !$mb['mb_id']) || $admin == 'super') {
		$mb = sql_fetch("SELECT {$fields} FROM {$g5['member_table']} WHERE mb_id IN ('{$config['cf_admin']}') LIMIT 1 ");
	}

	return $mb;
}


// 관리자인가?
function is_admin($mb_id)
{
	global $config, $group, $board;

	if(!$mb_id) return;

	$add_sql = "
		SELECT
			COUNT(A.idx) AS 'cnt'
		FROM
			g5_sub_admin AS A
		LEFT JOIN
			g5_member AS B
		ON	A.mb_no = B.mb_no
		WHERE
			B.mb_id = '{$mb_id}'";

	$sad = sql_fetch($add_sql);

	if($config['cf_admin'] == $mb_id || $sad['cnt'] > 0) return 'super';
	if(isset($group['gr_admin']) && ($group['gr_admin'] == $mb_id)) return 'group';
	if(isset($board['bo_admin']) && ($board['bo_admin'] == $mb_id)) return 'board';
	return '';
}


// 분류 옵션을 얻음
// 4.00 에서는 카테고리 테이블을 없애고 보드테이블에 있는 내용으로 대체
function get_category_option($bo_table='', $ca_name='')
{
	global $g5, $board, $is_admin;

	$categories = explode("|", $board['bo_category_list']/*.($is_admin?"|공지":"")*/); // 구분자가 | 로 되어 있음
	$str = "";
	for($i=0; $i<count($categories); $i++) {
		$category = trim($categories[$i]);
		if(!$category) continue;

		$str .= "<option value=\"$categories[$i]\"";
		if($category == $ca_name) {
			$str .= ' selected="selected"';
		}
		$str .= ">$categories[$i]</option>\n";
	}

	return $str;
}


// 게시판 그룹을 SELECT 형식으로 얻음
function get_group_select($name, $selected='', $event='')
{
	global $g5, $is_admin, $member;

	$sql = "SELECT gr_id, gr_subject FROM {$g5['group_table']} a ";
	if($is_admin == "group") {
		$sql.= " LEFT JOIN {$g5['member_table']} b ON (b.mb_id = a.gr_admin) WHERE b.mb_id = '{$member['mb_id']}' ";
	}
	$sql .= " ORDER BY a.gr_id ";

	$result = sql_query($sql);
	$str = "<select id=\"$name\" name=\"$name\" $event>\n";
	for($i=0; $row=sql_fetch_array($result); $i++) {
		if($i == 0) $str .= "<option value=\"\">선택</option>";
		$str .= option_selected($row['gr_id'], $selected, $row['gr_subject']);
	}
	$str .= "</select>";
	return $str;
}


function option_selected($value, $selected, $text='')
{
	if(!$text) $text = $value;
	if($value == $selected)
		return "<option value=\"$value\" selected=\"selected\">$text</option>\n";
	else
		return "<option value=\"$value\">$text</option>\n";
}


// '예', '아니오'를 SELECT 형식으로 얻음
function get_yn_select($name, $selected='1', $event='')
{
	$str = "<select name=\"$name\" $event>\n";
	if($selected) {
		$str .= "<option value=\"1\" selected>예</option>\n";
		$str .= "<option value=\"0\">아니오</option>\n";
	}
	else {
		$str .= "<option value=\"1\">예</option>\n";
		$str .= "<option value=\"0\" selected>아니오</option>\n";
	}
	$str .= "</select>";
	return $str;
}


// 포인트 부여
function insert_point($mb_id, $point, $content='', $rel_table='', $rel_id='', $rel_action='', $expire=0, $memo='')
{
	global $g5;
	global $config;
	global $is_admin;

	if(!$config['cf_use_point']) { return 0; }		// 포인트 사용을 하지 않는다면 return
	if(!$point) { return 0; }											// 포인트가 없다면 업데이트 할 필요 없음
	if(!$mb_id) { return 0; }											// 회원아이디가 없다면 업데이트 할 필요 없음

	$mb = sql_fetch("SELECT mb_no, mb_id FROM g5_member WHERE mb_id='".$mb_id."' AND mb_level BETWEEN 1 AND 10");
	if(!$mb['mb_no']) { return 0; }

	$mb_point = get_point_sum($mb_id);	// 회원포인트

	// 이미 등록된 내역이라면 건너뜀
	if($rel_table || $rel_id || $rel_action)
	{
		$sql = "
			SELECT
				COUNT(po_id) AS cnt
			FROM
				g5_point
			WHERE 1
				AND mb_id = '$mb_id'
				AND po_rel_table = '$rel_table'
				AND po_rel_id = '$rel_id'
				AND po_rel_action = '$rel_action' ";
		$row = sql_fetch($sql);
		if($row['cnt'])
			return -1;
	}

	// 포인트 건별 생성
	$po_expire_date = '9999-12-31';
	if($config['cf_point_term'] > 0) {
		if($expire > 0)
			$po_expire_date = date('Y-m-d', strtotime('+'.($expire - 1).' days', G5_SERVER_TIME));
		else
			$po_expire_date = date('Y-m-d', strtotime('+'.($config['cf_point_term'] - 1).' days', G5_SERVER_TIME));
	}

	$po_expired = 0;
	if($point < 0) {
		$po_expired = 1;
		$po_expire_date = G5_TIME_YMD;
	}
	$po_mb_point = $mb_point + $point;
	$sql = "
		INSERT INTO
			g5_point
	  SET
			mb_no          = '".$mb['mb_no']."',
			mb_id          = '$mb_id',
			po_datetime    = NOW(),
			po_content     = '".addslashes($content)."',
			po_point       = '$point',
			po_use_point   = '0',
			po_mb_point    = '$po_mb_point',
			po_expired     = '$po_expired',
			po_expire_date = '$po_expire_date',
			po_rel_table   = '$rel_table',
			po_rel_id      = '$rel_id',
			po_rel_action  = '$rel_action',
			po_memo        = '$memo'";
	sql_query($sql);

	// 포인트를 사용한 경우 포인트 내역에 사용금액 기록
	if($point < 0) {
		insert_use_point($mb_id, $point);
	}

	// member테이블 mb_point UPDATE
	sql_query("UPDATE g5_member SET mb_point = '$po_mb_point' WHERE mb_id = '$mb_id'");

	return 1;

}

// 사용포인트 입력
function insert_use_point($mb_id, $point, $po_id='')
{
	global $g5;
	global $config;

	$sql_order = ($config['cf_point_term']) ? " ORDER BY po_expire_date ASC, po_id ASC " : " ORDER BY po_id ASC ";

	$point1 = abs($point);

	$sql = "
		SELECT
			po_id, po_point, po_use_point
		FROM
			g5_point
		WHERE
			mb_id = '$mb_id'
			AND po_id <> '$po_id'
			AND po_expired = '0'
			AND po_point > po_use_point
		$sql_order ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$point2 = $row['po_point'];
		$point3 = $row['po_use_point'];

		if(($point2 - $point3) > $point1) {
			$sql = "
				UPDATE
					g5_point
				SET
					po_use_point = po_use_point + '$point1'
				WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);
			break;
		}
		else {
			$point4 = $point2 - $point3;
			$sql = "
				UPDATE
					g5_point
			  SET
					po_use_point = po_use_point + '$point4',
					po_expired = '100'
			  WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);

			$point1 -= $point4;
		}
	}
}

// 사용포인트 삭제
function delete_use_point($mb_id, $point) {
	global $g5, $config;

	$sql_order = ($config['cf_point_term']) ? " ORDER BY po_expire_date DESC, po_id DESC " : " ORDER BY po_id DESC ";

	$point1 = abs($point);
	$sql = "
		SELECT
			po_id, po_use_point, po_expired, po_expire_date
		FROM
			g5_point
		WHERE
			mb_id = '$mb_id'
			AND po_expired <> '1'
			AND po_use_point > 0
		$sql_order ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$point2 = $row['po_use_point'];

		$po_expired = $row['po_expired'];
		if($row['po_expired'] == 100 && ($row['po_expire_date'] == '9999-12-31' || $row['po_expire_date'] >= G5_TIME_YMD))
			$po_expired = 0;

		if($point2 > $point1) {
			$sql = "
				UPDATE
					g5_point
			  SET
					po_use_point = po_use_point - '$point1',
					po_expired = '$po_expired'
			  WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);
			break;
		}
		else {
			$sql = "
				UPDATE
					g5_point
			  SET
					po_use_point = '0',
					po_expired = '$po_expired'
			  WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);

			$point1 -= $point2;
		}
	}
}

// 소멸포인트 삭제
function delete_expire_point($mb_id, $point) {
	global $g5, $config;

	$point1 = abs($point);
	$sql = "
		SELECT
				po_id, po_use_point, po_expired, po_expire_date
	  FROM
				g5_point
	  WHERE
				mb_id = '$mb_id'
				and po_expired = '1'
				and po_point >= 0
				and po_use_point > 0
	  ORDER BY
				po_expire_date desc,
				po_id desc ";
	$result = sql_query($sql);

	for($i=0; $row=sql_fetch_array($result); $i++) {
		$point2 = $row['po_use_point'];
		$po_expired = '0';
		$po_expire_date = '9999-12-31';
		if($config['cf_point_term'] > 0)
			$po_expire_date = date('Y-m-d', strtotime('+'.($config['cf_point_term'] - 1).' days', G5_SERVER_TIME));

		if($point2 > $point1) {
			$sql = "
				UPDATE
					g5_point
			  SET
					po_use_point = po_use_point - '$point1',
					po_expired = '$po_expired',
					po_expire_date = '$po_expire_date'
			  WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);
			break;
		}
		else {
			$sql = "
				UPDATE
					g5_point
			  SET
					po_use_point = '0',
					po_expired = '$po_expired',
					po_expire_date = '$po_expire_date'
			  WHERE
					po_id = '{$row['po_id']}' ";
			sql_query($sql);

			$point1 -= $point2;
		}
	}
}

// 포인트 내역 합계
function get_point_sum($mb_id) {
	global $g5, $config;

	if($config['cf_point_term'] > 0) {

		// 소멸포인트가 있으면 내역 추가
		$expire_point = get_expire_point($mb_id);
		if($expire_point > 0) {
			$mb = get_member($mb_id, 'mb_point');

			$content = '포인트 소멸';
			$rel_table = '@expire';
			$rel_id = $mb_id;
			$rel_action = 'expire'.'-'.uniqid('');
			$point = $expire_point * (-1);
			$po_mb_point = $mb['mb_point'] + $point;
			$po_expire_date = G5_TIME_YMD;
			$po_expired = 1;

			$sql = "
				INSERT INTO
					g5_point
				SET
					mb_id = '$mb_id',
					po_datetime = '".G5_TIME_YMDHIS."',
					po_content = '".addslashes($content)."',
					po_point = '$point',
					po_use_point = '0',
					po_mb_point = '$po_mb_point',
					po_expired = '$po_expired',
					po_expire_date = '$po_expire_date',
					po_rel_table = '$rel_table',
					po_rel_id = '$rel_id',
					po_rel_action = '$rel_action' ";
			sql_query($sql);

			// 포인트를 사용한 경우 포인트 내역에 사용금액 기록
			if($point < 0) {
				insert_use_point($mb_id, $point);
			}
		}

		// 유효기간이 있을 때 기간이 지난 포인트 expired 체크
		$sql = "
			UPDATE
				g5_point
			SET
				po_expired = '1'
			WHERE
				mb_id = '$mb_id'
				AND po_expired <> '1'
				AND po_expire_date <> '9999-12-31'
				AND po_expire_date < '".G5_TIME_YMD."' ";
		sql_query($sql);

	}

	// 포인트 합을 포인트로그상의 최종 데이터의 잔여포인트로 대체함 : 2021-02-05
	$row = sql_fetch("SELECT po_mb_point AS sum_po_point FROM {$g5['point_table']} WHERE mb_id = '$mb_id' ORDER BY po_datetime DESC, po_id DESC LIMIT 1");
	//$row = sql_fetch("SELECT SUM(po_point) AS sum_po_point FROM {$g5['point_table']} WHERE mb_id = '$mb_id'");

	return $row['sum_po_point'];
}

// 소멸 포인트
function get_expire_point($mb_id) {
	global $g5, $config;

	if($config['cf_point_term'] == 0)
		return 0;

	$sql = "
		SELECT
			SUM(po_point - po_use_point) AS sum_point
	  FROM
			g5_point
	  WHERE
			mb_id = '$mb_id'
			AND po_expired = '0'
			AND po_expire_date <> '9999-12-31'
			AND po_expire_date < '".G5_TIME_YMD."' ";
	$row = sql_fetch($sql);

	return $row['sum_point'];
}

// 포인트 삭제
function delete_point($mb_id, $rel_table, $rel_id, $rel_action) {
	global $g5;

	$result = false;
	if($rel_table || $rel_id || $rel_action) {
		// 포인트 내역정보
		$sql = "
			SELECT
				*
			FROM
				g5_point
			WHERE
				mb_id = '$mb_id'
				AND po_rel_table = '$rel_table'
				AND po_rel_id = '$rel_id'
				AND po_rel_action = '$rel_action' ";
		$row = sql_fetch($sql);

		if($row['po_point'] < 0) {
			$mb_id = $row['mb_id'];
			$po_point = abs($row['po_point']);

			delete_use_point($mb_id, $po_point);
		}
		else {
			if($row['po_use_point'] > 0) {
				insert_use_point($row['mb_id'], $row['po_use_point'], $row['po_id']);
			}
		}

		$result = sql_query("DELETE FROM g5_point WHERE mb_id='$mb_id' AND po_rel_table='$rel_table' AND po_rel_id='$rel_id' AND po_rel_action='$rel_action'", false);

		// po_mb_point에 반영
		$sql = "
			UPDATE
				g5_point
		  SET
				po_mb_point = po_mb_point - '{$row['po_point']}'
		  WHERE
				mb_id = '$mb_id'
				AND po_id > '{$row['po_id']}' ";
		sql_query($sql);


		//////////////////////////////////
		// member테이블 mb_point UPDATE
		//////////////////////////////////
		$sum_point = get_point_sum($mb_id);																							// 포인트 내역의 합
		sql_query("UPDATE g5_member SET mb_point='$sum_point' WHERE mb_id='$mb_id'");		// 2022-04-20 부터 적용

	}

	return $result;
}



// 회원 레이어
function get_sideview($mb_id, $name='', $email='', $homepage='')
{
	global $config;
	global $g5;
	global $bo_table, $sca, $is_admin, $member;

	$email_enc = new str_encrypt();
	$email     = $email_enc->encrypt($email);
	$homepage  = set_http(clean_xss_tags($homepage));

	$name	    = get_text($name, 0, true);
	$email	  = get_text($email);
	$homepage = get_text($homepage);

	$tmp_name = "";
	if($mb_id) {
		//$tmp_name = "<a href=\"".G5_BBS_URL."/profile.php?mb_id=".$mb_id."\" class=\"sv_member\" title=\"$name 자기소개\" target=\"_blank\" onclick=\"return false;\">$name</a>";
		$tmp_name = '<a href="'.G5_BBS_URL.'/profile.php?mb_id='.$mb_id.'" class="sv_member" title="'.$name.' 자기소개" target="_blank" onclick="return false;">';

		if($config['cf_use_member_icon']) {
			$mb_dir = substr($mb_id,0,2);
			$icon_file = G5_DATA_PATH.'/member/'.$mb_dir.'/'.$mb_id.'.gif';

			if(file_exists($icon_file)) {
				$width = $config['cf_member_icon_width'];
				$height = $config['cf_member_icon_height'];
				$icon_file_url = G5_DATA_URL.'/member/'.$mb_dir.'/'.$mb_id.'.gif';
				$tmp_name .= '<img src="'.$icon_file_url.'" width="'.$width.'" height="'.$height.'" alt="">';

				if($config['cf_use_member_icon'] == 2) // 회원아이콘+이름
					$tmp_name = $tmp_name.' '.$name;
			}
			else {
				$tmp_name = $tmp_name." ".$name;
			}
		}
		else {
			$tmp_name = $tmp_name.' '.$name;
		}
		$tmp_name .= '</a>';

		$title_mb_id = '['.$mb_id.']';
	}
	else {
		if(!$bo_table)
			return $name;

		$tmp_name = '<a href="'.G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;sca='.$sca.'&amp;sfl=wr_name,1&amp;stx='.$name.'" title="'.$name.' 이름으로 검색" class="sv_guest" onclick="return false;">'.$name.'</a>';
		$title_mb_id = '[비회원]';
	}

	$str = "<span class=\"sv_wrap\">\n";
	$str .= $tmp_name."\n";

	$str2 = "<span class=\"sv\">\n";
	if($mb_id)
		$str2 .= "<a href=\"".G5_BBS_URL."/memo_form.php?me_recv_mb_id=".$mb_id."\" onclick=\"win_memo(this.href); return false;\">쪽지보내기</a>\n";
	if($email)
		$str2 .= "<a href=\"".G5_BBS_URL."/formmail.php?mb_id=".$mb_id."&amp;name=".urlencode($name)."&amp;email=".$email."\" onclick=\"win_email(this.href); return false;\">메일보내기</a>\n";
	if($homepage)
		$str2 .= "<a href=\"".$homepage."\" target=\"_blank\">홈페이지</a>\n";
	if($mb_id)
		$str2 .= "<a href=\"".G5_BBS_URL."/profile.php?mb_id=".$mb_id."\" onclick=\"win_profile(this.href); return false;\">자기소개</a>\n";
	if($bo_table) {
		if($mb_id)
			$str2 .= "<a href=\"".G5_BBS_URL."/board.php?bo_table=".$bo_table."&amp;sca=".$sca."&amp;sfl=mb_id,1&amp;stx=".$mb_id."\">아이디로 검색</a>\n";
		else
			$str2 .= "<a href=\"".G5_BBS_URL."/board.php?bo_table=".$bo_table."&amp;sca=".$sca."&amp;sfl=wr_name,1&amp;stx=".$name."\">이름으로 검색</a>\n";
	}
	if($mb_id)
		$str2 .= "<a href=\"".G5_BBS_URL."/new.php?mb_id=".$mb_id."\">전체게시물</a>\n";
	if($is_admin == "super" && $mb_id) {
		$str2 .= "<a href=\"".G5_ADMIN_URL."/member_form.php?w=u&amp;mb_id=".$mb_id."\" target=\"_blank\">회원정보변경</a>\n";
		$str2 .= "<a href=\"".G5_ADMIN_URL."/point_list.php?sfl=mb_id&amp;stx=".$mb_id."\" target=\"_blank\">포인트내역</a>\n";
	}
	$str2 .= "</span>\n";
	$str .= $str2;
	$str .= "\n<noscript class=\"sv_nojs\">".$str2."</noscript>";

	$str .= "</span>";

	return $str;
}


// 파일을 보이게 하는 링크 (이미지, 플래쉬, 동영상)
function view_file_link($file, $width, $height, $content='')
{
	global $config, $board;
	global $g5;
	static $ids;

	if(!$file) return;

	$ids++;

	// 파일의 폭이 게시판설정의 이미지폭 보다 크다면 게시판설정 폭으로 맞추고 비율에 따라 높이를 계산
	if($width > $board['bo_image_width'] && $board['bo_image_width']) {
		$rate = $board['bo_image_width'] / $width;
		$width = $board['bo_image_width'];
		$height = (int)($height * $rate);
	}

	// 폭이 있는 경우 폭과 높이의 속성을 주고, 없으면 자동 계산되도록 코드를 만들지 않는다.
	$attr = ($width) ? ' width="'.$width.'" height="'.$height.'" ' : '';

	if(preg_match("/\.({$config['cf_image_extension']})$/i", $file)) {
		$img = '<a href="'.G5_BBS_URL.'/view_image.php?bo_table='.$board['bo_table'].'&amp;fn='.urlencode($file).'" target="_blank" class="view_image">';
		$img .= '<img src="'.G5_DATA_URL.'/file/'.$board['bo_table'].'/'.urlencode($file).'" alt="'.$content.'" '.$attr.'>';
		$img .= '</a>';
		return $img;
	}
}


// view_file_link() 함수에서 넘겨진 이미지를 보이게 합니다.
// {img:0} ... {img:n} 과 같은 형식
function view_image($view, $number, $attribute)
{
	if($view['file'][$number]['view'])
		return preg_replace("/>$/", " $attribute>", $view['file'][$number]['view']);
	else
		//return "{".$number."번 이미지 없음}";
		return "";
}


/*
// {link:0} ... {link:n} 과 같은 형식
function view_link($view, $number, $attribute)
{
	global $config;

	if($view['link'][$number]['link'])
	{
		if(!preg_match("/target/i", $attribute))
			$attribute .= " target='$config['cf_link_target']'";
		return "<a href='{$view['link'][$number]['href']}' $attribute>{$view['link'][$number]['link']}</a>";
	}
	else
		return "{".$number."번 링크 없음}";
}
*/


function cut_str($str, $len, $suffix="…")
{
	$arr_str = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	$str_len = count($arr_str);

	if($str_len >= $len) {
		$slice_str = array_slice($arr_str, 0, $len);
		$str = join("", $slice_str);

		return $str . ($str_len > $len ? $suffix : '');
	} else {
		$str = join("", $arr_str);
		return $str;
	}
}

function cut_str2($str, $len, $suffix="") {
	$s = substr($str, 0, $len);
	$cnt = 0;
	for($i=0; $i<strlen($s); $i++) {
		if(ord($s[$i]) > 127) $cnt++;
	}
	$s = substr($s, 0, $len - ($cnt % 2));
	if(strlen($s) >= strlen($str)) $suffix = "";
	return $s . $suffix;
}

// TEXT 형식으로 변환
function get_text($str, $html=0, $restore=false)
{
	$source[] = "<";
	$target[] = "&lt;";
	$source[] = ">";
	$target[] = "&gt;";
	$source[] = "\"";
	$target[] = "&#034;";
	$source[] = "\'";
	$target[] = "&#039;";

	if($restore)
		$str = str_replace($target, $source, $str);

	// 3.31
	// TEXT 출력일 경우 &amp; &nbsp; 등의 코드를 정상으로 출력해 주기 위함
	if($html == 0) {
		$str = html_symbol($str);
	}

	if($html) {
		$source[] = "\n";
		$target[] = "<br/>";
	}

	return str_replace($source, $target, $str);
}


/*
// HTML 특수문자 변환 htmlspecialchars
function hsc($str)
{
	$trans = array("\"" => "&#034;", "'" => "&#039;", "<"=>"&#060;", ">"=>"&#062;");
	$str = strtr($str, $trans);
	return $str;
}
*/

// 3.31
// HTML SYMBOL 변환
// &nbsp; &amp; &middot; 등을 정상으로 출력
function html_symbol($str)
{
	return preg_replace("/\&([a-z0-9]{1,20}|\#[0-9]{0,3});/i", "&#038;\\1;", $str);
}


/*************************************************************************
 **
 **  SQL 관련 함수 모음
 **
 *************************************************************************/

function sql_connect($host, $user, $pass, $db=G5_MYSQL_DB)
{
	global $g5;

	$db_port = '';

	//프록시 서버 10.22.160.28

	if( in_array($host, array('10.22.160.28','211.56.4.58')) ) {
		$db_port = '6033';
	}
	else {
		$db_port = '3306';
	}

	if(function_exists('mysqli_connect') && G5_MYSQLI_USE) {
		$link = mysqli_connect($host, $user, $pass, $db, $db_port);

		// 연결 오류 발생 시 스크립트 종료
		if(mysqli_connect_errno()) {
			die('Connect Error: '.mysqli_connect_error());
		}
	}
	else {
		$link = mysql_connect($host, $user, $pass);
	}
	return $link;
}

function sql_close($link='')
{
	global $g5;

	if(!$link) $link = $g5['connect_db'];

	if(function_exists('mysqli_close') && G5_MYSQLI_USE) {
		mysqli_close($link);
	}
	else {
		mysql_close($link);
	}
}

// DB 선택
function sql_select_db($db, $connect)
{
	global $g5;

	if(function_exists('mysqli_select_db') && G5_MYSQLI_USE)
		return @mysqli_select_db($connect, $db);
	else
		return @mysql_select_db($db, $connect);
}


function sql_set_charset($charset, $link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	if(function_exists('mysqli_set_charset') && G5_MYSQLI_USE)
		mysqli_set_charset($link, $charset);
	else
		mysql_query("SET NAMES {$charset}", $link);
}


// mysqli_query 와 mysqli_error 를 한꺼번에 처리
// mysql connect resource 지정 - 명랑폐인님 제안
function sql_query($sql, $error=G5_DISPLAY_SQL_ERROR, $link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	// Blind SQL Injection 취약점 해결
	$sql = @trim($sql);
	// union의 사용을 허락하지 않습니다.
	//$sql = preg_replace("#^select.*from.*union.*#i", "select 1", $sql);
	$sql = preg_replace("#^select.*from.*[\s\(]+union[\s\)]+.*#i ", "select 1", $sql);
	// `information_schema` DB로의 접근을 허락하지 않습니다.
	$sql = preg_replace("#^select.*from.*where.*`?information_schema`?.*#i", "select 1", $sql);


	// 쿼리 이상 발생시 출력
	/*
	if($_SERVER['REMOTE_ADDR']=='220.117.134.164') {
		echo "<p id='sqls' style='font-size:11px;margin:10px 0 10px;text-align:left;color:#222;opacity:0.3'>".$sql.";</p>\n";
	}
	*/

	if(function_exists('mysqli_query') && G5_MYSQLI_USE) {
		if($error) {
			$result = @mysqli_query($link, $sql) or die("<p>$sql<p>" . mysqli_errno($link) . " : " .  mysqli_error($link) . "<p>error file : {$_SERVER['SCRIPT_NAME']}");
		}
		else {
			$result = @mysqli_query($link, $sql);
		}
	}
	else {
		if($error) {
			$result = @mysql_query($sql, $link) or die("<p>$sql<p>" . mysql_errno() . " : " .  mysql_error() . "<p>error file : {$_SERVER['SCRIPT_NAME']}");
		}
		else {
			$result = @mysql_query($sql, $link);
		}
	}

	return $result;
}


// 쿼리를 실행한 후 결과값에서 한행을 얻는다.
function sql_fetch($sql, $error=G5_DISPLAY_SQL_ERROR, $link=null)
{
	global $g5;

	if(!$link) $link = $g5['connect_db'];

	$result = sql_query($sql, $error, $link);
	if($result) {
		$row = sql_fetch_array($result);

		sql_free_result($result);
	}

	return $row;
}


// 결과값에서 한행 연관배열(이름으로)로 얻는다.
function sql_fetch_array($result)
{
	if(function_exists('mysqli_fetch_assoc') && G5_MYSQLI_USE)
		$row = @mysqli_fetch_assoc($result);
	else
		$row = @mysql_fetch_assoc($result);

	return $row;
}


// $result에 대한 메모리(memory)에 있는 내용을 모두 제거한다.
// sql_free_result()는 결과로부터 얻은 질의 값이 커서 많은 메모리를 사용할 염려가 있을 때 사용된다.
// 단, 결과 값은 스크립트(script) 실행부가 종료되면서 메모리에서 자동적으로 지워진다.
// 2022-05-19 : if($result != NULL) 조건문 추가
function sql_free_result($result)
{
	if($result != NULL) {
		if(function_exists('mysqli_free_result') && G5_MYSQLI_USE)
			return mysqli_free_result($result);
		else
			return mysql_free_result($result);
	}
}


function sql_password($value)
{
	// mysql 4.0x 이하 버전에서는 password() 함수의 결과가 16bytes
	// mysql 4.1x 이상 버전에서는 password() 함수의 결과가 41bytes
	$row = sql_fetch("SELECT PASSWORD('$value') AS pass");

	return $row['pass'];
}


function sql_insert_id($link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	if(function_exists('mysqli_insert_id') && G5_MYSQLI_USE)
		return mysqli_insert_id($link);
	else
		return mysql_insert_id($link);
}


function sql_num_rows($result)
{
	if(function_exists('mysqli_num_rows') && G5_MYSQLI_USE)
		return mysqli_num_rows($result);
	else
		return mysql_num_rows($result);
}


function sql_affected_rows($link=null)
{
	global $g5;

	$link = (!$link) ? $g5['connect_db'] : $link;

	if(function_exists('mysqli_affected_rows') && G5_MYSQLI_USE)
		return mysqli_affected_rows($link);
	else
		return mysql_affected_rows();
}


function sql_field_names($table, $link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	$columns = array();

	$sql = " select * from `$table` limit 1 ";
	$result = sql_query($sql, $link);

	if(function_exists('mysqli_fetch_field') && G5_MYSQLI_USE) {
		while($field = mysqli_fetch_field($result)) {
			$columns[] = $field->name;
		}
	} else {
		$i = 0;
		$cnt = mysql_num_fields($result);
		while($i < $cnt) {
			$field = mysql_fetch_field($result, $i);
			$columns[] = $field->name;
			$i++;
		}
	}

	return $columns;
}


function sql_error_info($link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	if(function_exists('mysqli_error') && G5_MYSQLI_USE) {
		return mysqli_errno($link) . ' : ' . mysqli_error($link);
	} else {
		return mysql_errno($link) . ' : ' . mysql_error($link);
	}
}


function query($query)
{
	global $g5;

	$result = mysqli_query($g5['connect_db'],$query);
	return $result ;
}


// PHPMyAdmin 참고
function get_table_define($table, $crlf="\n")
{
	global $g5;

	// For MySQL < 3.23.20
	$schema_create .= 'CREATE TABLE ' . $table . ' (' . $crlf;

	$sql = 'SHOW FIELDS FROM ' . $table;
	$result = sql_query($sql);
	while($row = sql_fetch_array($result))
	{
		$schema_create .= '	' . $row['Field'] . ' ' . $row['Type'];
		if(isset($row['Default']) && $row['Default'] != '')
		{
			$schema_create .= ' DEFAULT \'' . $row['Default'] . '\'';
		}
		if($row['Null'] != 'YES')
		{
			$schema_create .= ' NOT NULL';
		}
		if($row['Extra'] != '')
		{
			$schema_create .= ' ' . $row['Extra'];
		}
		$schema_create	 .= ',' . $crlf;
	} // end while
	sql_free_result($result);

	$schema_create = preg_replace('/,' . $crlf . '$/', '', $schema_create);

	$sql = 'SHOW KEYS FROM ' . $table;
	$result = sql_query($sql);
	while($row = sql_fetch_array($result))
	{
		$kname	= $row['Key_name'];
		$comment  = (isset($row['Comment'])) ? $row['Comment'] : '';
		$sub_part = (isset($row['Sub_part'])) ? $row['Sub_part'] : '';

		if($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
			$kname = "UNIQUE|$kname";
		}
		if($comment == 'FULLTEXT') {
			$kname = 'FULLTEXT|$kname';
		}
		if(!isset($index[$kname])) {
			$index[$kname] = array();
		}
		if($sub_part > 1) {
			$index[$kname][] = $row['Column_name'] . '(' . $sub_part . ')';
		} else {
			$index[$kname][] = $row['Column_name'];
		}
	} // end while
	sql_free_result($result);

	while(list($x, $columns) = @each($index)) {
		$schema_create	 .= ',' . $crlf;
		if($x == 'PRIMARY') {
			$schema_create .= '	PRIMARY KEY (';
		} else if(substr($x, 0, 6) == 'UNIQUE') {
			$schema_create .= '	UNIQUE ' . substr($x, 7) . ' (';
		} else if(substr($x, 0, 8) == 'FULLTEXT') {
			$schema_create .= '	FULLTEXT ' . substr($x, 9) . ' (';
		} else {
			$schema_create .= '	KEY ' . $x . ' (';
		}
		$schema_create	 .= implode($columns, ', ') . ')';
	} // end while

	$schema_create .= $crlf . ') ENGINE=MyISAM DEFAULT CHARSET=utf8';

	return $schema_create;
} // end of the 'PMA_getTableDef()' function


// 리퍼러 체크
function referer_check($url='')
{
	/*
	// 제대로 체크를 하지 못하여 주석 처리함
	global $g5;

	if(!$url)
		$url = G5_URL;

	if(!preg_match("/^http['s']?:\/\/".$_SERVER['HTTP_HOST']."/", $_SERVER['HTTP_REFERER']))
		alert("제대로 된 접근이 아닌것 같습니다.", $url);
	*/
}


// 한글 요일
function get_yoil($date, $full=0)
{
	$arr_yoil = array ('일', '월', '화', '수', '목', '금', '토');

	$yoil = date("w", strtotime($date));
	$str = $arr_yoil[$yoil];
	if($full) {
		$str .= '요일';
	}
	return $str;
}


// 날짜를 select 박스 형식으로 얻는다
function date_select($date, $name='')
{
	global $g5;

	$s = '';
	if(substr($date, 0, 4) == "0000") {
		$date = G5_TIME_YMDHIS;
	}
	preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/", $date, $m);

	// 년
	$s .= "<select name='{$name}_y'>";
	for($i=$m['0']-3; $i<=$m['0']+3; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['0']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>년 \n";

	// 월
	$s .= "<select name='{$name}_m'>";
	for($i=1; $i<=12; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['2']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>월 \n";

	// 일
	$s .= "<select name='{$name}_d'>";
	for($i=1; $i<=31; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['3']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>일 \n";

	return $s;
}


// 시간을 select 박스 형식으로 얻는다
// 1.04.00
// 경매에 시간 설정이 가능하게 되면서 추가함
function time_select($time, $name="")
{
	preg_match("/([0-9]{2}):([0-9]{2}):([0-9]{2})/", $time, $m);

	// 시
	$s .= "<select name='{$name}_h'>";
	for($i=0; $i<=23; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['0']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>시 \n";

	// 분
	$s .= "<select name='{$name}_i'>";
	for($i=0; $i<=59; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['2']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>분 \n";

	// 초
	$s .= "<select name='{$name}_s'>";
	for($i=0; $i<=59; $i++) {
		$s .= "<option value='$i'";
		if($i == $m['3']) {
			$s .= " selected";
		}
		$s .= ">$i";
	}
	$s .= "</select>초 \n";

	return $s;
}


// DEMO 라는 파일이 있으면 데모 화면으로 인식함
function check_demo()
{
	global $is_admin;
	if($is_admin != 'super' && file_exists(G5_PATH.'/DEMO'))
		alert('데모 화면에서는 하실(보실) 수 없는 작업입니다.');
}


// 문자열이 한글, 영문, 숫자, 특수문자로 구성되어 있는지 검사
function check_string($str, $options)
{
	global $g5;

	$s = '';
	for($i=0;$i<strlen($str);$i++) {
		$c = $str[$i];
		$oc = ord($c);

		// 한글
		if($oc >= 0xA0 && $oc <= 0xFF) {
			if($options & G5_HANGUL) {
				$s .= $c . $str[$i+1] . $str[$i+2];
			}
			$i+=2;
		}
		// 숫자
		else if($oc >= 0x30 && $oc <= 0x39) {
			if($options & G5_NUMERIC) {
				$s .= $c;
			}
		}
		// 영대문자
		else if($oc >= 0x41 && $oc <= 0x5A) {
			if(($options & G5_ALPHABETIC) || ($options & G5_ALPHAUPPER)) {
				$s .= $c;
			}
		}
		// 영소문자
		else if($oc >= 0x61 && $oc <= 0x7A) {
			if(($options & G5_ALPHABETIC) || ($options & G5_ALPHALOWER)) {
				$s .= $c;
			}
		}
		// 공백
		else if($oc == 0x20) {
			if($options & G5_SPACE) {
				$s .= $c;
			}
		}
		else {
			if($options & G5_SPECIAL) {
				$s .= $c;
			}
		}
	}

	// 넘어온 값과 비교하여 같으면 참, 틀리면 거짓
	return ($str == $s);
}


// 한글(2bytes)에서 마지막 글자가 1byte로 끝나는 경우
// 출력시 깨지는 현상이 발생하므로 마지막 완전하지 않은 글자(1byte)를 하나 없앰
function cut_hangul_last($hangul)
{
	global $g5;

	// 한글이 반쪽나면 ?로 표시되는 현상을 막음
	$cnt = 0;
	for($i=0;$i<strlen($hangul);$i++) {
		// 한글만 센다
		if(ord($hangul[$i]) >= 0xA0) {
			$cnt++;
		}
	}

	return $hangul;
}


// 테이블에서 INDEX(키) 사용여부 검사
function explain($sql)
{
	if(preg_match("/^(select)/i", trim($sql))) {
		$q = "explain $sql";
		echo $q;
		$row = sql_fetch($q);
		if(!$row['key']) $row['key'] = "NULL";
		echo " <font color=blue>(type={$row['type']} , key={$row['key']})</font>";
	}
}

// 악성태그 변환
function bad_tag_convert($code)
{
	global $view;
	global $member, $is_admin;

	if($is_admin && $member['mb_id'] != $view['mb_id']) {
		//$code = preg_replace_callback("#(\<(embed|object)[^\>]*)\>(\<\/(embed|object)\>)?#i",
		// embed 또는 object 태그를 막지 않는 경우 필터링이 되도록 수정
		$code = preg_replace_callback("#(\<(embed|object)[^\>]*)\>?(\<\/(embed|object)\>)?#i",
				create_function('$matches', 'return "<div class=\"embedx\">보안문제로 인하여 관리자 아이디로는 embed 또는 object 태그를 볼 수 없습니다. 확인하시려면 관리권한이 없는 다른 아이디로 접속하세요.</div>";'),
				$code);
	}

	return preg_replace("/\<([\/]?)(script|iframe|form)([^\>]*)\>?/i", "&lt;$1$2$3&gt;", $code);
}


// 토큰 생성
function _token()
{
	return md5(uniqid(rand(), true));
}


// 불법접근을 막도록 토큰을 생성하면서 토큰값을 리턴
function get_token()
{
	$token = md5(uniqid(rand(), true));
	set_session('ss_token', $token);

	return $token;
}


// POST로 넘어온 토큰과 세션에 저장된 토큰 비교
function check_token()
{
	set_session('ss_token', '');
	return true;
}


// 문자열에 utf8 문자가 들어 있는지 검사하는 함수
// 코드 : http://in2.php.net/manual/en/function.mb-check-encoding.php#95289
function is_utf8($str)
{
	$len = strlen($str);
	for($i = 0; $i < $len; $i++) {
		$c = ord($str[$i]);
		if($c > 128) {
			if(($c > 247)) return false;
			elseif($c > 239) $bytes = 4;
			elseif($c > 223) $bytes = 3;
			elseif($c > 191) $bytes = 2;
			else return false;
			if(($i + $bytes) > $len) return false;
			while($bytes > 1) {
				$i++;
				$b = ord($str[$i]);
				if($b < 128 || $b > 191) return false;
				$bytes--;
			}
		}
	}
	return true;
}


// UTF-8 문자열 자르기
// 출처 : https://www.google.co.kr/search?q=utf8_strcut&aq=f&oq=utf8_strcut&aqs=chrome.0.57j0l3.826j0&sourceid=chrome&ie=UTF-8
function utf8_strcut( $str, $size, $suffix='...' )
{
	$substr = substr( $str, 0, $size * 2 );
	$multi_size = preg_match_all( '/[\x80-\xff]/', $substr, $multi_chars );

	if( $multi_size > 0 )
		$size = $size + intval( $multi_size / 3 ) - 1;

	if( strlen( $str ) > $size ) {
		$str = substr( $str, 0, $size );
		$str = preg_replace( '/(([\x80-\xff]{3})*?)([\x80-\xff]{0,2})$/', '$1', $str );
		$str .= $suffix;
	}

	return $str;
}


/*
-----------------------------------------------------------
	Charset 을 변환하는 함수
-----------------------------------------------------------
iconv 함수가 있으면 iconv 로 변환하고
없으면 mb_convert_encoding 함수를 사용한다.
둘다 없으면 사용할 수 없다.
*/
function convert_charset($from_charset, $to_charset, $str)
{

	if( function_exists('iconv') )
		return iconv($from_charset, $to_charset, $str);
	elseif( function_exists('mb_convert_encoding') )
		return mb_convert_encoding($str, $to_charset, $from_charset);
	else
		die("Not found 'iconv' or 'mbstring' library in server.");
}


// mysqli_real_escape_string 의 alias 기능을 한다.
function sql_real_escape_string($str, $link=null)
{
	global $g5;

	if(!$link)
		$link = $g5['connect_db'];

	return mysqli_real_escape_string($link, $str);
}

function escape_trim($field)
{
	$str = call_user_func(G5_ESCAPE_FUNCTION, $field);
	return $str;
}


// $_POST 형식에서 checkbox 엘리먼트의 checked 속성에서 checked 가 되어 넘어 왔는지를 검사
function is_checked($field)
{
	return !empty($_POST[$field]);
}


function abs_ip2long($ip='')
{
	$ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
	return abs(ip2long($ip));
}


function get_selected($field, $value)
{
	return ($field==$value) ? ' selected="selected"' : '';
}


function get_checked($field, $value)
{
	return ($field==$value) ? ' checked="checked"' : '';
}


function is_mobile()
{
	return preg_match('/'.G5_MOBILE_AGENT.'/i', $_SERVER['HTTP_USER_AGENT']);
}


/*******************************************************************************
	유일한 키를 얻는다.

	결과 :

		년월일시분초00 ~ 년월일시분초99
		년(4) 월(2) 일(2) 시(2) 분(2) 초(2) 100분의1초(2)
		총 16자리이며 년도는 2자리로 끊어서 사용해도 됩니다.
		예) 2008062611570199 또는 08062611570199 (2100년까지만 유일키)

	사용하는 곳 :
	1. 게시판 글쓰기시 미리 유일키를 얻어 파일 업로드 필드에 넣는다.
	2. 주문번호 생성시에 사용한다.
	3. 기타 유일키가 필요한 곳에서 사용한다.
 *******************************************************************************/
// 기존의 get_unique_id() 함수를 사용하지 않고 get_uniqid() 를 사용한다.
function get_uniqid()
{
	global $g5;

	sql_query(" LOCK TABLE {$g5['uniqid_table']} WRITE ");
	while(1) {
		// 년월일시분초에 100분의 1초 두자리를 추가함 (1/100 초 앞에 자리가 모자르면 0으로 채움)
		$key = date('YmdHis', time()) . str_pad((int)(microtime()*100), 2, "0", STR_PAD_LEFT);

		$result = sql_query(" insert into {$g5['uniqid_table']} set uq_id = '$key', uq_ip = '{$_SERVER['REMOTE_ADDR']}' ", false);
		if($result) break; // 쿼리가 정상이면 빠진다.

		// insert 하지 못했으면 일정시간 쉰다음 다시 유일키를 만든다.
		usleep(10000); // 100분의 1초를 쉰다
	}
	sql_query(" UNLOCK TABLES ");

	return $key;
}


// CHARSET 변경 : euc-kr -> utf-8
function iconv_utf8($str)
{
	return iconv('euc-kr', 'utf-8', $str);
}


// CHARSET 변경 : utf-8 -> euc-kr
function iconv_euckr($str)
{
	return iconv('utf-8', 'euc-kr', $str);
}


// PC 또는 모바일 사용인지를 검사
function check_device($device)
{
	global $is_admin;

	if($is_admin) return;

	if($device=='pc' && G5_IS_MOBILE) {
		alert('PC 전용 게시판입니다.', G5_URL);
	} else if($device=='mobile' && !G5_IS_MOBILE) {
		alert('모바일 전용 게시판입니다.', G5_URL);
	}
}


// 게시판 최신글 캐시 파일 삭제
function delete_cache_latest($bo_table)
{
	$files = glob(G5_DATA_PATH.'/cache/latest-'.$bo_table.'-*');
	if(is_array($files)) {
		foreach ($files as $filename)
			unlink($filename);
	}
}

// 게시판 첨부파일 썸네일 삭제
function delete_board_thumbnail($bo_table, $file)
{
	if(!$bo_table || !$file)
		return;

	$fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
	$files = glob(G5_DATA_PATH.'/file/'.$bo_table.'/thumb-'.$fn.'*');
	if(is_array($files)) {
		foreach ($files as $filename)
			unlink($filename);
	}
}

// 에디터 이미지 얻기
function get_editor_image($contents, $view=true)
{
	if(!$contents)
		return false;

	// $contents 중 img 태그 추출
	if($view)
		$pattern = "/<img([^>]*)>/iS";
	else
		$pattern = "/<img[^>]*src=[\'\"]?([^>\'\"]+[^>\'\"]+)[\'\"]?[^>]*>/i";
	preg_match_all($pattern, $contents, $matchs);

	return $matchs;
}

// 에디터 썸네일 삭제
function delete_editor_thumbnail($contents)
{
	if(!$contents)
		return;

	// $contents 중 img 태그 추출
	$matchs = get_editor_image($contents);

	if(!$matchs)
		return;

	for($i=0; $i<count($matchs[1]); $i++) {
		// 이미지 path 구함
		$imgurl = @parse_url($matchs[1][$i]);
		$srcfile = $_SERVER['DOCUMENT_ROOT'].$imgurl['path'];

		$filename = preg_replace("/\.[^\.]+$/i", "", basename($srcfile));
		$filepath = dirname($srcfile);
		$files = glob($filepath.'/thumb-'.$filename.'*');
		if(is_array($files)) {
			foreach($files as $filename)
				unlink($filename);
		}
	}
}

// 1:1문의 첨부파일 썸네일 삭제
function delete_qa_thumbnail($file)
{
	if(!$file)
		return;

	$fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
	$files = glob(G5_DATA_PATH.'/qa/thumb-'.$fn.'*');
	if(is_array($files)) {
		foreach ($files as $filename)
			unlink($filename);
	}
}

// 스킨 style sheet 파일 얻기
function get_skin_stylesheet($skin_path, $dir='')
{
	if(!$skin_path)
		return "";

	$str = "";
	$files = array();

	if($dir)
		$skin_path .= '/'.$dir;

	$skin_url = G5_URL.str_replace("\\", "/", str_replace(G5_PATH, "", $skin_path));

	if(is_dir($skin_path)) {
		if($dh = opendir($skin_path)) {
			while(($file = readdir($dh)) !== false) {
				if($file == "." || $file == "..")
					continue;

				if(is_dir($skin_path.'/'.$file))
					continue;

				if(preg_match("/\.(css)$/i", $file))
					$files[] = $file;
			}
			closedir($dh);
		}
	}

	if(!empty($files)) {
		sort($files);

		foreach($files as $file) {
			$str .= '<link rel="stylesheet" href="'.$skin_url.'/'.$file.'?='.date("md").'">'."\n";
		}
	}

	return $str;

	/*
	// glob 를 이용한 코드
	if(!$skin_path) return '';
	$skin_path .= $dir ? '/'.$dir : '';

	$str = '';
	$skin_url = G5_URL.str_replace('\\', '/', str_replace(G5_PATH, '', $skin_path));

	foreach (glob($skin_path.'/*.css') as $filepath) {
		$file = str_replace($skin_path, '', $filepath);
		$str .= '<link rel="stylesheet" href="'.$skin_url.'/'.$file.'?='.date('md').'">'."\n";
	}
	return $str;
	*/
}

// 스킨 javascript 파일 얻기
function get_skin_javascript($skin_path, $dir='')
{
	if(!$skin_path)
		return "";

	$str = "";
	$files = array();

	if($dir)
		$skin_path .= '/'.$dir;

	$skin_url = G5_URL.str_replace("\\", "/", str_replace(G5_PATH, "", $skin_path));

	if(is_dir($skin_path)) {
		if($dh = opendir($skin_path)) {
			while(($file = readdir($dh)) !== false) {
				if($file == "." || $file == "..")
					continue;

				if(is_dir($skin_path.'/'.$file))
					continue;

				if(preg_match("/\.(js)$/i", $file))
					$files[] = $file;
			}
			closedir($dh);
		}
	}

	if(!empty($files)) {
		sort($files);

		foreach($files as $file) {
			$str .= '<script src="'.$skin_url.'/'.$file.'"></script>'."\n";
		}
	}

	return $str;
}

// file_put_contents 는 PHP5 전용 함수이므로 PHP4 하위버전에서 사용하기 위함
// http://www.phpied.com/file_get_contents-for-php4/
if(!function_exists('file_put_contents')) {
	function file_put_contents($filename, $data) {
		$f = @fopen($filename, 'w');
		if(!$f) {
			return false;
		} else {
			$bytes = fwrite($f, $data);
			fclose($f);
			return $bytes;
		}
	}
}


// HTML 마지막 처리
function html_end()
{
	global $html_process;

	return $html_process->run();
}

function add_stylesheet($stylesheet, $order=0)
{
	global $html_process;

	if(trim($stylesheet))
		$html_process->merge_stylesheet($stylesheet, $order);
}

function add_javascript($javascript, $order=0)
{
	global $html_process;

	if(trim($javascript))
		$html_process->merge_javascript($javascript, $order);
}

class html_process {
	protected $css = array();
	protected $js  = array();

	function merge_stylesheet($stylesheet, $order)
	{
		$links = $this->css;
		$is_merge = true;

		foreach($links as $link) {
			if($link[1] == $stylesheet) {
				$is_merge = false;
				break;
			}
		}

		if($is_merge)
			$this->css[] = array($order, $stylesheet);
	}

	function merge_javascript($javascript, $order)
	{
		$scripts = $this->js;
		$is_merge = true;

		foreach($scripts as $script) {
			if($script[1] == $javascript) {
				$is_merge = false;
				break;
			}
		}

		if($is_merge)
			$this->js[] = array($order, $javascript);
	}

	function run()
	{
		global $config, $g5, $member;

		$device = getDevice();

		$remote_addr = sql_real_escape_string($_SERVER['REMOTE_ADDR']);

		// 현재접속자 처리
		$tmp_sql = "SELECT COUNT(*) AS cnt FROM {$g5['login_table']} WHERE lo_ip='".$remote_addr."'";
		$tmp_row = sql_fetch($tmp_sql);

		if($tmp_row['cnt']) {
			$tmp_sql = "
				update
					{$g5['login_table']}
				set
					mb_id       = '".$member['mb_id']."',
					lo_datetime = '".G5_TIME_YMDHIS."',
					lo_location = '".$g5['lo_location']."',
					lo_url      = '".$g5['lo_url']."',
					lo_device   = '".$device."'
				WHERE
					lo_ip = '".$remote_addr."'";
			sql_query($tmp_sql, FALSE);
		}
		else {
			$tmp_sql = "
				INSERT INTO
					{$g5['login_table']}
				SET
					lo_ip       = '".$remote_addr."',
					mb_id       = '".$member['mb_id']."',
					lo_datetime = '".G5_TIME_YMDHIS."',
					lo_location = '".$g5['lo_location']."',
					lo_url      = '".$g5['lo_url']."',
					lo_device   = '".$device."'";
			sql_query($tmp_sql, FALSE);

			// 시간이 지난 접속은 삭제한다
			sql_query("DELETE FROM {$g5['login_table']} WHERE lo_datetime < '".date("Y-m-d H:i:s", G5_SERVER_TIME - (60 * $config['cf_login_minutes']))."'");

			// 부담(overhead)이 있다면 테이블 최적화
			//$row = sql_fetch(" SHOW TABLE STATUS FROM `$mysql_db` LIKE '$g5['login_table']' ");
			//if($row['Data_free'] > 0) sql_query(" OPTIMIZE TABLE $g5['login_table'] ");
		}

		$buffer = ob_get_contents();
		ob_end_clean();

		// 세션을 선택적으로 시작함
		lazy_session_start();

		$stylesheet = '';
		$links = $this->css;

		if(!empty($links)) {
			foreach ($links as $key => $row) {
				$order[$key] = $row[0];
				$index[$key] = $key;
				$style[$key] = $row[1];
			}

			array_multisort($order, SORT_ASC, $index, SORT_ASC, $links);

			foreach($links as $link) {
				if(!trim($link[1]))
					continue;

				$stylesheet .= PHP_EOL.$link[1];
			}
		}

		$javascript = '';
		$scripts = $this->js;
		$php_eol = '';

		unset($order);
		unset($index);

		if(!empty($scripts)) {
			foreach ($scripts as $key => $row) {
				$order[$key] = $row[0];
				$index[$key] = $key;
				$script[$key] = $row[1];
			}

			array_multisort($order, SORT_ASC, $index, SORT_ASC, $scripts);

			foreach($scripts as $js) {
				if(!trim($js[1]))
					continue;

				$javascript .= $php_eol.$js[1];
				$php_eol = PHP_EOL;
			}
		}

		/*
		</title>
		<link rel="stylesheet" href="default.css">
		밑으로 스킨의 스타일시트가 위치하도록 하게 한다.
		*/
		$buffer = preg_replace("/(<\/title>[^<]*<link[^>]+>)/i", "$1$stylesheet", $buffer);
		//$buffer = preg_replace('#(</title>[^<]*<link[^>]+>)#', "$1$stylesheet", $buffer);

		/*
		</head>
		<body>
		전에 스킨의 자바스크립트가 위치하도록 하게 한다.
		*/
		$buffer = preg_replace("/(<\/head>[^<]*<body[^>]*>)/i", "$javascript\n$1", $buffer);
		//$buffer = preg_replace('#(</head>[^<]*<body[^>]*>)#', "$javascript\n$1", $buffer);

		return $buffer;
	}
}

// 휴대폰번호의 숫자만 취한 후 중간에 하이픈(-)을 넣는다.
function hyphen_hp_number($hp)
{
	$hp = preg_replace("/[^0-9]/", "", $hp);
	return preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $hp);
}


// 로그인 후 이동할 URL
function login_url($url='')
{
	if(!$url) $url = G5_URL;
	return urlencode(clean_xss_tags(urldecode($url)));
}


// $dir 을 포함하여 https 또는 http 주소를 반환한다.
function https_url($dir, $https=true)
{
	if($https) {
		$url = (G5_HTTPS_DOMAIN) ? G5_HTTPS_DOMAIN.'/'.$dir : G5_URL.'/'.$dir;
	}
	else {
		$url = (G5_DOMAIN) ? G5_DOMAIN.'/'.$dir : G5_URL.'/'.$dir;
	}
	return $url;
}


// 게시판의 공지사항을 , 로 구분하여 업데이트 한다.
function board_notice($bo_notice, $wr_id, $insert=false)
{
	$notice_array = explode(",", trim($bo_notice));

	if($insert && in_array($wr_id, $notice_array))
		return $bo_notice;

	$notice_array = array_merge(array($wr_id), $notice_array);
	$notice_array = array_unique($notice_array);
	foreach ($notice_array as $key=>$value) {
		if(!trim($value))
			unset($notice_array[$key]);
	}
	if(!$insert) {
		foreach ($notice_array as $key=>$value) {
			if((int)$value == (int)$wr_id)
				unset($notice_array[$key]);
		}
	}
	return implode(",", $notice_array);
}


// goo.gl 짧은주소 만들기
function googl_short_url($longUrl)
{
	global $config;

	// Get API key from : http://code.google.com/apis/console/
	// URL Shortener API ON
	$apiKey = $config['cf_googl_shorturl_apikey'];

	$postData = array('longUrl' => $longUrl);
	$jsonData = json_encode($postData);

	$curlObj = curl_init();

	curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key='.$apiKey);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curlObj, CURLOPT_HEADER, 0);
	curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	curl_setopt($curlObj, CURLOPT_POST, 1);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

	$response = curl_exec($curlObj);

	//change the response json string to object
	$json = json_decode($response);

	curl_close($curlObj);

	return $json->id;
}


// 임시 저장된 글 수
function autosave_count($mb_id)
{
	global $g5;

	if($mb_id) {
		$row = sql_fetch(" select count(*) as cnt from {$g5['autosave_table']} where mb_id = '$mb_id' ");
		return (int)$row['cnt'];
	}
	else {
		return 0;
	}
}

// 본인확인내역 기록
function insert_cert_history($mb_id, $company, $method)
{
	global $g5;

	$sql = "
		INSERT INTO
			{$g5['cert_history_table']}
		SET
			mb_id = '$mb_id',
			cr_company = '$company',
			cr_method = '$method',
			cr_ip = '{$_SERVER['REMOTE_ADDR']}',
			cr_date = '".G5_TIME_YMD."',
			cr_time = '".G5_TIME_HIS."' ";
	sql_query($sql);
}

// 인증시도회수 체크
function certify_count_check($mb_id, $type)
{
	global $g5, $config;

	if($config['cf_cert_use'] != 2)
		return;

	if($config['cf_cert_limit'] == 0)
		return;

	$sql = "select count(*) as cnt from {$g5['cert_history_table']} ";
	$sql.= ($mb_id) ? " where mb_id = '$mb_id' " : " where cr_ip = '{$_SERVER['REMOTE_ADDR']}' ";
	$sql.= " and cr_method = '".$type."' and cr_date = '".G5_TIME_YMD."' ";

	$row = sql_fetch($sql);

	switch($type) {
		case 'hp'  : $cert = '휴대폰'; break;
		case 'ipin': $cert = '아이핀'; break;
		default    : break;
	}

	if((int)$row['cnt'] >= (int)$config['cf_cert_limit'])
		alert_close('오늘 '.$cert.' 본인확인을 '.$row['cnt'].'회 이용하셔서 더 이상 이용할 수 없습니다.');
}

// 1:1문의 설정로드
function get_qa_config($fld='*')
{
	global $g5;

	$sql = " select $fld from {$g5['qa_config_table']} ";
	$row = sql_fetch($sql);

	return $row;
}

// get_sock 함수 대체
if(!function_exists("get_sock")) {
	function get_sock($url)
	{
		// host 와 uri 를 분리
		//if(ereg("http://([a-zA-Z0-9_\-\.]+)([^<]*)", $url, $res))
		if(preg_match("/http:\/\/([a-zA-Z0-9_\-\.]+)([^<]*)/", $url, $res))
		{
			$host = $res[1];
			$get  = $res[2];
		}

		// 80번 포트로 소캣접속 시도
		$fp = fsockopen ($host, 80, $errno, $errstr, 30);
		if(!$fp) {
			die("$errstr ($errno)\n");
		}
		else {
			fputs($fp, "GET $get HTTP/1.0\r\n");
			fputs($fp, "Host: $host\r\n");
			fputs($fp, "\r\n");

			// header 와 content 를 분리한다.
			while(trim($buffer = fgets($fp,1024)) != "")
			{
				$header .= $buffer;
			}
			while(!feof($fp))
			{
				$buffer .= fgets($fp,1024);
			}
		}
		fclose($fp);

		// content 만 return 한다.
		return $buffer;
	}
}

// 인증, 결제 모듈 실행 체크
function module_exec_check($exe, $type)
{
	$error = '';
	$is_linux = false;
	if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
		$is_linux = true;

	// 모듈 파일 존재하는지 체크
	if(!is_file($exe)) {
		$error = $exe.' 파일이 존재하지 않습니다.';
	} else {
		// 실행권한 체크
		if(!is_executable($exe)) {
			if($is_linux)
				$error = $exe.'\n파일의 실행권한이 없습니다.\n\nchmod 755 '.basename($exe).' 과 같이 실행권한을 부여해 주십시오.';
			else
				$error = $exe.'\n파일의 실행권한이 없습니다.\n\n'.basename($exe).' 파일에 실행권한을 부여해 주십시오.';
		} else {
			// 바이너리 파일인지
			if($is_linux) {
				$search = false;
				$isbinary = true;
				$executable = true;

				switch($type) {
					case 'ct_cli':
						exec($exe.' -h 2>&1', $out, $return_var);

						if($return_var == 139) {
							$isbinary = false;
							break;
						}

						for($i=0; $i<count($out); $i++) {
							if(strpos($out[$i], 'KCP ENC') !== false) {
								$search = true;
								break;
							}
						}
						break;
					case 'pp_cli':
						exec($exe.' -h 2>&1', $out, $return_var);

						if($return_var == 139) {
							$isbinary = false;
							break;
						}

						for($i=0; $i<count($out); $i++) {
							if(strpos($out[$i], 'CLIENT') !== false) {
								$search = true;
								break;
							}
						}
						break;
					case 'okname':
						exec($exe.' D 2>&1', $out, $return_var);

						if($return_var == 139) {
							$isbinary = false;
							break;
						}

						for($i=0; $i<count($out); $i++) {
							if(strpos(strtolower($out[$i]), 'ret code') !== false) {
								$search = true;
								break;
							}
						}
						break;
				}

				if(!$isbinary || !$search) {
					$error = $exe.'\n파일을 바이너리 타입으로 다시 업로드하여 주십시오.';
				}
			}
		}
	}

	if($error) {
		$error = '<script>alert("'.$error.'");</script>';
	}

	return $error;
}

// 주소출력
function print_address($addr1, $addr2, $addr3, $addr4)
{
	$address = get_text(trim($addr1));
	$addr2   = get_text(trim($addr2));
	$addr3   = get_text(trim($addr3));

	if($addr4 == 'N') {
		if($addr2)
			$address .= ' '.$addr2;
	}
	else {
		if($addr2)
			$address .= ', '.$addr2;
	}

	if($addr3)
		$address .= ' '.$addr3;

	return $address;
}

// input vars 체크
function check_input_vars()
{
	$max_input_vars = ini_get('max_input_vars');

	if($max_input_vars) {
		$post_vars = count($_POST, COUNT_RECURSIVE);
		$get_vars = count($_GET, COUNT_RECURSIVE);
		$cookie_vars = count($_COOKIE, COUNT_RECURSIVE);

		$input_vars = $post_vars + $get_vars + $cookie_vars;

		if($input_vars > $max_input_vars) {
			alert('폼에서 전송된 변수의 개수가 max_input_vars 값보다 큽니다.\\n전송된 값중 일부는 유실되어 DB에 기록될 수 있습니다.\\n\\n문제를 해결하기 위해서는 서버 php.ini의 max_input_vars 값을 변경하십시오.');
		}
	}
}

// HTML 특수문자 변환 htmlspecialchars
function htmlspecialchars2($str)
{
	$trans = array("\"" => "&#034;", "'" => "&#039;", "<"=>"&#060;", ">"=>"&#062;");
	$str = strtr($str, $trans);
	return $str;
}

// date 형식 변환
function conv_date_format($format, $date, $add='')
{
	if($add)
		$timestamp = strtotime($add, strtotime($date));
	else
		$timestamp = strtotime($date);

	return date($format, $timestamp);
}

// 검색어 특수문자 제거
function get_search_string($stx)
{
	$stx_pattern = array();
	$stx_pattern[] = '#\.*/+#';
	$stx_pattern[] = '#\\\*#';
	$stx_pattern[] = '#\.{2,}#';
	$stx_pattern[] = '#[/\'\"%=*\#\(\)\|\+\&\!\$~\{\}\[\]`;:\?\^\,]+#';

	$stx_replace = array();
	$stx_replace[] = '';
	$stx_replace[] = '';
	$stx_replace[] = '.';
	$stx_replace[] = '';

	$stx = preg_replace($stx_pattern, $stx_replace, $stx);

	return $stx;
}

// XSS 관련 태그 제거
/*
function clean_xss_tags($str)
{
	$str = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);
	return $str;
}
*/
function clean_xss_tags($data)
{

	// Fix &entity\n;
	$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
	$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
	$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
	$data = preg_replace('/(--|union |select |insert |from |where |update |drop |if |join |decalre |and |or |column_name|table_name|openrowset|substr|xp_|sysobjects|syscolumns)/i', '$1;', $data);
	$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

	// Remove any attribute starting with "on" or xmlns
	$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

	// Remove javascript: and vbscript: protocols
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

	// Remove namespaced elements (we do not need them)
	$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

	do
	{
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml|javascript:)[^>]*+>#i', '', $data);
	}
	while ($old_data !== $data);

	// we are done...
	return $data;
}

// unescape nl 얻기
function conv_unescape_nl($str)
{
	$search = array('\\r', '\r', '\\n', '\n');
	$replace = array('', '', "\n", "\n");

	return str_replace($search, $replace, $str);
}

// 회원 삭제 (관리자 삭제)
function member_delete($mb_id)
{
	global $config;
	global $g5;

	$mb = sql_fetch("SELECT * FROM {$g5['member_table']} WHERE mb_id='".$mb_id."'");

	// 이미 삭제된 회원은 제외
	if($mb['mb_level']=='200') { return; }
		if(preg_match('#^[0-9]{8}.*삭제함#', $mb['mb_memo'])) { return; }

		/*
		// 추천인 포인트 반환
	if($mb['mb_recommend']) {
		$row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['member_table']} WHERE mb_id = '".addslashes($mb['mb_recommend'])."' ");
		if($row['cnt']) {
		  insert_point($mb['mb_recommend'], $config['cf_recommend_point'] * (-1), $mb_id.'님의 회원자료 삭제로 인한 추천인 포인트 반환', "@member", $mb['mb_recommend'], $mb_id.' 추천인 삭제');
		}
	}
		*/

	// 회원자료는 정보만 없앤 후 아이디는 변환저장 하여 다른 사람이 사용하지 못하도록 함 : 2018-04-06
	$leave_date = date('Y-m-d', G5_SERVER_TIME);

	$sql = "
		UPDATE
			{$g5['member_table']}
		SET
			mb_level        = 0,
			mb_leave_date   = '".$leave_date."',
			mb_leave_reason = '관리자가 삭제함',
			mb_memo         = '".$leave_date." 삭제함\n{$mb['mb_memo']}'
		WHERE
			mb_id = '$mb_id'";

	if(sql_query($sql)) {

		// 삭제회원정보테이블로 이동후 회원정보테이블에서 삭제
		sql_query("INSERT INTO g5_member_drop SELECT * FROM {$g5['member_table']} WHERE mb_id = '".$mb_id."'");
		sql_query("UPDATE g5_member_drop SET WHERE mb_leave_date='".date('Y-m-d')."' WHERE mb_id = '".$mb_id."'");		// 탈퇴일자업데이트

		sql_query("DELETE FROM {$g5['member_table']} WHERE mb_id = '$mb_id'");

		//주요 플래그성 정보만 복원
		//$REP['mb_id'] = '____^' . $mb_id;
		$REP['mb_id'] = date('ymdHi').'^' . $mb_id;		// 아이디 형식변환 2018-06-20 수정
		$REP['mb_level'] = '200';

		$replace_sql_add = ($mb['member_investor_type']!='') ? " member_investor_type = '".$mb['member_investor_type']."', " : "";

		$change_syndi_userid = ($mb['syndi_userid']) ? "__" . $mb['syndi_userid'] : "";

		$replace_sql = "
			INSERT INTO
				{$g5['member_table']}
			SET
				mb_no             = '".$mb['mb_no']."',
				mb_id             = '".$REP['mb_id']."',
				mb_level          = '".$REP['mb_level']."',
				member_group      = '".$mb['member_group']."',
				member_type       = '".$mb['member_type']."',
				$replace_sql_add
				mb_point          = '0',
				is_creditor       = '".$mb['is_creditor']."',
				is_owner_operator = '".$mb['is_owner_operator']."',
				receive_method    = '".$mb['receive_method']."',
				bank_code         = '".$mb['bank_code']."',
				account_num       = '".$mb['account_num']."',
				va_bank_code      = '".$mb['va_bank_code']."',
				virtual_account   = '".$mb['virtual_account']."',
				va_bank_code2     = '".$mb['va_bank_code2']."',
				virtual_account2  = '".$mb['virtual_account2']."',
				syndi_id          = '".$mb['syndi_id']."',
				syndi_userid      = '".$change_syndi_userid."',
				syndi_date        = '".$mb['syndi_date']."',
				remit_fee         = '".$mb['remit_fee']."',
				foreigner         = '".$mb['foreigner']."',
				mb_leave_date     = '".$leave_date."'";
		$replace_res = sql_query($replace_sql);

		// 포인트로그 아이디 변환
		$replace_res2 = sql_query("UPDATE {$g5['point_table']} SET mb_id='".$REP['mb_id']."' WHERE mb_id='$mb_id'");
		//sql_query("DELETE FROM {$g5['point_table']} WHERE mb_id = '$mb_id'");		// 포인트 테이블에서 삭제 ========= 삭제중지 : 2018-04-06

		// 가상계좌 무효화
		if($mb['virtual_account2']) {
			// KSNET가상계좌 사용불가상태로 변경
			sql_query("UPDATE KSNET_VR_ACCOUNT SET USE_FLAG='N', FINAL_DATE='".date('Ymd')."' WHERE VR_ACCT_NO='".$mb['virtual_account2']."' AND CUST_ID='".$mb['mb_no']."'");

			// 신한가상리스트에서도 사용불가로 변경
			sql_query("UPDATE IB_vact SET acct_st='9', close_il='".date('Ymd')."' WHERE acct_no='".$mb['virtual_account2']."'");
		}


		// 그룹접근가능 삭제
		sql_query("DELETE FROM {$g5['group_member_table']} WHERE mb_id = '$mb_id'");

		// 쪽지 삭제
		sql_query("DELETE FROM {$g5['memo_table']} WHERE me_recv_mb_id = '$mb_id' OR me_send_mb_id = '$mb_id'");

		// 스크랩 삭제
		sql_query("DELETE FROM {$g5['scrap_table']} WHERE mb_id = '$mb_id'");

		// 관리권한 삭제
		sql_query("DELETE FROM {$g5['auth_table']} WHERE mb_id = '$mb_id'");

		// 그룹관리자인 경우 그룹관리자를 공백으로
		sql_query("UPDATE {$g5['group_table']} SET gr_admin = '' WHERE gr_admin = '$mb_id'");

		// 게시판관리자인 경우 게시판관리자를 공백으로
		sql_query("UPDATE {$g5['board_table']} SET bo_admin = '' WHERE bo_admin = '$mb_id'");

		// 아이콘 삭제
		@unlink(G5_DATA_PATH.'/member/'.substr($mb_id, 0, 2).'/'.$mb_id.'.gif');

		// 주민번호 삭제 --------------------------------------------------------------
		$link2 = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2);

		$SDATA = sql_fetch("SELECT * FROM member_private WHERE mb_no='".$mb['mb_no']."'", "", $link2);
		if($SDATA['idx']) {
			sql_query("INSERT INTO member_private_drop (idx, mb_no, regist_number, rdate) VALUES ('".$SDATA['idx']."', '".$SDATA['mb_no']."', '".$SDATA['regist_number']."', CURDATE());", "", $link2);
			sql_query("DELETE FROM member_private WHERE mb_no='".$SDATA['mb_no']."'", "", $link2);
		}
		sql_close($link2);
		// 주민번호 삭제 --------------------------------------------------------------

	}
}


// 이메일 주소 추출
function get_email_address($email)
{
	preg_match("/[0-9a-z._-]+@[a-z0-9._-]{4,}/i", $email, $matches);
	return $matches[0];
}

// 파일명에서 특수문자 제거
function get_safe_filename($name)
{
	$pattern = '/["\'<>=#&!%\\\\(\)\*\+\?]/';
	$name = preg_replace($pattern, '', $name);
	return $name;
}

// 파일명 치환
function replace_filename($name)
{
	@session_start();
	$ss_id = session_id();
	$usec = get_microtime();
	$ext = array_pop(explode('.', $name));
	return sha1($ss_id.$_SERVER['REMOTE_ADDR'].$usec).'.'.$ext;
}

// 아이코드 사용자정보
function get_icode_userinfo($id, $pass)
{
	$res = get_sock('http://www.icodekorea.com/res/userinfo.php?userid='.$id.'&userpw='.$pass);
	$res = explode(';', $res);
	$userinfo = array(
		'code'      => $res[0], // 결과코드
		'coin'      => $res[1], // 고객 잔액 (충전제만 해당)
		'gpay'      => $res[2], // 고객의 건수 별 차감액 표시 (충전제만 해당)
		'payment'   => $res[3]  // 요금제 표시, A:충전제, C:정액제
	);
	return $userinfo;
}

// 인기검색어 입력
function insert_popular($field, $str)
{
    global $g5;

    if(!in_array('mb_id', $field)) {
        $sql = " insert into {$g5['popular_table']} set pp_word = '{$str}', pp_date = '".G5_TIME_YMD."', pp_ip = '{$_SERVER['REMOTE_ADDR']}' ";
        sql_query($sql, FALSE);
    }
}

// 문자열 암호화(구)
function get_encrypt_string($str)
{
	$encrypt = (defined('G5_STRING_ENCRYPT_FUNCTION') && G5_STRING_ENCRYPT_FUNCTION) ? call_user_func(G5_STRING_ENCRYPT_FUNCTION, $str) : sql_password($str);
	return $encrypt;
}

// 문자열 암호화 (SHA256 salt 방식)
function get_encrypt_string2($str)
{
	$strsalt = $str;
	for($i=0; $i<strlen($str); $i++) {
		$strsalt.= "$i";
	}

	$encrypt = "*" . strtoupper(hash('sha256', $strsalt));
	return $encrypt;
}

// 비밀번호 비교(구)
function check_password($pass, $hash)
{
	$password = get_encrypt_string($pass);
	return ($password === $hash);
}

// 비밀번호 비교(신)
function check_password2($pass, $hash)
{
	$password = get_encrypt_string2($pass);
	return ($password === $hash);
}


// 동일한 host url 인지
function check_url_host($url, $msg='', $return_url=G5_URL)
{
    if(!$msg)
        $msg = 'url에 타 도메인을 지정할 수 없습니다.';

    $p = @parse_url($url);
    $host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);

    //20170508 오픈 리다이렉트 취약점(16-603) 수정 (v 5.2.6)
    if(stripos($url, 'http:') !== false) {
        if(!isset($p['scheme']) || !$p['scheme'] || !isset($p['host']) || !$p['host'])
            alert('url 정보가 올바르지 않습니다.', $return_url);
    }

    if((isset($p['scheme']) && $p['scheme']) || (isset($p['host']) && $p['host'])) {
        //if($p['host'].(isset($p['port']) ? ':'.$p['port'] : '') != $_SERVER['HTTP_HOST']) {
        if($p['host'] != $host) {
            echo '<script>'.PHP_EOL;
            echo 'alert("url에 타 도메인을 지정할 수 없습니다.");'.PHP_EOL;
            echo 'document.location.href = "'.$return_url.'";'.PHP_EOL;
            echo '</script>'.PHP_EOL;
            echo '<noscript>'.PHP_EOL;
            echo '<p>'.$msg.'</p>'.PHP_EOL;
            echo '<p><a href="'.$return_url.'">돌아가기</a></p>'.PHP_EOL;
            echo '</noscript>'.PHP_EOL;
            exit;
        }
    }
}

// QUERY STRING 에 포함된 XSS 태그 제거
function clean_query_string($query, $amp=true)
{
    $qstr = trim($query);

    parse_str($qstr, $out);

    if(is_array($out)) {
        $q = array();

        foreach($out as $key=>$val) {
            $key = strip_tags(trim($key));
            $val = trim($val);

            switch($key) {
                case 'wr_id':
                    $val = (int)preg_replace('/[^0-9]/', '', $val);
                    $q[$key] = $val;
                    break;
                case 'sca':
                    $val = clean_xss_tags($val);
                    $q[$key] = $val;
                    break;
                case 'sfl':
                    $val = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\s]/", "", $val);
                    $q[$key] = $val;
                    break;
                case 'stx':
                    $val = get_search_string($val);
                    $q[$key] = $val;
                    break;
                case 'sst':
                    $val = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\s]/", "", $val);
                    $q[$key] = $val;
                    break;
                case 'sod':
                    $val = preg_match("/^(asc|desc)$/i", $val) ? $val : '';
                    $q[$key] = $val;
                    break;
                case 'sop':
                    $val = preg_match("/^(or|and)$/i", $val) ? $val : '';
                    $q[$key] = $val;
                    break;
                case 'spt':
                    $val = (int)preg_replace('/[^0-9]/', '', $val);
                    $q[$key] = $val;
                    break;
                case 'page':
                    $val = (int)preg_replace('/[^0-9]/', '', $val);
                    $q[$key] = $val;
                    break;
                case 'w':
                    $val = substr($val, 0, 2);
                    $q[$key] = $val;
                    break;
                case 'bo_table':
                    $val = preg_replace('/[^a-z0-9_]/i', '', $val);
                    $val = substr($val, 0, 20);
                    $q[$key] = $val;
                    break;
                case 'gr_id':
                    $val = preg_replace('/[^a-z0-9_]/i', '', $val);
                    $q[$key] = $val;
                    break;
                default:
                    $val = clean_xss_tags($val);
                    $q[$key] = $val;
                    break;
            }
        }

        if($amp)
            $sep = '&amp;';
        else
            $sep ='&';

        $str = http_build_query($q, '', $sep);
    } else {
        $str = clean_xss_tags($qstr);
    }

    return $str;
}

function get_device_change_url()
{
    $p = @parse_url(G5_URL);
    $href = $p['scheme'].'://'.$p['host'];
    if(isset($p['port']) && $p['port'])
        $href .= ':'.$p['port'];
    $href .= $_SERVER['SCRIPT_NAME'];

    $q = array();
    $device = 'device='.(G5_IS_MOBILE ? 'pc' : 'mobile');

    if($_SERVER['QUERY_STRING']) {
        foreach($_GET as $key=>$val) {
            if($key == 'device')
                continue;

            $key = strip_tags($key);
            $val = strip_tags($val);

            if($key && $val)
                $q[$key] = $val;
        }
    }

    if(!empty($q)) {
        $query = http_build_query($q, '', '&amp;');
        $href .= '?'.$query.'&amp;'.$device;
    } else {
        $href .= '?'.$device;
    }

    return $href;
}

// 스킨 path
function get_skin_path($dir, $skin)
{
    global $config;

    if(preg_match('#^theme/(.+)$#', $skin, $match)) { // 테마에 포함된 스킨이라면
        $theme_path = '';
        $cf_theme = trim($config['cf_theme']);

        $theme_path = G5_PATH.'/'.G5_THEME_DIR.'/'.$cf_theme;
        if(G5_IS_MOBILE) {
            $skin_path = $theme_path.'/'.G5_MOBILE_DIR.'/'.G5_SKIN_DIR.'/'.$dir.'/'.$match[1];
            if(!is_dir($skin_path))
                $skin_path = $theme_path.'/'.G5_SKIN_DIR.'/'.$dir.'/'.$match[1];
        } else {
            $skin_path = $theme_path.'/'.G5_SKIN_DIR.'/'.$dir.'/'.$match[1];
        }
    } else {
        if(G5_IS_MOBILE)
            $skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/'.$dir.'/'.$skin;
        else
            $skin_path = G5_SKIN_PATH.'/'.$dir.'/'.$skin;
    }

    return $skin_path;
}

// 스킨 url
function get_skin_url($dir, $skin)
{
    $skin_path = get_skin_path($dir, $skin);

    return str_replace(G5_PATH, G5_URL, $skin_path);
}

// 발신번호 유효성 체크
function check_vaild_callback($callback){
    $_callback = preg_replace('/[^0-9]/','', $callback);

    /**
     * 1588 로시작하면 총8자리인데 7자리라 차단
     * 02 로시작하면 총9자리 또는 10자리인데 11자리라차단
     * 1366은 그자체가 원번호이기에 다른게 붙으면 차단
     * 030으로 시작하면 총10자리 또는 11자리인데 9자리라차단
     */

    if( substr($_callback,0,4) == '1588') if( strlen($_callback) != 8) return false;
    if( substr($_callback,0,2) == '02')   if( strlen($_callback) != 9  && strlen($_callback) != 10 ) return false;
    if( substr($_callback,0,3) == '030')  if( strlen($_callback) != 10 && strlen($_callback) != 11 ) return false;

    if( !preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080|007)\-?\d{3,4}\-?\d{4,5}$/",$_callback) &&
        !preg_match("/^(15|16|18)\d{2}\-?\d{4,5}$/",$_callback) ){
        return false;
    } else if( preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080)\-?0{3,4}\-?\d{4}$/",$_callback )) {
        return false;
    } else {
        return true;
    }
}

// 문자열 암복호화
class str_encrypt
{
    var $salt;
    var $lenght;

    function __construct($salt='')
    {
        if(!$salt)
            $this->salt = md5(G5_MYSQL_PASSWORD);
        else
            $this->salt = $salt;

        $this->length = strlen($this->salt);
    }

    function encrypt($str)
    {
        $length = strlen($str);
        $result = '';

        for($i=0; $i<$length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) + ord($keychar));
            $result .= $char;
        }

        return base64_encode($result);
    }

    function decrypt($str) {
        $result = '';
        $str    = base64_decode($str);
        $length = strlen($str);

        for($i=0; $i<$length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }
}

function price_cutting($value, $text_only = false) {

	$return_str = '';

	if($value=='') {
		return '0';
	}

	if( is_numeric($value) ) {

		$unit_price    = 10000;		// 변환 최소단위
		$million_value = 0;
		$reail_value   = 0;

		if($value >= $unit_price) {
			$value = floor(floor($value) / $unit_price) * $unit_price;

			if($value >= 100000000) {
				$million_value = floor($value / 100000000);
				$return_str = number_format($million_value).'억';
				$value = $value - ($million_value * 100000000);
				$reail_value = ($million_value * 100000000);
			}

			if($value > 0) {
				$value = floor($value / $unit_price);
				$reail_value = $reail_value+ ($value * $unit_price);
				$return_str =  $return_str . number_format($value) .'만';
			}
			else {
				if($return_str == '') $return_str = '0';
			}
		}
		else {
			$return_str = $value;
		}

	}

	return $return_str;

}


function number2korean($num) {
  $return_val = "";
  if(!is_numeric($num)) {
		echo "<script>alert('유효한 숫자가 아닙니다')</script>";
		return 0;
  }

  $arr_number = strrev($num);

  for($i=strlen($arr_number)-1; $i>=0; $i--) {
		/////////////////////////////////////////////////
		// 현재 자리를 구함
		$digit = substr($arr_number, $i, 1);
		///////////////////////////////////////////////////////////
		// 각 자리 명칭
		switch($digit) {
			case '-' : $return_val.= "(-) "; break;
			case '0' : $return_val.= "";     break;
			case '1' : $return_val.= "일";   break;
			case '2' : $return_val.= "이";   break;
			case '3' : $return_val.= "삼";   break;
			case '4' : $return_val.= "사";   break;
			case '5' : $return_val.= "오";   break;
			case '6' : $return_val.= "육";   break;
			case '7' : $return_val.= "칠";   break;
			case '8' : $return_val.= "팔";   break;
			case '9' : $return_val.= "구";   break;
		}
		//echo $return_val."<br>";

		if($digit=="-") continue;

		///////////////////////////////////////////////////////////
		// 4자리 표기법 공통부분
		if($digit != 0) {
			if($i % 4 == 1)      $return_val.= "십";
			else if($i % 4 == 2) $return_val.= "백";
			else if($i % 4 == 3) $return_val.= "천";
		}
		//echo $return_val."<br>";

		///////////////////////////////////////////////////////////
		// 4자리 한자 표기법 단위
		if($i % 4 == 0) {
			if(floor($i / 4)==12)      $return_val.= "극";
			else if(floor($i / 4)==11) $return_val.= "재";
			else if(floor($i / 4)==10) $return_val.= "정";
			else if(floor($i / 4)==9)  $return_val.= "간";
			else if(floor($i / 4)==8)  $return_val.= "구";
			else if(floor($i / 4)==7)  $return_val.= "양";
			else if(floor($i / 4)==6)  $return_val.= "자";
			else if(floor($i / 4)==5)  $return_val.= "해";
			else if(floor($i / 4)==4)  $return_val.= "경";
			else if(floor($i / 4)==3)  $return_val.= "조";
			else if(floor($i / 4)==2)  $return_val.= "억";
			//else if(floor($i / 4)==1)  $return_val.= "만";
			else if(floor($i / 4)==1)  $return_val.= substr($num,-8,4)*1>0?"만":"";  // 2억만원으로 표기되는 문제로 인해서 전승찬 수정 2021-01-07
			else if(floor($i / 4)==0)  $return_val.= "";
		}
		//echo $return_val."<br>";
	}

/*
	$return_val = preg_replace("/극만/", "극", $return_val);
	$return_val = preg_replace("/재만/", "재", $return_val);
	$return_val = preg_replace("/정만/", "정", $return_val);
	$return_val = preg_replace("/간만/", "간", $return_val);
	$return_val = preg_replace("/구만/", "구", $return_val);
	$return_val = preg_replace("/양만/", "양", $return_val);
	$return_val = preg_replace("/자만/", "자", $return_val);
	$return_val = preg_replace("/해만/", "해", $return_val);
	$return_val = preg_replace("/경만/", "경", $return_val);
	$return_val = preg_replace("/조만/", "조", $return_val);
	$return_val = preg_replace("/억만/", "억", $return_val);
*/

	return $return_val;

}


// 상품 상태값 출력 (파라미터값 기준)
function get_product_state($recruit_period_start, $recruit_period_end, $product_open_date, $product_invest_sdate, $product_invest_edate, $state, $recruit_amount, $total_invest_amount, $invest_end_date){

	if($state) {

		switch($state) {
			case '1' : $product_state = '이자상환중';				break;
			case '2' : $product_state = '투자상환완료';			break;	//상품마감
			case '3' : $product_state = '투자금모집실패';		break;
			case '4' : $product_state = '부실';							break;
			case '5' : $product_state = '중도상환완료';			break;
			case '6' : $product_state = '대출계약취소';			break;	//대출취소(기표전)
			case '8' : $product_state = '지연/연체';				break;
			case '9' : $product_state = '매각';							break;
		}

	}
	else {

		$date     = date('Ymd');
		$datetime = $date.date('His');

		if(str_replace("-", "", $recruit_period_start) <= $date && str_replace("-", "", $recruit_period_end) >= $date) {			//모집기간중

			if($product_open_date > $datetime) {
				$product_state = "투자대기중";
			}
			else {

				if($product_invest_sdate < $datetime && $product_invest_edate > $datetime) {
					$product_state = '투자모집중';
				}

				if($recruit_amount <= $total_invest_amount) {
					$product_state = '투자마감';
				}

				if($product_invest_edate < $datetime) {
					$product_state = '투자마감';
				}
				else if($invest_end_date && $state=='') {
					$product_state = '투자마감';
				}

			}
		}
		else {	 //모집기간 종료후

			if($recruit_amount <= $total_invest_amount) {
				$product_state = '투자마감';
			}

			if($product_invest_edate < $datetime) {
				$product_state = '투자마감';
			}
			else if($invest_end_date && $state == '') {
				$product_state = '투자마감';
			}

		}

	}

	return $product_state;

}


function getProductStat($prd_idx) {

	###################################
	## 리턴 상태코드(code) 예시 :
	## A01 : 이자상환중
	## A02 : 투자상환완료 (상품마감)
	## A03 : 투자모집실패
	## A04 : 부실(매각협의중)
	## A05 : 중도일시상환
	## A06 : 대출취소(기표전)
	## A07 : 대출취소(기표후)
	## A08,A08S : 상환지연(연체시작일로부터 30일까지: 상환지연중 / 30일 이후: 연체)
	## A09 : 상환불가(90일 이상: 부실)
	## B00 : 상품준비중
	## B01 : 투자대기중
	## B02 : 투자모집중
	## B03 : 투자모집완료
	## B04 : 투자모집실패
	###################################

	if(!$prd_idx) return;

	$sql = "
		SELECT
			A.state, A.title, A.recruit_amount,
			A.recruit_period_start, A.recruit_period_end, A.open_datetime, A.start_datetime, A.end_datetime, A.invest_end_date,
			A.advance_invest, A.advance_invest_ratio
		FROM
			cf_product A
		WHERE
			idx='".$prd_idx."'";
	if( $PRDT = sql_fetch($sql) ) {

		// 연체
		if( in_array($PRDT['state'], array('8','9')) ) {

			// 연체일수 계산
			$sql = "
				SELECT
					overdue_start_date, overdue_end_date
				FROM
					cf_product_success
				WHERE
					product_idx='".$prd_idx."' AND overdue_start_date IS NOT NULL AND overdue_end_date IS NULL
				ORDER BY
					idx DESC LIMIT 1";
			$OVD = sql_fetch($sql);
			if($OVD['overdue_start_date'] && $OVD['overdue_end_date']=='') {
				$SDATE_OBJ = new DateTime($OVD['overdue_start_date']);
				$EDATE_OBJ = new DateTime(date('Y-m-d'));
				$TOTAL_DATE_OBJ = date_diff($SDATE_OBJ, $EDATE_OBJ);
				$ovdDayCnt = $TOTAL_DATE_OBJ->days;
			}
		}

		$RESULT = array(
			'code' => '',
			'code_str' => '',
			'advence_invest_ing' => '',
			'state' => '',
			'title' => ''
		);

		$nowdate = date('Y-m-d H:i:s');

		if($PRDT['state']) {
			if($PRDT['state']=='1')      {
				$RESULT['code']     = 'A01';
				$RESULT['code_str'] = "이자상환중";
			}
			else if($PRDT['state']=='2') {
				$RESULT['code']     = 'A02';
				$RESULT['code_str'] = '상환완료';
			}
			else if($PRDT['state']=='3') {
				$RESULT['code']     = 'A03';
				$RESULT['code_str'] = '모집실패';
			}
			else if($PRDT['state']=='4') {
				$RESULT['code']     = 'A04';
				$RESULT['code_str'] = '매각처리';
			}
			else if($PRDT['state']=='5') {
				$RESULT['code']     = 'A05';
				$RESULT['code_str'] = '중도상환완료';
			}
			else if($PRDT['state']=='6') {
				$RESULT['code']     = 'A06';
				$RESULT['code_str'] = '투자금반환완료';
			}
			else if($PRDT['state']=='7') {
				$RESULT['code']     = 'A07';
				$RESULT['code_str'] = '대출취소';
			}
			else if($PRDT['state']=='8') {
				if($ovdDayCnt <= 30) {
					$RESULT['code']     = 'A08';
					$RESULT['code_str'] = '상환지연중';
				}
				else {
					$RESULT['code']     = 'A08S';
					$RESULT['code_str'] = '연체중';
				}
			}
			else if($PRDT['state']=='9') {
				$RESULT['code']     = 'A09';
				$RESULT['code_str'] = '상환불가';
			}
		}
		else {

			$INVEST = sql_fetch("SELECT IFNULL(SUM(amount),0) AS total_amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y'");		// 투자금 합계

			/////////////////////////////////////////////////////
			// 투자종료플래그(invest_end_date) 가 없을 경우
			// 투자기간, 투자만료기록일 시점의 상태를 반환한다.
			/////////////////////////////////////////////////////
			if($PRDT['invest_end_date']=='') {

				/////////////////
				// 모집기간 전
				/////////////////
				if($PRDT['start_datetime'] > $nowdate) {

					$RESULT['code']     = 'B01';
					$RESULT['code_str'] = '투자대기중';

					// 사전투자상품 -> 사전투자금이 다 모이지 않았을 경우
					if($PRDT['advance_invest']=='Y' && $PRDT['star_datetime'] <= $nowdate) {

						$advance_invest_amount = $PRDT['recruit_amount'] * $PRDT['advance_invest_ratio'] / 100;

						if($advance_invest_amount > $INVEST['total_amount']) {
							$RESULT['advence_invest_ing'] = 'Y';
						}

					}

				}

				/////////////////
				// 모집기간 중
				/////////////////
				else if($PRDT['start_datetime'] <= $nowdate && $PRDT['end_datetime'] >= $nowdate) {

					if($PRDT['recruit_amount'] > $INVEST['total_amount']) {
						$RESULT['code']     = 'B02';
						$RESULT['code_str'] = '투자하기';
					//$RESULT['code_str'] = '투자모집중';
					}
					else {
						$RESULT['code']     = 'B03';
						$RESULT['code_str'] = '투자모집완료';
					}

				}

				/////////////////
				// 모집기간 후
				/////////////////
				else if($PRDT['end_datetime'] < $nowdate) {

					if($PRDT['recruit_amount'] <= $INVEST['total_amount']) {
						$RESULT['code']     = 'B03';
						$RESULT['code_str'] = '투자모집완료';
					}
					else {
						$RESULT['code']     = 'B04';
						$RESULT['code_str'] = '투자모집실패';
					}

				}

				/////////////////////////////////////////
				// 그 외, 모집기간이 설정되지 않은 경우
				/////////////////////////////////////////
				else {

					$RESULT['code']     = 'B00';
					$RESULT['code_str'] = '상품준비중';

				}

			}

			/////////////////////////////////////////////////////
			// 투자종료플래그(invest_end_date) 가 있을 경우
			/////////////////////////////////////////////////////
			else {

				$INVEST = sql_fetch("SELECT IFNULL(SUM(amount),0) AS total_amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y'");		// 투자금 합계 다시 호출

				if($PRDT['recruit_amount'] <= $INVEST['total_amount']) {
					$RESULT['code']     = 'B03';
					$RESULT['code_str'] = '투자모집완료';
				}
				else {
					$RESULT['code']     = 'B04';
					$RESULT['code_str'] = '투자모집실패';
				}

			}

		}

		$RESULT['state']          = $PRDT['state'];
		$RESULT['title']          = $PRDT['title'];
		//$RESULT['recruit_amount'] = $PRDT['recruit_amount'];
		//$RESULT['invest_count']   = $PRDT['invest_count'];
		//$RESULT['invest_amount']  = $PRDT['invest_amount'];

		return $RESULT;

	}
	else {
		return 0;
	}

}


// 상품번호에 따른 정산내역서 테이블찾기 - 테이블이 없으면 생성 : 2019-09-02 추가
function getBillTable($prd_idx, $conn='') {

	global $g5;
	if(!$conn) $conn = $g5['connect_db'];


	$range = "";
	if($prd_idx) {
		$range = floor($prd_idx/1000) * 1000;
		$range = sprintf('%05d', $range);		// 테이블생성명은 1000 단위 (zerofill 5)
		$table = "cf_product_bill_" . $range;
	}

	$tres = sql_query("SHOW TABLES LIKE '".$table."'", FALSE, $conn);
	if( sql_num_rows($tres) ) {
		$return_value = $table;
	}
	else {

		$product_count = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product WHERE idx='".$prd_idx."' AND state='1'")['cnt'];		// 이자상환중인 상품여부 체크

		if($product_count) {
			$tresx = sql_query("
				CREATE TABLE `$table` (
					`idx` BIGINT(13) UNSIGNED NOT NULL AUTO_INCREMENT,
					`product_idx` INT(11) UNSIGNED NULL DEFAULT NULL,
					`member_idx` INT(11) UNSIGNED NULL DEFAULT NULL,
					`invest_idx` INT(11) UNSIGNED NULL DEFAULT NULL,
					`bill_date` DATE NULL DEFAULT NULL COMMENT '정산대상일',
					`repay_date` DATE NULL DEFAULT NULL COMMENT '지급예정일',
					`dno` INT(4) UNSIGNED NULL DEFAULT NULL COMMENT '일자순번',
					`turn` INT(2) UNSIGNED NULL DEFAULT NULL COMMENT '귀속회차',
					`turn_sno` INT(2) UNSIGNED NULL DEFAULT '0' COMMENT '귀속회차내의 지불회차번호',
					`is_overdue` ENUM('Y','N') NULL DEFAULT 'N' COMMENT '연체구분',
					`invest_importance` DECIMAL(13,10) NULL DEFAULT 0 COMMENT '전체투자금대비투자비중',
					`invest_amount` BIGINT(20) NULL DEFAULT '0' COMMENT '투자금액',
					`partial_principal` BIGINT(20) NULL DEFAULT '0' COMMENT '부분상환액',
					`remain_principal` BIGINT(20) NULL DEFAULT '0' COMMENT '잔여원금(투자원금-부분상환액)',
					`day_interest` DECIMAL(18,8) UNSIGNED NULL DEFAULT 0 COMMENT '세전이자',
					`fee` DECIMAL(18,8) UNSIGNED NULL DEFAULT 0 COMMENT '플랫폼이용료',
					`rtimestamp` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '등록타임스템프',
					PRIMARY KEY (`idx`),
					INDEX `product_idx` (`product_idx`),
					INDEX `member_idx` (`member_idx`),
					INDEX `invest_idx` (`invest_idx`),
					INDEX `repay_date` (`repay_date`),
					INDEX `turn` (`turn`),
					INDEX `turn_sno` (`turn_sno`),
					INDEX `is_overdue` (`is_overdue`)
				)
				COMMENT='투자수익명세서(일별상세내역)'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
				ROW_FORMAT=DYNAMIC", FALSE, $conn);
		}

		if($tresx) {
			$return_value =  $table;
		}
		else {
			$return_value =  'NONEXISTENT';
		}
	}

	return $return_value;

}


// 시작일 부터 종료일 까지 일수 출력
function repayDayCount($sdate, $edate) {

	$sdate = trim($sdate);
	$edate = trim($edate);

	if($sdate=='' || $edate=='') {
		return 0;
	}
	else {
		if($sdate > $edate) {
			return 0;
		}
		else {

			$SDATE_OBJ = new DateTime($sdate);
			$EDATE_OBJ = new DateTime($edate);
			$TOTAL_DATE_OBJ = date_diff($SDATE_OBJ, $EDATE_OBJ);

			$days = $TOTAL_DATE_OBJ->days;

			return $days;

		}
	}

}

// 대출시작,종료일을 넣었을때 회차 출력하기 getRepayTurn(대출시작일, 대출종료일, 최종회차와 원금상환회차가 합쳐진 상품구분, 1개월미만상품여부)
function repayTurnCount($sdate, $edate, $exception_product=false, $shortTermProduct=false, $calcType='') {
	$sdate = trim($sdate);
	$edate = trim($edate);

	if($sdate=='' || $edate=='') {
		return 0;
	}
	else {
		if($sdate > $edate) {
			return 0;
		}
		else {

			$total_days = repayDayCount($sdate, $edate);

			$repay_turn = 0;
			if( !$shortTermProduct ) {

				for($i=0,$j=1; $i<$total_days; $i++,$j++) {

					if($calcType=='2') {
						$DATE[$i] = date("Y-m-d", strtotime($sdate . " $j days"));		// 초일불산입.말일산입
					}
					else {
						$DATE[$i] = date("Y-m-d", strtotime($sdate . " $i days"));		// 초일산입.말일불산입
					}

					// 대상일의 월이 바뀌면 회차증가
					if($i > 0) {
						if( substr($DATE[$i],0,7) > substr($DATE[$i-1],0,7) ) {
							$repay_turn += 1;
						}
					}

					if($exception_product) {
						// 예외처리 상품인 경우 최종원리금상환회차는 원금상환회차와 합쳐져 있다.
					}
					else {
						if($j==$total_days) {
							$repay_turn = $repay_turn + 1;
						}
					}

					//echo substr($DATE[$i-1],0,7) . " ~ " . substr($DATE[$i],0,7) . " : " . $repay_turn . "<br>\n";

				}

			}
			else {
				// 1개월미만 상품은 1회차로만...
				$repay_turn = 1;
			}

			return $repay_turn;

		}
	}
}
// echo repayTurnCount("2020-02-04", "2020-06-04");


///////////////////////////////////////////////////////////////////////////////
// 정산내역 추출
///////////////////////////////////////////////////////////////////////////////
// 상품정보(PRDT), 이자및상환스케쥴(REPAY) 및 상환총액정보(REPAYSUM) 추출
// DB 등록데이터 기준. 배열로 출력
// 투자 시뮬레이션 및 투자현황용
///////////////////////////////////////////////////////////////////////////////
function investStatement($product_idx, $principal, $loan_start_date='', $loan_end_date='', $invest_idx='') {

	// 투자자플랫폼이용료[(투자금액 * 0.08%) * 차수 (A:월별징수, B:상환시징수)] 타입에 따른 플랫폼 요율 적용시점 작업예정
	// 중도상환시 월수계산, 일수계산 처리기능 부족

	global $CONF, $BANK, $VBANK, $member;

	$prdt_query = "
		SELECT
			idx, state, category, title,
			invest_return, withhold_tax_rate, loan_interest_rate, overdue_rate, withhold_tax_rate, loan_usefee, invest_usefee, invest_usefee_type,
			invest_period, invest_days, recruit_period_start, recruit_period_end, recruit_amount,
			repay_type, advanced_payment, loan_start_date, loan_end_date, loan_end_date_orig
		FROM
			cf_product
		WHERE
			idx = '".$product_idx."' ";
	$PRDT = sql_fetch($prdt_query);


	$INI['principal'] = $principal;  // 투자원금
	$INI['static_repay_day'] = 5;   // 약정정산일

	if($PRDT['loan_start_date'] > '0000-00-00') {
		$INI['loan_start_date'] = $PRDT['loan_start_date'];
	}
	else {
		$INI['loan_start_date'] = ($loan_start_date) ? $loan_start_date : date('Y-m-d');
	}

	$INI['loan_start_date_day'] = (int)substr($INI['loan_start_date'], 8, 2);								// 대출실행일의 일자

	$PRDT['invest_return']   = ($PRDT['invest_return']) ? $PRDT['invest_return'] : 0;				// 투자수익율
	$PRDT['invest_usefee']   = ($PRDT['invest_usefee']) ? $PRDT['invest_usefee'] : 0;				// 투자자 플랫폼 이용요율

	$shortTermProduct = ($PRDT['invest_period']=='1' && $PRDT['invest_days']>'0') ? true : false;

	$SDATE_OBJ = new DateTime($INI['loan_start_date']);
	if($PRDT['loan_end_date'] > '0000-00-00') {
		// 종료일이 확정된 상품일 경우
		$EDATE_OBJ = new DateTime($PRDT['loan_end_date']);
	}
	else {
		// 종료일 미정인 상품일 경우
		if($shortTermProduct) {
			$PRDT['loan_end_date'] =  date('Y-m-d', strtotime($INI['loan_start_date']. '+'.$PRDT['invest_days'].' day'));
			$EDATE_OBJ = new DateTime($PRDT['loan_end_date']);
		}
		else {
			$PRDT['loan_end_date'] = date('Y-m-d', strtotime($INI['loan_start_date'].' +'.$PRDT['invest_period'].' month'));
			$EDATE_OBJ = new DateTime($PRDT['loan_end_date']);
		}
	}

	$TOTAL_DATE_OBJ = date_diff($SDATE_OBJ, $EDATE_OBJ);

	$INI['total_day_count']     = $TOTAL_DATE_OBJ->days;
	$INI['loan_end_date']				= $EDATE_OBJ->format('Y-m-d');

	$INI['repay_count'] = repayTurnCount($INI['loan_start_date'], $INI['loan_end_date'], false, $shortTermProduct);

	$INI['day_invest_interest'] = ($INI['principal'] * ($PRDT['invest_return']/100)) / 365;		// 일별 이자수익금
	$INI['day_invest_usefee']   = ($INI['principal'] * ($PRDT['invest_usefee']/100)) / 365;		// 일별 플랫폼이용료

	$INI['day_invest_interest_leapYear'] = ($INI['principal'] * ($PRDT['invest_return']/100)) / 366;		// (윤년)일별 이자수익금
	$INI['day_invest_usefee_leapYear']   = ($INI['principal'] * ($PRDT['invest_usefee']/100)) / 366;		// (윤년)플랫폼이용료

	$withhold_tax_rate = ($member['is_creditor'] == 'Y') ? 0 : sprintf("%0.3f", $PRDT['withhold_tax_rate']/100);

	//print_rr($INI, 'font-size:12px');

	if($shortTermProduct) {

		$REPAY[0]['repay_day']    = $INI['loan_end_date'];
		$REPAY[0]['target_sdate'] = $INI['loan_start_date'];
		$REPAY[0]['target_edate'] = $INI['loan_end_date'];
		$REPAY[0]['day_count']	  = $INI['total_day_count'];
		$REPAY[0]['principal']    = $INI['principal'];

	}
	else {

		$x = 0;
		for($i=0,$j=1; $i<$INI['repay_count']; $i++,$j++) {

			$REPAY[$x]['repay_num'] = $x+1;

			$EDATE_OBJ = new DateTime(date('Y-m-d', strtotime($SDATE_OBJ->format('Y-m').' last day next month')));	// 매 정산월의 마지막 일자
			$DIFF_OBJ  = date_diff($SDATE_OBJ, $EDATE_OBJ);

			if($EDATE_OBJ->format('Y-m-d') < $INI['loan_end_date']) {
				$repay_day = $SDATE_OBJ->format('Y-m').'-'.sprintf('%02d', $INI['static_repay_day']);
				$repay_day = date('Y-m-d', strtotime($repay_day.' +1 month'));

				$REPAY[$x]['repay_day']    = $repay_day;											// 정산지급일
				$REPAY[$x]['target_sdate'] = $SDATE_OBJ->format('Y-m-d');			// 정산시작일
				$REPAY[$x]['target_edate'] = $EDATE_OBJ->format('Y-m-d');			// 정산종료일
				$REPAY[$x]['day_count']    = $DIFF_OBJ->days + 1;							// 일자수
				$REPAY[$x]['principal']    = 0;																// 상환원금
				$SDATE_OBJ->modify('first day of next month');

				$x++;

			}
			else {

				//마지막 달 계산
				$LOAN_DATE_OBJ    = new DateTime($INI['loan_end_date']);
				$DIFF_OBJ         = date_diff($SDATE_OBJ, $LOAN_DATE_OBJ);
				$repay_day        = $LOAN_DATE_OBJ->format('Y-m-d');
				$static_repay_day = substr($repay_day, 0, 7)."-".sprintf("%02d", $INI['static_repay_day']);

				$REPAY[$x]['repay_day']    = $repay_day;
				$REPAY[$x]['target_sdate'] = $SDATE_OBJ->format('Y-m-d');
				$REPAY[$x]['target_edate'] = $repay_day;
				$REPAY[$x]['day_count']	   = $DIFF_OBJ->days;
				$REPAY[$x]['principal']    = $INI['principal'];

			}
		}

	}


	/////////////////////////////////////////////////////////////////////////////
	// 정산 차수 루프 시작
	/////////////////////////////////////////////////////////////////////////////
	for($i=0,$turn=1; $i<$INI['repay_count']; $i++,$turn++) {

		$daysOfYear = ( in_array(substr($REPAY[$i]['target_sdate'],0,4), $CONF['LEAP_YEAR']) ) ? 366 : 365;		// ★★★ 일별이자 산출 변수 (윤년구분) ★★★

		////////////////////////////////////////////////
		// 전송된 $invest_idx 가 있으면 지급기록 조회
		////////////////////////////////////////////////
		if($invest_idx) {
			$give_sql = "
				SELECT
					idx, `date`, invest_amount, interest, principal, is_creditor, remit_fee, receive_method,
					bank_name, account_num, bank_private_name, banking_date, mgtKey
				FROM
					cf_product_give
				WHERE 1
					AND invest_idx='".$invest_idx."'
					AND product_idx='".$PRDT['idx']."'
					AND turn='".$turn."'
					AND is_overdue='N'
					AND banking_date IS NOT NULL";
			$GIVE = sql_fetch($give_sql);
		}

		$REPAY[$i]['paied'] = ($GIVE['idx']) ? 'Y' : 'N';
		$REPAY[$i]['remit_fee'] = ($GIVE['remit_fee']=='1') ? $GIVE['remit_fee'] : $member['remit_fee'];

		if($REPAY[$i]['paied']=="Y") {
			$REPAY[$i]['paied_date']        = $GIVE['date'];
			$REPAY[$i]['give_idx']          = $GIVE['idx'];
			$REPAY[$i]['mgtKey']            = $GIVE['mgtKey'];
			$REPAY[$i]['is_creditor']       = $GIVE['is_creditor'];
			$REPAY[$i]['receive_method']	  = $GIVE['receive_method'];
			$REPAY[$i]['bank_name']			    = $GIVE['bank_name'];
			$REPAY[$i]['account_num']       = $GIVE['account_num'];
			$REPAY[$i]['bank_private_name'] = $GIVE['bank_private_name'];
			$REPAY[$i]['banking_date']      = $GIVE['banking_date'];
		}
		else {
			$REPAY[$i]['paied_date']     = '';
			$REPAY[$i]['give_idx']       = '';
			$REPAY[$i]['mgtKey']         = '';
			$REPAY[$i]['is_creditor']    = $member['is_creditor'];
			$REPAY[$i]['receive_method'] = $member['receive_method'];
			if($REPAY[$i]['receive_method']=='1') {
				$REPAY[$i]['bank_name']         = $member['bank_name'];
				$REPAY[$i]['account_num']       = $member['account_num'];
				$REPAY[$i]['bank_private_name'] = $member['bank_private_name'];
			}
			else if($REPAY[$i]['receive_method']=='2') {
				$REPAY[$i]['bank_name']         = $BANK[$member['va_bank_code2']];
				$REPAY[$i]['account_num']       = $member['virtual_account2'];
				$REPAY[$i]['bank_private_name'] = $member['va_private_name2'];
			}
			else {
				$REPAY[$i]['bank_name']         = "";
				$REPAY[$i]['account_num']       = "";
				$REPAY[$i]['bank_private_name'] = "";
			}
			$REPAY[$i]['banking_date'] = "";
		}


		$day_invest_interest = $INI['day_invest_interest'];
		$day_invest_usefee   = $INI['day_invest_usefee'];
		if( in_array(substr($REPAY[$i]['target_sdate'],0,4), $CONF['LEAP_YEAR']) ) {
			$day_invest_interest = $INI['day_invest_interest_leapYear'];
			$day_invest_usefee   = $INI['day_invest_usefee_leapYear'];
		}
		$REPAY[$i]['invest_interest'] = floor($day_invest_interest * $REPAY[$i]['day_count']);		// 투자수익(세전) -> 소수점이하 잘라냄


		/////////////////////////////////
		// 출력용 이자정산지급일 설정
		/////////////////////////////////
		$EXCEPTION_PRODUCT = array(94,95,97,98,109,111,117);
		if( $turn < $INI['repay_count'] ) {
			$REPAY[$i]['repay_schedule_date'] = $REPAY[$i]['repay_day'];
		}
		else {
			$REPAY[$i]['repay_schedule_date'] = $INI['loan_end_date'];
			/*
			if( in_array($product_idx, $EXCEPTION_PRODUCT) ) {
				$REPAY[$i]['repay_schedule_date'] = $INI['loan_end_date'];
			}
			else {
				if($shortTermProduct) {
					$REPAY[$i]['repay_schedule_date'] = $INI['loan_end_date'];
				}
				else {
					$REPAY[$i]['repay_schedule_date'] = date("Y-m-d", strtotime("+5 day", strtotime($INI['loan_end_date'])));		// 최종정산일 +5일 적용시 (예외적용)
				}
			}
			*/
		}


		//--------------------------------------------------------------------------------------------
		// 이자정산지급일에 따른 세율 변환
		// 세율조정 발생시기를 단정할 수 없으므로 조건시을 대입한다.
		// 2021-08-21 온투법 승인일
		// 2021-10-21 헬로핀테크 헬로크라우드대부 합병일
		// 법인은 무조건 27.5%, 개인은 정산일 기준 다르게 적용
		//--------------------------------------------------------------------------------------------
		if($PRDT['loan_start_date'] >= '2021-08-27') {
			$interest_tax_ratio = ($member['member_type']=='2') ? 0.25 : 0.14;
		}
		else {
			if( $REPAY[$i]['repay_schedule_date'] < '2021-10-21' ) {

				$interest_tax_ratio = ($member['member_type']=='2') ? 0.25 : 0.25;

				// 0.14로 정산된것 상품회차 예외처리
				if( $product_idx == '6281' && $turn >= 3) {
					if($member['member_type']=='1') $interest_tax_ratio = 0.14;
				}

				// 0.14로 정산된 상품 예외처리
				if( in_array($product_idx, array('6561','6573','6584','6596','6607')) ) {
					if($member['member_type']=='1') $interest_tax_ratio = 0.14;
				}

			}
			else {
				$interest_tax_ratio = ($member['member_type']=='2') ? 0.25 : 0.14;
			}
		}

		$local_tax_ratio = 0.1;		// 소득세: interest_tax_ratio의 10%
		//--------------------------------------------------------------------------------------------


		////////////////////////////////////////////
		// 일별 플랫폼이용료 설정 (예외설정사항을 최우선으로 적용)
		////////////////////////////////////////////
		$EXTFEE_ROW = sql_fetch("SELECT idx, fee FROM cf_platform_fee WHERE member_idx='".$member['mb_no']."' AND product_idx='".$PRDT['idx']."'");
		if($EXTFEE_ROW['idx']) {
			$day_invest_usefee  = ($INI['principal'] * ($EXTFEE_ROW['fee']/100)) / $daysOfYear;
		}
		else {
			$day_invest_usefee = ($REPAY[$i]['remit_fee']=='1') ? 0 : ($INI['principal'] * ($PRDT['invest_usefee']/100)) / $daysOfYear;		// 플랫폼 수수료 면제 대상자처리 -> 일별 플랫폼 수수료를 0으로 설정
		}

		$REPAY[$i]['invest_usefee'] = floor($day_invest_usefee * $REPAY[$i]['day_count']);	 // 소수점이하 절사  (분할징수용)

		// 만기일시징수방식일때 월별 투자자플랫폼이용료 대입
		if($PRDT['invest_usefee_type']=='B') {
			$sum_invest_usefee += $REPAY[$i]['invest_usefee'];		// 만기일시징수방식일때의 투자자플랫폼이용료 계산
			$REPAY[$i]['invest_usefee'] = ($turn==$INI['repay_count']) ? 0 : $sum_invest_usefee;
		}


		$REPAY[$i]['interest_income_tax'] = floor( ($REPAY[$i]['invest_interest'] * $interest_tax_ratio) / 10 ) * 10;			// 이자소득세 => 이자수익 * 0.25 :::: 원단위 절사
		$REPAY[$i]['local_income_tax']    = floor( (($REPAY[$i]['interest_income_tax'] * $local_tax_ratio) / 10) ) * 10;	// 당월 지방소득세 :::: 원단위 절사


		// 원천징수 제외
		if($REPAY[$i]['is_creditor']=='Y') {
			// 대부업 회원
			$REPAY[$i]['interest_income_tax'] = 0;
			$REPAY[$i]['local_income_tax']    = 0;
		}
		else {
			// 법인+소득세1000원미만인 경우 (소액부징수)
			if($member['member_type']=='2' && $REPAY[$i]['interest_income_tax'] < 1000) {
				$REPAY[$i]['interest_income_tax'] = 0;
				$REPAY[$i]['local_income_tax']    = 0;
			}
		}

		$REPAY[$i]['withhold']   = $REPAY[$i]['interest_income_tax'] + $REPAY[$i]['local_income_tax'];										// 원천징수세액 = 이자소득세 + 지방소득세
		$REPAY[$i]['interest']   = $REPAY[$i]['invest_interest'] - $REPAY[$i]['withhold'] - $REPAY[$i]['invest_usefee'];	// 투자수익(세후) = 이자수익 - 세금 - 플랫폼이용료


		$REPAY[$i]['principal'] = ($turn < $INI['repay_count']) ? 0 : $INI['principal'];			// 상환원금
		// 특수물건 상환원금 예외처리(171번 상품은 연체정산시 원금을 처리하도록 한다.)
		if($product_idx=='171') {
			$REPAY[$i]['principal'] = 0;
		}

		$REPAY[$i]['send_price'] = $REPAY[$i]['interest'] + $REPAY[$i]['principal'];																			// 실입금액(투자수익(세후) + 원금)


		$SUM['principal']          += $REPAY[$i]['principal'];
		$SUM['day_count']          += $REPAY[$i]['day_count'];
		$SUM['invest_interest']    += $REPAY[$i]['invest_interest'];																											// 전체 투자수익(세전)
		$SUM['invest_usefee']      += $REPAY[$i]['invest_usefee'];																												// 전체 플랫폼이용료
		$SUM['interest_income_tax']+= $REPAY[$i]['interest_income_tax'];																									// 전체 이자소득세
		$SUM['local_income_tax']   += $REPAY[$i]['local_income_tax'];																											// 전체 지방소득세
		$SUM['withhold']           += $REPAY[$i]['withhold'];																															// 전체 원천징수액
		$SUM['interest']           += $REPAY[$i]['interest'];																															// 전체 투자수익(세후)
		$SUM['send_price']         += $REPAY[$i]['send_price'];																														// 전체 실입금액


		/////////////////////////
		// 투자 성패기록 추출
		/////////////////////////
		$sql = "
			SELECT
				loan_interest_state, loan_principal_state, invest_give_state, invest_principal_give, overdue_receive, overdue_give,
				overdue_start_date, overdue_end_date
			FROM
				cf_product_success
			WHERE 1=1
				AND product_idx='".$PRDT['idx']."'
				AND turn='".$turn."'";
		$SUCC = sql_fetch($sql);
		$REPAY[$i]['SUCCESS'] = $SUCC;

		if($SUCC['overdue_start_date']>'0000-00-00') {

			$ovd_day_invest_interest = ($INI['principal'] * ($PRDT['overdue_rate']/100)) / $daysOfYear;
			$ovd_edate = ($SUCC['overdue_end_date']=='' || $SUCC['overdue_end_date']=='0000-00-00') ? G5_TIME_YMD : $SUCC['overdue_end_date'];

			$OVD_SDATE_OBJ = new DateTime($SUCC['overdue_start_date']);
			$OVD_EDATE_OBJ = new DateTime($ovd_edate);
			$OVD_TOTAL_DATE_OBJ = date_diff($OVD_SDATE_OBJ, $OVD_EDATE_OBJ);

			///////////////////////////////////
			// 연체이자 지급기록 추출 및 지급계좌 설정
			///////////////////////////////////
			$ovd_give_sql = "
				SELECT
					idx, `date`, invest_amount, interest, principal, is_creditor, remit_fee, receive_method, bank_name, account_num, bank_private_name, banking_date, mgtKey
				FROM
					cf_product_give
				WHERE 1
					AND invest_idx='".$invest_idx."'
					AND product_idx='".$PRDT['idx']."'
					AND turn='".$turn."'
					AND is_overdue='Y'
					AND banking_date IS NOT NULL";
			$OVD_GIVE = sql_fetch($ovd_give_sql);


			$REPAY[$i]['OVERDUE']['repay_num']    = $turn;
			$REPAY[$i]['OVERDUE']['target_sdate'] = $SUCC['overdue_start_date'];
			$REPAY[$i]['OVERDUE']['target_edate'] = $ovd_edate;
			$REPAY[$i]['OVERDUE']['day_count']    = $OVD_TOTAL_DATE_OBJ->days;
			$REPAY[$i]['OVERDUE']['principal']    = $OVD_GIVE['principal'];

			$REPAY[$i]['OVERDUE']['invest_interest'] = floor($ovd_day_invest_interest * $REPAY[$i]['OVERDUE']['day_count']);			// 투자수익(세전) -> 소수점이하 잘라냄
			$REPAY[$i]['OVERDUE']['invest_usefee']   = ($OVD_GIVE['remit_fee']=='1') ? 0 : floor($day_invest_usefee * $REPAY[$i]['OVERDUE']['day_count']);			// 소수점이하 절사


			if($OVD_GIVE['idx']) {
				$REPAY[$i]['OVERDUE']['paied']               = 'Y';
				$REPAY[$i]['OVERDUE']['paied_date']          = $OVD_GIVE['date'];
				$REPAY[$i]['OVERDUE']['give_idx']            = $OVD_GIVE['idx'];
				$REPAY[$i]['OVERDUE']['mgtKey']              = $OVD_GIVE['mgtKey'];
				$REPAY[$i]['OVERDUE']['is_creditor']         = $OVD_GIVE['is_creditor'];
				$REPAY[$i]['OVERDUE']['receive_method']      = $OVD_GIVE['receive_method'];
				$REPAY[$i]['OVERDUE']['bank_name']           = $OVD_GIVE['bank_name'];
				$REPAY[$i]['OVERDUE']['account_num']         = $OVD_GIVE['account_num'];
				$REPAY[$i]['OVERDUE']['bank_private_name']   = $OVD_GIVE['bank_private_name'];
				$REPAY[$i]['OVERDUE']['banking_date']        = $OVD_GIVE['banking_date'];

				if($OVD_GIVE['is_creditor']=="Y") {     // 대부업 회원 일때 원천징수 제로 처리
					$REPAY[$i]['OVERDUE']['interest_income_tax'] = 0;
					$REPAY[$i]['OVERDUE']['local_income_tax']    = 0;
				}
				else {
					$REPAY[$i]['OVERDUE']['interest_income_tax'] = floor( ($REPAY[$i]['OVERDUE']['invest_interest'] * $interest_tax_ratio) / 10 ) * 10;
					$REPAY[$i]['OVERDUE']['local_income_tax']    = floor( (($REPAY[$i]['OVERDUE']['interest_income_tax'] * $local_tax_ratio) / 10) ) * 10;
				}

				$REPAY[$i]['OVERDUE']['withhold']    = $REPAY[$i]['OVERDUE']['interest_income_tax'] + $REPAY[$i]['OVERDUE']['local_income_tax'];
				$REPAY[$i]['OVERDUE']['interest']    = $REPAY[$i]['OVERDUE']['invest_interest'] - $REPAY[$i]['OVERDUE']['withhold'] - $REPAY[$i]['OVERDUE']['invest_usefee'];

				if($product_idx=='171') {
					$REPAY[$i]['OVERDUE']['principal'] = $INI['principal'];
				}

				$REPAY[$i]['OVERDUE']['send_price']  = $REPAY[$i]['OVERDUE']['interest'] + $REPAY[$i]['OVERDUE']['principal'];

				$REPAY[$i]['OVERDUE']['repay_schedule_date'] = $REPAY[$i]['repay_schedule_date'];
			}

		}

	}

	// 최종 입금 회차 계산
	$r = sql_fetch("SELECT MAX(turn) AS max_turn FROM cf_product_success WHERE product_idx='".$PRDT['idx']."'");
	$SUM['last_repay_turn'] = $r['max_turn'];

	$RETURN_ARR = array("PRDT"=>$PRDT, "INI"=>$INI, "REPAY"=>$REPAY, "REPAYSUM"=>$SUM);
	return $RETURN_ARR;

}
// 정산내역 추출 끝 ------------------------------------------------------------------



function paging($total, $page, $size, $ppb=5) {
	if($total == 0) return;

	$total_page = ceil($total / $size);
	$temp = $page % $ppb;

	if($temp == 0) {
		$a = $ppb - 1;
		$b = $temp;
	}
	else {
		$a = $temp - 1;
		$b = $ppb - $temp;
	}

	$start = $page - $a;
	$end = $page + $b;
	//echo "<ul>\n";

	//처음페이지
	if($page > $ppb) {
		echo "	<span class='arrow btn_paging' data-page='1'><img src='/images/bbs/btn_first.gif' alt='맨앞'></span>\n";
	}

	//이전페이지
	if($page > $ppb) {
		$back_page = $start - 1;
		echo "	<span class='arrow btn_paging' data-page='".$back_page."'><img src='/images/bbs/btn_prev.gif' alt='이전'></span>\n";
	}

	//페이지 출력
	for($i = $start; $i <= $end; $i++) {
		if($i > $total_page) break;
		if($page == $i){
			echo "	<span class='now'>".$i."</span>\n";
		}
		else{
			echo "	<span class='btn_paging' data-page='".$i."'>".$i."</span>\n";
		}
	}

	//다음페이지
	if($end < $total_page) {
		$next_page = $end + 1;
		echo "	<span class='arrow btn_paging' data-page='".$next_page."'><img src='/images/bbs/btn_next.gif' alt='이전'></span>\n";
	}

	//마지막 페이지
	if($end < $total_page) {
		echo "	<span class='arrow btn_paging' data-page='".$total_page."'><img src='/images/bbs/btn_last.gif' alt='맨뒤'></span>\n";
	}

	//echo "</ul>\n";

}




// 상품이미지 업로드 용
function UploadFile($FileSaveDir, $MaxUploadMaga, $FormFieldName, $DeleteFile, $RenameFile,$imgYn="") {

	$img_check_array = array('jpg','jpeg','gif','png','bmp');
	$img_detail_check_array = array('image/jpeg', 'image/JPG', 'image/X-PNG', 'image/PNG', 'image/png', 'image/x-png', 'image/gif','image/bmp','image/pjpeg');
	if($imgYn=="Y"){
		$ex = explode(".",$_FILES[$FormFieldName]['name']);
		$mt = $ex[(count($ex)-1)];

		if(!in_array(strtolower($mt), $img_check_array)){
			alert("업로드가 불가한 확장자 입니다.");
			exit;
		}

		if(!in_array($_FILES[$FormFieldName]['type'], $img_detail_check_array)) {
			alert("지정된 이미지만 허용됩니다.");
			exit;
		}
	}

	//echo $FileSaveDir.$MaxUploadMaga.$FormFieldName.$DeleteFile.$RenameFile;
	//echo "<font size=2 color=red>";																						 //에러메시지 빨간색으로 보여주기 위해

	if(!@is_dir($FileSaveDir)) {																								 //디렉토리 존재  확인
		if(!@mkdir($FileSaveDir,0777)){
			echo " >>>>>>>>>>파일을 저장할 디렉토리 만들기에 실패하였습니다.<<<<<<<<<<<<<br>";
		}
	}

	if($_FILES[$FormFieldName]['size'] > 0) {			// 4.1.0 이전의 $_FILES 대신에 $HTTP_POST_FILES를 사용, 업로드 되었을경우
		$MaxFileSize    = 1024 * 1024 * $MaxUploadMaga;																//업로드 최대 사이즈 설정하기(MB)
		$TargetFileName = $_FILES[$FormFieldName]['name'];
		$TargetFileName = preg_replace("/[ #\&\+\-%@=\/\\\:;,\'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i","", trim($TargetFileName));
		$UpFileName     = explode(".", $TargetFileName);
		$UpFileType     =  strtolower($UpFileName[count($UpFileName) - 1]);

		if($RenameFile != "") {
			$TargetFileName = $RenameFile;					 //원하는 이름을 파일이름 재설정.".".$UpFileType
		}
		else {
			$i = 0;
			while(file_exists("$FileSaveDir/$TargetFileName")) {
				$TargetFileName = $UpFileName[0]."[".$i++."].".$UpFileType;									 //동일 파일명 처리 : 기존파일명(숫자).확장자
			}
		}


		if(preg_match("/^(php|html|php3|html|htm|asp|jsp|phtml|cgi|jhtml|exe)$/i",$UpFileType))	{			 //파일 타입제한 형식제한
			echo " >>>>>>>>>>>업로드 할수 없는 파일 형식입니다. 업로드에 실패 하였습니다.<<<<<<<<<<<<<br>";
			//echo "</font>";																								    	//빨간색 폰트 닫음
			return $DeleteFile;																									 //삭제될 파일명 리턴(파일 수정시 이전파일명)
		}
		else {
			if($MaxFileSize < $_FILES[$FormFieldName]['size'])	{
				echo " >>>>>>>>>>파일  업로드 용량을 초과했습니다. 최대 업로드 용량은 ".$MaxUploadMaga."MB입니다.<<<<<<<<<<<<<br>";
				//echo "</font>";																								   	//빨간색 폰트 닫음
				return $DeleteFile;																								 //삭제될 파일명 리턴
			}
			else {
				if(@move_uploaded_file($_FILES[$FormFieldName]['tmp_name'], "$FileSaveDir/$TargetFileName")) {  //업로드된파일 옴기기
				 chmod("$FileSaveDir/$TargetFileName", 0644);

					if($DeleteFile != "" ) {																				 		// 삭제될 파일명이 있을 경우
						if($DeleteFile != $TargetFileName) {															//지울파일과 업로드한 파일이 같지 않을 경우
							if(file_exists("$FileSaveDir/$DeleteFile")) {
								@unlink("$FileSaveDir/$DeleteFile");														//이전 파일 삭제(파일 수정시 이전파일명)
							}
						}
					}
					//echo "</font>";																							   	//빨간색 폰트 닫음
					//echo $TargetFileName;
					return $TargetFileName;																					//업로드된 파일명 반환
				}
				else {
					echo  ">>>>>>>>>>임시 파일을 파일 저장 디렉토리로 옮기는 과정의 에러가 발생하여 업로드에 실패하였습니다.<<<<<<<<<<<<<br>";
					//echo "</font>";																						    	//빨간색 폰트 닫음
					return $DeleteFile;																							//삭제될 파일명 반환(파일 수정시 이전파일명)
				}
			}
		}
	}
	else {																			//php.ini 설정 상태로 인하여 업로드 실패하였을 경우 //(upload_tmp_dir, file_uploads,post_max_size, upload_max_filesize,max_execution_time)
		//echo "</font>";																						    				//빨간색 폰트 닫음
		return $DeleteFile;																										//삭제될 파일명 반환(파일 수정시 이전파일명)
	}
}


/*=============================================================================
  섬네일 이미지
 =============================================================================*/
function thumbnail2($file, $save_filename, $save_dir, $wpixel=160, $hpixel="") {

  list($width, $height, $type, $attr) = @getImageSize($file);

  if($width < $wpixel && $height < $wpixel) {
    copy($file, $save_dir .'/'. $save_filename); return;
  }

  if($width > $wpixel){
    $max_width = $wpixel;
    $max_height = (!$hpixel) ? ($wpixel * $height) / $width : $hpixel;
	}

  switch($type){
    case 1: $src_img = ImageCreateFromGif($file);  break;
    case 2: $src_img = ImageCreateFromJPEG($file); break;
    case 3: $src_img = ImageCreateFromPNG($file);  break;
    default : return 0;
  }

  $img_width  = $width;
  $img_height = $height;

  if($img_width > $max_width || $img_height > $max_height) {
    if($img_width == $img_height) {
      $dst_width  = $max_width;
      $dst_height = $max_height;
    }
    else if($img_width > $img_height) {
      $dst_width  = $max_width;
      $dst_height = ceil(($max_width / $img_width) * $img_height);
    }
    else {
      $dst_height = $max_height;
      $dst_width  = ceil(($max_height / $img_height) * $img_width);
    }
  }
  else {
    $dst_width  = $img_width;
    $dst_height = $img_height;
  }

  $srcx = ($dst_width < $max_width) ? ceil(($max_width - $dst_width)/2) : 0;
  $srcy = ($dst_height < $max_height) ? ceil(($max_height - $dst_height)/2) : 0;

  if($type == 1) {
    $dst_img = imagecreate($max_width, $max_height);
  }else{
    $dst_img = imagecreatetruecolor($max_width, $max_height);
  }

  $bgc = ImageColorAllocate($dst_img, 255, 255, 255);
  ImageFilledRectangle($dst_img, 0, 0, $max_width, $max_height, $bgc);
  ImageCopyResampled($dst_img, $src_img, $srcx, $srcy, 0, 0, $dst_width, $dst_height, ImageSX($src_img),ImageSY($src_img));

  if($type == 1) {
    ImageInterlace($dst_img);
    ImageGif($dst_img, $save_dir.'/'.$save_filename);
  }
  else if($type == 2){
    ImageInterlace($dst_img);
    ImageJPEG($dst_img, $save_dir.'/'.$save_filename);
  }
  else if($type == 3){
    ImagePNG($dst_img, $save_dir.'/'.$save_filename);
  }

  ImageDestroy($dst_img);
  ImageDestroy($src_img);

	if($image_info = getImageSize($save_dir.'/'.$save_filename)) {
		return $save_filename;
	}

}


/*=============================================================================
  이미지 rotateImage(파일경로, 회전각도)
 =============================================================================*/
function rotateImage($file, $angle) {
	list($width,$height,$type,$attr) = @getImageSize($file);
	switch($type){
		case 1:
			$src_img = ImageCreateFromGif($file);
			$rotate  = ImageRotate($src_img, $angle, 0);
			ImageGif($rotate, $file);
		  ImageDestroy($src_img);
		break;
		case 2:
			$src_img = ImageCreateFromJPEG($file);
			$rotate  = ImageRotate($src_img, $angle, 0);
			ImageJPEG($rotate, $file);
		  ImageDestroy($src_img);
		break;
		case 3:
			$src_img = ImageCreateFromPNG($file);
			$rotate  = ImageRotate($src_img, $angle, 0);
			ImagePng($rotate, $file);
		  ImageDestroy($src_img);
		break;
		default : return 0; break;
	}
}


/*=============================================================================
  플랫폼 가져오기
 =============================================================================*/
function getPlatForm() {
	if(preg_match("/(ipad|android\s3\.0|xoom|sch-i800|playbook|tablet|kindle\/i.test(window.navigator.userAgent.toLowerCase()))/i", @$_SERVER["HTTP_USER_AGENT"])) {
		$platform = "TABLET";
	}
	else if(preg_match("/(iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile)/i", @$_SERVER["HTTP_USER_AGENT"])) {
		$platform = "MOBILE";
	}
	else {
		$platform = "PC";
	}
	return $platform;
}

/*=============================================================================
  플랫폼 가져오기 (getPlatForm -> getDevice 로 대체할것)
 =============================================================================*/
function getDevice($user_agent = '') {
	if(!$user_agent) $user_agent = @$_SERVER['HTTP_USER_AGENT'];

	if($user_agent=='') {
		$device = "MOBILE";
	}
	else {

		if(preg_match("/(ipad|android\s3\.0|xoom|sch-i800|playbook|tablet|kindle\/i.test(window.navigator.userAgent.toLowerCase()))/i", $user_agent)) {
			$device = "TABLET";
		}
		else if(preg_match("/(iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile)/i", $user_agent)) {
			$device = "MOBILE";
		}
		else {
			$device = "PC";
		}

	}
	return $device;
}


function print_rr($arr, $add_style='text-align:left;font-size:12px;line-height:13px;') {
	$style = 'text-align:left;font-size:12px;line-height:13px;';
	$style.= $add_style;
	echo "<pre style=\"$style\"><xmp>";
	print_r($arr);
	echo "</xmp></pre>";
}


//문자열 추출
function str_f6($val, $ss, $ee){
	$temp_arr = explode($ss, $val);
	$temp_arr2 = explode($ee, $temp_arr[1]);
	$value = trim($temp_arr2[0]);
	$temp_arr = $temp_arr2 = NULL;
	return $value;
}


function debug_flush($msg) {
	ob_end_clean();
	echo $msg;
	//echo str_pad(' ',256);
	ob_flush();
	flush();
}


///////////////////////////////////////////////////////////////////////////////
// (2017-06-21) 회원 변경내역 기록
///////////////////////////////////////////////////////////////////////////////
function member_edit_log($mb_no) {
	if($mb_no) {
		sql_query("UPDATE g5_member SET edit_datetime = NOW() WHERE mb_no='$mb_no'");		// 수정시간 변경
		if(sql_query("INSERT INTO g5_member_history (SELECT * FROM g5_member WHERE mb_no='$mb_no')")) {
			return true;
		}
	}
}


function aes128Encrypt($key, $data) {
	if(16 !== strlen($key)) { $key = hash('MD5', $key, true); }
	$padding = 16 - (strlen($data) % 16);
  $data.= str_repeat(chr($padding), $padding);
  return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16)));
}

function aes128Decrypt($key, $data) {
  $data = base64_decode($data);
  if(16 !== strlen($key)) { $key = hash('MD5', $key, true); }
  $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16));
  $padding = ord($data[strlen($data) - 1]);
  return substr($data, 0, -$padding);
}


function aes256Encrypt($key, $data) {
	if(32 !== strlen($key)) { $key = hash('MD5', $key, true); }
	return base64_encode(openssl_encrypt($data, "aes-256-cbc", $key, true, str_repeat(chr(0), 16)));
}

function aes256Decrypt($key, $data) {
  if(32 !== strlen($key)) { $key = hash('MD5', $key, true); }
	return openssl_decrypt(base64_decode($data), "aes-256-cbc", $key, true, str_repeat(chr(0), 16));
}

// 우편번호로 지역번호 파악
function getAreaFromZipcode($zipcode = null){
    if(empty($zipcode) OR $zipcode == null){
        return null;
    }
    $zipcode = substr($zipcode, 0 ,2);
    if(in_array($zipcode, range(1,9))) {  // 서울
        return 11;
    }else if(in_array($zipcode, range(10, 20))){ // 경기
        return 12;
    }else if(in_array($zipcode, range(21,23))){ // 인천
        return 15;
    }else if(in_array($zipcode, range(24,26))){ // 강원
        return 25;
    }else if(in_array($zipcode, range(27,29))){ // 충북
        return 24;
    }else if($zipcode == 30){  // 세종시
        return 24;
    }else if(in_array($zipcode, range(31,33))){ // 충청남도
        return 23;
    }else if(in_array($zipcode, range(34,35))){ // 대전
        return 16;
    }else if(in_array($zipcode, range(36,40))){ // 경북
        return 20;
    }else if(in_array($zipcode, range(41,43))){ // 대구
        return 14;
    }else if(in_array($zipcode, range(44,45))){ // 울산
        return 17;
    }else if(in_array($zipcode, range(46,49))){ // 부산
        return 13;
    }else if(in_array($zipcode, range(50,53))){ // 경남
        return 19;
    }else if(in_array($zipcode, range(54,56))){ // 전북
        return 22;
    }else if(in_array($zipcode, range(57,60))){ // 전남
        return 21;
    }else if(in_array($zipcode, range(61,62))){ // 광주
        return 18;
    }else if($zipcode == 63){ // 제주
        return 26;
    }
}

// 제목에서 지역 조회
function getAreaFromTitle($title){
    if(empty($title) OR $title == null) return null;
    if(strpos($title, "서울") !== false) {  // 서울
        return 11;
    }else if(strpos($title, "경기") !== false){ // 경기
        return 12;
    }else if(strpos($title, "인천") !== false){ // 인천
        return 15;
    }else if(strpos($title, "강원") !== false){ // 강원
        return 25;
    }else if(strpos($title, "충북") !== false){ // 충북
        return 24;
    }else if(strpos($title, "세종시") !== false){ // 세종시
        return 24;
    }else if(strpos($title, "충청남도") !== false){ // 충청남도
        return 23;
    }else if(strpos($title, "대전") !== false){ // 대전
        return 16;
    }else if(strpos($title, "경북") !== false){ // 경북
        return 20;
    }else if(strpos($title, "대구") !== false){ // 대구
        return 14;
    }else if(strpos($title, "울산") !== false){ // 울산
        return 17;
    }else if(strpos($title, "부산") !== false){ // 부산
        return 13;
    }else if(strpos($title, "경남") !== false){ // 경남
        return 19;
    }else if(strpos($title, "전북") !== false){ // 전북
        return 22;
    }else if(strpos($title, "전남") !== false){ // 전남
        return 21;
    }else if(strpos($title, "광주") !== false){ // 광주
        return 18;
    }else if(strpos($title, "제주") !== false){ // 제주
        return 26;
    }
}


/*=============================================================================
   strtotime 보정 함수
  =============================================================================*/
function strtotimeMonth($sTime, $iTime=null) {

	if(is_null($iTime) === TRUE || is_int($iTime) === FALSE) {
		$iTime = time();
	}

	$sTransDay = date('d', $iTime);
	$sLastDay  = date('t', $iTime);

	if($sTransDay == $sLastDay) {
		$iResTime = strtotime('last day of ' . $sTime, $iTime);
	} else {
		$iResTime = strtotime($sTime, $iTime);
	}

	return $iResTime;

}

// 모바일 더보기 기능, 한페이지에 보여줄 행, 현재페이지, 총페이지수, URL
function m_get_paging($write_pages, $cur_page, $total_page)
{
    global $aslang;

    //$url = preg_replace('#&amp;page=[0-9]*(&amp;page=)$#', '$1', $url);
    $url = preg_replace('#&amp;page=[0-9]*#', '', $url) . '&amp;page=';

    $html = "";

    // 마지막 페이지 구하기
    // $start_page = ( ( (int)( ($cur_page - 1 ) / $write_pages ) ) * $write_pages ) + 1;
    // $end_page = $start_page + $write_pages - 1;

    if($total_page > 1)
    {
        // 다음
        if($cur_page < $total_page) {
            $html = '<button type="button" name="more_list" class="m_more_list" data-target="'.($cur_page+1).'">더보기 ('.$cur_page.'/'.$total_page.')</button>';
        }
    }

    return $html;
}

// html 제거
function html_clean($str)
{
    $str = str_replace("&nbsp;", " ", $str);
    $str = preg_replace('/\s+/', ' ',$str);
    $str = trim($str);
    return $str;
}


/**
 * 투자상품 상태값 찾기
 * @param string $state 진행현황
 * @param string $open_datetime 상품출력시작일시
 * @param string $invest_end_date 투자모집완료일
 * @param string $start_datetime 투자모집시작일시
 * @param string $end_datetime 투자모집만료일시
 * @param int $recruit_amount 모집금액(대출금액)
 * @param int $amount 투자금액 (cf_product_invest)
 * @return bool
 */
function getProductState($state = "", $open_datetime = "", $invest_end_date = "", $start_datetime = "", $end_datetime = "", $recruit_amount = 0, $amount = 0)
{
    $pstate = '';
    $date = date('Y-m-d H:i:s');

    if($state) {
				if($state == '1')      $pstate = '이자상환중';
        else if($state == '2') $pstate = '정상상환';
        else if($state == '3') $pstate = '투자금모집실패';
        else if($state == '4') $pstate = '부실';
        else if($state == '5') $pstate = '중도상환';
        else if($state == '6') $pstate = '대출계약취소(기표전)';
        else if($state == '7') $pstate = '대출계약취소(기표후)';
        else if($state == '8') $pstate = '연체중';
        else if($state == '9') $pstate = '상환불가';
    } else {

        if($open_datetime > $date) {
            $pstate = '상품준비중';
        }else{

            if(empty($invest_end_date)) // 투자모집완료일이 없다면
            {
                if($end_datetime < $date) { // 투자모집만료일시
                    $pstate = '투자금모집실패';
                } else {
                    $pstate = '대기중';
                }
            }else{
                if($recruit_amount > $invest_amount) {
                    $pstate = '투자모집실패';
                } else {
                    $pstate = '투자모집완료';
                }
            }

            if($start_datetime > $date) // 모집기간 전
            {
                $pstate = '투자대기중';

            }
                else if($start_datetime <= $date && $end_datetime >= $date) // 모집기간 중
            {
                if($recruit_amount == $amount) {
                    $pstate = '투자금모집완료';
                }else{
                    $pstate = '투자금모집중';
                }
            }
                else if($end_datetime > $date)
            {
                if($recruit_amount == $amount) {
                    $pstate = '투자금모집완료';
                }else{
                    $pstate = '투자금모집실패';
                }
            }
        }
    }
    return $pstate;
}

// URL 접속검사
function url_exists($url, $port = 80)
{
    $url = str_replace("http://", "", $url);
    $url = str_replace("https://", "", $url);

    if(strstr($url, "/")) {
        $url = explode("/", $url, 2);
        $url[1] = "/".$url[1];

    } else {
        $url = array($url, "/");
    }

    $fso = fsockopen($url[0], $port);

    if($fso) {
        fputs($fso, "GET ".$url[1]." HTTP/1.1\nHost:".$url[0]."\n\n");
        $gets = fgets($fso, 4096);
        fclose($fso);

        if(preg_match('/^HTTP\/.* 200 OK/',$gets)){
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// 날짜 차이계산
function diff_date($dt_menor, $dt_maior, $str_interval = 'd', $relative = true)
{
    if( is_string( $dt_menor)) $dt_menor = date_create( $dt_menor);
    if( is_string( $dt_maior)) $dt_maior = date_create( $dt_maior);

    $diff = date_diff( $dt_menor, $dt_maior, ! $relative);

    switch( $str_interval){
        case "y":
            $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
        case "m":
            $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
            break;
        case "d":
            $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
            break;
        case "h":
            $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
            break;
        case "i":
            $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
            break;
        case "s":
            $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
            break;
    }
    if($diff->invert){
        return (-1 * $total);
    }else{
        return $total;
    }
}


// 소수점 자리수 끊기 (자리수 이하 버림)
function floatCutting($n, $commaUnderLength=0) {
	if(is_numeric($n)) {
		if(preg_match('/\./', $n)) {
			$N = explode('.', $n);
			$value = $N[0];

			if($commaUnderLength) {
				for($i=0,$j=1; $i<strlen($N[1]);$i++,$j++) {
					if($i==0) $value.= '.';
					if($j <= $commaUnderLength) {
						$value.= substr($N[1], $i, 1);
					}
					else break;
				}
			}
		}
		else {
			$value = $n;
		}
	}
	return $value;
}


// 소수점 이하 0으로 끝나는 수 버림
function floatRtrim($number) {
	if($number > 0) {
		if(preg_match('/\./', $number)) {
			$_number = rtrim($number, "0");
			$locale_info = localeconv();
			$return_number = rtrim($_number, $locale_info['decimal_point']);
		}
		else {
			$return_number = $number;
		}
	}
	else {
		$return_number = 0;
	}
	return $return_number;
}


// 2차원 배열에서 열 기준으로 정렬
function array_sort_by_column(&$arr, $col, $dir=SORT_ASC) {
	$sort_col = array();
	foreach ($arr as $key => $row) {
		$sort_col[$key] = $row[$col];
	}
	array_multisort($sort_col, $dir, $arr);
}


// IP로 지역정보 얻기 (GeoIP 모듈이 설치되어있어야 동작함)
function IP_AREA($ip) {
	if($ip)	{
/*
		$GeoIP = geoip_record_by_name($ip);
		$GeoIP['region_code'] = $GeoIP['region']; unset($GeoIP['region']);
		$GeoIP['region_name'] = ($GeoIP['country_code'] && $GeoIP['region_code']) ? geoip_region_name_by_code($GeoIP['country_code'], $GeoIP['region_code']) : '';


	//$ARR['continent_code'] = $GeoIP['continent_code'];
    $ARR['country_code']   = $GeoIP['country_code'];
  //$ARR['country_code3']  = $GeoIP['country_code3'];
    $ARR['country_name']   = $GeoIP['country_name'];
    $ARR['region_code']    = $GeoIP['region_code'];
    $ARR['region_name']    = preg_replace("/\'/", "", $GeoIP['region_name']);
		$ARR['city']           = $GeoIP['city'];
  //$ARR['postal_code']    = $GeoIP['postal_code'];
		$ARR['latitude']       = $GeoIP['latitude'];
		$ARR['longitude']      = $GeoIP['longitude'];
  //$ARR['dma_code']       = $GeoIP['dma_code'];
  //$ARR['area_code']      = $GeoIP['area_code'];

		return $ARR;
*/
	}
}


// 요청된 address로 사이트-키워드 분기
function urlParse($address) {

	$address = trim($address);
	$address = (iconv('utf-8', 'utf-8', $address)==$address) ? urldecode($address) : urldecode(iconv('euc-kr', 'utf-8', $address));
	$address = urldecode(urldecode(urldecode($address)));
	$ARR['address'] = $address;

	$is_paid = '';

	$addr = preg_replace("/(http:\/\/|https:\/\/)/i", "", $address);

	$ADDRESS = explode("/", $addr);
	//print_r($ADDRESS);
	for($i=0; $i<count($ADDRESS); $i++) {
		$ADDRESS[$i] = urldecode($ADDRESS[$i]);
	}

	// 사이트인덱스 및 카테고리 분기
	if(substr($ADDRESS[0], 0, 4)=='www.') { $ADDRESS[0] = preg_replace("/^www\./", "", $ADDRESS[0]); }
	if(substr($ADDRESS[0], 0, 2)=='m.')   { $ADDRESS[0] = preg_replace("/^m\./", "", $ADDRESS[0]); }
	//echo $ADDRESS[0]."\n";

	//**** Naver 분기 ****//
	if(preg_match("/(naver\.com|blog\.me)/i", $ADDRESS[0])) {
		if( preg_match("/blog\.me|blog\.naver/i", $ADDRESS[0]) )    { $ADDRESS[0] = "naver.blog"; }				// blog.me 변환
		else if( preg_match("/adcr\.naver\.com\/adcr\?/i", $addr) ) { $ADDRESS[0] = "naver.powerlink"; $is_paid = '1'; }	// 네이버 파워링크 구분
		else if( preg_match("/ad\.search\.naver/i", $addr) )        { $ADDRESS[0] = "naver.powerlink"; $is_paid = '1'; }	// 네이버 파워링크 구분
		else if( preg_match("/mail/i", $addr) )                     $ADDRESS[0] = "naver.mail";
		else $ADDRESS[0] = ( preg_match("/search\./i", $addr) ) ? "naver.search" : "naver";
	}

	//**** Google 분기 ****//
	else if( preg_match("/google/", $ADDRESS[0]) || preg_match("/google/", $ADDRESS[1]) || preg_match("/google/", $ADDRESS[2]) || preg_match("/goo\.gl/", $addr) ) {
		if( preg_match("/googleads\.g\.doubleclick\.net/i", $ADDRESS[0]) ) {
			$ADDRESS[0] = "google.adwords";			// 구글애드워즈
			$ARR['link_loc'] = urldecode(str_f6($address, "&url=", "&"));
			$is_paid = '1';
		}
		else if( preg_match("/googleadservices/i", $ADDRESS[0]) ) {
			$ADDRESS[0] = "google.adsense";			// 구글애드센스
			$ARR['link_loc'] = urldecode(str_f6($address, "&ref=", "&"));
		//$is_paid = '1';
		}
		else if( preg_match("/tpc\.googlesyndication/i", $ADDRESS[0]) ) {
			$ADDRESS[0] = "google.admanager";		// 구글애드매니저 네트워크
			$ARR['link_loc'] = urldecode(str_f6($address, "&ref=", "&"));
			$is_paid = '1';
		}
		else if( preg_match("/googleapis/i", $ADDRESS[0]) && preg_match("/suggest/i", $addr) )  $ADDRESS[0] = "google.suggest";				// 구글서제스트
		else if( preg_match("/cse\.google/i", $ADDRESS[0]) )    $ADDRESS[0] = "google.cse";						// 구글 맟춤검색
		else if( preg_match("/mail\.google/i", $ADDRESS[0]) )   $ADDRESS[0] = "google.mail";					// 구글 메일
		else if( preg_match("/search/i", $ADDRESS[1]) )         $ADDRESS[0] = "google.search";
		else if( preg_match("/goo\.gl\//i", $ADDRESS[1]) )      $ADDRESS[0] = "google.URL Shortener";
		else $ADDRESS[0] = "google";
	}

	//**** Daum / Nate 분기 ****//
	else if( preg_match("/daum\.net/i", $ADDRESS[0]) ) {
		if( preg_match("/(keyword\.daum|keyword\.ad\.daum)/i", $ADDRESS[1]) || preg_match("/(keyword\.daum|keyword\.ad\.daum)/i", $ADDRESS[2]) ) {
			$ADDRESS[0] = "daum.keyword"; $is_paid = '1';		// 다음 키워드 광고에 대한 레퍼러는  $ADDRESS[1] 에 포함되어있음.
		}
		else {
			if( preg_match("/search/i", $ADDRESS[0]) ) {
				$ADDRESS[0] = ( preg_match("/nate/i", $ADDRESS[1]) ) ? "nate.search" : "daum.search";
			}
			else if( preg_match("/blog\./i", $ADDRESS[0]) )  $ADDRESS[0] = "daum.blog";
			else if( preg_match("/cafe\./i", $ADDRESS[0]) )  $ADDRESS[0] = "daum.cafe";
			else if( preg_match("/media\./i", $ADDRESS[0]) ) $ADDRESS[0] = "daum.media";
			else $ADDRESS[0] = "daum";
		}
	}

	//**** Kakao 분기 ****//
	else if( preg_match("/kakao\.com/i", $address) ) {
		$ADDRESS[0] = "kakao";
		if( preg_match("/v\.kakao/i",  $ADDRESS[0]) )     $ADDRESS[0] = "kakao.channel";
		if( preg_match("/story\.kakao/i",  $ADDRESS[0]) ) $ADDRESS[0] = "kakao.story";
		if( preg_match("/1boon\.kakao/i",  $ADDRESS[0]) ) $ADDRESS[0] = "kakao.1boon";
	}
	//**** 카카오 브런치 분기 ****//
	else if( preg_match("/^brunch\.co/i", $ADDRESS[0]) ) $ADDRESS[0] = "kakao.brunch";

	//**** 티스토리 분기 ****//
	else if( preg_match("/tistory\.co/i", $ADDRESS[0]) ) $ADDRESS[0] = "tistory.blog";

	//**** Bing 분기 ****//
	else if( preg_match("/bing\.com/i", $address) ) {
		$ADDRESS[0] = ( preg_match("/search/i",  $ADDRESS[0]) ) ? "bing.search" : "bing";
	}

	//**** Zum 분기 ****//
	else if( preg_match("/zum\.com/i", $address) ) {
		$ADDRESS[0] = ( preg_match("/search/i",  $ADDRESS[0]) ) ? "zum.search" : "zum";
	}

	else if( preg_match("/facebook\.com/i",  $ADDRESS[0]) )  $ADDRESS[0] = "facebook";
	else if( preg_match("/twitter\.com/i",  $ADDRESS[0]) )   $ADDRESS[0] = "twitter";
	else if( preg_match("/instagram\.com/i",  $ADDRESS[0]) ) $ADDRESS[0] = "instagram";
	else if( preg_match("/youtube\.com/i",  $ADDRESS[0]) )   $ADDRESS[0] = "youtube";

	//**** 기타 분기 ****//
	else if( preg_match("/hellofunding\.co|hellofunding\.kr/i",  $ADDRESS[0]) ) $ADDRESS[0] = "hellofunding";
	else if( preg_match("/wowstar\.co/i",  $ADDRESS[0]) )      $ADDRESS[0] = "wowstar";
	else if( preg_match("/toomoda\.com/i", $address) )         $ADDRESS[0] = "toomoda";
	else if( preg_match("/p2plending\.or\.kr/i", $address) )   $ADDRESS[0] = "p2plending";
	else if( preg_match("/p2plounge\.co/i", $address) )        $ADDRESS[0] = "p2plounge";
	else if( preg_match("/fundingbox\.co/i", $address) )       $ADDRESS[0] = "fundingbox";
	else if( preg_match("/funscan\.co/i", $address) )          $ADDRESS[0] = "funscan";
	else if( preg_match("/finnq\.co/i", $address) )            $ADDRESS[0] = "finnq";

	//**** 그외 처리 ****//
	else {
		$is_unknown_site = true;
	}

	if( preg_match("/^mail/", $ADDRESS[0]) ) {
		$ADDRESS[0] = preg_replace("/[0-9]/", "", $ADDRESS[0]);
	}

	if($is_unknown_site) {
		$ARR['site_id'] = $ADDRESS[0];
		$ARR['site_ca'] = '';
	}
	else {
		$ADDRESS[0] = preg_replace("/\.com|\.net|\.co\.kr|\.org|\.or\.kr|\.kr/", "", $ADDRESS[0]);
		$ADDRESS_ARR = explode(".", $ADDRESS[0]);
		$ARR['site_id'] = $ADDRESS_ARR[0];
		$ARR['site_ca'] = $ADDRESS_ARR[1];
	}

	// 키워드 분기
	if(!$is_unknown_site) {
		$addr_count = count($ADDRESS);
		for($i=0,$j=1; $i<$addr_count; $i++,$j++) {
			if($ADDRESS[$i]) {
				if(preg_match("/\?q=/i", $ADDRESS[$i]))            $ARR['keyword'] = str_f6($ADDRESS[$i], "?q=", "&");
				else if(preg_match("/\&q=/i", $ADDRESS[$i]))       $ARR['keyword'] = str_f6($ADDRESS[$i], "&q=", "&");
				else if(preg_match("/\?query=/i", $ADDRESS[$i]))   $ARR['keyword'] = str_f6($ADDRESS[$i], "?query=", "&");
				else if(preg_match("/\&query=/i", $ADDRESS[$i]))   $ARR['keyword'] = str_f6($ADDRESS[$i], "&query=", "&");
				else if(preg_match("/\?keyword=/i", $ADDRESS[$i])) $ARR['keyword'] = str_f6($ADDRESS[$i], "?keyword=", "&");
				else if(preg_match("/\&keyword=/i", $ADDRESS[$i])) $ARR['keyword'] = str_f6($ADDRESS[$i], "&keyword=", "&");
				else if(preg_match("/\?DMKW=/i", $ADDRESS[$i]))    $ARR['keyword'] = str_f6($ADDRESS[$i], "?DMKW=", "&");
				else if(preg_match("/\&DMKW=/i", $ADDRESS[$i]))    $ARR['keyword'] = str_f6($ADDRESS[$i], "&DMKW=", "&");

				if(preg_match("/\?nzq=/i", $ADDRESS[$i]))     $ARR['pkeyword'] = str_f6($ADDRESS[$i], "?nzq=", "&");					// 다음 :: 상위 키워드 추출
				else if(preg_match("/\&nzq=/i", $ADDRESS[$i]))     $ARR['pkeyword'] = str_f6($ADDRESS[$i], "&nzq=", "&");		  // 다음 :: 상위 키워드 추출
			//else if(preg_match("/\&sq\=/i", $ADDRESS[$i])) $ARR['ackeyword'] = str_f6($ADDRESS[$i], "&sq=", "&");					// 다음 :: 자동완성 키워드 추출
			//else if(preg_match("/\&pq\=/i", $ADDRESS[$i]))      $ARR['pkeyword'] = str_f6($ADDRESS[$i], "&pq=", "&");			// 빙 :: 자동완성 키워드 추출
			//else if(preg_match("/\&acq\=/i", $ADDRESS[$i]))     $ARR['ackeyword'] = str_f6($ADDRESS[$i], "&acq=", "&");		// 네이버 :: 자동완성 키워드 추출

				if($ARR['keyword']==$ARR['pkeyword']) $ARR['pkeyword'] = '';
			}
		}

		if($ARR['keyword']) {
			$ARR['keyword'] = (strlen($ARR['keyword']) < 50) ? urldecode($ARR['keyword']) : '';
		}
	}

	$ARR['is_paid'] = $is_paid;

	if( in_array($ADDRESS[0], array('hellofunding','finnq','wowstar','chosun')) ) $ARR['site_id'] = '';		// 특정 사이트 트래픽은 제외한다.

	//print_r($ADDRESS);

	unset($ADDRESS);
	return($ARR);

}

function tvtalk_get_adult() {

	global $member;
	$key = "jumin";

	$row = sql_fetch("SELECT mb_1 FROM g5_member WHERE mb_id='".$member['mb_id']."'");
	if (!$row['mb_1']) return "Y";

	$birthday = decrypt($row['mb_1'],$key);
	$age = date("Y") - substr($birthday, 0, 4);
	$adult = ($age < 19) ? 'N' : 'Y';

	return $adult;

}

function getDateInterval($sd, $ed) {
	if($sd && $ed) {
		if( $INTERVAL = date_diff(date_create($sd), date_create($ed)) ) {
			$interval= "";
			if($INTERVAL->d) $interval.= $INTERVAL->d."일";
			if($INTERVAL->h) $interval.= " ".$INTERVAL->h."시간";
			if($INTERVAL->i) $interval.= " ".$INTERVAL->i."분";
			if($INTERVAL->s) $interval.= " ".$INTERVAL->s."초";
		}
		return @trim($interval);
	}
	else {
		return false;
	}
}

//가중치랜덤
function weighted_random($weights_array) {
  $r = rand(1, array_sum($weights_array));
  for($i=0; $i<count($weights_array); $i++) {
    $r -= $weights_array[$i];
    if($r < 1) return $i;
  }
  return false;
}

function unique_un_replace($strVal)
{
	$strVal = str_replace("&amp;","&",$strVal);
	$strVal = str_replace("&quot;","\"",$strVal);
	$strVal = str_replace("&apos;","'",$strVal);
	$strVal = str_replace("&lt;","<",$strVal);
	$strVal = str_replace("&gt;",">",$strVal);
	$strVal = str_replace("%2F","/",$strVal);
	$strVal = str_replace("%3A",":",$strVal);

	return $strVal;
}

function unique_replace($strVal)
{
	$strVal = str_replace("&","&amp;",$strVal);
	$strVal = str_replace("\"","&quot;",$strVal);
	$strVal = str_replace("'","&apos;",$strVal);
	$strVal = str_replace("<","&lt;",$strVal);
	$strVal = str_replace(">","&gt;",$strVal);

	return $strVal;
}

/* utf-8 힌글 자르기 */
function strcut_utf8($str, $len, $checkmb=false, $tail='')
{
	preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match); // target for BMP
	$m = $match[0];
	$slen = strlen($str); // length of source string
	$tlen = strlen($tail); // length of tail string
	$mlen = count($m); // length of matched characters

	if ($slen <= $len) return $str;
	if (!$checkmb && $mlen <= $len) return $str;
	$ret = array();
	$count = 0;

	for ($i=0; $i < $len; $i++) {
		$count += ($checkmb && strlen($m[$i]) > 1)?2:1;
		if ($count + $tlen > $len) break;
		$ret[] = $m[$i];
	}
	return join('', $ret).$tail;
}



// 헬로펀딩 상품 투자 요약보고
FUNCTION fn_cf_product_admin_report($SE)
{
	IF($SE)
	{
	$PRDT = sql_fetch("SELECT start_num, title, recruit_amount, invest_return, recruit_period_start, loan_start_date, loan_end_date, start_datetime,invest_period,invest_days FROM cf_product WHERE idx='".$SE."'");

	IF($PRDT["invest_period"] == "1" && $PRDT["invest_days"])
	{
		$invest_period = $PRDT["invest_days"]." 일";
	} ELSE {
		$invest_period = $PRDT["invest_period"]." 개월";
	}

	// 투자소요시간 측정
	$LAST_INVEST = sql_fetch("SELECT insert_date, insert_time FROM cf_product_invest WHERE product_idx='".$SE."' AND invest_state='Y' ORDER BY idx DESC LIMIT 1");
	$last_invest_datetime = $LAST_INVEST['insert_date']." ".$LAST_INVEST['insert_time'];
	$interval = getDateInterval($PRDT['start_datetime'], $last_invest_datetime);

	$sql = "
		SELECT
			B.mb_id, B.mb_name, B.mb_co_name, B.member_type, B.member_investor_type,
			A.member_idx, A.amount, A.is_advance_invest, A.syndi_id AS flatform_id,
			(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_count,
			(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_amount,
			(SELECT is_auto_invest FROM cf_product_invest_detail WHERE invest_idx=A.idx ORDER BY idx DESC LIMIT 1) AS is_auto_invest,
			(SELECT amount FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='1') AS auto_invest_amount
		FROM
			cf_product_invest A
		LEFT JOIN
			g5_member B  ON A.member_idx = B.mb_no
		WHERE (1)
			AND A.product_idx='".$SE."'
			AND A.invest_state='Y'
			$where_plus
		ORDER BY
			A.amount DESC";
	//echo $sql;
	$res  = sql_query($sql);
	$rows = sql_num_rows($res);

	$TOTAL = array(
						'COUNT'      => 0,
						'AMOUNT'     => 0,
						'M1_COUNT'   => 0,
						'M1_AMOUNT'  => 0,
						'M11_COUNT'  => 0,
						'M11_AMOUNT' => 0,
						'M12_COUNT'  => 0,
						'M12_AMOUNT' => 0,
						'M13_COUNT'  => 0,
						'M13_AMOUNT' => 0,
						'M2_COUNT'   => 0,
						'M2_AMOUNT'  => 0,
						'M3_COUNT'   => 0,
						'M3_AMOUNT'  => 0,
						'M32_COUNT'   => 0,
						'M32_AMOUNT'  => 0,
						'M33_COUNT'   => 0,
						'M33_AMOUNT'  => 0,
						'M34_COUNT'   => 0,
						'M34_AMOUNT'  => 0,
						'AUTO_INVEST_AMOUNT' => 0
					);

	$TOTAL_A = array(
							'COUNT'      => 0,
							'AMOUNT'     => 0,
							'M1_COUNT'   => 0,
							'M1_AMOUNT'  => 0,
							'M11_COUNT'  => 0,
							'M11_AMOUNT' => 0,
							'M12_COUNT'  => 0,
							'M12_AMOUNT' => 0,
							'M13_COUNT'  => 0,
							'M13_AMOUNT' => 0,
							'M2_COUNT'   => 0,
							'M2_AMOUNT'  => 0,
							'M3_COUNT'   => 0,
							'M3_AMOUNT'  => 0,
							'M32_COUNT'   => 0,
							'M32_AMOUNT'  => 0,
							'M33_COUNT'   => 0,
							'M33_AMOUNT'  => 0,
							'M34_COUNT'   => 0,
							'M34_AMOUNT'  => 0
						);

	$TOTAL_B = array(
							'COUNT'      => 0,
							'AMOUNT'     => 0,
							'M1_COUNT'   => 0,
							'M1_AMOUNT'  => 0,
							'M11_COUNT'  => 0,
							'M11_AMOUNT' => 0,
							'M12_COUNT'  => 0,
							'M12_AMOUNT' => 0,
							'M13_COUNT'  => 0,
							'M13_AMOUNT' => 0,
							'M2_COUNT'   => 0,
							'M2_AMOUNT'  => 0,
							'M3_COUNT'   => 0,
							'M3_AMOUNT'  => 0,
							'M32_COUNT'   => 0,
							'M32_AMOUNT'  => 0,
							'M33_COUNT'   => 0,
							'M33_AMOUNT'  => 0,
							'M34_COUNT'   => 0,
							'M34_AMOUNT'  => 0
						);


	for($i=0; $i<$rows; $i++) {
		$LIST[$i] = sql_fetch_array($res);

		////////////////////////////////////
		// 전체 현황
		////////////////////////////////////
		$TOTAL['COUNT'] += 1;
		$TOTAL['AMOUNT'] += $LIST[$i]['amount'];
		if($LIST[$i]['is_auto_invest']=='1') {
			$TOTAL['AUTO_INVEST_AMOUNT'] += $LIST[$i]['auto_invest_amount'];
		}

		if($LIST[$i]['member_type']=='2') {
			$TOTAL['M2_COUNT'] += 1;
			$TOTAL['M2_AMOUNT'] += $LIST[$i]['amount'];
		}
		else {
			$TOTAL['M1_COUNT'] += 1;
			$TOTAL['M1_AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_investor_type']=='2') {
				$TOTAL['M12_COUNT'] += 1;
				$TOTAL['M12_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['member_investor_type']=='3') {
				$TOTAL['M13_COUNT'] += 1;
				$TOTAL['M13_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL['M11_COUNT'] += 1;
				$TOTAL['M11_AMOUNT'] += $LIST[$i]['amount'];
			}
		}

		if($LIST[$i]['flatform_id']=='finnq') {
			$TOTAL['M3_COUNT'] += 1;
			$TOTAL['M3_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='hktvwowstar') {
			$TOTAL['M32_COUNT'] += 1;
			$TOTAL['M32_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='chosun') {
			$TOTAL['M33_COUNT'] += 1;
			$TOTAL['M33_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='oligo') {
			$TOTAL['M34_COUNT'] += 1;
			$TOTAL['M34_AMOUNT'] += $LIST[$i]['amount'];
		}

		////////////////////////////////////
		// 최초 투자자 현황 데이터
		////////////////////////////////////
		if($LIST[$i]['total_invest_count']==1) {

			$TOTAL_A['COUNT'] += 1;
			$TOTAL_A['AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_type']=='2') {
				$TOTAL_A['M2_COUNT'] += 1;
				$TOTAL_A['M2_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_A['M1_COUNT'] += 1;
				$TOTAL_A['M1_AMOUNT'] += $LIST[$i]['amount'];

				if($LIST[$i]['member_investor_type']=='2') {
					$TOTAL_A['M12_COUNT'] += 1;
					$TOTAL_A['M12_AMOUNT'] += $LIST[$i]['amount'];
				}
				else if($LIST[$i]['member_investor_type']=='3') {
					$TOTAL_A['M13_COUNT'] += 1;
					$TOTAL_A['M13_AMOUNT'] += $LIST[$i]['amount'];
				}
				else {
					$TOTAL_A['M11_COUNT'] += 1;
					$TOTAL_A['M11_AMOUNT'] += $LIST[$i]['amount'];
				}
			}

			if($LIST[$i]['flatform_id']=='finnq') {
				$TOTAL_A['M3_COUNT'] += 1;
				$TOTAL_A['M3_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='hktvwowstar') {
				$TOTAL_A['M32_COUNT'] += 1;
				$TOTAL_A['M32_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='chosun') {
				$TOTAL_A['M33_COUNT'] += 1;
				$TOTAL_A['M33_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='oligo') {
				$TOTAL_A['M34_COUNT'] += 1;
				$TOTAL_A['M34_AMOUNT'] += $LIST[$i]['amount'];
			}

		}

		////////////////////////////////////
		// 기존 투자자 현황 데이터
		////////////////////////////////////
		else {

			$TOTAL_B['COUNT'] += 1;
			$TOTAL_B['AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_type']=='2') {
				$TOTAL_B['M2_COUNT'] += 1;
				$TOTAL_B['M2_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_B['M1_COUNT'] += 1;
				$TOTAL_B['M1_AMOUNT'] += $LIST[$i]['amount'];

				if($LIST[$i]['member_investor_type']=='2') {
					$TOTAL_B['M12_COUNT'] += 1;
					$TOTAL_B['M12_AMOUNT'] += $LIST[$i]['amount'];
				}
				else if($LIST[$i]['member_investor_type']=='3') {
					$TOTAL_B['M13_COUNT'] += 1;
					$TOTAL_B['M13_AMOUNT'] += $LIST[$i]['amount'];
				}
				else {
					$TOTAL_B['M11_COUNT'] += 1;
					$TOTAL_B['M11_AMOUNT'] += $LIST[$i]['amount'];
				}
			}

			if($LIST[$i]['flatform_id']=='finnq') {
				$TOTAL_B['M3_COUNT'] += 1;
				$TOTAL_B['M3_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='hktvwowstar') {
				$TOTAL_B['M32_COUNT'] += 1;
				$TOTAL_B['M32_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='chosun') {
				$TOTAL_B['M33_COUNT'] += 1;
				$TOTAL_B['M33_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='oligo') {
				$TOTAL_B['M34_COUNT'] += 1;
				$TOTAL_B['M34_AMOUNT'] += $LIST[$i]['amount'];
			}

		}

	}


	$strTitle = "헬로펀딩 제{$PRDT['start_num']}호 상품 투자 요약보고";

	$strContent = "

	<html>
	<head>
	<title>헬로펀딩 상품 투자 요약보고</title>
	<meta name='viewport' content='width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes'>
	<meta name='mobile-web-app-capable' content='yes'>
	<meta name='apple-mobile-web-app-capable' content='yes'>
	<link href='https://fonts.googleapis.com/css?family=Nanum+Gothic:400,700,800&subset=korean' rel='stylesheet'>
	<link href='/css/report.css?ver=".DATE('YmdHis')."' rel='stylesheet'>
	</head>
	<body>

	<div class='content_guide'>
		<div class='title_guide'>".$strTitle."</div>

		<table class='tb1_guide'>
		<tr>
			<th class='tb1_title_area' colspan='3'>".$PRDT['title']."</th>
		</tr>
		<tr>
			<th class='th_33'>모집금액</th>
			<td colspan='2' class='td_int_area td_gray fb'>".price_cutting($PRDT['recruit_amount'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>투자수익율</th>
			<td colspan='2' class='td_int_area td_gray fb'>".$PRDT['invest_return']." %</td>
		</tr>
		<tr>
			<th class='th_33'>투자기간</th>
			<td colspan='2' class='td_int_area td_gray fb'>".$invest_period."</td>
		</tr>
		<tr>
			<th class='th_33'>투자소요시간</th>
			<td colspan='2' class='td_int_area fb'>".$interval."</td>
		</tr>
		<tr>
			<th class='th_33'>전체투자현황</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>법인투자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M2_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M2_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>개인투자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M1_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M1_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_deep_gray th_33'>개인-일반투자자</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M11_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M11_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_deep_gray th_33'>개인-소득적격투자자</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M12_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M12_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_deep_gray th_33'>개인-전문투자자</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M13_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M13_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>최초투자자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL_A['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL_A['AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>기존투자자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL_B['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL_B['AMOUNT'])."원</td>
		</tr>
		</table>


		<table class='tb1_guide'>
		<tr>
			<th class='tb1_title_area' colspan='3'>신디케이션 서비스별 투자 발생내역</th>
		</tr>
		<tr>
			<th class='th_33'>서비스명</th>
			<th class='th_33'>투자건수</th>
			<th class='th_33'>투자금액</th>
		</tr>
		<tr>
			<td class='td_txt_area'>핀크</td>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M3_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M3_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td class='td_txt_area'>한경</td>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M32_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M32_AMOUNT'])."원</td>
		</tr>
		<tr>
			<td class='td_txt_area'>올리고</td>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M34_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M34_AMOUNT'])."원</td>
		</tr>
		</table>

		<table class='tb1_guide'>
		<tr>
			<th class='tb1_title_area' colspan='4'>투자형태별 집계</th>
		</tr>
		<tr>
			<th class='th_25'>전체모집금액</th>
			<th class='th_25'>자동투자액</th>
			<th class='th_25'>수동투자액</th>
			<th class='th_25'>자동투자액 비중</th>
		</tr>
		<tr>
			<td class='td_int_area'>".price_cutting($TOTAL['AMOUNT'])."원</td>
			<td class='td_int_area'>".price_cutting($TOTAL['AUTO_INVEST_AMOUNT'])."원</td>
			<td class='td_int_area'>".price_cutting(($TOTAL['AMOUNT']-$TOTAL['AUTO_INVEST_AMOUNT']))."원</td>
			<td class='td_int_area'>".floatCutting(@sprintf('%.2f', $TOTAL['AUTO_INVEST_AMOUNT']/$TOTAL['AMOUNT']*100))."%</td>
		</tr>
		</table>

		<table class='tb2_guide'>
		<tr>
			<th class='tb1_title_area' colspan='8'>투자 상세내역</th>
		</tr>
		<tr>
			<th class='th_5'>NO</th>
			<th>업체명<br />/성명</th>
			<th>투자자<br />유형</th>
			<th>투자처</th>
			<th>투자금액</th>
			<th>투자<br />형태</th>
			<th>누적<br />투자수</th>
			<th>누적<br />투자액</th>
		</tr>";

	FOR($i=0,$j=1,$lnum=21; $i<$rows; $i++,$j++,$lnum++) {
		$name = ($LIST[$i]['member_type']=='2') ? $LIST[$i]['mb_co_name'] : $LIST[$i]['mb_name'];
		if($LIST[$i]['member_type']=='2') {
			$member_type = '법인';
		}
		else {
			if($LIST[$i]['member_investor_type']=='3')  $member_type = '전문';
			else if($LIST[$i]['member_investor_type']=='2') $member_type = '소득적격';
			else $member_type = '개인';
		}
		if($LIST[$i]['flatform_id']=='finnq') {
			$flatform = '핀크';
		}
		else if($LIST[$i]['flatform_id']=='finnq') {
			$flatform = '한경';
		}
		else if($LIST[$i]['flatform_id']=='oligo') {
			$flatform = '올리고';
		}
		else if($LIST[$i]['flatform_id']=='kakaopay') {
			$flatform = '카카오페이';
		}
		else {
			$flatform = '헬로';
		}
		$invest_gubun = ($LIST[$i]['is_auto_invest']=='1') ? '자동투자' : '일반투자';

	$strContent .= "
		<tr>
			<td class='td_txt_area'>".$j."</td>
			<td class='td_txt_area'>".$name."</td>
			<td class='td_txt_area'>".$member_type."</td>
			<td class='td_txt_area'>".$flatform."</td>
			<td class='td_int_area'>".price_cutting($LIST[$i]['amount'])."원</td>
			<td class='td_txt_area'>".$invest_gubun."</td>
			<td class='td_int_area'>".number_format($LIST[$i]['total_invest_count'])."건</td>
			<td class='td_int_area'>".price_cutting($LIST[$i]['total_invest_amount'])."원</td>
		</tr>";

		}
	$strContent .= "</table>
	</div>


	</body>
	</html>
	";

	//ECHO $strContent;

	$Query = "INSERT INTO
						   cf_product_admin_report
						   (product_idx, title, product, content, reg_time)
						   VALUES
						   ('".$SE."','".addslashes($strTitle)."','".addslashes($PRDT['title'])."','".addslashes($strContent)."',now())";
	sql_query($Query);
	}
}

FUNCTION fn_hello_status_smssend($product_idx)
{
	//global $_admin_sms_number;

	$_admin_sms_number = "15886760";

	$intTime = TIME();
	$dtmH	 = DATE("H");
	$strSendYn = "";

	IF($dtmH >= "00" AND $dtmH <= "06")
	{
		$strSendYn = "1";	// SMS 발송대기 플래그
	}

	$Query = "SELECT pidx, title, product, content,reg_time FROM cf_product_admin_report WHERE product_idx='".$product_idx."'";

	$Result = sql_query($Query);

	$i = 0;
	IF($Row=sql_fetch_array($Result))
	{
		UNSET($sms_msg);

		$pidx		=	$Row["pidx"];
		$title		=	$Row["title"];
		$product	=	stripslashes($Row["product"]);
		$content	=	stripslashes($Row["content"]);
		$reg_time	=	$Row["reg_time"];

		$Qm = "SELECT midx, cphone FROM cf_product_admin_user WHERE recyn='Y'";
		$Rm = sql_query($Qm);

		$i = 0;
		WHILE($Rowm=sql_fetch_array($Rm))
		{
			$midx		=	$Rowm["midx"];
			$cphone		=	$Rowm["cphone"];

			$Q2 = "INSERT INTO cf_product_admin_report_send
				   (pidx,midx,send_time,reg_time,end_time,ipaddr,sendyn)
				   VALUES
				   ('".$pidx."','".$midx."',".$intTime.",0,0,'','".$strSendYn."');";

			sql_query($Q2);

			//ECHO $_admin_sms_number."--".$title."--".$product."--".$cphone."--".$midx."--".$intTime."--".DATE("Y-m-d H:i:s",TIME())."--".DATE("Y-m-d H:i:s",(TIME()+600))."<BR>";

			$sms_msg = $title."\n\n";
			$sms_msg .= $product."\n\n";
			$sms_msg .= "https://www.hellofunding.co.kr/hello_report/?RT=".$intTime.$midx;

			//$cphone = "010-2333-4749";
			IF($strSendYn == "")	// 새벽시간에는 발송하지 않음.   cron설정 /home/crowdfund/schedule_work/hello_status_smssend_recommend.php 오전7시 실행  sendyn이 1인것만
			{
				unit_sms_send($_admin_sms_number, $cphone, $sms_msg, DATE("Y-m-d H:i:s",$intTime+600));
			}
			//unit_sms_send($_admin_sms_number, $cphone, $sms_msg);
			$i++;
		}
		IF($i > 0)
		{
			sql_free_result($Rm);
		}
		sql_free_result($Result);
	}
}

function fn_hello_status_smssend_scf($report_idx) {

	$_admin_sms_number = "15886760";

	$intTime = TIME();
	$dtmH	 = DATE("H");
	$strSendYn = "";

	IF($dtmH >= "00" AND $dtmH <= "06") {
		$strSendYn = "1";	// SMS 발송대기 플래그
	}

	$Query = "SELECT pidx, title, product, content,reg_time FROM cf_product_admin_report WHERE pidx='".$report_idx."'";
	$Result = sql_query($Query);

	$i = 0;

	if ($Row=sql_fetch_array($Result)) {

		UNSET($sms_msg);

		$pidx		=	$Row["pidx"];
		$title		=	$Row["title"];
		$product	=	stripslashes($Row["product"]);
		$content	=	stripslashes($Row["content"]);
		$reg_time	=	$Row["reg_time"];

		$Qm = "SELECT midx, cphone FROM cf_product_admin_user WHERE recyn='Y'";
	//$Qm = "SELECT midx, cphone FROM cf_product_admin_user WHERE (cphone='010-8624-6176' or cphone='010-8894-4740') ";
		$Rm = sql_query($Qm);

		$i = 0;

		WHILE($Rowm=sql_fetch_array($Rm)) {

			$midx		=	$Rowm["midx"];
			$cphone		=	$Rowm["cphone"];

			$Q2 = "INSERT INTO cf_product_admin_report_send
				   (pidx,midx,send_time,reg_time,end_time,ipaddr,sendyn)
				   VALUES
				   ('".$pidx."','".$midx."',".$intTime.",0,0,'','".$strSendYn."');";

			sql_query($Q2);

			ECHO $_admin_sms_number."--".$title."--".$product."--".$cphone."--".$midx."--".$intTime."--".DATE("Y-m-d H:i:s",TIME())."--".DATE("Y-m-d H:i:s",(TIME()+600))."<BR>";

			$sms_msg = $title."\n\n";
			//$sms_msg .= $product."\n\n";
			$sms_msg .= "https://www.hellofunding.co.kr/hello_report/scf_report.php?RT=".$intTime.$midx;

			//$cphone = "010-8624-6176";  // 전승찬
			//$cphone = "010-8894-4740";  // 이상규
			IF($strSendYn == "")	// 새벽시간에는 발송하지 않음.   cron설정 /home/crowdfund/schedule_work/hello_status_smssend_recommend.php 오전7시 실행  sendyn이 1인것만
			{
				unit_sms_send($_admin_sms_number, $cphone, $sms_msg, DATE("Y-m-d H:i:s",$intTime+600));
				//unit_sms_send($_admin_sms_number, $cphone, $sms_msg);
			}
			//unit_sms_send($_admin_sms_number, $cphone, $sms_msg);
			$i++;
		}

		IF($i > 0) {
			sql_free_result($Rm);
		}
		sql_free_result($Result);
	}

}
function fn_cf_product_admin_report_scf($today) {


	if (!$today) $today = date("Y-m-d");

	$chk_sql = "SELECT COUNT(*) scf_not_end FROM cf_product WHERE category='3' AND start_date='$today' AND invest_end_date=''";
	$chk_row = sql_fetch($chk_sql);

	$psql = "SELECT SUM(recruit_amount) sum_recruit_amount, count(idx) sum_idx FROM cf_product WHERE  category='3' AND start_date='$today'";
	$prow = sql_fetch($psql);
	$recruit_total_amount = $prow["sum_recruit_amount"];
	$recruit_total_prd = $prow["sum_idx"];

	$s_sql = "SELECT count";

	$sql = "
		SELECT
			B.mb_id, B.mb_name, B.mb_co_name, B.member_type, B.member_investor_type,
			A.idx as inv_idx, A.member_idx, A.amount, A.is_advance_invest, A.syndi_id AS flatform_id, A.first_inv,
			(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_count,
			(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.member_idx AND invest_state='Y') AS total_invest_amount,
			(SELECT is_auto_invest FROM cf_product_invest_detail WHERE invest_idx=A.idx ORDER BY idx DESC LIMIT 1) AS is_auto_invest,
			(SELECT amount FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='1') AS auto_invest_amount
		FROM
			cf_product_invest A
		LEFT JOIN
			g5_member B  ON A.member_idx = B.mb_no
		LEFT JOIN
			cf_product C  ON A.product_idx = C.idx
		WHERE (1)
			AND C.category='3' AND C.start_date='$today'
			AND A.invest_state='Y'
			$where_plus
		ORDER BY
			A.amount DESC";
	//echo $sql;
	$res  = sql_query($sql);
	$rows = sql_num_rows($res);

	for($i=0; $i<$rows; $i++) {

		$LIST[$i] = sql_fetch_array($res);

		////////////////////////////////////
		// 전체 현황
		////////////////////////////////////
		$TOTAL['COUNT'] += 1;
		$TOTAL['AMOUNT'] += $LIST[$i]['amount'];
		if($LIST[$i]['is_auto_invest']=='1') {
			$TOTAL['AUTO_INVEST_AMOUNT'] += $LIST[$i]['auto_invest_amount'];
		}

		if($LIST[$i]['member_type']=='2') {
			$TOTAL['M2_COUNT'] += 1;
			$TOTAL['M2_AMOUNT'] += $LIST[$i]['amount'];
		}
		else {
			$TOTAL['M1_COUNT'] += 1;
			$TOTAL['M1_AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_investor_type']=='2') {
				$TOTAL['M12_COUNT'] += 1;
				$TOTAL['M12_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['member_investor_type']=='3') {
				$TOTAL['M13_COUNT'] += 1;
				$TOTAL['M13_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL['M11_COUNT'] += 1;
				$TOTAL['M11_AMOUNT'] += $LIST[$i]['amount'];
			}
		}

		if($LIST[$i]['flatform_id']=='finnq') {
			$TOTAL['M3_COUNT'] += 1;
			$TOTAL['M3_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='hktvwowstar') {
			$TOTAL['M32_COUNT'] += 1;
			$TOTAL['M32_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='chosun') {
			$TOTAL['M33_COUNT'] += 1;
			$TOTAL['M33_AMOUNT'] += $LIST[$i]['amount'];
		}
		else if($LIST[$i]['flatform_id']=='oligo') {
			$TOTAL['M34_COUNT'] += 1;
			$TOTAL['M34_AMOUNT'] += $LIST[$i]['amount'];
		}

		////////////////////////////////////
		// 최초 투자자 현황 데이터
		////////////////////////////////////
		//if($LIST[$i]['total_invest_count']==1) {
		if($LIST[$i]['first_inv']=="Y") {

			$TOTAL_A['COUNT'] += 1;
			$TOTAL_A['AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_type']=='2') {
				$TOTAL_A['M2_COUNT'] += 1;
				$TOTAL_A['M2_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_A['M1_COUNT'] += 1;
				$TOTAL_A['M1_AMOUNT'] += $LIST[$i]['amount'];

				if($LIST[$i]['member_investor_type']=='2') {
					$TOTAL_A['M12_COUNT'] += 1;
					$TOTAL_A['M12_AMOUNT'] += $LIST[$i]['amount'];
				}
				else if($LIST[$i]['member_investor_type']=='3') {
					$TOTAL_A['M13_COUNT'] += 1;
					$TOTAL_A['M13_AMOUNT'] += $LIST[$i]['amount'];
				}
				else {
					$TOTAL_A['M11_COUNT'] += 1;
					$TOTAL_A['M11_AMOUNT'] += $LIST[$i]['amount'];
				}
			}

			if($LIST[$i]['flatform_id']=='finnq') {
				$TOTAL_A['M3_COUNT'] += 1;
				$TOTAL_A['M3_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='hktvwowstar') {
				$TOTAL_A['M32_COUNT'] += 1;
				$TOTAL_A['M32_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='chosun') {
				$TOTAL_A['M33_COUNT'] += 1;
				$TOTAL_A['M33_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='oligo') {
				$TOTAL_A['M34_COUNT'] += 1;
				$TOTAL_A['M34_AMOUNT'] += $LIST[$i]['amount'];
			}

		}

		////////////////////////////////////
		// 기존 투자자 현황 데이터
		////////////////////////////////////
		else {

			$TOTAL_B['COUNT'] += 1;
			$TOTAL_B['AMOUNT'] += $LIST[$i]['amount'];

			if($LIST[$i]['member_type']=='2') {
				$TOTAL_B['M2_COUNT'] += 1;
				$TOTAL_B['M2_AMOUNT'] += $LIST[$i]['amount'];
			}
			else {
				$TOTAL_B['M1_COUNT'] += 1;
				$TOTAL_B['M1_AMOUNT'] += $LIST[$i]['amount'];

				if($LIST[$i]['member_investor_type']=='2') {
					$TOTAL_B['M12_COUNT'] += 1;
					$TOTAL_B['M12_AMOUNT'] += $LIST[$i]['amount'];
				}
				else if($LIST[$i]['member_investor_type']=='3') {
					$TOTAL_B['M13_COUNT'] += 1;
					$TOTAL_B['M13_AMOUNT'] += $LIST[$i]['amount'];
				}
				else {
					$TOTAL_B['M11_COUNT'] += 1;
					$TOTAL_B['M11_AMOUNT'] += $LIST[$i]['amount'];
				}
			}

			if($LIST[$i]['flatform_id']=='finnq') {
				$TOTAL_B['M3_COUNT'] += 1;
				$TOTAL_B['M3_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='hktvwowstar') {
				$TOTAL_B['M32_COUNT'] += 1;
				$TOTAL_B['M32_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='chosun') {
				$TOTAL_B['M33_COUNT'] += 1;
				$TOTAL_B['M33_AMOUNT'] += $LIST[$i]['amount'];
			}
			else if($LIST[$i]['flatform_id']=='oligo') {
				$TOTAL_B['M34_COUNT'] += 1;
				$TOTAL_B['M34_AMOUNT'] += $LIST[$i]['amount'];
			}
		}

	}


	$m=substr($today,5,2)*1;
	$d=substr($today,8,2)*1;

	$strTitle = "SCF 상품 투자요약 ($m/$d)";

	$strContent = "

	<html>
	<head>
	<title>확정매출채권 상품 투자 요약보고</title>
	<meta name='viewport' content='width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes'>
	<meta name='mobile-web-app-capable' content='yes'>
	<meta name='apple-mobile-web-app-capable' content='yes'>
	<link href='https://fonts.googleapis.com/css?family=Nanum+Gothic:400,700,800&subset=korean' rel='stylesheet'>
	<link href='/css/report.css?ver=".DATE('YmdHis')."' rel='stylesheet'>
	</head>
	<body>

	<div class='content_guide' style='width:90%;'>

		<table class='tb1_guide'>
		<tr>
			<th class='tb1_title_area' colspan='3'>".$strTitle."</th>
		</tr>
		<tr>
			<th class='th_33'>모집금액</th>
			<td class='td_int_area td_gray fb'>".number_format($recruit_total_prd)."건</td>
			<td class='td_int_area td_gray fb'>".price_cutting($recruit_total_amount)."원</td>
		</tr>

		<tr>
			<th class='th_33'>전체투자현황</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['AMOUNT'])."원</td>
		</tr>

		<tr>
			<th class='th_33'>법인투자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M2_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M2_AMOUNT'])."원</td>
		</tr>

		<tr>
			<th class='th_33'>개인투자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL['M1_COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL['M1_AMOUNT'])."원</td>
		</tr>

		<tr>
			<th class='th_deep_gray th_33'>개인-일반</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M11_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M11_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_deep_gray th_33'>개인-소득</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M12_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M12_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_deep_gray th_33'>개인-전문</th>
			<td class='td_int_area td_gray'>".NUMBER_FORMAT($TOTAL['M13_COUNT'])."건</td>
			<td class='td_int_area td_gray'>".price_cutting($TOTAL['M13_AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>최초투자자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL_A['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL_A['AMOUNT'])."원</td>
		</tr>
		<tr>
			<th class='th_33'>기존투자자</th>
			<td class='td_int_area'>".NUMBER_FORMAT($TOTAL_B['COUNT'])."건</td>
			<td class='td_int_area'>".price_cutting($TOTAL_B['AMOUNT'])."원</td>
		</tr>
		</table>

		<table class='tb2_guide'>
		<tr>
			<th class='tb1_title_area' colspan='8'>투자 상세내역</th>
		</tr>
		<tr>
			<th class='th_5'>NO</th>
			<th>업체명<br />/성명</th>
			<th>투자자<br />유형</th>
			<!--th>투자처</th-->
			<th>투자금액</th>
			<!--th>투자<br />형태</th-->
			<th>누적<br />투자수</th>
			<th>누적<br />투자액</th>
		</tr>";

	FOR($i=0,$j=1,$lnum=21; $i<$rows; $i++,$j++,$lnum++) {
		$name = ($LIST[$i]['member_type']=='2') ? $LIST[$i]['mb_co_name'] : $LIST[$i]['mb_name'];
		if($LIST[$i]['member_type']=='2') {
			$member_type = '법인';
		}
		else {
			if($LIST[$i]['member_investor_type']=='3')  $member_type = '전문';
			else if($LIST[$i]['member_investor_type']=='2') $member_type = '소득적격';
			else $member_type = '개인';
		}
		if($LIST[$i]['flatform_id']=='finnq') {
			$flatform = '핀크';
		}
		else if($LIST[$i]['flatform_id']=='finnq') {
			$flatform = '한경';
		}
		else if($LIST[$i]['flatform_id']=='oligo') {
			$flatform = '올리고';
		}
		else if($LIST[$i]['flatform_id']=='kakaopay') {
			$flatform = '카카오페이';
		}
		else {
			$flatform = '헬로';
		}
		$invest_gubun = ($LIST[$i]['is_auto_invest']=='1') ? '자동투자' : '일반투자';

	$strContent .= "
		<tr>
			<td class='td_txt_area'>".$j."</td>
			<td class='td_txt_area'>".$name."</td>
			<td class='td_txt_area'>".$member_type."</td>
			<!--td class='td_txt_area'>".$flatform."</td-->
			<td class='td_int_area'>".price_cutting($LIST[$i]['amount'])."원</td>
			<!--td class='td_txt_area'>".$invest_gubun."</td-->
			<td class='td_int_area'>".number_format($LIST[$i]['total_invest_count'])."건</td>
			<td class='td_int_area'>".price_cutting($LIST[$i]['total_invest_amount'])."원</td>
		</tr>";

		}
	$strContent .= "</table>
	</div>
	<br/><br/>

	</body>
	</html>
	";

	$pidx_rep = "SCF_".date("Ymd");

	$Query = "INSERT INTO
						   cf_product_admin_report
						   (product_idx, title, product, content, reg_time)
						   VALUES
						   ('$pidx_rep','".addslashes($strTitle)."','$pidx_rep','".addslashes($strContent)."',now())";
	sql_query($Query);

	echo $strContent;

	return sql_insert_id();
}


class Hello_Banner
{
	public $CODE;
	public $Ndate;

	Public  function __construct()
	{
		$this->Ndate = DATE("Y-m-d");
	}

	Public  function __destruct()
	{
	}

	Public Function RsContent()
	{
		$strTable = "cf_banner";
		$strWhere = " WHERE cfcode='".$this->CODE."' AND recyn='Y' AND (sdate<='".$this->Ndate."' AND edate>='".$this->Ndate."')";

		$strColumn = "repimg, mrepimg, targeturl, targetlink";
		$strQuery = "SELECT ".$strColumn." FROM ".$strTable.$strWhere." ORDER BY sort_id ASC";

		$strResult = sql_query($strQuery);
		$i = 0;
		WHILE($strRow=sql_fetch_array($strResult))
		{
			$repimg			= $strRow["repimg"];
			$mrepimg		= $strRow["mrepimg"];
			$targeturl		= $strRow["targeturl"];
			$targetlink		= $strRow["targetlink"];

			$retval[] = ARRAY(
							"repimg"		=>	$repimg,
							"mrepimg"		=>	$mrepimg,
							"targeturl"		=>	$targeturl,
							"targetlink"	=>	$targetlink
					  );

			$i++;
		}
		IF($i > 0)
		{
			sql_free_result($strResult);
		}
		return $retval;

	}

	Public Function RsSection()
	{
		$retval = ARRAY(
						ARRAY("0001","메인상단"),
						ARRAY("0002","메인중간2"),
						ARRAY("0003","메인중간3"),
						ARRAY("0005","메인중간4"),
						ARRAY("0004","상품상세 이벤트 1번"),
						ARRAY("0006","상품상세 이벤트 2번")
			       );
		return $retval;
	}

	Public Function FnRecyn()
	{
		$retval = ARRAY(
						ARRAY("Y","노출"),
						ARRAY("N","비노출")
			       );
		return $retval;
	}

	Public Function FnTarget()
	{
		$retval = ARRAY(
						ARRAY("_self","본창"),
						ARRAY("_blank","새창"),
						ARRAY("_opener","부모창")
			       );
		return $retval;
	}
}

Class Event_Board
{
	public $DB_RESULT;
	public $ndate;

	Public  function __construct()
	{
		$this->ndate = DATE("Y-m-d");
	}

	Public  function __destruct()
	{
	}

	Public Function RsCount()
	{
		$row =	sql_num_rows($this->DB_RESULT);
		return $row;
	}

	Public Function FnList($strSearch, $page, $strColumn)
	{
		$strTable	=	"hello_board";

		IF($strSearch["STXT"]) {
			IF($strWhere) {  $strWhere .= " AND "; } ELSE { $strWhere = " WHERE "; }
			$strWhere .= "(title LIKE '%".add_str($strSearch["STXT"])."%' OR content LIKE '%".add_str($strSearch["STXT"])."%')";
		}
		IF($strSearch["SC"]) {
			IF($strSearch["SC"] <> "A")
			{
				IF($strWhere) {  $strWhere .= " AND "; } ELSE { $strWhere = " WHERE "; }
				IF($strSearch["SC"] == "Y")
				{
					$strWhere .= " edate>='".DATE("Y-m-d")."' AND mainyn='Y'";
				} ELSEIF($strSearch["SC"] == "N") {
					$strWhere .= " edate<'".DATE("Y-m-d")."'";
				}
			}
		}
		IF($strSearch["SCC"]) {
			IF($strSearch["SCC"] <> "A")
			{
				IF($strWhere) {  $strWhere .= " AND "; } ELSE { $strWhere = " WHERE "; }
				IF($strSearch["SCC"] == "Y")
				{
					$strWhere .= " edate>='".DATE("Y-m-d")."' AND mainmyn='Y'";
				} ELSEIF($strSearch["SCC"] == "N") {
					$strWhere .= " edate<'".DATE("Y-m-d")."'";
				}
			}
		}

		$num_per_page = 15;

		$strOrder	=	"reg_date DESC, sort_id ASC";

		IF(!$page) { $page = 1; }

		$rowList = fr_board_list($strColumn,$strTable,"",$strWhere,$strOrder,"",$num_per_page,"2000",$connect);

		return $rowList;
	}

	Public Function FnListFront($strSearch, $page, $num_per_page, $strColumn)
	{
		$strTable	=	"hello_board";

		$strWhere = " WHERE recyn='Y' AND section='1'";

		IF($strSearch["STXT"]) {
			IF($strWhere) {  $strWhere .= " AND "; } ELSE { $strWhere = " WHERE "; }
			$strWhere .= "(title LIKE '%".add_str($strSearch["STXT"])."%' OR content LIKE '%".add_str($strSearch["STXT"])."%')";
		}
		IF($strSearch["SC"]) {
			IF($strSearch["SC"] <> "A")
			{
				IF($strWhere) {  $strWhere .= " AND "; } ELSE { $strWhere = " WHERE "; }
				IF($strSearch["SC"] == "Y")
				{
					$strWhere .= " sdate<='".DATE("Y-m-d")."' AND edate>='".DATE("Y-m-d")."'";
				} ELSEIF($strSearch["SC"] == "N") {
					$strWhere .= " sdate<='".DATE("Y-m-d")."' AND edate<'".DATE("Y-m-d")."'";
				}
			}
		}
		$strOrder	=	"sort_id ASC, reg_date DESC";

		IF(!$page) { $page = 1; }

		$rowList = fr_board_list2($strColumn,$strTable,"",$strWhere,$strOrder,"",$num_per_page,"2000",$page,$connect);

		return $rowList;
	}

	Public Function FnMainFront($strColumn,$strkind)
	{
		global $num_per_page;

		IF(!$num_per_page)
		{
			$num_per_page = 3;
		}
		$strTable	=	"hello_board";
		$strWhere  = " WHERE recyn='Y' AND section='1'";
		IF($strkind == "pc")
		{
			$strWhere  .= " AND mainyn='Y'";
		} ELSEIF($strkind == "mobile") {
			$strWhere  .= " AND mainmyn='Y'";
		}
		$strWhere .= " AND sdate<='".DATE("Y-m-d")."' AND edate>='".DATE("Y-m-d")."'";
		$strOrder	=	"reg_date DESC";

		IF(!$page) { $page = 1; }

		$rowList = fr_board_list($strColumn,$strTable,"",$strWhere,$strOrder,"",$num_per_page,"2000",$connect);

		return $rowList;
	}

	Function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
	{
		$datetime1 = date_create($date_1);
		$datetime2 = date_create($date_2);

		$interval = date_diff($datetime1, $datetime2);

		$strRet = "";
		IF($date_1 > $date_2)
		{
			$strRet = "-";
		}

		return $strRet.$interval->format($differenceFormat);
	}

	Function StrDateReplace($mdate,$strTar,$strRep)
	{
		$retval = STR_REPLACE($strTar,$strRep,$mdate);
		return $retval;
	}

	Public Function StrKind()
	{
		$retval = ARRAY(
							ARRAY("Y","노출"),
							ARRAY("N","비노출")
					   );
		return $retval;
	}

	Public Function FnTarget()
	{
		$retval = ARRAY(
							ARRAY("_self","본창"),
							ARRAY("_window","새창")
					   );
		return $retval;
	}


	Public Function FnNowState($dtmdate)
	{
		IF($dtmdate == "0000-00-00")
		{
			$retval = "대기";
		} ELSE {
			IF($dtmdate >= $this->ndate)
			{
				$retval = "진행";
			} ELSE {
				$retval = "종료";
			}
		}
		return $retval;
	}

	Public Function FnRepimg($strimg, $obj, $linkurl)
	{
		$strArrImg		=	 EXPLODE("^",$strimg);
		$retval = $linkurl."/".$strArrImg[$obj];

		return $retval;
	}

	Public Function FnView($SEQ, $strColumn)
	{
		FOR($i=0;$i<COUNT($strColumn);$i++)
		{
			${$strColumn[$i]} = "";
		}

		IF($SEQ)
		{
			$strTable	=	"hello_board";

			$strWhere	=	" WHERE idx='".add_str($SEQ)."'";
			$strOrder	=	"idx";
			$intLimit1	=	0;
			$intLimit2	=	1;
			$intStrlen	=	100;

			$rowView = fr_board_view($strColumn,$strTable,"",$strWhere,$strOrder,$intLimit1,$intLimit2,$intStrlen,$connect_db);

			IF($rowView[0]["idx"])
			{
				FOR($i=0;$i<COUNT($strColumn);$i++)
				{
					$strValues[$strColumn[$i]] = $rowView[0][$strColumn[$i]];
				}
			}
		}
		return $strValues;
	}
}

FUNCTION replace_integer($strTitle)
{
	$strTitleVal = preg_replace("/[^0-9]/", "", $strTitle);
	$strTitleVal = TRIM($strTitleVal);
	IF(!$strTitleVal) { $strTitleVal = 0; }

	return $strTitleVal;
}



///////////////////////////////////////////////////////////////////////////////
// CURL 전송 : $url 파라미터에 도메인주소를 직접 입력
///////////////////////////////////////////////////////////////////////////////
function curlTo($url, $method='POST', $data=array() , $detail_url='', $headers='', $returnType='')
{
	global $g5;
	global $_CONF;
	global $member;

	$ret = array();

	IF(!$headers)
	{
		$headers = [
			'Content-Type: application/json; charset=UTF-8'
		];
	}

	$url = $url . $detail_url;


	//////////////////////
	// 로그 기록 시작
	//////////////////////
	$data_json = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$title = '';
	if( preg_match("/product\/kakao_prd_change_status\.php/", $detail_url) ) $title = "상품진행상태변경";

	$logSql = "
		INSERT INTO
			kakaopay_today_log
		SET
			ip      = '".$_SERVER['REMOTE_ADDR']."',
			title   = '".$title."',
			path    = '".$url."',
			referer = '".$_SERVER['HTTP_REFERER']."',
			input   = '".$data_json."',
			mb_id   = '".$member['mb_id']."',
			rdate   = NOW()";
	sql_query($logSql);
	$log_id = sql_insert_id();		// 로그ID


	$json_data = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if ($method=="PUT") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	}
	else if ($method=="DELETE") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	}
	else if ($method=="GET") {
		$get_data = http_build_query($data,'','&');
		$url = $url."?".$get_data;
	}
	else { // POST default
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}

	curl_setopt($ch, CURLOPT_URL, $url);
	$result = curl_exec($ch);

	$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header      = substr($result, 0, $header_size);
	$body        = substr($result, $header_size);

	curl_close($ch);


	$ret["http_code"] = $http_code;
	$ret["head"]      = $header;
	$ret["body"]      = json_decode($body, true);
	$ret["req_url"]   = $url;

	if($returnType=='json') $ret = json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);		// 결과값을 json 으로 받기 원할 경우

	//////////////////////
	// 로그 기록 마무리
	//////////////////////
	$result_json = json_encode($ret,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$logSql = "UPDATE kakaopay_today_log SET output = '".addSlashes($result_json)."', edate = NOW() WHERE idx = '".$log_id."'";
	sql_query($logSql);

	$logDelSql = "DELETE FROM kakaopay_today_log WHERE rdate <= '".date('Y-m-d H:i:s', strtotime('-1 day'))."'";
	sql_query($logDelSql);

	return $ret;

}

///////////////////////////////////////////////////////////////////////////////
// CURL 전송 : $url 파라미터에 도메인주소를 직접 입력
///////////////////////////////////////////////////////////////////////////////
function curlNiceAuth($data=array(), $headers='', $returnType='')
{
	global $g5;
	global $_CONF;
	global $member;

	$method     = 'POST';
	$tmpLog     = true;
	$url        = "https://auth.hellofunding.co.kr";
	$detail_url = "NiceAuth.php";

	$ret = array();

	if(!$headers) {
		$headers = [
			'Content-Type: application/json; charset=UTF-8'
		];
	}

	$url = $url . '/' . $detail_url;

	//////////////////////
	// 로그 기록 시작
	//////////////////////
	$json_data = json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	$title = '나이스본인인증';

	if($tmpLog) {
		$logSql = "
			INSERT INTO
				niceauth_log
			SET
				mb_id   = '".$member['mb_id']."',
				ip      = '".$_SERVER['REMOTE_ADDR']."',
				title   = '".$title."',
				path    = '".$url."',
				referer = '".$_SERVER['HTTP_REFERER']."',
				input   = '".$json_data."',
				rdate   = NOW()";
		sql_query($logSql);
		$log_id = sql_insert_id();		// 로그ID
	}


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if ($method=="PUT") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	}
	else if ($method=="DELETE") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	}
	else if ($method=="GET") {
		$get_data = http_build_query($data,'','&');
		$url = $url."?".$get_data;
	}
	else { // POST default
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}

	curl_setopt($ch, CURLOPT_URL, $url);
	$result = curl_exec($ch);

	$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header      = substr($result, 0, $header_size);
	$body        = substr($result, $header_size);

	curl_close($ch);


	$ret['http_code'] = $http_code;
	$ret['head']      = $header;
	$ret['body']      = $body;  //json_decode($body, true);
	$ret['req_url']   = $url;

	//////////////////////
	// 로그 기록 마무리
	//////////////////////
	if($tmpLog) {
		$result_json = json_encode($ret['body'],JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

		$logSql = "UPDATE niceauth_log SET output = '".addSlashes($result_json)."', edate = NOW() WHERE idx = '".$log_id."'";
		sql_query($logSql);

		$logDelSql = "DELETE FROM niceauth_log WHERE rdate <= '".date('Y-m-d H:i:s', strtotime('-2 hour'))."'";
		sql_query($logDelSql);
	}

	if($returnType=='json') {
		$value = json_encode($ret['body'], JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);		// 결과값을 json 으로 받기 원할 경우
	}
	else {
		$value = $ret['body'];
	}

	return $value;

}


///////////////////////////////////////
// 대상일의 유효일(영업일) 가져오기 (밀거나 땡기거나..)
// before : 1일 전일, after  : 1일 후일
// 함수추가일 : 2020-11-03 배재수
///////////////////////////////////////
function getUsableDate($date, $arg='after') {
	if($date=='') return 0;

	global $CONF;

	$STATIC_HOLYDAY = array('01-01', '03-01', '05-01', '05-05', '06-06', '08-15', '10-03', '10-09', '12-25');

	$yoil_no = date('w', strtotime($date));

	if( in_array($date, $CONF['DYNAMIC_HOLYDAY']) || in_array(substr($date,5,5), $STATIC_HOLYDAY) || in_array($yoil_no, array('0','6')) ) {

		if($arg=='before') {
			$date = date("Y-m-d", strtotime($date . " -1 day"));
		}
		else {
			$date = date("Y-m-d", strtotime($date . " +1 day"));
		}

		return getUsableDate($date, $arg);

	}
	else {

		return $date;

	}

}

// 한글성명 1글자만 마스킹처리
function hanStrMasking($str) {
	if(mb_strlen($str, "utf-8") <= 5) {
		return mb_substr($str, 0, mb_strlen($str, "utf-8")-1) . "*";
	}
	else {
		return $str;
	}
}


function get_product_type($cat, $mor='') {
	if($cat=="1") {
		$prd_type = "동산";
	}
	else if($cat=="2") {
		$prd_type = ($mor=="1") ? "주택담보" : "부동산";
	}
	else if($cat=="3") {
		$prd_type = "확정매출채권";
	}
	else {
		$prd_type = "";
	}
	return $prd_type;
}


// 일별이자 및 수수료의 차수별 합산액의 소수점이하 99999...일 경우 강제로 +1 하여 반환하여줌
function customRoundOff($num) {

	if(!$num) return;

	if( preg_match("/\.9999/", $num) ) {
		$NX = explode('.', $num);
		$num= $NX[0] + 1;
	}

	return $num;

}


// 주어진 금액에서 세액 분리
function getTaxArr($befTaxInterest=0) {

	global $CONF;
	global $member;

	$ARR = array(
		'tax_sum'      => 0,
		'interest_tax' => 0,
		'local_tax'    => 0
	);

	if($member['member_type']=='2') {
		$interest_tax_ratio = $CONF['corp']['interest_tax_ratio'];
		$local_tax_ratio    = $CONF['corp']['local_tax_ratio'];
	}
	else {
		$interest_tax_ratio = $CONF['indi']['interest_tax_ratio'];
		$local_tax_ratio    = $CONF['indi']['local_tax_ratio'];
	}

	if($befTaxInterest > 0) {
		$ARR['interest_tax'] = floor( $befTaxInterest * $interest_tax_ratio / 10 ) * 10;			// 이자소득세 = 이자수익 * 0.25
		$ARR['local_tax']    = floor( $ARR['interest_tax'] * $local_tax_ratio / 10 ) * 10;		// 지방소득세(원단위 절사)
		$ARR['tax_sum']      = $ARR['interest_tax'] + $ARR['local_tax'];
	}

	return $ARR;

}


// 투자확인서 메일 발송 2021-08-28 전승찬
function invest_cfm_mail($invest_idx) {

	global $CONF;
	global $member;


	$invest_sql = "SELECT A.* ,
						  B.start_num , B.title , B.invest_period, B.invest_days, B.invest_return
					 FROM cf_product_invest A
				LEFT JOIN cf_product B ON(A.product_idx=B.idx)
					WHERE A.idx='$invest_idx' AND A.member_idx='".$member["mb_no"]."' AND A.invest_state='Y'";
	$invest_res = sql_query($invest_sql);
	$invest_row = sql_fetch_array($invest_res);

	$product_idx = $invest_row["product_idx"];
	$ho = $invest_row["start_num"];
	$title = $invest_row["title"];
	$amount = $invest_row["amount"];
	if ($invest_row["invest_period"]==1) $gigan = $invest_row["invest_days"]." 일";
	else $gigan = $invest_row["invest_period"]." 개월";
	$sooek = $invest_row["invest_return"];


	$mail_subject = "[헬로펀딩] 투자 확인서";
	$mail_from_name = "(주)헬로펀딩";
	$mail_from_email = "cs@hellofunding.co.kr";
	//$mail_from_email = $CONF['customer_mail'];


	$mail_to_name  = $member["mb_name"];
	$mail_to_email = $member["mb_email"];

	// 임시
	//$mail_to_name = "임금님";
	//$mail_to_email = "jsc6176@hellofunding.co.kr";
	//$mail_to_email = "jsc6176@naver.com";
	//$mail_to_name = "이상규";
	//$mail_to_email = "arpino123@naver.com";

	if ($mail_to_email<>"jsc6176@naver.com" AND $mail_to_email<>"arpino123@naver.com") return;

	$mail_form = '
	<!doctype html>
	<html lang="en">
	 <head>
	  <meta charset="UTF-8">
	  <meta name="Generator" content="EditPlus®">
	  <meta name="Author" content="">
	  <meta name="Keywords" content="">
	  <meta name="Description" content="">
	  <title>Document</title>
	 </head>
	 <body>

	<div id="frameS" style="margin:0 auto;width:802px;background:#fff">

		<div style="width:100%;height:82px;"><img src="https://www.hellofunding.co.kr/images/mail/mail_top.png" width11="802"></div>


		<div style="width:740px;padding:30px;min-height:250px;border-left:1px solid #1a1d28;border-right:1px solid #1a1d28; font-size;14px;border-bottom:0px solid #1a1d28">

			<br><br>

			<span style="color: rgb(0, 0, 0);">[헬로펀딩] '.$ho.'호 상품 투자확인서</span>

			<br><br>

			<span style="color: rgb(0, 0, 0);">
				회원님이 투자하신 '.$ho.'호 상품의 투자확인서를 교부해드립니다.<br/>
				해당 상품의 내용을 충분히 이해하고 투자하였으며<br/>
				이용약관, 연계투자약관에 따른 투자위험을 확인하고 동의하였음을 확인합니다.<br/><br/>

				<b>상품명</b> : '.$title.'<br/>
				<b>투자금액</b> : '.number_format($amount).' 원<br/>
				<b>투자기간</b> : '.$gigan.'<br/>
				<b>예상 투자수익률</b> : 연 '.$sooek.' %<br/>
				<b>투자상품설명</b> : <a href="https://dev2.hellofunding.co.kr/investment/investment.php?prd_idx='.$product_idx.'" target=_blank style="border:1px solid #B7DFFC; background-color:#B7DFFC;color:black; font-size:12px; padding:3px; text-decoration:none; border-radius:3px;">상품설명보기</a><br/>

				<br/>감사합니다.<br/>

			</span>

		</div>

		<div style="width:770px; padding-left:30px; padding-top: 10px; padding-bottom:10px; background-color:#F2F2F2; border:1px solid #1a1d28; border-top:0px;">
			<span style="font-size: 10pt;">본 메일은 온라인투자연계금융업 및 이용자 보호에 관한 법률에 따라 발송되었습니다.</span>
			<br><br/>
			<span style="font-size: 10pt;">
			㈜헬로핀테크<br/>
			서울특별시 강남구 대치동 945-10 KT&G 대치타워 5층<br/>
			1588-6760<br/>
			</span>
		</div>

	</div>

	 </body>
	</html>
	';


	$res = mailer($mail_from_name, $mail_from_email, $mail_to_email, $mail_to_name, $mail_subject, $mail_form, 1);

}


function ResetRcvNo($invest_idx) {
	if($invest_idx=='') return false;

	$rcv_no = 'I' . $invest_idx . '_' . strtoupper(substr(uniqid(),-6));
	return $rcv_no;
}


?>
