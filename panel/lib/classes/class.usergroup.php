<?php

class usergroup {

	public $ID = 0;
	public $ad = null;
	public $goster = 1;
	public $seviye = 1;
	public $permissions = array();

	public function get_byID($userGroupID) {
		$sqlString = sprintf('select * from p_uye_gruplari where id=%d limit 1', $userGroupID);
		$query = mysql_query($sqlString);
		if ($query && mysql_num_rows($query) > 0) {
			$result = mysql_fetch_array($query);
			$this->ID = (int) $result["id"];
			$this->ad = $result["ad"];
			$this->goster = (int) $result["goster"];
			$this->seviye = (int) $result["seviye"];
			$this->get_permissions();
			unset($result, $query, $sqlString);
		} else {
			throw new Exception("Girmiş olduğunuz ID'ye sahip bir üye grubu bulunamamıştır", 13);
		}
	}

	private function get_permissions() {
		$permissions = array();
		$sqlString = sprintf('select * from `p_uye_grup_izinler` where `uye_grup_id`=%1$d', $this->ID);
		$query = mysql_query($sqlString);
		$ks = array("okuma", "yazma");
		while ($result = mysql_fetch_array($query)) {
			$izinler = array();
			$result["modul_id"] = (int) $result["modul_id"];
			foreach ($ks as $kss) {
				$izinler[$kss] = (int) $result[$kss];
			}
			if ($result["modul_id"] > 0) {
				$permissions[$result["modul_id"]] = $izinler;
			} else {
				$permissions[$result["modul_id"]][$result["sabit"]] = $izinler;
			}
		}
		$this->permissions = $permissions;
	}

	private function set_permissions() {
		mysql_query("delete from `p_uye_grup_izinler` where `uye_grup_id`={$this->ID}");
		foreach ($this->permissions as $modul_id => $mvk) {
			$mv = $mvk;
			if ($modul_id == 0) {
				foreach ($mvk as $sabit => $mvk2) {
					$mv = $mvk2;
					mysql_query(sprintf('insert into `p_uye_grup_izinler` (`uye_grup_id`, `modul_id`, `sabit`, `okuma`, `yazma`) values(%1$d, %2$d, "%3$s", %4$d, %5$d)', $this->ID, 0, $sabit, $mv["okuma"], $mv["yazma"])) or die(mysql_error());
				}
			} else {
				mysql_query(sprintf('insert into `p_uye_grup_izinler` (`uye_grup_id`, `modul_id`, `sabit`, `okuma`, `yazma`) values(%1$d, %2$d, "%3$s", %4$d, %5$d)', $this->ID, $modul_id, null, $mv["okuma"], $mv["yazma"])) or die(mysql_error());
			}
		}
	}

	/*
	 * islem ;
	 *	0: okuma
	 * 	1: yazma
	 */

	public function check_permission($modul_id, $sabit, $islem) {
		$modul_id = (int) $modul_id;
		if ($this->ID == 1) {
			return true;
		}
		// permissionlarda key var mi diye bakiliyor
		if (isset($this->permissions[$modul_id])) {
			$ts = array("okuma", "yazma");
			$t = $ts[$islem];
			if ($modul_id == 0) {
				return $this->permissions[$modul_id][$sabit][$t] == 1;
			} else {
				return $this->permissions[$modul_id][$t] == 1;
			}
		}

		// herhangi bir sonuc yoksa false donuyoruz, yani izin yok
		return false;
	}

	public function update($tip = 0) {
		switch ($tip) {
			case 1 :
				// üye grubu silme işlemi
				/* $sqlString = sprintf('update p_uyeler set aktif=%d where id=%d', $this->aktif, $this->ID);
				  $query = mysql_query($sqlString);
				  unset($sqlString);
				  if ($query) {
				  return true;
				  } else {
				  throw new Exception("Üye silinirken hata oluştu.", 14);
				  } */
				break;
			// standart update
			default :
				$sqlString = sprintf('update p_uye_gruplari set ad="%s", goster=%d where id=%d', guvenlik($this->ad), $this->goster, $this->ID);
				$query = mysql_query($sqlString);
				unset($sqlString);
				if ($query) {
					$this->set_permissions();
					return true;
				} else {
					throw new Exception("Üye grubu bilgileri güncellenirken hata oluştu.", 15);
				}
				break;
		}
	}

	public function save() {// üye oluşturma
		$sqlString = sprintf('insert into p_uye_gruplari (ad, goster, seviye) values("%s", %d, %d)', guvenlik($this->ad), $this->goster, $this->seviye);
		$query = mysql_query($sqlString);
		unset($sqlString);
		if ($query) {
			$this->ID = mysql_insert_id();
			$this->set_permissions();
			return true;
		} else {
			throw new Exception("Üye grubu oluşturulurken hata oluştu.", 16);
		}
	}

}

?>