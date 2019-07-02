<?php

session_start ();
require_once ("lib/core.php");
$R->init ();
$U = new user();
$UG = new usergroup();
if ( $U->is_loggedIn () ) {
	$UG->get_byID ( $U->uyeGrupID );
} else {
	die ( ":/" );
}
if ( !isset ( $_REQUEST[ "modul" ] ) ) {
	die ( "err_arg" );
}
define ( 'CSV_COL_DELIMITER' , ';' );
define ( 'CSV_ROW_DELIMITER' , "\n" );
$selected_modul_id = $_REQUEST[ "modul" ];
$selected_modul = fetch_to_array ( "select * from d_moduller where id={$selected_modul_id} limit 1" );


define ( "SADECE_TABLOLAMA_VERILERI" , false );
if ( !SADECE_TABLOLAMA_VERILERI ) {
	$sorgu = mysql_query ( "select * from d_modul_ozellikler where modul_id={$selected_modul_id}" );
} else {
	$sorgu = mysql_query ( "select * from d_modul_liste_ogeler where modul_id={$selected_modul_id} order by sira asc" );
}
$num_rows = mysql_num_rows ( $sorgu );
$columns = array();
$i = 0;
while ( $okut = mysql_fetch_array ( $sorgu ) ) {
	// sadece tum ozellikler sat
	if ( !SADECE_TABLOLAMA_VERILERI ) {
		$okut[ "tur" ] = 0;
		$okut[ "x_id" ] = $okut[ "id" ];
		if ( in_array ( $okut[ "tip" ] , array( 1 , 5 , 6 ) ) ) {
			continue;
		}
	}
	$columns[ $i ][ "ds" ] = $okut[ "tur" ];
	if ( $okut[ "tur" ] == 0 ) {
		$prop = fetch_to_array ( "select * from d_modul_ozellikler where id={$okut[ "x_id" ]} limit 1" );
		$columns[ $i ][ "t" ] = $prop[ "ad" ];
		$columns[ $i ][ "ft" ] = ( int ) $prop[ "tip" ];
		$columns[ $i ][ "ftt" ] = ( int ) $prop[ "alt_tip" ];
		$columns[ $i ][ "p_id" ] = ( int ) $prop[ "id" ];
		switch ( $prop[ "tip" ] ) {
			case 0:
				switch ( $prop[ "alt_tip" ] ) {
					case 0:
						$columns[ $i ][ "k" ] = $prop[ "id" ] == $selected_modul[ "name_field_prop_id" ] ? 1 : 0;
						break;
					case 1:
					case 2:
						$width = 5;
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
	$columns[ $i ][ "k" ] = isset ( $columns[ $i ][ "k" ] ) ? $columns[ $i ][ "k" ] : 0;
	$i++;
}


// c_date
if ( !SADECE_TABLOLAMA_VERILERI ) {
	$i = count ( $columns );
	$columns[ $i ] = array();
	$columns[ $i ][ "ft" ] = 0;
	$columns[ $i ][ "ftt" ] = 3;
	$columns[ $i ][ "dm" ] = "c_date";
	$columns[ $i ][ "ds" ] = 0;
	$columns[ $i ][ "t" ] = "Eklenme Tarihi";
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////

$select_fields = array();
$select_tables = array();
$select_statements = array();

$select_fields[] = "mtable.UID";
$select_fields[] = "mtable.dil_id";
$select_fields[] = "mtable.c_date";
$select_tables[] = "`{$selected_modul[ "tablo_adi" ]}` as mtable";
$select_statements[] = "mtable.deleted=0";
foreach ( $columns as $column ) {
	if ( $column[ "ds" ] == 1 ) {
		$select_fields[] = "rel_table{$column[ "dm" ]}.y_id as relation{$column[ "dm" ]}";
		$select_tables[] = "o_iliskiler as rel_table{$column[ "dm" ]}";
		$select_statements[] = "rel_table{$column[ "dm" ]}.x_modul_id={$selected_modul_id}";
		$select_statements[] = "rel_table{$column[ "dm" ]}.x_id=mtable.UID";
		$select_statements[] = "rel_table{$column[ "dm" ]}.y_modul_id={$column[ "dm" ]}";
		//$select_fields[] = "(select y_id from o_iliskiler where x_modul_id={$selected_modul_id} and x_id mtable.UID and y_modul_id={$column["dm"]}) as relation{$column["dm"]}";
	} else {
		$select_fields[] = "mtable.`{$column[ "dm" ]}`";
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////
$_CSV = '';
$col_index_chars = range ( "A" , "Z" );
$col_index = 0;
$row_index = 1;
foreach ( $columns as $column ) {
	if ( $col_index > 0 ) {
		$_CSV .= CSV_COL_DELIMITER;
	}
	$_CSV .= $column[ "t" ];
	$col_index++;
}
$_CSV .= CSV_ROW_DELIMITER;
$row_index++;

////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////


function write_table_line ( $root_id , $basamak = 0 ) {
	global $selected_modul_id , $selected_modul;
	global $select_fields , $select_tables , $select_statements;
	global $columns;
	global $col_index_chars;
	global $row_index;
	global $_CSV;

	$sqlstring = "select " . implode ( ", " , $select_fields ) . ", GROUP_CONCAT(`dil_id` SEPARATOR ',') AS `f_dil_ids`  from " . implode ( ", " , $select_tables ) . " where " . implode ( " and " , array_merge ( $select_statements , array( "mtable.root_id={$root_id}" ) ) ) . " group by mtable.UID";
	$query = mysql_query ( $sqlstring ) or die ( mysql_error () );
	$column_count = count ( $columns );
	while ( $result = mysql_fetch_array ( $query , MYSQL_ASSOC ) ) {
		$f_dil_ids = explode ( "," , $result[ "f_dil_ids" ] );


		for ( $dng = 0; $dng < $column_count; $dng++ ) {
			if ( $columns[ $dng ][ "ds" ] == 0 ) { // standart ozellikler
				//if ($columns[$dng]["ft"] == 0 && $columns[$dng]["ftt"] == 0) {
				$value = $result[ $columns[ $dng ][ "dm" ] ];
				if ( $columns[ $dng ][ "ft" ] == 2 ) {
					$value = $result[ $columns[ $dng ][ "dm" ] ] == 1 ? "Evet" : "Hayır";
				} elseif ( $columns[ $dng ][ "ft" ] == 3 || $columns[ $dng ][ "ft" ] == 4 ) {
					$value = fetch ( "select etiket from d_modul_ozellik_secenekler where prop_id={$columns[ $dng ][ "p_id" ]} and deger={$result[ $columns[ $dng ][ "dm" ] ]} limit 1" );
					if ( empty ( $value ) ) {
						$value = "-";
					}
				}
			} else {
				$value = fetch ( "select `{$columns[ $dng ][ "dm_field" ]}` from `{$columns[ $dng ][ "dm_table" ]}` where UID={$result[ "relation{$columns[ $dng ][ "dm" ]}" ]} limit 1" );
			}

			if ( $dng > 0 ) {
				$_CSV .= CSV_COL_DELIMITER;
			}
			$_CSV .= $value;
		}
		$_CSV .= CSV_ROW_DELIMITER;

		$row_index++;
		write_table_line ( $result[ "UID" ] , $basamak + 1 );
	}

}

write_table_line ( 0 , 0 );

////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set active sheet index to the first sheet, so Excel opens this as the first sheet


if ( true ) {

	header ( "Pragma: public" );
	header ( "Expires: 0" );
	header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header ( "Cache-Control: private" , false );
	header ( "Content-Type: application/octet-stream;  charset=utf-8" );
	header ( "Content-Disposition: attachment; filename=\"{$selected_modul[ 'ad' ]}.csv\";" );
	header ( "Content-Transfer-Encoding: binary" );
}
$_CSV = iconv ( "UTF-8" , "ISO-8859-9" , $_CSV );
echo $_CSV;
//				$objWriter->save(realpath("../uploads/x.xls"));
exit;
?>