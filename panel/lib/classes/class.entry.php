<?php

class entry {

// privates
	private $_pictures = array();
	private $_picture_names = array();
	private $_picture_descriptions = array();
	private $_relatives = array();
	private $_fields = array();
	private $_modul_id = 0;
	private $_x_id = 0;
	private $_dil_id = 0;
// public variables for settings
	public $get_pictures = global_default_entry_get_pictures;
	public $get_relatives = global_default_entry_get_relatives;
// public variable for readings
	public $exist = false;
	public $UID = 0;

	public function reset() {
		$this->_pictures = array();
		$this->_relatives = array();
		//var_dump($this->_fields);
		foreach ($this->_fields as $k => $field) {
			try {
				if (!empty($this->$k)) {
					$this->$k = null;
					unset($this->$k);
				}
			} catch (exception $e) {
				var_dump($e);
			}
		}

		$this->_fields = array();
		$this->exist = false;
	}

	public function __construct($modul_id = 0, $x_id = 0, $dil_id = 0) {
		if ($modul_id != 0) {
			$this->init($modul_id, $x_id, $dil_id);
		}
	}

	public function init($modul_id, $x_id, $dil_id = 0) {
		$this->reset();
// dil id setlenmemis ise varsayilani getir
		if ($dil_id == 0) {
			$dil_id = dil_id;
		}
		$d = fetch_to_array(sprintf('select * from `%1$s` where `UID`=%2$d and `dil_id`=%3$d limit 1', fetch_one("d_moduller", "id", $modul_id, "tablo_adi"), $x_id, $dil_id));
		if ($d != false) {
			$this->exist = true;
			$this->_modul_id = $modul_id;
			$this->_x_id = $x_id;
			$this->_dil_id = $dil_id;

			foreach ($d as $k => $v) {
				$this->_fields[$k] = $v;
				$this->$k = $v;
			}

			if ($this->get_pictures) {
				$this->init_pictures();
			}

			if ($this->get_relatives) {
				$this->init_relatives();
			}
		}
	}

	public function init_from_mysql_array($array, $modul_id) {
		$this->reset();
		$d = $array;
		if ($d != false) {
			$this->exist = true;
			$this->_modul_id = $modul_id;
			$this->_x_id = $d["UID"];
			$this->_dil_id = $d["dil_id"];

			foreach ($d as $k => $v) {
				$this->_fields[$k] = $v;
				$this->$k = $v;
			}

			if ($this->get_pictures) {
				$this->init_pictures();
			}

			if ($this->get_relatives) {
				$this->init_relatives();
			}
		}
	}

	private function init_pictures() {
		$picture_type_ids_query = mysql_query(sprintf('select `id` from `d_modul_resim_tipler` where `modul_id`=%1$d', $this->_modul_id));
		if (mysql_num_rows($picture_type_ids_query) > 0) {
			while ($picture_type_ids = mysql_fetch_array($picture_type_ids_query, MYSQL_NUM)) {
				if (false) { // simdilik burayi es geciyoruz ilerde bakariz
					$picture_sizes_query = mysql_query(sprintf('select `klasor_ad`, `en`, `boy` from `d_modul_resim_boyutlar` where `resim_tip_id`=%1$d order by `en` desc'));
					if (mysql_num_rows($picture_sizes_query) == 0) {
						continue;
					}

					while ($picture_sizes = mysql_fetch_array($picture_sizes_query)) {
						
					}
				}

				$pictures_query = mysql_query(sprintf('select `dosya_adi`, `ad`, `aciklama` from `o_resimler` where `modul_id`=%1$d and `x_id`=%2$d and `tip_id`=%3$d order by `sira` asc', $this->_modul_id, $this->_x_id, $picture_type_ids[0]));
				if (mysql_num_rows($pictures_query) == 0) {
					$this->_pictures[$picture_type_ids[0]] = false;
					continue;
				}
				$this->_pictures[$picture_type_ids[0]] = array();
				$this->_picture_names[$picture_type_ids[0]] = array();
				$this->_picture_descriptions[$picture_type_ids[0]] = array();
				while ($pictures = mysql_fetch_array($pictures_query, MYSQL_ASSOC)) {
					$this->_pictures[$picture_type_ids[0]][] = $pictures["dosya_adi"];
					$this->_picture_names[$picture_type_ids[0]][] = $pictures["ad"];
					$this->_picture_descriptions[$picture_type_ids[0]][] = $pictures["aciklama"];
				}
			}
		}
	}

	public function __get($name) {
		if (!isset($this->$name)) {
			return null;
		}
	}

	private function init_relatives() {
		$relations_query = mysql_query(sprintf('select `relative_to` from `d_modul_iliskiler` where `modul_id`=%1$d', $this->_modul_id));
		while ($relations = mysql_fetch_array($relations_query, MYSQL_NUM)) {
			$relations[0] = (int) $relations[0];
			$this->_relatives[$relations[0]] = array();
			$relatives_query = mysql_query(sprintf('select `y_id` from `o_iliskiler` where `x_modul_id`=%1$d and `x_id`=%2$d and `y_modul_id`=%3$d', $this->_modul_id, $this->UID, $relations[0]));
			while ($relatives = mysql_fetch_array($relatives_query, MYSQL_NUM)) {
				$this->_relatives[$relations[0]][] = (int) $relatives[0];
			}
		}
	}

	public function relations($relative_modul_id) {
		if (!isset($this->_relatives[$relative_modul_id])) {
			return false;
		}
		return $this->_relatives[$relative_modul_id];
	}

	public function relation($relative_modul_id, $index = 0) {
		if (!isset($this->_relatives[$relative_modul_id]) || count($this->_relatives[$relative_modul_id]) == 0) {
			return false;
		}
		return $this->_relatives[$relative_modul_id][$index];
	}

	public function pictures($type_id) {
		if ($this->_pictures[$type_id] == null || $this->_pictures[$type_id] == false) {
			return false;
		}
		$key = key($this->_pictures[$type_id]);
		if ($key === null) {
			if ($this->picture_count($type_id) == 0) {
				return false;
			}
		}
		$r = current($this->_pictures[$type_id]);
		if ($r === null) {
			return false;
		}
		next($this->_pictures[$type_id]);
		return $r;
	}

	public function picture_informations_by_file_name($picture_file_name) {
		$found = false;
		$tp = $this->_pictures;
		reset($tp);
		foreach ($tp as $ci => $ca) {
			$x = array_search($picture_file_name, $ca);
			//var_dump($ca);
			if ($x !== null) {
				$found = true;
				break;
			}
		}
		if ($found) {
			return array("ad" => $this->_picture_names[$ci][$x], "aciklama" => $this->_picture_descriptions[$ci][$x]);
		}
		return false;
	}

	public function picture_reset($type_id) {
		@reset($this->_pictures[$type_id]);
	}

	public function picture_count($type_id) {
		return isset($this->_pictures[$type_id]) && $this->_pictures[$type_id] != false ? (int) count($this->_pictures[$type_id]) : 0;
	}

}

?>