<?php
include_once(realpath(dirname(__DIR__)).'/common.php');

$list = array();
$device = array();

//회원
$sql = "
SELECT
    a.mb_id, a.lo_ip, a.lo_location, a.lo_url, a.lo_device, a.lo_datetime,
    b.mb_nick, b.mb_name, b.mb_email, b.mb_homepage, b.mb_open, b.mb_point
FROM
    {$g5['login_table']} a
LEFT JOIN
    {$g5['member_table']} b
ON
    a.mb_id=b.mb_id
WHERE (1)
    AND a.mb_id<>'{$config['cf_admin']}'
    AND a.mb_id!=''
ORDER BY
    a.lo_datetime DESC";

$res  = sql_query($sql);
$rows = $res->num_rows;
for($i=0; $i<$rows; $i++) {
    $row1 = sql_fetch_array($res);
    $row1['lo_url'] = get_text($row1['lo_url']);
    $row1['name'] = get_sideview($row1['mb_id'], cut_str($row1['mb_name'], $config['cf_cut_name']), $row1['mb_email'], $row1['mb_homepage']);

    array_push($list, $row1);
}

//비회원
$sql2 = "
    SELECT
        a.mb_id, a.lo_ip, a.lo_location, a.lo_url, a.lo_device, a.lo_datetime
    FROM
        {$g5['login_table']} a
    WHERE
        a.mb_id=''
    ORDER BY
        a.lo_datetime DESC";

$res2  = sql_query($sql2);
$rows2 = $res2->num_rows;

for($i=0; $i<$rows2; $i++) {
    $row2 = sql_fetch_array($res2);
    $row2['lo_url'] = get_text($row2['lo_url']);
    $row2['name'] = '<span style="color:#ccc">unknown</span>';

    array_push($list, $row2);
}

$device['PC']     = sql_fetch("SELECT COUNT(lo_device) AS cnt FROM {$g5['login_table']} WHERE lo_device = 'PC'");
$device['MOBILE'] = sql_fetch("SELECT COUNT(lo_device) AS cnt FROM {$g5['login_table']} WHERE lo_device = 'MOBILE'");
$device['TABLET'] = sql_fetch("SELECT COUNT(lo_device) AS cnt FROM {$g5['login_table']} WHERE lo_device = 'TABLET'");

for($i=0; $i<count($list); $i++) {
    switch($list[$i]['lo_device']) {
        case 'PC'     : $list[$i]['device_icon'] = '<img src="/images/flaticon/pc.png" title="PC"/>';     break;
        case 'MOBILE' : $list[$i]['device_icon'] = '<img src="/images/flaticon/mobile.png"title="MOBILE"/>'; break;
        case 'TABLET' : $list[$i]['device_icon'] = '<img src="/images/flaticon/tablet.png"title="TABLET"/>'; break;
        default       : $list[$i]['device_icon'] = ''; break;
    }
}

$jsonFileName = "currentConnect.json";
$jsonFilePath = G5_DATA_PATH.DIRECTORY_SEPARATOR."current_connect".DIRECTORY_SEPARATOR;

// 경로생성
if (is_dir($jsonFilePath) === false) {
    @mkdir($jsonFilePath, 0707, true);
    $f = @fopen($jsonFilePath.'index.php', 'w');
    @fwrite($f, '');
    @fclose($f);
    @chmod($f, 0644);
    @chmod($jsonFilePath, 0775);
}

// JSON 저장
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: application/json');

if(file_put_contents($jsonFilePath.$jsonFileName, json_encode(array("list"=>$list, "device"=>$device, "member"=>$rows, "no_member"=>$rows2)))) { // JSON 파일저장
	// echo "JSON SUCCESS: " . count($list) . "EA \n";
}

unset($list);
sql_close($g5['connect_db']);

exit;

?>
