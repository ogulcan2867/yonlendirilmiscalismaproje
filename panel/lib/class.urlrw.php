<?PHP 
function __($girdi){
	global $ayarlar;
	$hz = $ayarlar["sunucu"];
	$ch = explode("?", $girdi);
	$ch2 = explode("&", $ch[1]);
	foreach ($ch2 as $x){
		$temp = explode("=", $x);
		$args[$temp[0]] = $temp[1];
	}
	if ($ch[0] == ""){
		$dosya = "index.php";
	} else {
		$dosya = $ch[0];
	}
	switch ($dosya)	{
		case "index.php":
			if (isset($args["p"])){
					$link = $hz.sql("select permant from sayfalar where id={$args["p"]}").".html";
			}
			break;
	}
	return $link;
}
function tr($girdi){
	/*	$girdi = str_replace("ğ","&#287;",$girdi);
	 $girdi = str_replace("Ğ","&#286;",$girdi);
	 $girdi = str_replace("ı","&#305;",$girdi);
	 $girdi = str_replace("İ","&#304;",$girdi);
	 $girdi = str_replace("ş","&#351;",$girdi);
	 $girdi = str_replace("Ş","&#350;",$girdi);
	 */
	$girdi = str_replace("ç", "c", $girdi);
	$girdi = str_replace("Ç", "C", $girdi);
	$girdi = str_replace("ğ", "g", $girdi);
	$girdi = str_replace("Ğ", "g", $girdi);
	$girdi = str_replace("ı", "i", $girdi);
	$girdi = str_replace("İ", "I", $girdi);
	$girdi = str_replace("ö", "o", $girdi);
	$girdi = str_replace("Ö", "o", $girdi);
	$girdi = str_replace("ş", "s", $girdi);
	$girdi = str_replace("Ş", "S", $girdi);
	$girdi = str_replace("ü", "u", $girdi);
	$girdi = str_replace("Ü", "U", $girdi);
	
	return $girdi;
}
function linkolustur($tur, $veri) {
	global $ayarlar;
	switch ($tur){
		case 0:
			$link = (substr($veri, 0, 4) == "http") ? $veri : $ayarlar["sunucu"].$veri;
			break;
		case 1:
			$link = __("?p={$veri}");
			break;
	}
	return $link;
}
class urldegistir {
	var $base;
	var $yapilar = array();
	var $ext = ".html";
	public function link_olustur($tur,$deg = null) {
		return sprintf($this->yapilar[$tur],$deg);
	}
	public function yapi_ekle($tur,$yapi) {
		$this->yapilar[$tur] = $yapi;
	}
	function getBase() {
		$base = "http://";
		$base .= $_SERVER["SERVER_NAME"];
		$temp = explode("/",$_SERVER["PHP_SELF"]);
		unset($temp[0],$temp[count($temp)]);
		if(count($temp) > 0) {
			$temp = implode("/",$temp);
			$base .= "/" . $temp . "/";
		}
		if(substr($base,strlen($base) - 1,1) != "/") {
			$base .= "/";
		}
		return $base;
	}	
}

?>
