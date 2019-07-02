<?php
	class panel {
		public $includes = array();
		public $dil_id = 0;
		private $ayarlar = array();

		// > smtp ayarları
		public $mailSMTP = 'smtp.yandex.com.tr';
		public $mailPort = 465;
		public $mailUserName = 'bilgi@argenova.com.tr';
		public $mailPassword = 'arge488';
		public $mailFromAddr = "bilgi@argenova.com.tr";
		public $mailFromTitle = "Argenova Test";
		public $mailSecurity = "ssl";
		// smtp ayarları <

		public function __construct() {

		}

		public function init($includes = array()) {
			$includes = array_merge($this->includes, $includes);
		}

		public function get_ayarlar() {
			$sqlString = sprintf('select * from p_ayarlar where (dil_id=0 or dil_id=%d)', $this->dil_id);
			$query = mysql_query($sqlString);
			if ($query && mysql_num_rows($query) > 0) {
				$this->ayarlar = array();
				while ($result = mysql_fetch_array($query)) {
				}
			} else {
				throw new Exception("Ayarlar okunurken hata oluştu.", 8);
			}
		}

	}
?>