#!/usr/local/php/bin/php -q
<?

set_time_limit(0);

///////////////////////////////////////////////////////////////////////////////
// 회원별 은행예치금 vs 헬로예치금 비교 :: 자료 비교용
// php -q /home/crowdfund/public_html/adm/etc/member_point_check.php [YmdHi]
///////////////////////////////////////////////////////////////////////////////

$path = "/home/crowdfund/public_html";
include_once($path . '/common.cli.php');
include_once(G5_LIB_PATH . "/insidebank.lib.php");

if($_SERVER['argv']['1'] != date('YmdHi')) exit;

/*
$sql = "SELECT mb_id FROM g5_point WHERE LEFT(po_datetime, 7) >= '2020-12' AND mb_id NOT LIKE '%^%' GROUP BY mb_id ORDER BY mb_id";
$res = sql_query($sql);
while($ROW = sql_fetch_array($res)) {
	if($ROW['mb_id']) $MB_ID[] = $ROW['mb_id'];
}
*/

$mb_id = "wasset0503
wat2win
waterkim
wawa1547@naver.com
wax102098@naver.com
waxg31@naver.com
wbc4590
wbeater@empas.com
wcsin1004
Wcsin79
wdwdwd157845
webma01
wenditime@naver.com
werrewr@naver.com
wfrisia
whaite7
whatduw7
whatluck
whdms80@naver.com
whdmswjddlqs@naver.com
whdrbdlek
whghdus17
white11
white5223
white928
white9696@hanmail.net
whitears
whitears486
whiteel1029
whiteleehee
whitewoogi
whkoh777
whlee1231
whoa8@naver.com
whtjrwns123@naver.com
wickedmandoo
widsusan@gmail.com
willy4u@daum.net
wincys1
wind1806@naver.com
winday21
windy0002
winhy333@naver.com
winlike25
winteb
winyo12
wisdom
wisdomsz@hanmail.net
witch62
wizyou
wjdgns2346
wjdrnf4791
wjdwls1013
wjkamui
wjs037999
wjs5125
wjseekd4371
wjsrkf80
wjsthddlsla
wkd3455
wkddnjs33367@naver.com
wkdenfl12
wkdhdls12@nate.com
wkdskfk0419@naver.com
wken125@naver.com
wldhwldk4
wldud9802
wldusdia
wlov4evr
wlqudghks
wlsdl0821@hanmail.net
wlsdl1013@naver.com
wlsgml6635
wlsrlftkd
wlstjddl38
wlstjr96
wltjs6378@gmail.com
wltnguddlekd
wlwl032287
wlwpdms1004
wmk6063@daum.net
wn090707
wn1962
wndmllan
wndms1109
wnsgur0830
wnsydladjd
Wodms0316
wodms7957
wodnjs88
wogns2605
wogns6227
woldomaster
wolfboy32
won03348556naver.com@naver.com
won2606
wonigoo
wonj448
wonjae93
wonju320
wonjun0218
wonminfa
wontaejeong
wonylove@nate.com
woo0505
woo7696
woodbox
woohagi
wookshin6
woon8809
woong.jjw@gmaill.com
woongddoli@gmail.com
woono99
woonoh1203
woony37
woori1007
woori1007123
woosko
wootaeslk@nate.com
wootwj
wow201@paran.com
woyoung2
wpos210@naver.com
wrpdla
ws3551
wsk1478@daum.net
wss720
wsx105
wtkiller
wum423
ww70mj77
www7820
wyin0325
w_sunny91@naver.com
xelloss00
xero99
xeugene
xhappyx0295
xhappyx1004
xhappyx1365
xinc897
xincub
xinxiang
xkfkrdlf1
xkxkfkcl
xmaru76
xmdss2000@naver.com
xo082@naver.com
xogns783
xx891015
xxalcuxx@nate.com
xyeud12
xywwww
y0ung6
y103804@kakao.com
y77lsw
y7t2d533
y7w7FUND
y8957k@hanmail.net
y9528j@naver.com
y9805244@korea.kr
yaban91
yadas22
yagost
yaho05
yak824
yalfhee
yang0618
yangmin365
yanua17
yappgood
ybjin65@hanmail.net
ybkim2030
ybw1525
ybynice
ycn1004@korea.com
yduck5504@naver.com
ydysm2000
ye0913
ye3park@naver.com
yebby103
yee1007@naver.com
yeesha
yelee4978
yelimlee0089
yem1130
yeo0820@naver.com
yeonhwa86
yeonjung84
yeonwj
yeowook@hanmail.net
yeppy8827
yes8082
yeshyun9
yesjung77@hanmail.net
yesmaaan
yesmadam22
yesme84
yeti302
yfreefox
yge0907
ygh0584
yh88614
yhj8901
yhj9569
yhk526
yhsnice
yichy0501
yijihy
yin324
yj0104yj
yj1791@naver.com
yjcg@nate.com
yjcp1024
yjg1204
yjh1977
yjh3026
yjh9781@naver.com
yjinseob
yjk5114
yjmin777
yjoo1213
yjsysysy04
yjw065
yjyb1026
ykbti375
ykking
ykseo@katech.re.kr
yngdk5
yobsmyo
yojaju
yojaju1
yojaju2
yojaju3
yojaju5
yokaze
yoko918
yong535
YONGSUB48
yonyjoa
yoogj3
yoojaeung@nate.com
yoojeong1011
yoolmoo@naver.com
yoon4593
yoonking66
yoonking70
yoonms1021
yoonsong
YOOWOOSUNG
yooyang82
yoshirou21
you48you
you789
yougmaguy
youha61
youlim6868
youn1011
youn740
youn8405
youn8406
young1@mbccb.co.kr
young4623
young730521
youngahkk@naver.com
youngf21c@naver.com
younghye
youngi9
youngin
youngjae80
youngjinic
youngsin1969
youriallee
youtruth01
youyadady@hanmail.net
yoy07028
yoyo99
yr022801
yr4msplee
yr4msplee1
yr570228
ys0825
ys2190
yscandzard
ysgi1223
yshcwh
yshh55
yshstar@naver.com
ysk0203
ystorm2000@naver.com
ytkwon7770
yturyt1
yu5893
yubs16
yujeng98
yujini00
yujinkim0225@gmail.com
yukia16
yum1808@naver.com
yumn12
yun1113
yun6511
Yun9232
yunha624
yunicorn0213
yuniksong2
yunnury
yunnzzang
yunshiri2
yunwoo0707@naver.com
yuriks
yuwonjo
ywkim24
ywlsdk55
ywy810@hanmail.net
ywy810@naver.com
yy1443
yyhykj@naver.com
yyj0691
yyk8115@naver.com
yyminimi
yysjun
yysnice
yyw7000
zaher94
zamong3
zaqazaqaz
zassu2
zay0817
zaza7601516@gmail.com
zcb2468
zcode4uman
zealpuma
zealpuma2
zeraphi
zerone99@naver.com
zerous7@naver.com
zesting@hanmail.net
zetpower@daum.net
zhenji
zina79
zionia777
zizoo98
zkffhtm
zmffjtmxjs
zmshin@daum.net
zodba17
zoolov
zoolov05
zoolov83
zoon425
zpalsl
zpfjqps
zpijfd
zuiren
zuzl15
Zxclgt11
zxcstarzxh
zzanga486@nate.com
zzangdol09
zzangdoll77@naver.com
zzangpkt
zzibba95
zzinaya
zzizim83
zzky1220
zzoo0206
zzzz200
zzzz5315";

$MB_ID = explode("\n", $mb_id);
//print_r($MB_ID); exit;


$list_count = count($MB_ID);
//echo $list_count."\n"; exit;

if( $list_count ) {

	$sdate = date('YmdHi');

	for($i=0; $i<$list_count; $i++) {

		//if($i==3) break;

		$MB_ID[$i] = trim($MB_ID[$i]);

		$sql = "
			SELECT
				A.mb_no, A.mb_id, A.mb_name, A.mb_co_name,
				(SELECT IFNULL(SUM(po_point),0) FROM g5_point WHERE mb_no=A.mb_no) AS hello_point
			FROM
				g5_member A
			WHERE 1
				AND A.mb_id = '".$MB_ID[$i]."'";
		//echo $sql . "\n";
		$R = sql_fetch($sql);



		// 투자대기중인 금액 (기표전)
		$LOCK = sql_fetch("
			SELECT
				IFNULL(SUM(A.amount),0) AS invest_amount
			FROM
				cf_product_invest A
			LEFT JOIN
				cf_product B  ON A.product_idx=B.idx
			WHERE 1
				AND A.member_idx = '".$R['mb_no']."'
				AND A.invest_state = 'Y'
				AND B.state = ''");

		if($R['mb_no']) {

			// 고객 투자정보조회(4100)
			$ARR['REQ_NUM'] = "041";
			$ARR['CUST_ID'] = $R['mb_no'];
			$INSIDEBANK_RESULT = insidebank_request('256', $ARR);

			$diff_amt = $INSIDEBANK_RESULT['BALANCE_AMT'] - $LOCK['invest_amount'] - $R['hello_point'];

			$sqlx = "INSERT INTO member_point_check";
			$sqlx.= " SET sdate = '".$sdate."', mb_no = '".$R['mb_no']."', mb_id = '".$R['mb_id']."', bpoint = '".$INSIDEBANK_RESULT['BALANCE_AMT']."', lockpoint = '".$LOCK['invest_amount']."', hpoint = '".$R['hello_point']."', diff_amt = '".$diff_amt."', dt = NOW()";

			//echo $sqlx."\n";
			echo $i."\n";
			sql_query($sqlx);

			$diff_amt = 0;

		}

	}


}

sql_close();
exit;

?>