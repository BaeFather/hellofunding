<?
include_once('_common.php');
$ori = "";
$bb = masterEncrypt($ori, true);
echo $bb."<br/><br/>";

$imsi = "lcjuajXApns/ykZfSy7tEYf5/JM3Cb5vJXUfOdNfKcgrXp+yf5WZl1WxpUGFzVEZ2U3cG4G5pTin02vMaQtsbs0sCOsbE5+YAnkt3NPAnrCaAzkLUaK9oCmfwpDRjy+kQWJLfN34hPn405/RTE0YrJagixSj5F/gxkYX335V45cNb3sV42q6wWUYWsD8FmpCK+VuRM/JAjRnJEypenFqoePT2wtbql5zlhwhq3wagq6VuNh0rPdQVovh45bbkxT4v8/dZvpioqlWNaXBFy0nHlartpCE9deU/3CGNuLMygFk8TUKcUAuLGrhnB6fZVkH+7TE+eInLFwHTNjV/OeWDg==";
$aa = masterDecrypt($imsi, true);

echo $aa;
?>