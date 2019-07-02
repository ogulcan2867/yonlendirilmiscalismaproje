<?php

session_start ();
require_once ("lib/core.php");
define ( "base_href" , process_url ( get_current_page_url ( true ) , 1 ) );
$R->init ();
$U = new user();
$UG = new usergroup();
if ( $U->is_loggedIn () ) {
	$UG->get_byID ( $U->uyeGrupID );
} else {
	die ( ":/" );
}
if ( !isset ( $_REQUEST[ "action" ] ) ) {
	die ( "err_arg" );
}
$action = $_REQUEST[ "action" ];
switch ( $action ) {
	case 'fileupload':
		if ( isset ( $_FILES[ 'userfile' ] ) ) {
			$bfname = $_FILES[ 'userfile' ][ 'name' ];
			$ext = '.' . get_file_extension ( $bfname );
			$bfname = str_replace ( $ext , '' , $bfname );
			$i = 0;
			while ( true ) {
				$fname = $bfname . ($i > 0 ? '_' . $i : null) . $ext;
				if ( !file_exists ( _DIR_UPLOADS_ . $fname ) ) {
					break;
				}
				$i++;
			}
			$path = _DIR_UPLOADS_ . $fname;
			if ( move_uploaded_file ( $_FILES[ 'userfile' ][ 'tmp_name' ] , $path ) ) {
				die ( json_encode ( array( 'success' => true , 'file_name' => $path ) ) );
			}
			die ( json_encode ( array( 'success' => false ) ) );
		}
		break;
	case "permant":
		$return = array( "msg" => 0 , "perm" => null , "text" => null );
		$dil_id = ( int ) $_POST[ "dil_id" ];
		$record_id = ( int ) $_POST[ "record_id" ];
		$modul_id = ( int ) $_POST[ "modul_id" ];
		$name = guvenlik ( $_POST[ "name" ] );
		$permant = guvenlik ( $_POST[ "permant" ] );
		$permant_ = _trim_permanent_link ( $permant );
		$return[ "perm" ] = $permant_;
		if ( $permant_ == "" ) {
			$new = get_new_permalink ( $modul_id , $record_id , $dil_id , $name );
			$return[ "perm" ] = $new;
		}
		//$new = get_new_permalink($modul_id, $record_id, $);

		die ( json_encode ( $return ) );
		break;
	case "table_update_field_value":
		$defaults = array( "iF" => "UID" , "dF" => "deleted" , "sF" => "sira" , "s" => "" , "t" => "" );
		/*
		 * iF = id field
		 * dF = deleted Field
		 * sF = sira field
		 * s = sorgu ek
		 * t = tablo
		 */
		$deger = guvenlik ( $_POST[ "deger" ] );
		$rel = guvenlik ( $_POST[ "rel" ] );
		if ( !is_numeric ( $deger ) || $deger < 0 ) {
			exit ();
		}
		$temp = explode ( "|" , $rel );
		$args = array();
		foreach ( $temp as $x ) {
			$temp2 = explode ( "::" , $x );
			if ( isset ( $temp2[ 1 ] ) ) {
				$args[ $temp2[ 0 ] ] = guvenlik ( $temp2[ 1 ] );
			} else {
				$args[ $temp2[ 0 ] ] = "";
			}
		}
		$args = array_merge ( $defaults , $args );
		//var_dump($args);
		//print_r($args);
		//$yeniSira = intval(sql("select max({$args["sF"]}) from `{$args["t"]}` where  {$args["iF"]}!={$args["i"]} {$args["s"]}")) + 1;
		$yeniSira = $deger;
		mysql_query ( "update `{$args[ "t" ]}` set {$args[ "sF" ]}={$yeniSira} where {$args[ "iF" ]}={$args[ "i" ]}" ) or die ( mysql_error () );
		die ( json_encode ( array( "aff" => mysql_affected_rows () ) ) );
		break;
	case "table_update_field_value2":
		$deger = guvenlik ( $_POST[ "deger" ] );
		$id = $_POST[ "id" ];
		$id_field = !isset ( $_POST[ "id_field" ] ) ? 'UID' : $_POST[ "id_field" ];
		mysql_query ( "update `{$_POST[ "table" ]}` set {$_POST[ "field" ]}='{$deger}' where {$id_field}={$id}" ) or die ( mysql_error () );
		die ( json_encode ( array( "aff" => mysql_affected_rows () ) ) );
		break;

	case "crop":

		$yol = $_GET[ "yol" ];
		$wgen = $_GET[ "gen" ];
		$wyuk = $_GET[ "yuk" ];

		$resim = new resim();
		$resim->kaynak = $yol;
		$resim->genislik ( $wgen );
		$resim->yukseklik ( $wyuk );
		$resim->kirp ( $_GET[ 'x' ] , $_GET[ 'y' ] , $_GET[ 'w' ] , $_GET[ 'h' ] );
//
		if ( $wgen <> 0 ) {
			$resim->boyutlandir ( $wgen , $wyuk );
		}
		if ( !$resim->goster () ) {
			die ( "hataa" );
		}
		$resim->oldur ();
		exit;
		break;

	case "photo_order" :
		$id = $_POST[ "i" ];
		$new_order = $_POST[ "o" ];
		mysql_query ( sprintf ( 'update `o_resimler` set `sira`=%1$d where `id`=%2$d limit 1' , $new_order , $id ) ) or die ( mysql_error () );
		break;

	case "tercume" :
		if ( isset ( $_POST[ "dil_id" ] ) && isset ( $_POST[ "anlam" ] ) && isset ( $_POST[ "kelime" ] ) ) {
			$dil_id = $_POST[ "dil_id" ];
			$kelime = guvenlik ( $_POST[ "kelime" ] );
			$anlam = guvenlik ( $_POST[ "anlam" ] );
			$anlam = trim ( $anlam , "\n\r " );
			$already_inserted = fetch ( sprintf ( 'select count(*) from `o_dil_ceviriler` where `dil_id`=%1$d and `kelime`="%2$s"' , $dil_id , $kelime ) ) > 0;
			$q = mysql_query ( sprintf ( ($already_inserted ? 'update `o_dil_ceviriler` set `anlam`="%3$s" where `dil_id`=%1$d and `kelime`="%2$s" limit 1' : 'insert into `o_dil_ceviriler` (`dil_id`, `kelime`, `anlam`) values(%1$d, "%2$s", "%3$s")' ) , $dil_id , $kelime , $anlam ) );
			if ( $q ) {
				
			}
		}
		break;

	case "tercume2" :
		if ( isset ( $_POST[ "dil_id" ] ) && isset ( $_POST[ "anlam" ] ) && isset ( $_POST[ "kelime" ] ) ) {
			$dil_id = $_POST[ "dil_id" ];
			$kelime = urldecode ( guvenlik ( $_POST[ "kelime" ] ) );
			$anlam = guvenlik ( $_POST[ "anlam" ] );
			$anlam = trim ( $anlam , "\n\r " );
			$already_inserted = fetch ( sprintf ( 'select count(*) from `p_dil_ceviriler` where `dil_id`=%1$d and `kelime`="%2$s"' , $dil_id , $kelime ) ) > 0;
			$q = mysql_query ( sprintf ( ($already_inserted ? 'update `p_dil_ceviriler` set `anlam`="%3$s" where `dil_id`=%1$d and `kelime`="%2$s" limit 1' : 'insert into `p_dil_ceviriler` (`dil_id`, `kelime`, `anlam`) values(%1$d, "%2$s", "%3$s")' ) , $dil_id , $kelime , $anlam ) );
			if ( $q ) {
				
			}
		}
		break;

	case "tag":
		//$term = guvenlik ( $_GET[ "term" ] );
		$term = guvenlik ( $_GET[ "q" ] );
		$modul_id = $_GET[ "modul_id" ];
		$dil_id = $_GET[ "dil_id" ];
		$dizi = array();
		$maxRows = 20; //$_GET[ "maxRows" ];
		//$dizi[] = array("id" => 1, "label" => "deneme", "value" => "testtt");
		$sorgu = mysql_query ( sprintf ( 'select * from `o_etiketler` where `deleted`=0 and `dil_id`=%1$d and `ad` like "%2$s" order by `ad` asc limit %3$d' , $dil_id , '%' . $term . '%' , $maxRows ) ) or die ( mysql_error () );
		while ( $okut = mysql_fetch_array ( $sorgu ) ) {
			//$dizi[] = array( "id" => $okut[ "id" ] , "label" => $okut[ "ad" ] , "value" => $okut[ "ad" ] );
			$dizi[] = array( "id" => $okut[ "id" ] . "|" . $okut[ "ad" ] , "text" => $okut[ "ad" ] );
		}
		die ( json_encode ( $dizi ) );
		break;
	case "menu_kullanilmayan":
		$modul_id = $_POST[ "modul_id" ];
		$dil_id = $_POST[ "dil_id" ];
		$checked = isset ( $_POST[ "selected" ] ) && is_numeric ( $_POST[ "selected" ] ) ? $_POST[ "selected" ] : 0;
		$name = isset ( $_POST[ "name" ] ) ? $_POST[ "name" ] : "record";

		$modul = fetch_to_array ( "select * from `d_moduller` where id=" . $modul_id . " limit 1" );
		$table_name = $modul[ "tablo_adi" ];
		$name_prop = fetch_one ( "d_modul_ozellikler" , "id" , $modul[ "name_field_prop_id" ] , "tablo_field" );
		$RETURN = array();

		$items_query = mysql_query ( "select UID, `{$name_prop}` from `{$table_name}` where deleted=0 and `dil_id`={$dil_id} group by UID order by `{$name_prop}` asc" );
		while ( $items = mysql_fetch_array ( $items_query ) ) {
			$selected = $items[ 0 ] == $checked ? " checked='checked'" : null;
			$item = array();
			$item[ 'id' ] = $items[ 0 ];
			$item[ 'text' ] = $items[ 1 ];
			$RETURN[] = $item;
		}

		die ( json_encode ( $RETURN ) );
		break;
	case "menu":
		$modul_id = $_POST[ "modul_id" ];
		$dil_id = $_POST[ "dil_id" ];
		$checked = isset ( $_POST[ "selected" ] ) && is_numeric ( $_POST[ "selected" ] ) ? $_POST[ "selected" ] : 0;
		$name = isset ( $_POST[ "name" ] ) ? $_POST[ "name" ] : "record";

		$modul = fetch_to_array ( "select * from `d_moduller` where id=" . $modul_id . " limit 1" );
		$name_prop = fetch_one ( "d_modul_ozellikler" , "id" , $modul[ "name_field_prop_id" ] , "tablo_field" );
		$table_name = $modul[ "tablo_adi" ];
		$RETURN = array();
		$max_nest = 15;

		function modul_relation_list ( $root_id = 0 , $cur_nest_index = 0 , $parents = array() ) {
			global $modul_id, $dil_id , $RETURN , $max_nest , $table_name , $name_prop;
			$items_query = mysql_query ( "select UID, `{$name_prop}` from `{$table_name}` where deleted=0 and `root_id`={$root_id} and `dil_id`={$dil_id} group by UID order by `{$name_prop}` asc" );
			while ( $items = mysql_fetch_array ( $items_query ) ) {
				global $item;
				$item = array();
				$item[ 'id' ] = $items[ 0 ];
				$item[ 'text' ] = $items[ 1 ];
				$item[ 'parents' ] = $parents;
				hooks_run ( 'YENIPANEL/AJAX/MENU/AFTER_PARSE/' . $modul_id , $modul_id );
				$RETURN[] = $item;
				modul_relation_list ( $items[ 0 ] , $cur_nest_index + 1 , array_merge ( $parents , array( $item[ 'text' ] ) ) );
			}

		}

		modul_relation_list ();
		die ( json_encode ( $RETURN ) );
		break;
	case "menu_ara":
		$modul_id = $_POST[ "modul_id" ];
		$dil_id = $_POST[ "dil_id" ];
		$checked = isset ( $_POST[ "selected" ] ) && is_numeric ( $_POST[ "selected" ] ) ? $_POST[ "selected" ] : 0;
		$name = isset ( $_POST[ "name" ] ) ? $_POST[ "name" ] : "record";

		$modul = fetch_to_array ( "select * from `d_moduller` where id=" . $modul_id . " limit 1" );
		$name_prop = fetch_one ( "d_modul_ozellikler" , "id" , $modul[ "name_field_prop_id" ] , "tablo_field" );
		$RETURN = array();

		function modul_relation_list ( $root_id , $checked , $max_nest , $cur_nest_index , $table_name , $name_prop , $name ) {
			global $dil_id;
			//$items_query = mysql_query("select UID, `{$name_prop}`, dil_id from `{$table_name}` where deleted=0 and `root_id`={$root_id} group by UID order by `{$name_prop}` asc");
			$items_query = mysql_query ( "select UID, `{$name_prop}` from `{$table_name}` where deleted=0 and `root_id`={$root_id} and `dil_id`={$dil_id} group by UID order by `{$name_prop}` asc" );
			$ret = array();
			while ( $items = mysql_fetch_array ( $items_query ) ) {
				$selected = $items[ 0 ] == $checked ? " checked='checked'" : null;
				//echo("<divalue='{$items[0]}'{$selected}>{$i[]tems[1]}</option>");
				$ad = $items[ 1 ];
				/* $items["dil_id"] = (int) $items["dil_id"];
				  if ($items["dil_id"] != 1) {
				  $ad = fetch(sprintf('select `%1$s` from `%2$s` where UID=%3$d and dil_id=%4$d limit 1', $name_prop, $table_name, $items[0], 1));
				  if (empty($ad)) {
				  $ad = "<span style='color: red'>[TKG]</span> " . $items[1];
				  }
				  }
				 */
				$item = array();
				$item[ 'id' ] = $items[ 0 ];
				$item[ 'text' ] = $ad;
				$item[ 'children' ] = modul_relation_list ( $items[ 0 ] , $checked , $max_nest , $cur_nest_index + 1 , $table_name , $name_prop , $name );
				if ( count ( $item[ 'children' ] ) == 0 ) {
					unset ( $item[ 'children' ] );
				}
				//printf ( '<div class="step_%5$d"><input type="radio" name="%1$s" id="%1$s%2$d_%6$d" value="%2$d"%4$s /><label for="%1$s%2$d_%6$d">%3$s</label>' , $name , $items[ 0 ] , $ad , $selected , $cur_nest_index + 1 , $dil_id );
				//modul_relation_list ( $items[ 0 ] , $checked , $max_nest , $cur_nest_index + 1 , $table_name , $name_prop , $name );
				//echo("</div>");
				$ret[] = $item;
			}
			return $ret;

		}

		$RETURN = modul_relation_list ( 0 , $checked , 15 , 0 , $modul[ "tablo_adi" ] , $name_prop , $name );
		die ( json_encode ( $RETURN ) );
		break;
	case "menu_old":
		$modul_id = $_POST[ "modul_id" ];
		$dil_id = $_POST[ "dil_id" ];
		$checked = isset ( $_POST[ "selected" ] ) && is_numeric ( $_POST[ "selected" ] ) ? $_POST[ "selected" ] : 0;
		$name = isset ( $_POST[ "name" ] ) ? $_POST[ "name" ] : "record";

		$modul = fetch_to_array ( "select * from `d_moduller` where id=" . $modul_id . " limit 1" );
		$name_prop = fetch_one ( "d_modul_ozellikler" , "id" , $modul[ "name_field_prop_id" ] , "tablo_field" );

		function modul_relation_list ( $root_id , $checked , $max_nest , $cur_nest_index , $table_name , $name_prop , $name ) {
			global $dil_id;
			//$items_query = mysql_query("select UID, `{$name_prop}`, dil_id from `{$table_name}` where deleted=0 and `root_id`={$root_id} group by UID order by `{$name_prop}` asc");
			$items_query = mysql_query ( "select UID, `{$name_prop}` from `{$table_name}` where deleted=0 and `root_id`={$root_id} and `dil_id`={$dil_id} group by UID order by `{$name_prop}` asc" );
			while ( $items = mysql_fetch_array ( $items_query ) ) {
				$selected = $items[ 0 ] == $checked ? " checked='checked'" : null;
				//echo("<divalue='{$items[0]}'{$selected}>{$i[]tems[1]}</option>");
				$ad = $items[ 1 ];
				/* $items["dil_id"] = (int) $items["dil_id"];
				  if ($items["dil_id"] != 1) {
				  $ad = fetch(sprintf('select `%1$s` from `%2$s` where UID=%3$d and dil_id=%4$d limit 1', $name_prop, $table_name, $items[0], 1));
				  if (empty($ad)) {
				  $ad = "<span style='color: red'>[TKG]</span> " . $items[1];
				  }
				  }
				 */
				printf ( '<div class="step_%5$d"><input type="radio" name="%1$s" id="%1$s%2$d_%6$d" value="%2$d"%4$s /><label for="%1$s%2$d_%6$d">%3$s</label>' , $name , $items[ 0 ] , $ad , $selected , $cur_nest_index + 1 , $dil_id );
				modul_relation_list ( $items[ 0 ] , $checked , $max_nest , $cur_nest_index + 1 , $table_name , $name_prop , $name );
				echo("</div>");
			}

		}

		modul_relation_list ( 0 , $checked , 15 , 0 , $modul[ "tablo_adi" ] , $name_prop , $name );
		break;
	case 'test':
		$test = array();
		$test[] = array( 'id' => 1 , 'text' => 'ahmet' );
		$test[] = array( 'id' => 2 , 'text' => 'mehmet' );
		$test[] = array( 'id' => 3 , 'text' => 'deneme' );
		die ( json_encode ( $test ) );
		break;
}
?>