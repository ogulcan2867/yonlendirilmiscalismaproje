<?php

function modul_ozellik_to_structure_query($dizi) {
	$query_text = null;
	switch ($dizi["tip"]) {
		case 0:
			switch ($dizi["alt_tip"]) {
				case 0:
					$query_text .= get_mysql_field_structure_query(0, array("maxlength" => $dizi["maxlength"])); //"VARCHAR({$dizi["maxlength"]}) NULL DEFAULT NULL COLLATE 'utf8_turkish_ci'";
					break;
				case 1:
					$query_text .= get_mysql_field_structure_query(2); //"INT(10) UNSIGNED NULL DEFAULT '0'";
					break;
				case 2:
					$query_text .= get_mysql_field_structure_query(4); //"FLOAT UNSIGNED NULL DEFAULT '0'";
					break;
				case 3:
					$query_text .= get_mysql_field_structure_query(5); //"TIMESTAMP NULL DEFAULT NULL";
					break;
			}
			break;
		case 7:
			switch ($dizi["alt_tip"]) {
				case 0:
					$query_text .= get_mysql_field_structure_query(0); //"VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_turkish_ci'";
					break;
				case 1:
					$query_text .= get_mysql_field_structure_query(1); //"TEXT NULL COLLATE 'utf8_turkish_ci'";
					break;
			}
			break;
		case 6:
			$query_text .=  get_mysql_field_structure_query(0); //"VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_turkish_ci'";
			break;
		case 1:
		case 5:
			$query_text .= get_mysql_field_structure_query(1); //"TEXT NULL COLLATE 'utf8_turkish_ci'";
			break;
		case 2:
		case 8:
		case 9:
			$query_text .= get_mysql_field_structure_query(3); //"TINYINT(3) UNSIGNED NULL DEFAULT '0'";
			break;
		case 3:
		case 4:
			$query_text .= get_mysql_field_structure_query(2); //"INT(10) UNSIGNED NULL DEFAULT '0'";
			break;
	}
	return $query_text;
}

function get_mysql_field_structure_query($type, $args = array()) {
	$defaults = array("maxlength" => 255, "collation" => 'utf8_turkish_ci', "default" => 0);
	$args = array_merge($defaults, $args);
	switch ($type) {
		case 0:
			return sprintf('VARCHAR(%1$d) NULL DEFAULT NULL COLLATE "%2$s"', $args["maxlength"], $args["collation"]);
			break;
		case 1:
			return sprintf('TEXT NULL COLLATE "%1$s"', $args["collation"]);
			break;
		case 2:
			return sprintf('INT(10) UNSIGNED NULL DEFAULT "%1$d"', $args["default"]);
			break;
		case 3:
			return sprintf('TINYINT(3) UNSIGNED NULL DEFAULT "%1$d"', $args["default"]);
			break;
		case 4:
			return sprintf('FLOAT UNSIGNED NULL DEFAULT "%1$d"', $args["default"]);
			break;
		case 5:
			return 'TIMESTAMP NULL DEFAULT NULL';
			break;
	}
}

function is_mysql_table_field_exists($table, $field) {
	$result = mysql_query("SHOW COLUMNS FROM ". $table); 
	while ($row = mysql_fetch_assoc($result)) { 
		if($row["Field"] == $field) {
			return true;
		}
	}
	return false;
}

function drop_n_create_module_table($modul_id) {
	$modul_datalar = fetch_to_array(sprintf('select * from `d_moduller` where id=%1$d limit 1', $modul_id));
	mysql_query("DROP TABLE IF EXISTS `{$modul_datalar["tablo_adi"]}`") or die(mysql_error());
	$query_text = "CREATE TABLE `{$modul_datalar["tablo_adi"]}` (";
	$query_text .= "\n\t`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT";
	$query_text .= ",\n\t`root_id` INT(10) UNSIGNED NULL DEFAULT '0'";

	$query = mysql_query("select * from d_modul_ozellikler where modul_id={$modul_id} order by sira asc");
	while ($okut = mysql_fetch_array($query)) {
		$query_text .= ",\n\t`{$okut["tablo_field"]}` ";
		$query_text .= modul_ozellik_to_structure_query($okut);
		if ($okut["tip"] == 8) {
			$query_text .= ",\n\t`{$okut["parametre1"]}` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_turkish_ci'";
		}
	}


	$query_text .= ",\n\t`UID` INT(10) UNSIGNED NULL DEFAULT '0'";
	$query_text .= ",\n\t`dil_id` TINYINT(3) UNSIGNED NULL DEFAULT '0'";
	$query_text .= ",\n\t`deleted` TINYINT(3) UNSIGNED NULL DEFAULT '0'";
	$query_text .= ",\n\t`protected` TINYINT(3) UNSIGNED NULL DEFAULT '0'";
	$query_text .= ",\n\t`publishing` TINYINT(3) UNSIGNED NULL DEFAULT '1'";
	$query_text .= ",\n\t`okundu` TINYINT(3) UNSIGNED NULL DEFAULT '0'";
	$query_text .= ",\n\t`c_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
	$query_text .= ",\n\t`u_date` TIMESTAMP NULL DEFAULT NULL";
	$query_text .= ",\n\tPRIMARY KEY (`id`)\n)\nCOLLATE='utf8_turkish_ci'\nENGINE=InnoDB;";
	mysql_query($query_text) or die(mysql_error() . "<br />" . $query_text);
}

function fetch_file_array_from_directory($directory) {
	$array = array();
	foreach (scandir($directory) as $file) {
		if (in_array($file, array(".", ".."))) {
			continue;
		}
		if(is_dir($file)) {
			continue;
		}
		$array[] = $file;
	}
	return $array;
}

if (!function_exists("panel_ayarlar")) {

	function panel_ayarlar($ayar_key) {
		$doncek = fetch(sprintf('select `deger` from `p_ayarlar` where `ayar_key`="%1$s" limit 1', $ayar_key));
		if ($doncek == false) {
			// todo eger bulamadiysa bi de aktif dilde aratacak onu dondurecek son olarak
		}
		return $doncek;
	}

}
?>