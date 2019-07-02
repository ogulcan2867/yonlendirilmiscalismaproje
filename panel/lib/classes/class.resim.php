<?php

/*
 * V: 1.0.3
 * */

class resim {

	public $kaynak = null;
	public $hedef = null;
	public $ustuneyaz = false;
	public $kalite = 100;
	public $tur = null;
	private $src = null;
	private $tmp = null;
	private $eskiEn = 0;
	private $eskiBoy = 0;
	private $yeniEn = 0;
	private $yeniBoy = 0;
	private $Imagick = null;
	public $ImagickExists = false;
	public $ImagickChecked = false;

	private function yarat() {
		if (is_null($this->src)) {
			$gis = getimagesize($this->kaynak);
			$width = $gis[0];
			$height = $gis[1];
			$type = $gis["mime"];
			$this->eskiEn = $width;
			$this->eskiBoy = $height;
			$this->tur = $this->turgetir($type);
			if ($this->check_imagick_exist()) {
				$this->Imagick = new Imagick($this->kaynak);
				$this->src = true;
			} else {
				$this->src = $this->turdenresim($this->tur, $this->kaynak);
			}
		}
	}

	private function check_imagick_exist() {
		if ($this->ImagickChecked) {
			return $this->ImagickExists;
		} else {
			$this->ImagickChecked = true;
			$this->ImagickExists = $_SERVER["HTTP_HOST"] == "localhost" ? false : class_exists("Imagick");
			return $this->check_imagick_exist();
		}
	}

	private function turdenresim($tur, $kaynak) {
		switch ($tur) {
			case "jpg" :
				return imagecreatefromjpeg($kaynak);
				break;
			case "gif" :
				return imagecreatefromgif($kaynak);
				break;
			case "png" :
				return imagecreatefrompng($kaynak);
				break;
		}
	}

	public function watermark($dosya, $x = "50%", $y = "50%") {
		$this->yarat();
		// gercek resim init
		$base = !is_null($this->tmp) ? $this->tmp : $this->src;
		$imagewidth = $this->yeniEn != 0 ? $this->yeniEn : $this->eskiEn;
		$imageheight = $this->yeniBoy != 0 ? $this->yeniBoy : $this->eskiBoy;

		// watermark init
		$gis = getimagesize($dosya);
		$watermarkwidth = $gis[0];
		$watermarkheight = $gis[1];
		$watermark = $this->turdenresim($this->turgetir($gis["mime"]), $dosya);
		// artik baslas
		if (is_numeric($x)) {
			$startwidth = $x < 0 ? $imagewidth - $watermarkwidth - $x : $x;
		} else {
			$startwidth = (intval($x) / 100) * ($imagewidth - $watermarkwidth);
		}
		if (is_numeric($y)) {
			$startheight = $y < 0 ? $imageheight - $watermarkheight - (-1 * $y) : $y;
		} else {
			$startheight = (intval($y) / 100) * ($imageheight - $watermarkheight);
		}
		imagecopy($base, $watermark, $startwidth, $startheight, 0, 0, $watermarkwidth, $watermarkheight);
		$this->tmp = $base;
	}

	private function turgetir($mime) {
		switch ($mime) {
			case "image/jpeg" :

			case "image/jpg" :

			case "image/pjpg" :
				return "jpg";
				break;
			case "image/gif" :
				return "gif";
				break;
			case "image/png" :
				return "png";
				break;
		}
	}

	public function kaydet() {
		$this->yarat();
		$kayityeri = ($this->ustuneyaz) ? $this->kaynak : $this->hedef;
		if ($this->check_imagick_exist()) {
			return $this->Imagick->writeImage($kayityeri);
		} else {
			$this->tmp = !is_null($this->tmp) ? $this->tmp : $this->src;
			if ($this->yeniEn == 0) {
				$this->yeniEn = $this->eskiEn;
			}
			if ($this->yeniBoy == 0) {
				$this->yeniBoy = $this->eskiBoy;
			}
			switch ($this->tur) {
				case "jpg" :
					return (imagejpeg($this->tmp, $kayityeri, $this->kalite)) ? true : false;
					break;
				case "png" :
					return (imagepng($this->tmp, $kayityeri, $this->_pngQuality($this->kalite))) ? true : false;
					break;
				case "gif" :
					return (imagegif($this->tmp, $kayityeri, $this->kalite)) ? true : false;
					break;
			}
		}
	}

	public function kopyala() {
		$kayityeri = ($this->ustuneyaz) ? $this->kaynak : $this->hedef;
		$this->yarat();
		if ($this->check_imagick_exist()) {
			return copy($this->kaynak, $kayityeri);
		} else {
			$this->tmp = $this->src;
			try {
				switch ($this->tur) {
					case "jpg" :
						return (imagejpeg($this->tmp, $kayityeri, $this->kalite)) ? true : false;
						break;
					case "png" :
						if ($this->tur == "png" || $this->tur == "gif") {
							imagealphablending($this->tmp, false);
							imagesavealpha($this->tmp, true);
							$transparent = imagecolorallocatealpha($this->tmp, 255, 255, 255, 127);
							imagefilledrectangle($this->tmp, 0, 0, $this->yeniEn, $this->yeniBoy, $transparent);
						}
						return (imagepng($this->tmp, $kayityeri, 0)) ? true : false;
						break;
					case "gif" :
						return (imagegif($this->tmp, $kayityeri, $this->kalite)) ? true : false;
						break;
				}
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}
	}

	private function _pngQuality($kalite) {
		$kalite = floor($kalite / 10) - 1;
		$kalite = $kalite < 0 ? 0 : $kalite;
		//$kalite = 10 - $kalite;
		return $kalite;
	}

	public function goster() {
		if ($this->check_imagick_exist()) {
			switch ($this->tur) {
				case "jpg" :
					header('Content-type: image/jpeg');
					break;
				case "png" :
					header('Content-type: image/png');
					break;
				case "gif" :
					header('Content-type: image/gif');
					break;
			}
			echo $this->Imagick;
		} else {
			switch ($this->tur) {
				case "jpg" :
					header('Content-type: image/jpeg');
					imagejpeg($this->tmp, null, $this->kalite);
					break;
				case "png" :
					header('Content-type: image/png');
					imagepng($this->tmp, null, 0);
					break;
				case "gif" :
					if ($this->tur == "png" || $this->tur == "gif") {
						imagealphablending($this->tmp, false);
						imagesavealpha($this->tmp, true);
						$transparent = imagecolorallocatealpha($this->tmp, 255, 255, 255, 127);
						imagefilledrectangle($this->tmp, 0, 0, $this->yeniEn, $this->yeniBoy, $transparent);
					}
					header('Content-type: image/gif');
					imagegif($this->tmp, null, $this->kalite);
					break;
			}
		}
	}

	public function boyutlandir($en, $boy) {
		$this->yarat();
		$this->yeniEn = $en;
		$this->yeniBoy = $boy;
		if ($this->check_imagick_exist()) {
			return $this->Imagick->resizeImage($this->yeniEn, $this->yeniBoy, imagick::FILTER_LANCZOS, 1, false);
		} else {
			//echo("<br />resim::boyutlandir : en={$en}, boy={$boy}<br />");
			if ($this->tmp = imagecreatetruecolor($en, $boy)) {
				if ($this->tur == "png" || $this->tur == "gif") {
					imagealphablending($this->tmp, false);
					imagesavealpha($this->tmp, true);
					$transparent = imagecolorallocatealpha($this->tmp, 255, 255, 255, 127);
					imagefilledrectangle($this->tmp, 0, 0, $this->yeniEn, $this->yeniBoy, $transparent);
				}
				if (imagecopyresized($this->tmp, $this->src, 0, 0, 0, 0, $this->yeniEn, $this->yeniBoy, $this->eskiEn, $this->eskiBoy)) {
					$this->eskiEn = $en;
					$this->eskiBoy = $boy;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}

	public function orantili_boyutlandir($minen, $minboy) {
		$this->yarat();
		$en = $this->eskiEn;
		$boy = $this->eskiBoy;
		if ($en < $minen && $boy < $minboy) {
			return false;
		} elseif ($en == $boy) {
			if ($minboy > $minen) {
				$boy = $minen;
			} else {
				$boy = $minboy;
			}
			$en = $boy;
			return $this->boyutlandir($en, $boy);
		} else {

			if ($en > $boy) {
				$ratio = $en / $boy;
				if ($ratio > 1.5) {
					$boy = $minen / $ratio;
					$en = $minen;
				} else {
					$en = $minboy * $ratio;
					$boy = $minboy;
				}
			} else {
				$ratio = $boy / $en;
				if ($ratio < 1.5) {
					$en = $minboy / $ratio;
					$boy = $minboy;
				} else {
					$boy = $minen * $ratio;
					$en = $minboy;
				}
			}
			return $this->boyutlandir($en, $boy);
		}
	}

	public function kirp($x, $y, $en, $boy) {
		$this->yarat();
		$this->yeniEn = $en;
		$this->yeniBoy = $boy;
		if ($this->check_imagick_exist()) {
			$this->Imagick->cropImage($this->yeniEn, $this->yeniBoy, $x, $y);
		} else {
			if (!$this->tmp = imagecreatetruecolor($this->yeniEn, $this->yeniBoy)) {
				return false;
			} else {
				if ($this->tur == "png" || $this->tur == "gif") {
					imagealphablending($this->tmp, false);
					imagesavealpha($this->tmp, true);
					$transparent = imagecolorallocatealpha($this->tmp, 255, 255, 255, 127);
					imagefilledrectangle($this->tmp, 0, 0, $this->yeniEn, $this->yeniBoy, $transparent);
				}
				if (imagecopyresampled($this->tmp, $this->src, 0, 0, $x, $y, $this->yeniEn, $this->yeniBoy, $en, $boy)) {
					$this->src = $this->tmp;
					$this->eskiEn = $en;
					$this->eskiBoy = $boy;
					return true;
				} else {
					return false;
				}
			}
		}
	}

	public function oldur() {
		if ($this->check_imagick_exist()) {
			$this->Imagick->clear();
			$this->Imagick->destroy();
		} else {
			if (is_resource($this->src)) {
				imagedestroy($this->src);
			}
			if (is_resource($this->tmp)) {
				imagedestroy($this->tmp);
			}
		}
	}

	public function genislik($sayi = null) {
		$this->yarat();
		if (is_null($sayi)) {
			return $this->eskiEn;
		} else {
			$this->yeniEn = $sayi;
		}
	}

	public function yukseklik($sayi = null) {
		$this->yarat();
		if (is_null($sayi)) {
			return $this->eskiBoy;
		} else {
			$this->yeniBoy = $sayi;
		}
	}

	public function filtre($filtre, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null) {
		$this->yarat();
		$this->tmp = $this->src;
		if ($this->tur == "png" || $this->tur == "gif") {
			imagealphablending($this->tmp, false);
			imagesavealpha($this->tmp, true);
			$transparent = imagecolorallocatealpha($this->tmp, 255, 255, 255, 127);
			imagefilledrectangle($this->tmp, 0, 0, $this->yeniEn, $this->yeniBoy, $transparent);
		}
		if (!imagefilter($this->tmp, $filtre, $arg1, $arg2, $arg3, $arg4)) {
			die("filtre uygulanamiyor");
		} else {
			return true;
		}
	}

}

?>