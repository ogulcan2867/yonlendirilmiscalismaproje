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

$sort_field_ = isset ( $_GET[ "sort_field" ] ) && $_GET[ "sort_field" ] != "null" ? $_GET[ "sort_field" ] : null;
$sort_field = $sort_field_ != null ? "mtable." . $sort_field_ : 'mtable.UID';
$sort_asc_ = isset ( $_GET[ "sort_asc" ] ) ? $_GET[ "sort_asc" ] : null;
$sort_asc = $sort_asc_ != null ? $sort_asc_ : 'desc';
$selected_modul_id = $_GET[ "modul_id" ];
$selected_modul = fetch_to_array ( sprintf ( 'select * from d_moduller where id=%1$d limit 1' , $selected_modul_id ) );
$aktif_modul = $selected_modul_id;
$name_field = null;
if ( $selected_modul[ "name_field_prop_id" ] > 0 ) {
	$name_field = fetch ( sprintf ( 'select tablo_field from d_modul_ozellikler where id=%1$d limit 1' , $selected_modul[ "name_field_prop_id" ] ) );
}

$sorgu = mysql_query ( "select * from d_modul_liste_ogeler where modul_id={$selected_modul_id} order by sira asc" );
$num_rows = mysql_num_rows ( $sorgu );
$columns = array();

//
$jquery_template_markup = "";

$left_width = 93; // %7 si checkbox + duzenleme kolonlarina gitti kaldi %93 width
if ( $selected_modul[ "listede_cdate" ] == 1 ) {
	$left_width -= 6;
}
$i = 0;
while ( $okut = mysql_fetch_array ( $sorgu ) ) {
	$width = null;
	$jquery_template_markup .= "<td>";
	$columns[ $i ][ "ds" ] = $okut[ "tur" ];
	if ( $okut[ "tur" ] == 0 ) {
		$prop = fetch_to_array ( "select * from d_modul_ozellikler where id={$okut[ "x_id" ]} limit 1" );
		$columns[ $i ][ "t" ] = ___ ( $prop[ "ad" ] );
		$columns[ $i ][ "ft" ] = $prop[ "tip" ];
		$columns[ $i ][ "ftt" ] = $prop[ "alt_tip" ];
		$columns[ $i ][ "p_id" ] = $prop[ "id" ];
		$columns[ $i ][ "parametre1" ] = $prop[ "parametre1" ];
		switch ( $prop[ "tip" ] ) {
			case 0:
				switch ( $prop[ "alt_tip" ] ) {
					case 0:
						$columns[ $i ][ "k" ] = $prop[ "id" ] == $selected_modul[ "name_field_prop_id" ] ? 1 : 0;
						$jquery_template_markup .= '${' . $prop[ "tablo_field" ] . '}';
						break;
					case 1:
					case 2:
						$width = 5;
						$jquery_template_markup .= '<input type=\'text\' class="form-control" value=\'${' . $prop[ "tablo_field" ] . '}\' >';
						break;
					case 3:
						$width = 10;
						$columns[ $i ][ "mw" ] = 100;
						break;
				}
				break;
			case 2:
				$width = 1;
				$columns[ $i ][ "cb" ] = 1;
				break;
		}

		$columns[ $i ][ "dm" ] = $prop[ "tablo_field" ];
	} elseif ( $okut[ "tur" ] == 1 ) {
		$columns[ $i ][ "ft" ] = 0;
		$columns[ $i ][ "ftt" ] = 0;

		$rel = fetch_to_array ( "select * from d_modul_iliskiler where modul_id={$selected_modul_id} and relative_to={$okut[ "x_id" ]} limit 1" );
		$rel_mod = fetch_to_array ( "select modul.*, ozel.tablo_field from d_moduller as modul, d_modul_ozellikler as ozel where modul.id={$rel[ "relative_to" ]} and ozel.id=modul.name_field_prop_id limit 1" );
		$columns[ $i ][ "t" ] = $rel[ "ad" ];
		$columns[ $i ][ "dm" ] = $rel[ "relative_to" ];
		$columns[ $i ][ "dm_table" ] = $rel_mod[ "tablo_adi" ];
		$columns[ $i ][ "dm_field" ] = $rel_mod[ "tablo_field" ];
	}

	// flow olmayanlar null degildir, dogal olarak kalan width'ten du$
	if ( !is_null ( $width ) ) {
		$left_width -= $width;
	}
	$columns[ $i ][ "k" ] = isset ( $columns[ $i ][ "k" ] ) ? $columns[ $i ][ "k" ] : 0;
	$columns[ $i ][ "w" ] = $width;
	$i++;
	$jquery_template_markup .= "</td>";
}
$select_fields = array();
$select_tables = array();
$select_statements = array();

$select_fields[] = "mtable.UID";
$select_fields[] = "mtable.c_date";
$select_fields[] = "mtable.protected";
$select_fields[] = "mtable.publishing";
$select_fields[] = "mtable.okundu";
$select_fields[] = "mtable.dil_id";
$select_tables[] = "`{$selected_modul[ "tablo_adi" ]}` as mtable";
$select_statements[] = "mtable.deleted=0";


foreach ( $columns as $column ) {
	if ( $column[ "ds" ] == 1 ) {
		if ( false ) {
			$select_fields[] = "rel_table{$column[ "dm" ]}.y_id as relation{$column[ "dm" ]}";
			$select_tables[] = "o_iliskiler as rel_table{$column[ "dm" ]}";
			$select_statements[] = "rel_table{$column[ "dm" ]}.x_modul_id={$selected_modul_id}";
			$select_statements[] = "rel_table{$column[ "dm" ]}.x_id=mtable.UID";
			$select_statements[] = "rel_table{$column[ "dm" ]}.y_modul_id={$column[ "dm" ]}";
			//$select_fields[] = "(select y_id from o_iliskiler where x_modul_id={$selected_modul_id} and x_id mtable.UID and y_modul_id={$column["dm"]}) as relation{$column["dm"]}";
		}
	} else {
		$select_fields[] = "mtable.`{$column[ "dm" ]}`";
		if ( $column[ "ft" ] == 8 ) {
			$select_fields[] = "mtable.`{$column[ "parametre1" ]}`";
		}
	}
}

$search_kw = null;
if ( isset ( $_GET[ "search_kw" ] ) ) {
	$search_kw = $_GET[ "search_kw" ];
	$search_kw = guvenlik ( $search_kw );
	$search_kw = urldecode ( $search_kw );
	$search_kw = guvenlik ( $search_kw );
	$search_kw = trim ( $search_kw );
	if ( $search_kw != "" ) {
		$search_array = array();
		$module_search_fields_query = mysql_query ( sprintf ( 'select * from d_modul_ozellikler where `modul_id`=%1$d and ((`tip`=0 and `alt_tip`=0) or `tip` in (1, 5, 7))' , $selected_modul_id ) );
		while ( $module_search_fields = mysql_fetch_array ( $module_search_fields_query ) ) {
			$search_array[] = sprintf ( 'mtable.`%1$s` like "%%%2$s%%"' , $module_search_fields[ 'tablo_field' ] , $search_kw );
		}
		$select_statements[] = sprintf ( '(%1$s)' , implode ( ' or ' , $search_array ) );
	}
}
$total_record_count = mysql_num_rows ( mysql_query ( "select *  from " . implode ( ", " , $select_tables ) . " where " . implode ( " and " , $select_statements ) . " group by mtable.UID" ) );

// sayfa basina kayit dizisi
$record_per_page_array = array( 25 , 50 , 75 , 100 );
$record_per_page_i = isset ( $_GET[ "rpp" ] ) && is_numeric ( $_GET[ "rpp" ] ) && $_GET[ "rpp" ] > -1 && $_GET[ "rpp" ] < count ( $record_per_page_array ) ? ( int ) $_GET[ "rpp" ] : 0;
$record_per_page_i = isset ( $_GET[ "rpp" ] ) && is_numeric ( $_GET[ "rpp" ] ) && in_array ( $_GET[ "rpp" ] , $record_per_page_array ) ? array_search ( $_GET[ "rpp" ] , $record_per_page_array ) : 0;
$record_per_page = $record_per_page_array[ $record_per_page_i ];

// toplam sayfa sayisini bulmak icin kayit sayisini, sayfa basi
// kac kayit olacaksa ona bolup tabanini aliyoruz. yuvarlarsak patlar istersen dene ;)
$total_page = floor ( $total_record_count / $record_per_page );
if ( $total_page * $record_per_page < $total_record_count ) {
	$total_page++;
}

$current_page = isset ( $_GET[ "page" ] ) && is_numeric ( $_GET[ "page" ] ) && $_GET[ "page" ] > 0 && $_GET[ "page" ] <= $total_page ? ( int ) $_GET[ "page" ] : 1;

$name_field_by_table_id = array();
$table_name_by_table_id = array();

$n = array();
$n[ 'search_kw' ] = $search_kw;
$n[ 'current_page' ] = $current_page;
$n[ 'record_per_page' ] = $record_per_page_i;
$n[ 'sort_field' ] = $sort_field_;
$n[ 'sort_asc' ] = $sort_asc_;
$_SESSION[ 'modul_' . $selected_modul_id ] = json_encode ( $n );

$RETURN = array();

$parentship = array();

function get_modul_item_parents ( $modul_id , $record_id ) {
	global $parentship;
	if ( !isset ( $parentship[ $modul_id ] ) ) {
		$parentship[ $modul_id ] = array();
	}
	$this_items_parents_ids = array();
	$this_items_parents_names = array();
	$modul = fetch_to_array ( sprintf ( 'select * from d_moduller where id=%1$d limit 1' , $modul_id ) );
	if ( $modul[ 'name_field_prop_id' ] == 0 ) {
		return false;
	}
	$name_field = fetch ( sprintf ( 'select tablo_field from d_modul_ozellikler where id=%1$d limit 1' , $modul[ 'name_field_prop_id' ] ) );
	$id = $record_id;
	while ( true ) {
		if ( $id == 0 ) {
			break;
		}
		if ( !isset ( $parentship[ $modul_id ][ $id ] ) ) {
			$parentship[ $modul_id ][ $id ] = fetch_to_array ( sprintf ( 'select root_id, `%3$s` as _____ad from `%1$s` where UID=%2$d limit 1' , $modul[ 'tablo_adi' ] , $id , $name_field ) );
		}
		$this_items_parents_ids[] = $parentship[ $modul_id ][ $id ][ 'root_id' ];
		$this_items_parents_names[] = $parentship[ $modul_id ][ $id ][ '_____ad' ];

		$id = $parentship[ $modul_id ][ $id ][ 'root_id' ];
	}
	$this_items_parents_names = array_reverse ( $this_items_parents_names );
	unset ( $this_items_parents_names[ count ( $this_items_parents_names ) - 1 ] );
	return $this_items_parents_names;

}

function write_table_line ( $basamak = 0 , $limit = null ) {
	// globaller gruplanmis durumda, tek satira cekmeyin
	global $RETURN;
	//global $_WRITE_PERMISSION , $_READ_PERMISSION;
	global $_LANGUAGES , $link_suffix_array;
	global $selected_modul_id , $selected_modul , $root_modul_id , $sub_modul_id;
	global $select_fields , $select_tables , $select_statements;
	global $columns;
	//global $checkbox_color_array;
	//global $UG;
	global $name_field_by_table_id , $table_name_by_table_id;
	global $name_field;
	global $sort_field , $sort_asc;
	//
	$sqlstring = "select " . implode ( ", " , $select_fields ) . ", GROUP_CONCAT(`dil_id` SEPARATOR ',') AS `f_dil_ids`  from " . implode ( ", " , $select_tables ) . " where " . implode ( " and " , $select_statements ) . " group by mtable.UID order by {$sort_field} {$sort_asc}, mtable.dil_id asc" . $limit;
	//echo $sqlstring;
	$query = mysql_query ( $sqlstring ) or die ( mysql_error () . "\n" . $sqlstring );
	//echo $sqlstring;
	$column_count = count ( $columns );
	while ( $result = mysql_fetch_array ( $query , MYSQL_ASSOC ) ) {
		$NE = $result;
		unset($NE['f_dil_ids']);

		$f_dil_ids = explode ( "," , $result[ "f_dil_ids" ] );
		$dil = in_array ( dil_id , $f_dil_ids ) ? dil_id : $f_dil_ids[ 0 ];
		foreach ( $columns as $column ) {
			if ( $column[ 'ds' ] == 0 ) {
				switch ( $column[ 'ft' ] ) {
					case 0:
						if ( $column[ 'dm' ] == $name_field ) {
							if ( $dil != $result[ 'dil_id' ] ) {
								$NE[ $column[ 'dm' ] ] = fetch ( "select `{$column[ 'dm' ]}` from {$selected_modul[ "tablo_adi" ]} where `UID`={$result[ "UID" ]} and dil_id=" . $dil . " limit 1" );
							}
							//$NE[ $column[ 'dm' ] ] = $dil . "-" . $result[ 'dil_id' ] . "-" . $NE[ $column[ 'dm' ] ];
						}
						break;
					case 3:
					case 4:
						$NE[ $column[ 'dm' ] ] = fetch ( "select etiket from d_modul_ozellik_secenekler where prop_id={$column[ "p_id" ]} and deger={$result[ $column[ "dm" ] ]} limit 1" );
						break;
					case 8:
						if ( $result[ $column[ "dm" ] ] == 0 ) {
							$ad = "Bağlantı";
							$link = generate_link ( $result[ $column[ "dm" ] ] , $result[ $column[ "parametre1" ] ] , $result[ $column[ "parametre1" ] ] , array() , null , false );
						} else {
							if ( !isset ( $name_field_by_table_id[ $result[ $column[ "dm" ] ] ] ) ) {
								$name_field_by_table_id[ $result[ $column[ "dm" ] ] ] = fetch ( sprintf ( 'select f.tablo_field from d_moduller as m, d_modul_ozellikler as f where m.id=%1$d and f.id=m.name_field_prop_id' , $result[ $column[ "dm" ] ] ) );
							}
							if ( !isset ( $table_name_by_table_id[ $result[ $column[ "dm" ] ] ] ) ) {
								$table_name_by_table_id[ $result[ $column[ "dm" ] ] ] = fetch ( sprintf ( 'select m.tablo_adi from d_moduller as m where m.id=%1$d' , $result[ $column[ "dm" ] ] ) );
							}
							$ad = fetch ( sprintf ( 'select `%1$s` from `%2$s` where UID=%3$d limit 1' , $name_field_by_table_id[ $result[ $column[ "dm" ] ] ] , $table_name_by_table_id[ $result[ $column[ "dm" ] ] ] , $result[ $column[ "parametre1" ] ] ) );
							$link = generate_link ( $result[ $column[ "dm" ] ] , $result[ $column[ "parametre1" ] ] , $result[ $column[ "parametre1" ] ] , array() , null , false );
						}
						$NE[ 'link' ] = array( 'ad' => $ad , 'link' => $link );
						unset ( $NE[ $column[ "parametre1" ] ] );
						break;
				}
			} elseif ( $column[ 'ds' ] == 1 ) {
				global $rel_qb;
				$rel_qb = new qb();
				$rel_qb->add_table ( $column[ "dm_table" ] . ' as t1' );
				$rel_qb->add_table ( 'o_iliskiler as ___rel' );
				$rel_qb->add_read_field ( 't1.UID' );
				$rel_qb->add_read_field ( 't1.' . $column[ "dm_field" ] );
				$rel_qb->add_condition ( '___rel.x_modul_id' , $selected_modul_id );
				$rel_qb->add_condition ( '___rel.y_modul_id' , $column[ "dm" ] );
				$rel_qb->add_condition ( '___rel.x_id' , $result[ "UID" ] );
				$rel_qb->add_condition ( '___rel.y_id' , 't1.UID' );
				//hooks_globals ( array( 'rel_qb' ) );
				hooks_run ( 'PANEL/MODUL/LIST/RELATION/before_run_query' , $selected_modul_id , $column[ 'dm' ] );
				$relation_query = mysql_query ( $rel_qb->select () ); //mysql_query ( sprintf ( 'select r.UID, r.`%1$s` from `%2$s` as r, `o_iliskiler` as rel where rel.`x_modul_id`=%3$d and rel.`x_id`=%4$d and rel.`y_modul_id`=%5$d and r.`UID`=rel.`y_id`' , $column[ "dm_field" ] , $column[ "dm_table" ] , $selected_modul_id , $result[ "UID" ] , $column[ "dm" ] ) );
				$r_dng = 0;
				$string = "";
				global $items;
				while ( $items = mysql_fetch_array ( $relation_query ) ) {
					if ( $r_dng > 0 ) {
						$string .= ", ";
					}

					//hooks_globals ( array( 'items' ) );
					hooks_run ( 'PANEL/MODUL/LIST/RELATION/read_query' , $selected_modul_id , $column[ 'dm' ] );
					$string .= $items[ 1 ];
					$r_dng++;
				}
				$exp = explode(',', $string);
				$NE[ 'rel' . $column[ 'dm' ] ] = $exp[0];
			}
		}

		if ( $selected_modul[ "linklenebilir" ] == 1 ) {
			$NE[ '___link' ] = generate_link ( $selected_modul_id , $result[ "UID" ] , null , array() , $dil , false );
		}

		$NE[ '___parents' ] = get_modul_item_parents ( $selected_modul_id , $result[ 'UID' ] );
		$RETURN[] = $NE;
	}

}

write_table_line ( 0 , " limit " . ($current_page - 1) * $record_per_page . ", " . $record_per_page );
$ret = array( "entries" => $RETURN , "current_page" => $current_page , 'record_per_page' => $record_per_page , 'total_page' => $total_page , 'total_record_count' => $total_record_count );
$ret[ 'sess' ] = json_decode ( $_SESSION[ 'modul_' . $selected_modul_id ] );
die ( json_encode ( $ret ) );
?>