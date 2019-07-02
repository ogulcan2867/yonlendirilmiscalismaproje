<?php
	/*
	 version 1.0.4
	 */
	class tarih {
		public $gun = 0, $ay = 0, $yil = 0, $saat = 0, $dakika = 0, $saniye = 0;
		public $gunler = array("Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar");
		public $gunler_kisa = array("Pzt","Sal","Çar","Per","Cum","Cmt","Paz");
		public $aylar = array("Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık");
		public $girisformat = "Y-m-d H:i:s";
		public function __construct($format =null) {
			if(!is_null($format)) {
				$this->girisformat = $format;
			}
		}

		public function init($tarih, $format =null) {
			if(!is_null($format)) {
				$this->girisformat = $format;
			}
			if(is_numeric($tarih)) {
				$temp = $this->tarihParse_("d-m-Y H:i:s", date("d-m-Y H:i:s", $tarih));
			} else {
				$temp = $this->tarihParse_($this->girisformat, $tarih);
			}
			$this->gun = intval($temp["gun"]);
			$this->ay = intval($temp["ay"]);
			$this->yil = intval($temp["yil"]);
			$this->saat = intval($temp["saat"]);
			$this->dakika = intval($temp["dakika"]);
			$this->saniye = intval($temp["saniye"]);
		}

		private function tarihParse_($stFormat, $stData) {
			$aDataRet = array();
			$aDataRet['gun'] = 0;
			$aDataRet['ay'] = 0;
			$aDataRet['yil'] = 0;
			$aDataRet['saat'] = 0;
			$aDataRet['dakika'] = 0;
			$aDataRet['saniye'] = 0;
			$aPieces = @split('[:/.\ \-]', $stFormat);
			$aDatePart = @split('[:/.\ \-]', $stData);
			foreach($aPieces as $key => $chPiece) {
				switch ($chPiece) {
					case 'd' :

					case 'j' :
						$aDataRet['gun'] = $aDatePart[$key];
						break;

					case 'F' :

					case 'M' :

					case 'm' :

					case 'n' :
						$aDataRet['ay'] = $aDatePart[$key];
						break;

					case 'o' :

					case 'Y' :

					case 'y' :
						$aDataRet['yil'] = $aDatePart[$key];
						break;

					case 'g' :

					case 'G' :

					case 'h' :

					case 'H' :
						$aDataRet['saat'] = $aDatePart[$key];
						break;

					case 'i' :
						$aDataRet['dakika'] = $aDatePart[$key];
						break;

					case 's' :
						$aDataRet['saniye'] = $aDatePart[$key];
						break;
				}

			}
			return $aDataRet;
		}

		public function toMySQL_Timestamp() {
			return $this->ver("Y-m-d H:i:s");
		}

		public function toPHP_time() {
			return  mktime($this->saat, $this->dakika, $this->saniye, $this->ay, $this->gun, $this->yil);
		}

		public function fromPHP_time($value) {
			$this->init($value);
		}

		public function fromMySQL_Timestamp($value) {
			$this->init($value, "Y-m-d H:i:s");
		}

		public function ver($format) {
			$len = strlen($format);
			$doncek = "";
			for($dng = 0; $dng < $len; $dng++) {
				$parca = substr($format, $dng, 1);
				switch ($parca) {
					case 'W' :
						$doncek .= date("W", $this->toPHP_time());
						break;
					case 'd' :
						$doncek .= $this->ikile($this->gun);
						break;
					case 'l' :
						$doncek .= $this->gunler[date("N", $this->toPHP_time()) - 1];
						break;
					case 'D' :
						$doncek .= $this->gunler_kisa[date("N", $this->toPHP_time()) - 1];
						break;
					case 'N' :
						$doncek .= date("N", $this->toPHP_time()) - 1;
						break;
					case 'j' :
						$doncek .= $this->gun;
						break;

					case 'F' :
						$doncek .= $this->aylar[date("n", $this->toPHP_time()) - 1];
						break;
					case 'M' :

					case 'm' :
						$doncek .= $this->ikile($this->ay);
						break;
					case 'n' :
						$doncek .= $this->ay;
						break;

					case 'o' :

					case 'Y' :
						$doncek .= $this->yil;
						break;
					case 'y' :
						$doncek .= substr($this->yil, 2);
						break;

					case 'g' :

					case 'h' :
						if($this->saat > 11) {
							$x = $this->saat - 12;
						} else {
							$x = $this->saat;
						}
						$doncek .= $parca == "h" ? $this->ikile($x) : intval($x);
						break;

					case 'G' :

					case 'H' :
						$doncek .= $parca == "H" ? $this->ikile($this->saat) : intval($this->saat);
						break;
					case 'i' :
						$doncek .= $this->ikile($this->dakika);
						break;

					case 's' :
						$doncek .= $this->ikile($this->saniye);
						break;
					default :
						$doncek .= $parca;
				}
			}
			return $doncek;
		}

		private function ikile($girdi) {
			if(strlen($girdi) == 1) {
				return "0{$girdi}";
			} else {
				return $girdi;
			}
		}

	}
?>