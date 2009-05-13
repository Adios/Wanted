<?php 
$base_url = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/';
($_SERVER['REQUEST_METHOD'] != 'POST') && header("Location: $base_url") && exit();

$image = make_poster('../template/1.png', upload_guardian(), guardian());

$path = 'posters/' . md5(uniqid(mt_rand(), true)) . '.png';
(!$fh = fopen($path, 'w')) && die('>.^');
(fwrite($fh, $image) === FALSE) && die('>.^');
fclose($fh);

/* response by accpeted type */

if (strstr($_SERVER['HTTP_ACCEPT'], 'json') || strstr($_SERVER['HTTP_ACCEPT'], 'javascript'))
{
	echo json_encode(Array('path' => $path));
}
else 
{	
	echo <<<SUCCESS
<html>
<head>
	<script type="text/javascript">
		window.top.window.submit_ok('$path')
	</script>
</head>
<body>
	<img src="$path" alt="The Poster." title="Right-click to save." />
</body>
</html>
SUCCESS;
}

/* end */

function upload_guardian()
{
	$path = 'images/unknown.png';
	$file = $_FILES['picture'];
	
	if (empty($file['tmp_name'])) return $path;

	if ($file['size'] > 512000) error_page('You are too big. >/////<');

	if (!getimagesize($file['tmp_name'])) error_page('not an image file.  >.^');

	if (($pos = strrpos($file['name'], '.')) && in_array(strtolower(substr($file['name'], $pos)), Array('.jpg', '.jpeg', '.png')));
	else error_page('the image is not allowed');

	$upload_path = '../stub/' . md5(uniqid(mt_rand(), true));
	if (move_uploaded_file($file['tmp_name'], $upload_path)) $path = $upload_path;
	else error_page('unknown error.');

	return $path;	
}

function guardian()
{
	$conf = $_POST['poster'];
	
	/* existence */
	if (!isset($conf) || !isset($conf['name']) || !isset($conf['reason']) || !isset($conf['money']))
		die('>.^');

	/* length */
	if ((strlen($conf['name']) > 32) || (strlen($conf['reason']) > 64) || (strlen($conf['money']) > 16))
		die('^.<');

	/* escape */
	 
	/* It looks like that imagemagick API handle this correctly */
	//$name = preg_replace('/[^\w\d ]/', '', $conf['name']);
	//$reason = preg_replace('/[^\w\d ]/', '', $conf['reason']);
	
	$name = $conf['name'];
	$reason = $conf['reason'];
	$money = preg_replace('/[\D]/', '', $conf['money']);

	/* digits */
	if (!is_numeric($money)) $money = 0;

	/* inclusion */
	$title = 'WANTED';
	(in_array($conf['title'], Array('WANTED', 'FIND'))) && $title = $conf['title'];

	$action = 'REWARD';
	(in_array($conf['action'], Array('REWARD', 'UNDER', 'SPEND'))) && $action = $conf['action'];

	$content = Array(
		'title' => Array(30, 40, $title),
		'reward' => Array(240, 20, "$action $$money"),
	);	

	(!empty($name)) && $content['name'] = Array(175, 35, $name);
	(!empty($reason)) && $content['reason'] = Array(215, 18, $reason);

	return $content;
}	

function make_poster($template, $figure, $content)
{
	$poster = new Imagick($template);

	foreach ($content as $kind => $c)
		draw_text($poster, $c[0], $c[1], $c[2]);

	draw_figure($poster, $figure, 75);
	if (!strstr($figure, 'unknown'))
		unlink($figure);
	return $poster;
}

function draw_text($poster, $y, $font_size, $text)
{
	$draw = new ImagickDraw();

	$draw->setFont(is_ascii($text) ? '../font/bernhc.ttf' : '../font/ヒラギノ角ゴ StdN W8.otf');

	$draw->setFillOpacity(0.6);
	$draw->setFontSize($font_size);
	$draw->annotation(0, $font_size, $text);	

	$fm = $poster->queryFontMetrics($draw, $text, false);
	$text_im = trample_text_layer($draw, $fm['textWidth'], $fm['textHeight']);

	if ($text_im->getImageWidth() > 160)
		$text_im->scaleImage(160, $text_im->getImageHeight());

	$poster->compositeImage($text_im, imagick::COMPOSITE_OVER, ($poster->getImageWidth() - $text_im->getImageWidth()) / 2, $y);
}

function is_ascii($str)
{
	$max = strlen($str);
	for ($i = 0; $i < $max; $i++)
		if (ord($str[$i]) > 128)
		{
			$str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
			return false;
		}
	return true;
}

function trample_text_layer($draw, $text_width, $text_height)
{
	$im = new Imagick();
	$im->newImage($text_width, $text_height, 'none', 'png');  
	$im->drawImage($draw);

	$noise_layer = $im->clone();
	$noise_layer->addNoiseImage(imagick::NOISE_POISSON);
	$im->compositeImage($noise_layer, imagick::COMPOSITE_DSTIN, 0, 0);

	$im->waveImage(0.3, 30);
	$im->chopImage(0, 2, 0, 0);

	$width = $im->getImageWidth();
	$height = $im->getImageHeight();

	for ($begin = 0; $begin < $width; $begin += $width / 3)
	{
		$begin = floor($begin);
		$portion = $im->getImageRegion(floor($width / 3) - 1, $height, $begin, 0);
		$portion->swirlImage(15);
		$im->compositeImage($portion, imagick::COMPOSITE_REPLACE, $begin, 0);
	}

	$im->blurImage(0.5, 0.1); 

	return $im;
}

function draw_figure($poster, $path, $y)
{
	$im = new Imagick($path);

        $im->modulateImage(100, 0, 100);
        $im->contrastImage(true);
        $im->contrastImage(true);
        $im->contrastImage(true);
        $im->gaussianBlurImage(5, 0.5);


	if ($im->getImageHeight() > 100)
		$im->scaleImage($im->getImageWidth() * 100 / $im->getImageHeight(), 100);
	
	if ($im->getImageWidth() > 125)
		$im->scaleImage(125, $im->getImageHeight() * 125 / $im->getImageWidth());

        $noise_layer2 = new Imagick();
        $noise_layer2->newImage($im->getImageWidth(), $im->getImageHeight(), 'none', 'png');
        $noise_layer2->addNoiseImage(imagick::NOISE_RANDOM);
        $noise_layer2->setImageOpacity(0.5);

        $noise_layer1 = new Imagick();
        $noise_layer1->newImage($im->getImageWidth(), $im->getImageHeight(), 'none', 'png');
        $noise_layer1->addNoiseImage(imagick::NOISE_RANDOM);
        $noise_layer1->modulateImage(100, 0, 100);
        $noise_layer1->setImageOpacity(0.3);

	$x = ($poster->getImageWidth() - $im->getImageWidth()) / 2;
        $poster->compositeImage($im, imagick::COMPOSITE_COLORBURN, $x, $y);
        $poster->compositeImage($noise_layer1, imagick::COMPOSITE_LIGHTEN, $x, $y);
        $poster->compositeImage($noise_layer2, imagick::COMPOSITE_SOFTLIGHT, $x, $y);
}

function error_page($message) 
{
echo <<<FAILURE
<html>
<head>
	<script type="text/javascript">
		window.top.window.error_occured("$message");
	</script>
</head>
<body>
</body>
</html>
FAILURE;

die();
}
?>
