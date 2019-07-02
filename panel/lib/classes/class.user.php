<?php
	class user {
		public $ID = 0;
		public $email = null;
		public $sifre = null;
		public $isim = null;
		public $aktif = 0;
		public $uyeGrupID = 0;
		public $aktivasyonKod = null;
		public $moduller = array();
		public $modulSayfalar = array();
		public $sessionKey = "user_id";

		public function __construct($sessionKey = null) {
			if (!is_null($sessionKey)) {
				$this->sessionKey = $sessionKey;
			}
		}
		
		public function get_byID($userID) {
			return $this->get_userById($userID);
		}
		
		public function get_userById($userID) {
			$sqlString = sprintf('select * from p_uyeler where id=%d limit 1', $userID);
			$query = mysql_query($sqlString);
			if ($query && mysql_num_rows($query) > 0) {
				$result = mysql_fetch_array($query);
				$this->ID = $result["id"];
				$this->email = $result["email"];
				$this->sifre = $result["sifre"];
				$this->isim = $result["isim"];
				$this->aktif = $result["aktif"];
				$this->uyeGrupID = $result["uye_grup_id"];
				unset($result, $query, $sqlString);
			} else {
				throw new Exception("Girmiş olduğunuz ID'ye sahip bir hesap bulunamamıştır", 2);
			}
		}

		public function get_userByEmail($email) {
			$sqlString = sprintf('select * from p_uyeler where aktif=1 and email="%s" limit 1', guvenlik($email));
			$query = mysql_query($sqlString);
			if ($query && mysql_num_rows($query) > 0) {
				$result = mysql_fetch_array($query);
				$this->ID = $result["id"];
				$this->email = $result["email"];
				$this->sifre = $result["sifre"];
				$this->isim = $result["isim"];
				$this->aktif = $result["aktif"];
				$this->uyeGrupID = $result["uye_grup_id"];
				unset($result, $query, $sqlString);
			} else {
				throw new Exception("Girmiş olduğunuz e-mail adresine sahip bir hesap bulunamamıştır", 3);
			}
		}

		public function get_userByActivationCode($auth) {
			$sqlString = sprintf('select * from p_uyeler where aktivasyonkod="%s" limit 1',guvenlik($auth));
			$query = mysql_query($sqlString);
			if ($query && mysql_num_rows($query) > 0) {
				$result = mysql_fetch_array($query);
				$this->ID = $result["id"];
				$this->email = $result["email"];
				$this->sifre = $result["sifre"];
				$this->isim = $result["isim"];
				$this->aktif = $result["aktif"];
				$this->uyeGrupID = $result["uye_grup_id"];
				unset($result, $query, $sqlString);
			} else {
				throw new Exception("Aktivasyon koduna bağlı işlem bulunamadı", 10);
			}
		}

		public function is_loggedIn($sessionKey = null) {
			if (!is_null($sessionKey)) {
				$this->sessionKey = $sessionKey;
			}
			$result = isset($_SESSION[$this->sessionKey]);
			if ($result) {
				$this->get_userById($_SESSION[$this->sessionKey]);
			}
			return $result;
		}

		public function is_userExists($email) {
			$sqlString = sprintf('select count(*) from p_uyeler where aktif=1 and email="%s"',guvenlik($email));
			$query = mysql_query($sqlString);
			if ($query) {
				$result = mysql_fetch_array($query);
				return $result[0] > 0;
			} else {
				throw new Exception("Girmiş olduğunuz e-mail adresine sahip bir hesap bulunamamıştır", 1);
			}
		}

		public function set_session($sessionKey = null) {
			if (!is_null($sessionKey)) {
				$this->sessionKey = $sessionKey;
			}
			$_SESSION[$this->sessionKey] = $this->ID;
		}

		public function unset_session($sessionKey = null) {
			if (!is_null($sessionKey)) {
				$this->sessionKey = $sessionKey;
			}
			unset($_SESSION[$this->sessionKey]);
		}

		public function set_password($password, $userID = 0) {
			if ($userID == 0) {
				$userID = $this->ID;
			}
			$password = md5($password);
			$sqlString = sprintf('update p_uyeler set sifre="%s" where id=%d limit 1', $password,guvenlik($userID));
			$query = mysql_query($sqlString);
			unset($password, $sqlString);
			if ($query) {
				return true;
			} else {
				throw new Exception("Şifre değiştirilemedi", 4);
			}
		}

		public function update($tip = 0) {
			switch($tip) {
				case 1 :
				// üye silme işlemi
					$sqlString = sprintf('update p_uyeler set aktif=%d where id=%d', $this->aktif, $this->ID);
					$query = mysql_query($sqlString);
					unset($sqlString);
					if ($query) {
						return true;
					} else {
						throw new Exception("Üye silinirken hata oluştu.", 6);
					}
					break;
				case 2 :
				// aktivasyon koduna özel update
					$data = !is_null($this->aktivasyonKod) ? "'{$this->aktivasyonKod}'" : "null";
					$sqlString = sprintf('update p_uyeler set aktivasyonkod=%s where id=%d', $data, $this->ID);
					$query = mysql_query($sqlString);
					unset($data, $sqlString);
					if ($query) {
						return true;
					} else {
						throw new Exception("Üye aktivasyon kodunu güncellenirken hata oluştu.", 9);
					}
					break;
				case 3 :
				// aktivasyon koduna özel update
					$sqlString = sprintf('update p_uyeler set sifre=md5("%s") where id=%d', $this->sifre, $this->ID);
					$query = mysql_query($sqlString);
					unset($data, $sqlString);
					if ($query) {
						return true;
					} else {
						throw new Exception("Üye şifresini güncellenirken hata oluştu.", 9);
					}
					break;
				case 0 :
				// standart update
				default :
					$sqlString = sprintf('update p_uyeler set email="%s", isim="%s", aktif=%d, uye_grup_id=%d where id=%d',guvenlik($this->email),guvenlik($this->isim), $this->aktif, $this->uyeGrupID, $this->ID);
					$query = mysql_query($sqlString);
					unset($sqlString);
					if ($query) {
						return true;
					} else {
						throw new Exception("Üye bilgileri güncellenirken hata oluştu.", 5);
					}
					break;
			}
		}

		public function save() {// üye oluşturma
			$sqlString = sprintf('insert into p_uyeler (email, sifre, isim, uye_grup_id) values("%s", "%s", "%s", %d)',guvenlik($this->email), md5($this->sifre),guvenlik($this->isim), $this->uyeGrupID, $this->ID);
			$query = mysql_query($sqlString);
			unset($sqlString);
			if ($query) {
				$this->ID = mysql_insert_id();
				return true;
			} else {
				throw new Exception("Üye hesabı oluşturulurken hata oluştu.", 7);
			}
		}

	}
?>