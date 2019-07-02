<?php
class url {
	var $url = null;
	public function __construct($url = null) {
		if(!is_null($url)) {
			$this->url = $url;
		}
	}
	public function extract($url = null) {
		$doncek = array("tur" => null, "domain" => null);
		if(!is_null($url)) {
			$this->url = $url;
		}
		$tempurl = $this->url;
		$turler = array("http://","https://");
		foreach($turler as $tur) {
			if(strpos($tempurl,$tur) == 0) {
				$doncek["tur"] = $tur;
				$tempurl = str_replace($tur," ", $tempurl);
				break;
			}
		}
		$temparray = explode("/", $tempurl);
		$tempurl = $temparray[0];
		$doncek["domain"] = $temparray[0];
		
		
		return $doncek;
	}
}
?>