<?php
class icerik {
	public $ID = 0;
	public $tip_id = 0;
	public $baslik = null;
	public $detay = null;
	public $dil_id = 0;
	public $u_id = 0;
	public $tarih_1 = null;
	public $tarih_2 = null;
	public $sira = 0;
	public $uye_id = 0;
	public $aktif = 0;
	public $olusturulma_tarihi = null;

	public function get_byID($icerikID, $dil_id) {
		$sqlString = sprintf('select * from s_icerik where u_id=%1$d and dil_id=%2$d limit 1', $icerikID, $dil_id);
		$query = mysql_query($sqlString);
		if ($query && mysql_num_rows($query) > 0) {
			$result = mysql_fetch_array($query);
			$this->ID = $result["id"];
			$this->tip_id = $result["tip_id"];
			$this->baslik = $result["baslik"];
			$this->detay = $result["detay"];
			$this->dil_id = $result["dil_id"];
			$this->u_id = $result["u_id"];
			$this->tarih_1 = $result["tarih_1"];
			$this->tarih_2 = $result["tarih_2"];
			$this->sira = $result["sira"];
			$this->uye_id = $result["uye_id"];
			$this->olusturulma_tarihi = $result["c_date"];
			
			unset($result, $query, $sqlString);
		} else {
			throw new Exception("Girmiş olduğunuz ID'ye sahip bir içerik bulunamamıştır", 13);
		}
	}

	public function update($tip = 0) {
		switch($tip) {
			case 1 :
			// üye grubu silme işlemi
			/*$sqlString = sprintf('update p_uyeler set aktif=%d where id=%d', $this->aktif, $this->ID);
			 $query = mysql_query($sqlString);
			 unset($sqlString);
			 if ($query) {
			 return true;
			 } else {
			 throw new Exception("Üye silinirken hata oluştu.", 14);
			 }*/
				break;
			// standart update
			default :
				$sqlString = sprintf('update s_icerik set baslik="%1$s", detay="%2$s" where id=%1$d', mysql_escape_string($this->baslik), mysql_escape_string($this->detay), $this->ID);
				$query = mysql_query($sqlString);
				unset($sqlString);
				if ($query) {
					return true;
				} else {
					throw new Exception("İçerik bilgileri güncellenirken hata oluştu.", 15);
				}
				break;
		}
	}

	public function save() {// icerik oluşturma
		$sqlString = sprintf('insert into s_icerik (baslik, detay, tip_id, uye_id) values("%3$s", "%4$s", %1$d, %2$d)', $this->tip_id, $this->uye_id, mysql_escape_string($this->baslik), mysql_escape_string($this->detay));
		$query = mysql_query($sqlString);
		unset($sqlString);
		if ($query) {
			$this->ID = mysql_insert_id();
			mysql_query("update s_icerik set u_id = {$this->ID} where id={$this->ID} limit 1");
			return true;
		} else {
			throw new Exception("İçerik oluşturulurken hata oluştu.", 16);
		}
	}

}

?>