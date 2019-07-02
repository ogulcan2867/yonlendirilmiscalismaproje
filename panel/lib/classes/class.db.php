<?php
	class DB {
		public $server = "localhost";
		public $UID = "root";
		public $PW = "";
		public $database_scheme = null;
		public $keep_queries = false;
		public $errors = 2;
		// 0 : kapali; 1: temel; 2: tumu

		private $baglanti = null;
		private $executed_query_count = 0;
		private $executed_queries = array();
		private $query_execute_time = 0;
		private $last_insert_id = 0;
		private $affected_rows = 0;
		private $read_rows = 0;

		private function _hatabas($kod, $m, $l) {
			$hata_seviye = 1;
			switch($kod) {
				case 1045 :
					$mesaj = "Giris reddedildi : '{$this->UID}'@'{$this->server}'";
					$hata_seviye = 1;
					break;
				case 1049 :
					$mesaj = "'{$this->database_scheme}' bulunamadi";
					$hata_seviye = 1;
					break;
				case 4000 :
					$mesaj = "Sorgu calistirilamadi";
					$hata_seviye = 2;
					break;
				default :
					$mesaj = "Bilinmeyen hata kod : {$kod}";
					$hata_seviye = 2;
			}
			if ($this->errors >= $hata_seviye) {
				throw new Exception("{$m} satir {$l} : {$mesaj}");
			}
		}

		public function __construct($server = null, $UID = null, $PW = null, $database_scheme = null) {
			if (!is_null($server) || !is_null($UID) || !is_null($PW) || !is_null($database_scheme)) {
				$this->server = $server;
				$this->UID = $UID;
				$this->PW = $PW;
				$this->database_scheme = $database_scheme;
				$this->connect();
			}
		}

		public function connect() {
			if (!$this->baglanti = mysql_connect($this->server, $this->UID, $this->PW, true)) {
				$this->_hatabas(mysql_errno(), __METHOD__, __LINE__);
			}

			if (!mysql_select_db($this->database_scheme, $this->baglanti)) {
				$this->_hatabas(mysql_errno(), __METHOD__, __LINE__);
			}
		}

		public function query($queryString) {
			$time = explode(' ', microtime());
			$stime = $time[1] + $time[0];
			$sorgu = mysql_query($queryString, $this->baglanti);
			if ($sorgu == false) {
				$this->_hatabas(4000, __METHOD__, __LINE__);
			} else {
				$time = explode(" ", microtime());
				$etime = $time[1] + $time[0];
				$this->last_insert_id = mysql_insert_id();
				$this->query_execute_time = $etime - $stime;
				$this->executed_query_count++;

				$affected_rows = mysql_affected_rows();
				$this->affected_rows = $affected_rows;

				$last_insert_id = mysql_insert_id();
				$this->last_insert_id = $last_insert_id;

				$read_rows = mysql_num_rows($sorgu);
				$this->read_rows = $read_rows;
				if ($this->keep_queries) {
					$this->executed_queries[] = array("query" => $queryString, "affected_rows" => $affected_rows, "last_insert_id" => $last_insert_id, "read_rows" => $read_rows, "exec_time" => $this->query_execute_time);
				}
			}
			unset($stime, $etime, $time);
			return $sorgu;
		}

		public function read(&$obje) {
			if (is_resource($obje)) {
				return mysql_fetch_array($obje);
			}
			return false;
		}

		public function give($queryString, $forceSingleField = false) {
			$arr = $this->read($this->query($queryString));
			if (count($arr) > 2 && !$forceSingleField) {
				return $arr;
			} else {
				return $arr[0];
			}
		}

		public function executed_queries() {
			return $this->executed_queries;
		}

		public function query_execute_time() {
			return round($this->query_execute_time, 4);
		}

		public function executed_query_count() {
			return $this->executed_query_count;
		}

	}
?>