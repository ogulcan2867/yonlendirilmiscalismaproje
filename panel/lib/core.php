<?php

define("_MIN_LOG_LEVEL_", 10);

if (file_exists('.git') && false) {
	define("_DIR_SITE_ROOT_", realpath("../asite") . "/");
} else {
	define("_DIR_SITE_ROOT_", realpath("../") . "/");
}

define("_DIR_PANEL_ROOT_", realpath("."), false);
define("_DIR_PANEL_LIBRARY_ROOT_", (!realpath("lib/") ? realpath("../lib/") : realpath("lib/")), false);
define("_DIR_PANEL_CLASSES_ROOT_", (!realpath("lib/classes/") ? realpath("../lib/classes/") : realpath("lib/classes/")), false);

define("_DIR_UPLOADS_", "../uploads/");
define("_DIR_TEMP_UPLOADS_", _DIR_UPLOADS_ . "temp/");
define("_DIR_FILE_UPLOADS_", _DIR_UPLOADS_ . "files/");

// argenova upload dosya izin sabitleri
// fonksyonlarda kullanmak icin
define("ARG_UP_ALL", 0); // tumu
define("ARG_UP_PICTURE", 1); // sadece resimler
define("ARG_UP_DOCUMENT", 2); // word, excel, pdf gibi dosyalar
include_once _DIR_PANEL_LIBRARY_ROOT_ . "/common.php";
require_once _DIR_PANEL_LIBRARY_ROOT_ . "/db.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.core.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.entry.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.query_builder.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.tarih.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.usergroup.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.user.php";
include_once _DIR_PANEL_CLASSES_ROOT_ . "/class.resim.php";
if (file_exists(_DIR_SITE_ROOT_ . '/lib.hook.php')) {
	include_once _DIR_SITE_ROOT_ . "/lib.hook.php";
} else {
	include_once _DIR_PANEL_LIBRARY_ROOT_ . "/lib.hook.php";
}
if (file_exists(_DIR_SITE_ROOT_ . "/layout.php")) {
	include_once _DIR_SITE_ROOT_ . "/layout.php";
}
$R = new panel();

// class.entry icin
define('global_default_entry_get_pictures', false);
define('global_default_entry_get_relatives', false);

function yonlendir($url) {
	if (!headers_sent()) {
		header("Location: {$url}");
	} else {
		die("<script type='text/javascript'>location.href = '{$url}';</script>");
	}
	exit;
}

function guvenlik($var) {
	return htmlspecialchars($var, ENT_QUOTES);
}

function guvenlik_(&$girdi) {
	$girdi = guvenlik($girdi);
}

function fetch_one($tablo, $sart_field, $sart_veri, $doncek_field) {
	try {
		$q = mysql_query(sprintf('select `%4$s` from `%1$s` where `%2$s`="%3$s" limit 1', $tablo, guvenlik($sart_field), guvenlik($sart_veri), $doncek_field));
		if (mysql_num_rows($q) > 0) {
			$r = mysql_fetch_array($q);
			return $r[0];
		}
	} catch (exception $e) {
		
	}
	return false;
}

function fetch($sql) {
	$result = '';
	if (!$tt1 = mysql_query($sql)) {
		return mysql_error();
	}
	if (mysql_num_rows($tt1) > 0)
		$result = mysql_result($tt1, 0);
	mysql_free_result($tt1);
	return $result;
}

function fetch_to_array($sor) {
	try {
		$sorgu = mysql_query($sor);
		$okut = mysql_fetch_array($sorgu);
		return $okut;
	} catch (exception $e) {
		return false;
	}
}

function generateRandomText($count, $rm_similar = false) {
	$chars = array_flip(array_merge(range(0, 9), range('A', 'Z')));
	if ($rm_similar) {
		unset($chars[0], $chars[1], $chars[2], $chars[5], $chars[8], $chars['B'], $chars['I'], $chars['O'], $chars['Q'], $chars['S'], $chars['U'], $chars['V'], $chars['Z']);
	}
	for ($i = 0, $text = ''; $i < $count; $i++) {
		$text .= array_rand($chars);
	}
	return $text;
}

function parse_panel_url($array) {
	$doncek = "";
	foreach ($array as $key => $value) {
		$doncek .= "&{$key}={$value}";
	}
	return "?" . trim($doncek, " &");
}

function echo2(&$data, $default = null) {
	echo(isset($data) ? $data : $default);
}

/*
 * log level 1-10:
 * 	1 yuksek oncelik, 10 dusuk oncelik
 * 
 */

function arg_log($string, $level = 5) {
	if ($level > _MIN_LOG_LEVEL_) {
		return;
	}
	return false;
}

function is_translatable_field($array) {
	return !(($array["tip"] == 0 && in_array($array["alt_tip"], array(1, 2, 3))) || in_array($array["tip"], array(2, 3, 4)));
}

function _trim_permanent_link($girdi) {
	$girdi = htmlspecialchars_decode($girdi, ENT_QUOTES);
	$deg = array("İ", "ç", "ğ", "ı", "ö", "ş", "ü", "Ç", "Ğ", "Ö", "Ş", "Ü", " ", '.', "'", "`", "’", '"');
	$yen = array("i", "c", "g", "i", "o", "s", "u", "c", "g", "o", "s", "u", "-", "-", "-", "-", "-", '-');
	$karakterler = "a b c d e f g h i j k l m n o p q r s t u v w y z x 0 1 2 3 4 5 6 7 8 9 - _";
	$karakterler = explode(" ", $karakterler);
	$girdi = str_replace($deg, $yen, $girdi);
	$girdi = trim(strtolower($girdi));
	$doncek = "";
	for ($dng = 0, $max = strlen($girdi); $dng < $max; $dng++) {
		$curChar = substr($girdi, $dng, 1);
		if (in_array($curChar, $karakterler)) {
			$doncek .= $curChar;
		}
	}
	while (strpos($doncek, "__") !== false) {
		$doncek = str_replace("__", "_", $doncek);
	}
	$doncek = trim($doncek, " _-");
	return $doncek;
}

function set_permalink($modul_id, $x_id, $dil_id, $string) {
	// direk kelime permanentini aliyoruz kenarda birakicaz bunu eger kendisi veya 
	// alt varyasyonlari varsa _0, _1 diye olusturucaz
	$def_permant = _trim_permanent_link($string);
	// eger latin alfabesi degilse default olarak tablo adini atiyoruk
	if (!empty($string) && empty($def_permant)) {
		$def_permant = fetch_one("d_moduller", "id", $modul_id, "tablo_adi");
	}

	// setlenecek olan icin bir degisken, varsayilan permantdan basliyor
	$new_permant = $def_permant;

	// kayit varligini sorguluyoruz
	// kayit false degilse yani diziyse iceride varolan perma bu modulde, bu id'de mi eger oyleyse
	// varsayilan mi, varsayilan degilse de varsayilan yap...
	$exist = fetch_to_array(sprintf('select * from `o_permanents` where `permant`="%1$s" limit 1', $new_permant));
	if ($exist != false) {
		if ($exist["modul_id"] == $modul_id && $exist["x_id"] == $x_id && $exist["dil_id"] == $dil_id) {
			if ($exist["varsayilan"] == 0) {
				mysql_query(sprintf('update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1', $modul_id, $x_id, $dil_id)) or die(mysql_error());
				mysql_query(sprintf('update `o_permanents` set `varsayilan`=1 where `id`=%1$d  limit 1', $exist["id"])) or die(mysql_error());
			}
			return;
		}
	}

	$exist_i = 0;
	while ($exist != false) {
		$exist_i++;
		$new_permant = $def_permant . "_" . $exist_i;
		$exist = fetch_to_array(sprintf('select * from `o_permanents` where `permant`="%1$s" limit 1', $new_permant));
		if ($exist !== false) {
			if ($exist["modul_id"] == $modul_id && $exist["x_id"] == $x_id && $exist["dil_id"] == $dil_id) {
				if ($exist["varsayilan"] == 0) {
					mysql_query(sprintf('update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1', $modul_id, $x_id, $dil_id)) or die(mysql_error());
					mysql_query(sprintf('update `o_permanents` set `varsayilan`=1 where `id`=%1$d  limit 1', $exist["id"])) or die(mysql_error());
				}
				return;
			}
		}
	}
	mysql_query(sprintf('update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1', $modul_id, $x_id, $dil_id)) or die(mysql_error());
	mysql_query(sprintf('insert into `o_permanents` (`modul_id`, `x_id`, `permant`, `dil_id`, `varsayilan`) values(%1$d, %2$d, "%3$s", %4$d, 1)', $modul_id, $x_id, $new_permant, $dil_id)) or die(mysql_error());
}

function get_new_permalink($modul_id, $x_id, $dil_id, $string) {
	// direk kelime permanentini aliyoruz kenarda birakicaz bunu eger kendisi veya 
	// alt varyasyonlari varsa _0, _1 diye olusturucaz
	$def_permant = _trim_permanent_link($string);
	// eger latin alfabesi degilse default olarak tablo adini atiyoruk
	if (!empty($string) && empty($def_permant)) {
		$def_permant = fetch_one("d_moduller", "id", $modul_id, "tablo_adi");
	}

	// setlenecek olan icin bir degisken, varsayilan permantdan basliyor
	$new_permant = $def_permant;

	// kayit varligini sorguluyoruz
	// kayit false degilse yani diziyse iceride varolan perma bu modulde, bu id'de mi eger oyleyse
	// varsayilan mi, varsayilan degilse de varsayilan yap...
	$exist = fetch_to_array(sprintf('select * from `o_permanents` where `permant`="%1$s" limit 1', $new_permant));
	if ($exist != false) {
		if ($exist["modul_id"] == $modul_id && $exist["x_id"] == $x_id && $exist["dil_id"] == $dil_id) {
			if ($exist["varsayilan"] == 0) {
				//mysql_query ( sprintf ( 'update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1' , $modul_id , $x_id , $dil_id ) ) or die ( mysql_error () );
				//mysql_query ( sprintf ( 'update `o_permanents` set `varsayilan`=1 where `id`=%1$d  limit 1' , $exist[ "id" ] ) ) or die ( mysql_error () );
			}
			return $new_permant;
		}
	}

	$exist_i = 0;
	while ($exist != false) {
		$exist_i++;
		$new_permant = $def_permant . "_" . $exist_i;
		$exist = fetch_to_array(sprintf('select * from `o_permanents` where `permant`="%1$s" limit 1', $new_permant));
		if ($exist !== false) {
			if ($exist["modul_id"] == $modul_id && $exist["x_id"] == $x_id && $exist["dil_id"] == $dil_id) {
				if ($exist["varsayilan"] == 0) {
					//mysql_query ( sprintf ( 'update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1' , $modul_id , $x_id , $dil_id ) ) or die ( mysql_error () );
					//mysql_query ( sprintf ( 'update `o_permanents` set `varsayilan`=1 where `id`=%1$d  limit 1' , $exist[ "id" ] ) ) or die ( mysql_error () );
				}
				return $new_permant;
			}
		}
	}
	return $new_permant;
	//mysql_query ( sprintf ( 'update `o_permanents` set `varsayilan`=0 where `modul_id`=%1$d and `x_id`=%2$d and `varsayilan`=1 and `dil_id`=%3$d limit 1' , $modul_id , $x_id , $dil_id ) ) or die ( mysql_error () );
	//mysql_query ( sprintf ( 'insert into `o_permanents` (`modul_id`, `x_id`, `permant`, `dil_id`, `varsayilan`) values(%1$d, %2$d, "%3$s", %4$d, 1)' , $modul_id , $x_id , $new_permant , $dil_id ) ) or die ( mysql_error () );
}

function get_file_extension($file) {
	return @array_pop(explode('.', $file));
}

function get_current_page_url($get_query_string = false) {
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	//var_dump($_SERVER);
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . ($get_query_string ? $_SERVER["REQUEST_URI"] : null);
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"] . ($get_query_string ? $_SERVER["REQUEST_URI"] : null);
	}
	return $pageURL;
}

function unparse_url($parsed_url) {
	$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
	$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
	$pass = ($user || $pass) ? "$pass@" : '';
	$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
	return "$scheme$user$pass$host$port$path$query$fragment";
}

// up level : aktif dizinden kac dizin yukari gidilecek
function process_url($url, $up_level = 0) {
	$temp = parse_url($url);
	//var_dump($temp);
	//echo("<hr>");
	$temp["path"] = !isset($temp["path"]) ? "" : $temp["path"];
	$temp["path"] = rtrim($temp["path"], "/");
	$temp2 = explode("/", $temp["path"]);
	$temp3 = implode("/", array_slice($temp2, 0, count($temp2) - $up_level));
	$temp3 = substr($temp3, strlen($temp3) - 1) != "/" ? $temp3 . "/" : $temp3;
	$temp["path"] = $temp3;
	unset($temp["query"], $temp["fragment"]);
	unset($temp3, $temp2);
	return unparse_url($temp);
	//var_dump($temp2);
	//echo("<hr>");
	//var_dump();
}

function richtext_editor_getvalue($value) {
	$proccessed_link = process_url(get_current_page_url(), 1);
	if (substr($proccessed_link, strlen($proccessed_link) - 1) != "/") {
		$proccessed_link .= "/";
	}
	$proccessed_link .= "uploads/";
	$value = str_replace($proccessed_link, "uploads/", $value);
	return $value;
}

function richtext_editor_setvalue($value) {
	$proccessed_link = process_url(get_current_page_url(), 1);
	if (substr($proccessed_link, strlen($proccessed_link) - 1) != "/") {
		$proccessed_link .= "/";
	}
	$proccessed_link .= "uploads/";
	$value = str_replace(guvenlik("\"uploads/"), guvenlik("\"{$proccessed_link}"), $value);
	$value = str_replace(guvenlik("'uploads/"), guvenlik("'{$proccessed_link}"), $value);
	return $value;
}

function qs($string, $exclude = null, $export = 0) {
	/*
	 * export:	0 = array cikti
	 * 			1 = string cikti
	 */
	if (is_array($exclude)) {
		$exclude_ = array(); // sayfanÄ±n id sini sil
		$exclude_ = array_merge($exclude_, $exclude);
	} elseif (!is_null($exclude)) {
		$exclude_[] = $exclude;
	}


	$narray = array();
	$keys = array_keys($_GET);
	for ($dng = 0, $m = count($_GET); $dng < $m; $dng++) {
		if (in_array($keys[$dng], $exclude_)) {
			continue;
		}
		$narray[$keys[$dng]] = $_GET[$keys[$dng]];
	}

	switch ($export) {
		case 0:
			return $narray;
			break;
		case 1:
			$pos = strpos($string, "?");
			$pos = $pos === false ? strlen($string) : $pos;
			$doncek = substr($string, 0, $pos);
			$keys = array_keys($narray);
			for ($dng = 0, $n = count($narray); $dng < $n; $dng++) {
				$doncek .= ($dng == 0) ? "?" : "&";
				$doncek .= $keys[$dng] . "=" . $narray[$keys[$dng]];
			}
			return $doncek;
			break;
	}
}

function generate_link($modul_id, $x_id, $url = null, $relateds = array(), $dil_id = 0, $hidelast = true) {
	$new_link = array();
	$_SLUGRULES = array();
	$dil_id = $dil_id == 0 ? dil_id : $dil_id;
	/*
	 * $_SLUGRULES olusturma kismi baslangic
	 */
	$slug_rules_query = mysql_query('select * from `o_slug_rules`');
	while ($slug_rules = mysql_fetch_array($slug_rules_query)) {
		$temp_slug = array();
		$temp = explode("/", $slug_rules["permant"]);
		foreach ($temp as $t) {
			if (preg_match('/\{(\w+)\:(\d+)\:(\d+)\}/', $t, $sonuc)) {
				switch ($sonuc[1]) {
					case "modul":
						$temp_slug[] = array("modul", $sonuc[2], $sonuc[3]);
						break;
					default:
						die("arg_err_lib_17");
				}
			} else {
				$temp_slug[] = $t;
			}
		}
		$_SLUGRULES[$slug_rules["id"]] = $temp_slug;
	}
	unset($temp_slug, $temp, $t);
	/*
	 * $_SLUGRULES olusturma kismi bitis
	 */

	$base = process_url(get_site_base_href(), 1);
	foreach ($_SLUGRULES as $k => $slug) {
		$l = $slug[count($slug) - 1];
		if (is_array($l) && $l[0] == "modul" && $l[1] == $modul_id) {
			$fek = "gl_" . $modul_id;
			if (function_exists($fek)) {
				$fek_r = $fek($x_id, $k, $slug, $l);
				if ($fek_r == false) {
					continue;
				}
			}
			$new_link[] = get_permanent_link($modul_id, $x_id, $dil_id);
			if (count($slug) > 1) {
				for ($dng = count($slug) - 2, $m = 0; $dng >= $m; $dng--) {
					//echo "\rdng:" . $dng . "<br />";
					if (is_array($slug[$dng]) && $slug[$dng][0] == "modul") {
						// iliskilendirilmis olanlardan 1 tane getir

						$try_to_get_one_of_relateds_id = fetch(sprintf('select `y_id` from `o_iliskiler` where `x_id`=%1$d and `x_modul_id`=%2$d and `y_modul_id`=%3$d limit 1', $x_id, $modul_id, $slug[$dng][1]));
						if ($try_to_get_one_of_relateds_id == false) {
							if (isset($relateds[$slug[$dng][1]])) {
								$new_link[] = get_permanent_link($slug[$dng][1], $relateds[$slug[$dng][1]], $dil_id);
							}
						} else {
							// todo eger modul kendine 1'den cok bagli ise calismasi yapilacak
							$new_link[] = get_permanent_link($slug[$dng][1], $try_to_get_one_of_relateds_id, $dil_id);
						}
					} else {
						$new_link[] = $slug[$dng];
					}
				}
			}
		}
		//echo "\rc:" . count($new_link) . "<br />";
	}

	if ($hidelast && count($new_link) > 1) {
		unset($new_link[0]);
	}

	$link = $base . implode("/", array_reverse($new_link));

	if (count($new_link) == 0) {
		if ($modul_id == 0 && !empty($url)) {
			$new_link[] = $url;
			$link = $url;
		}
	}
	unset($l, $k, $slug);

	return $link;
}

function get_permanent_link($modul_id, $x_id, $dil_id = 0) {
	$dil_id = $dil_id == 0 ? dil_id : $dil_id;
	$permanent_query = mysql_query(sprintf('select `permant` from `o_permanents` where `modul_id`=%1$d and `x_id`=%2$d and dil_id=%3$d order by varsayilan desc limit 1', $modul_id, $x_id, $dil_id));
	if (mysql_num_rows($permanent_query) > 0) {
		$permant = mysql_fetch_array($permanent_query);
		return $permant[0];
	}
	return false;
}

function get_permanent_detail($permanent) {
	$permanent_query = mysql_query(sprintf('select * from `o_permanents` where `permant`="%1$s" limit 1', $permanent));
	if (mysql_num_rows($permanent_query) > 0) {
		$permant = mysql_fetch_array($permanent_query, 1);
		return $permant;
	}
	return false;
}

function get_site_base_href() {
	$base = "http";
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$base .= "s";
	}
	$base .= "://";
	$base .= $_SERVER["SERVER_NAME"];
	$temp = explode("/", $_SERVER["PHP_SELF"]);
	unset($temp[0], $temp[count($temp)]);
	if (count($temp) > 0) {
		$temp = implode("/", $temp);
		$base .= "/" . $temp . "/";
	}
	if (substr($base, strlen($base) - 1, 1) != "/") {
		$base .= "/";
	}
	return $base;
}

function delTree($dir) {
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? @delTree("$dir/$file") : @unlink("$dir/$file");
	}
	return @rmdir($dir);
}

function dump_messages($dizi) {
	foreach (array_keys($dizi) as $d) {
		dump_message(implode("<br />", $dizi[$d]), $d);
	}
}

// tur :
//	- 0 : basarili (tick)
//	- 1 : uyari (unlem)
//	- 2 : soru (soru isareti)
//	- 3 : eklendi (arti)
//	- 4 : hata (carpi)
function dump_message($string, $tur = 0) {
	$classes = array('note');
	switch ($tur) {
		case 0:
			$classes[] = 'note-success';
			break;
		case 4:
			$classes[] = 'note-danger';
			break;
	}
	printf('<div class="%1$s">%2$s</div>', implode(' ', $classes), $string);
}

function panel_ayarlar($ayar_key) {
	$doncek = fetch(sprintf('select `deger` from `p_ayarlar` where `ayar_key`="%1$s" limit 1', $ayar_key));
	if ($doncek == false) {
		// todo eger bulamadiysa bi de aktif dilde aratacak onu dondurecek son olarak
	}
	return $doncek;
}

function get_mime_types($id) {
	$return = array();
	if ($id == ARG_UP_ALL || $id == ARG_UP_PICTURE) { // resim dosyalari
		$return[] = 'image/gif';
		$return[] = 'image/jpeg';
		$return[] = 'image/pjpeg';
		$return[] = 'image/jpg';
		$return[] = 'image/pjpg';
		$return[] = 'image/png';
	}
	if ($id == ARG_UP_ALL || $id == ARG_UP_DOCUMENT) { // dokuman (word, excel, pdf) dosyalari
		$return[] = 'application/octet-stream';
		$return[] = 'text/plain'; // .txt
		$return[] = 'application/pdf'; // .pdf
		$return[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		$return[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	}
	return $return;
}

$dil_id = isset($_SESSION["panel_dil"]) ? $_SESSION["panel_dil"] : fetch('select id from o_diller where pdil_aktif=1 order by sira asc limit 1');
$_SESSION["panel_dil"] = (int) $dil_id;
$_SESSION["panel_dil"] = $_SESSION["panel_dil"] == 0 ? 1 : $_SESSION["panel_dil"];
define("dil_id", $dil_id);
$_DIL = array();
$sorgu = mysql_query("select * from p_dil_ceviriler group by kelime");
while ($okut = mysql_fetch_array($sorgu)) {
	$_DIL[$okut["kelime"]] = $okut["kelime"];
}

$sorgu = mysql_query("select * from p_dil_ceviriler where dil_id=" . $dil_id);
while ($okut = mysql_fetch_array($sorgu)) {
	$_DIL[$okut["kelime"]] = $okut["anlam"];
}

function ___($index, $islem = 0, $html = false) {
	global $_DIL, $dil_id;
	$index = trim(guvenlik($index));
	if (!isset($_DIL[$index])) {
		if (fetch(sprintf('select count(*) from p_dil_ceviriler where dil_id=%1$d and kelime="%2$s"', $dil_id, $index)) == 0) {
			mysql_query(sprintf('insert into p_dil_ceviriler (dil_id, kelime) values(%1$d, "%2$s")', $dil_id, $index));
		}
	}
	if (isset($_DIL[$index]) && !empty($_DIL[$index])) {
		$temp = $html ? htmlspecialchars_decode($_DIL[$index]) : $_DIL[$index];
		switch ($islem) {
			case 1:
				return strtoupper2($temp);
				break;
			case 2:
				return strtolower2($temp);
				break;
			default:
				return str_replace("\n", "<br />", $temp);
		}
	} else {
		return $index;
	}
}

if (!function_exists('mb_strcmp')) {

	function mb_strcmp($str1, $str2, $enc = 'UTF-8') {
		if (!is_string($str1)) {
			throw new Exception("Param str1[" . gettype($str1) . "] not a string");
		}
		if (!is_string($str2)) {
			throw new Exception("Param str2[" . gettype($str2) . "] not a string");
		}
		$str1 = mb_convert_encoding($str1, $enc);
		$str2 = mb_convert_encoding($str2, $enc);
		return strcmp($str1, $str2) == 0;
	}

}

function table_get_items_json($root_id, $cur_nest_index, $max_nest = 100, $parents = array(), $dil_id = 1) {
	global $goptions, $table_name, $name_field;
	if($dil_id){
		$sorgu = 'and dil_id ='.$dil_id;
	}else{
		$sorgu = '';
	}
	$sql_string = sprintf('select * from `%1$s` where `root_id`=%2$d and `deleted`=0 '.$sorgu.'', $table_name, $root_id);
	$rel_query = mysql_query($sql_string) or die(mysql_error());
	while ($rel = mysql_fetch_array($rel_query)) {
		$option = array();
		$option['id'] = $rel["UID"];
		$option['text'] = $rel[$name_field];
		$option['parents'] = $parents;
		$goptions[] = $option;
		if ($cur_nest_index + 1 < $max_nest) {
			table_get_items_json($rel["UID"], $cur_nest_index + 1, $max_nest, array_merge($parents, array($option['text'])));
		}
	}
}

?>