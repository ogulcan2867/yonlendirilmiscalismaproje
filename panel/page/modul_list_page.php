<?php
// checkbox renkleri / css de mevcuttur
$checkbox_color_array = array( "green" , "blue" , "purple" , "red" , "cyan" , "orange" );
$checkbox_color_description_array = array();
$status_message = -1;
if ( isset ( $_GET[ "sm" ] ) ) {
	$status_message = $_GET[ "sm" ];
}

$link_suffix_array = array();

$custom_status_message_text = array();
// silme islemi
$aktif_modul = $sub_modul_id != -1 ? $sub_modul_id : $root_modul_id;
if ( isset ( $_GET[ "sil" ] ) && $_WRITE_PERMISSION ) {
	// aktif modulu bulmaca...
	// submodul id -1 degilse dogal olarak alt moduldur ve alt modulun datalarini getiriyoruk
	$modul_tablo_adi = $selected_modul[ "tablo_adi" ]; //fetch_one("d_moduller", "id", $aktif_modul, "tablo_adi");
	// aktif modul herhangi bir module alttan baglantili mi
	$sub_relatives_query = mysql_query ( "select * from d_modul_iliskiler where `relative_to`={$aktif_modul}" );
	$is_sub_relative = mysql_num_rows ( $sub_relatives_query ) > 0;
	// eger baglantili ise diziye aliyoruz
	if ( $is_sub_relative ) {
		$modul_top_relatives = array();
		while ( $sub_relatives = mysql_fetch_array ( $sub_relatives_query ) ) {
			$m = fetch_to_array ( "select `id`, `ad`, `tablo_adi` from `d_moduller` where `id`={$sub_relatives[ "modul_id" ]} limit 1" );
			$modul_top_relatives[ $sub_relatives[ "modul_id" ] ] = $m;
			unset ( $m );
		}
	}

	// dizi degilse de diziye cevirip dizi gibi isliyecegiz
	$_POST[ "ids" ] = isset ( $_POST[ "ids" ] ) ? $_POST[ "ids" ] : array( $_GET[ "sil" ] );
	$ids = $_POST[ "ids" ];
	foreach ( $ids as $id ) {
		// sayisal bir deger degilse bir sonraki ogeye gec
		if ( !is_numeric ( $id ) ) {
			continue;
		}

		$veri = fetch_to_array ( "select * from `{$modul_tablo_adi}` where UID={$id} limit 1" );
		if ( in_array ( $veri[ "protected" ] , array( 1 , 2 ) ) ) {
			continue;
		}

		// eger herhangi bir ust module bagli ise bagli oldugu kayitlari kontrol edicez
		if ( $is_sub_relative ) {
			foreach ( $modul_top_relatives as $tr ) {
				$query_string = sprintf ( 'select count(*) from o_iliskiler as rel, `%4$s` as mtablo where rel.x_modul_id=%1$d and rel.y_modul_id=%2$d and rel.y_id=%3$d and mtablo.deleted=0 and mtablo.UID=rel.x_id' , $tr[ "id" ] , $aktif_modul , $id , $tr[ "tablo_adi" ] );
				$record_count = intval ( fetch ( $query_string ) );
				if ( $record_count > 0 ) {
					$custom_status_message_text[ 4 ][] = ___ ( "Silmek istediğiniz kayıt ile bağlantılı " ) . '`<em>' . ___ ( $tr[ "ad" ] ) . '</em>`' . ___ ( " modülünde kayıt olduğu için bu kaydı silemezsiniz." );
					continue 2;
				}
			}
		}

		// kayit var mi, yoksa 10'luk log at
		if ( !is_null ( $veri ) && $veri != false ) {
			$query_string = sprintf ( 'update `%1$s` set `deleted`=1 where `UID`=%2$d' , $modul_tablo_adi , $id );
			if ( mysql_query ( $query_string ) ) {
				if ( mysql_affected_rows () > 0 ) {
					$status_message = 1;
					mysql_query ( sprintf ( 'insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)' , $U->ID , $selected_modul_id , $id , 3 ) ) or die ( mysql_error () );
				}
			} else {
				log ( mysql_error () , 3 );
			}
		} else {
			arg_log ( "record not found in '$modul_tablo_adi' with UID '{$id}'" , 10 );
		}
	}
}
?><div class="card card-shadow mb-4">
	<div class="card-body">
		<div class="clearfix">&nbsp;</div>
		<?php
		if ( $status_message != -1 ) {
			switch ( $status_message ) {
				case 1:
                    echo '<script>swal("İşlem Başarılı!", "İşlem başarıyla gerçekleşmiştir.", "success");</script>';
                    break;
				case 2:
                    echo '<script>swal("İşlem Başarılı!", "Kayıt başarıyla güncellenmiştir.", "success");</script>';
					break;
				case 3:
                    echo '<script>swal("İşlem Başarılı!", "Kayıt başarıyla eklenmiştir.", "success");</script>';
					break;
			}
		}
		?>


		<?php
		$have_picture = fetch ( "select count(*) from `d_modul_resim_tipler` where `modul_id`={$selected_modul_id}" ) > 0;

		$left_width = 93; // %7 si checkbox + duzenleme kolonlarina gitti kaldi %93 width
		if ( $selected_modul[ "listede_cdate" ] == 1 ) {
			$left_width -= 6;
		}
// modulun lsitesinde gorunecek ogeleri getiriyoruz
		$sorgu = mysql_query ( "select * from d_modul_liste_ogeler where modul_id={$selected_modul_id} order by sira asc" );
		$num_rows = mysql_num_rows ( $sorgu );


		$columns = array();

//
		$jquery_template_markup = "";

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

		$checkbox_index = 0;
		foreach ( $columns as $column ) {
			if ( isset ( $column[ "cb" ] ) && $column[ "cb" ] == 1 ) {
				$checkbox_color_description_array[ $checkbox_color_array[ $checkbox_index ] ] = $column[ "t" ];
				$checkbox_index++;
			}
		}
		$select_fields = array();
		$select_tables = array();
		$select_statements = array();

		$select_fields[] = "mtable.UID";
		$select_fields[] = "mtable.c_date";
		$select_fields[] = "mtable.publishing";
		$select_fields[] = "mtable.protected";
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
		if ( false ) {
			echo("<pre>");
			print_r ( $columns );
			echo("</pre>");
		}
/////////////////////////////////////////////////////////////////////////////
// sayfalama
/////////////////////////////////////////////////////////////////////////////
		if ( isset ( $_SESSION[ 'modul_' . $selected_modul_id ] ) ) {
			$modx = json_decode ( $_SESSION[ 'modul_' . $selected_modul_id ] );
		}

		$total_record_count = mysql_num_rows ( mysql_query ( "select *  from " . implode ( ", " , $select_tables ) . " where " . implode ( " and " , array_merge ( $select_statements , array( "mtable.root_id=0" ) ) ) . " group by mtable.UID" ) );

// sayfa basina kayit dizisi
		$record_per_page_array = array( 25 , 50 , 75 , 100 );
		$record_per_page_i = isset ( $_GET[ "rpp" ] ) && is_numeric ( $_GET[ "rpp" ] ) && $_GET[ "rpp" ] > -1 && $_GET[ "rpp" ] < count ( $record_per_page_array ) ? ( int ) $_GET[ "rpp" ] : 0;
		$record_per_page_i = isset ( $_GET[ "rpp" ] ) && is_numeric ( $_GET[ "rpp" ] ) && in_array ( $_GET[ "rpp" ] , $record_per_page_array ) ? array_search ( $_GET[ "rpp" ] , $record_per_page_array ) : 0;
		$record_per_page = $record_per_page_array[ $record_per_page_i ];
		if ( isset ( $modx ) ) {
			$record_per_page_i = $modx->record_per_page;
			$record_per_page = $record_per_page_array[ $record_per_page_i ];
		}
// toplam sayfa sayisini bulmak icin kayit sayisini, sayfa basi
// kac kayit olacaksa ona bolup tabanini aliyoruz. yuvarlarsak patlar istersen dene ;)
		$total_page = floor ( $total_record_count / $record_per_page );
		if ( $total_page * $record_per_page < $total_record_count ) {
			$total_page++;
		}

		$current_page = isset ( $_GET[ "sayfa" ] ) && is_numeric ( $_GET[ "sayfa" ] ) && $_GET[ "sayfa" ] > 0 && $_GET[ "sayfa" ] <= $total_page ? ( int ) $_GET[ "sayfa" ] : 1;
		if ( isset ( $modx ) ) {
			$current_page = $modx->current_page;
		}

		function generate_pagination_div ( $total_page , $current_page , $args = array() ) {
			$defs = array( 'class' => null );
			$args = array_merge ( $defs , $args );
			$empty_link = qs ( get_current_page_url ( true ) , array( "sayfa" ) , 1 );
			?>
			<div class="col-sm-2 <?php echo($args[ 'class' ]) ?>">
				<nav>
					<ul class="pagination">
						<li class="paginate_button page-item previous prev_" id="data_table_previous">
							<a href="#" class="page-link">Geri</a>
						</li>
						<li class="paginate_button page-item active">
							<select onchange="change_pagin(this)" class="form-control no_js" style="width: 50px">
								<option value="">1</option>
							</select>
						</li>
						<li class="paginate_button page-item next next_" id="data_table_next">
							<a href="#" class="page-link">İleri</a>
						</li>
					</ul>
				</nav>
			</div>
		<?php } ?>


		<div class="table_actions">
			<?php
			if ( $_WRITE_PERMISSION ) {
				?>
				<div class="fltl">
					<a href="<?php echo(parse_panel_url ( array_merge ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "islem" => "ekle" ) , $link_suffix_array ) )); ?>" class="btn btn-circle btn-success"><i class="fa fa-plus"></i> <?php echo(___ ( "Kayıt Ekleme" )); ?></a>
					<?php
					if ( $total_record_count > 0 ) {
						?>
						<a href="javascript:;" onclick="deleted_checkeds_records();" class="btn btn-outline-danger"><i class="fa fa-remove"></i> <?php echo(___ ( "SEÇİLİ KAYITLARI SİL" )); ?></a>
						<script type="text/javascript">
							function deleted_checkeds_records() {
								var $form = $("#modul_list_page_form");
								var $checked = $form.find("tr").find("> td:eq(0) input:checkbox:checked")
								if ($checked.length == 0) {
									//alert("<?php echo(___ ( "Silmek için en az 1 kayıt seçmelisiniz." )); ?>");
									toastr.options = Arasbil.toastr_options("block_top");
									toastr['error']("<?php echo(___ ( "Silmek için en az 1 kayıt seçmelisiniz." )); ?>")
                                    } else {
									var c = confirm("<?php echo(___ ( "Seçili kayıtları silmek istediğinizden emin misiniz?" )); ?>");
									if (c) {
										$form.submit();
									}
								}
							}
						</script>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
		<br />
		<div class="table_actions">
			<div class="row">
				<div class="col-sm-10">
					<div class="search_divisi">
						<input type="text" class="form-control" placeholder="<?PHP echo(___ ( "Ara..." )); ?>" value="<?php echo(isset ( $modx->search_kw ) ? $modx->search_kw : null) ?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="clearfix">&nbsp;</div>
		<form action="<?php echo(parse_panel_url ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "sil" => "" ) )); ?>" method="post" id="modul_list_page_form">
			<table id="mtablea" class="table table-striped table-bordered table-full-width table-hover" summary="">
				<thead>
					<tr>
						<?php
						if ( $_WRITE_PERMISSION ) {
							?>
							<th style="width: 1%;" data-thtype="uid_checkbox">
								<input type="checkbox" class="uid_checkbox all" />
							</th>
							<?php
						}
						if ( false ) {
							echo("<pre>");
							print_r ( $columns );
							echo("</pre>");
							echo $left_width;
						}



// text fieldlari yaydirmaca
						$flow = array();
						$flow_ = 0;
						for ( $dng = 0; $dng < $num_rows; $dng++ ) {
							if ( $columns[ $dng ][ "w" ] == null ) {
								$flow[ $dng ] = 1;
								if ( $columns[ $dng ][ "k" ] == 1 ) {
									$flow[ $dng ] = 1.5;
								}
								$flow_ += $flow[ $dng ];
							}
						}
						foreach ( $flow as $key => $value ) {
							$o = intval ( $left_width / $flow_ * $value );
							$columns[ $key ][ "w" ] = $o;
						}

// kolonlari yazdirma vakti
						$checkbox_index = 0;
						foreach ( $columns as $column ) {
							$th_classes = array();
							$style = "";
							$style .= "width: {$column[ "w" ]}%; ";
							if ( isset ( $column[ "mw" ] ) ) {
								$style .= "min-width: {$column[ "mw" ]}px;";
							}
							$is_sortable = false;
							if ( $column[ 'ds' ] == 0 && in_array ( $column[ 'ft' ] , array( 0 , 2 , 3 , 4 ) ) ) {
								$is_sortable = true;
								$th_classes[] = 'sortable';
							}
							$thtype = "";
							if ( $column[ 'ds' ] == 0 ) {
								if ( ( $column[ 'ft' ] == 0 && in_array ( $column[ 'ftt' ] , array( 0 , 3 ) )) || in_array ( $column[ 'ft' ] , array( 3 , 4 , 7 ) ) ) {
									$thtype = 'plain';
								} elseif ( $column[ 'ft' ] == 0 && in_array ( $column[ 'ftt' ] , array( 1 , 2 ) ) ) {
									$thtype = 'text_update';
								} elseif ( $column[ 'ft' ] == 2 ) {
									$thtype = 'checkbox';
								} elseif ( $column[ 'ft' ] == 8 ) {
									$thtype = 'link';
								}
							} else {
								$thtype = 'plain';
							}
							$target_field = $column[ 'ds' ] == 0 ? $column[ 'dm' ] : 'rel' . $column[ 'dm' ];
							if ( isset ( $modx->sort_field ) ) {
								if ( $target_field == $modx->sort_field ) {
									$th_classes[] = $modx->sort_asc === 'desc' ? 'sort1' : 'sort0';
								}
							}
							printf ( '<th class="%1$s" data-target_field="%2$s" data-thtype="%3$s" style="%4$s">' , implode ( ' ' , $th_classes ) , $target_field , $thtype , $style );
							if ( isset ( $column[ "cb" ] ) && $column[ "cb" ] == 1 ) {
								//echo("<span class='dib sprite icon b{$checkbox_color_array[ $checkbox_index ]}'>&nbsp;</span>");
								//$checkbox_index++;
							} else {
								
							}
							echo($column[ "t" ]);
							echo("</th>");
						}
						?>
						<?PHP
						if ( $selected_modul[ "listede_cdate" ] == 1 ) {
							?>
							<th class="text-center sortable" data-thtype='date' data-target_field='c_date' style="width: 6%; min-width: 100px;">
								<abbr title="<?php echo(___ ( "Oluşturma Tarihi" )); ?>"><?php echo(___ ( "O.T." )); ?></abbr>
							</th>
							<?php
						}
						?>
						<th class="text-center sortable" data-thtype='checkbox' data-target_field='publishing' style="width: 6%;min-width: 100px;">
							<abbr title="<?php echo(___ ( "Yayınlanma Durumu" )); ?>"><?php echo(___ ( "Y.Durumu" )); ?></abbr>
						</th>
						<th class="text-center" data-thtype='buttons' style="width: 6%;min-width: 100px;">
							<?php echo(___ ( "İşlem" )); ?>
						</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="table_actions">
				<div class="row">
					<div class="col-sm-10">
						<span id="toplamkayith">
							<?php printf ( '%3$s <strong id="toplamkayit">%1$d</strong> %4$s' , $total_record_count , $total_page , ___ ( "Toplam" ) , ___ ( "kayıt" ) , ___ ( "Sayfa" ) ); ?>
						</span>
					</div>
					<?php
					generate_pagination_div ( $total_page , $current_page , array( 'class' => 'pull-right text-right' ) );
					?>
				</div>
			</div>
		</form>
		<script type="text/javascript">
			$(document).ready(function () {
				$(".search_divisi input").keydown(function (e) {
					if (e.keyCode == 13) {
						module_search_kw = $(this).val();
						get_module_table_datas();
					}
				});
				$pagination = $(".pagination");
				var $arrow_left = $pagination.find('.prev_');
				$arrow_left.click(function (e) {
					e.preventDefault();
					change_pagin(module_current_page - 1);
				});
				var $arrow_right = $pagination.find('.next_');
				$arrow_right.click(function (e) {
					e.preventDefault();
					change_pagin(module_current_page + 1);
				});
				//////////////////////////////////////////////////////////////////////
				//////////////////////////////////////////////////////////////////////
				$("#modul_list_page_form").on('change', 'input', table_input_change)
				//////////////////////////////////////////////////////////////////////
				//////////////////////////////////////////////////////////////////////
				$("#mtablea thead th.sortable").on('click', function () {
					var $this = $(this);
					var $ths = $this.parent().find('> th');
					var this_i = $ths.index($this);
					$ths.filter(':not(:eq(' + this_i + '))').removeClass('sort0 sort1');
					if ($this.hasClass("sort0")) {
						$this.removeClass("sort0");
						$this.addClass("sort1");
					} else if ($this.hasClass("sort1")) {
						$this.removeClass("sort1");
					} else {
						$this.addClass("sort0");
					}
					get_module_table_datas();
				});
				get_module_table_datas();
			});

			var checkbox_color_array = <?php echo(json_encode ( $checkbox_color_array )) ?>;
			var module_current_page = <?php echo(isset ( $modx->current_page ) ? $modx->current_page : 1) ?>;
			var module_rpp = <?php echo($record_per_page) ?>;
			var module_search_kw = <?php echo(isset ( $modx->search_kw ) ? "'" . $modx->search_kw . "'" : "null"); ?>;
			var module_name_field = <?php echo($selected_modul[ "name_field_prop_id" ] > 0 ? "'" . fetch ( sprintf ( 'select tablo_field from d_modul_ozellikler where id=%1$d limit 1' , $selected_modul[ "name_field_prop_id" ] ) ) . "'" : "false") ?>;
			function get_module_table_datas() {
				var $table = $("#mtablea");
				var $ths = $table.find('> thead th');
				var $tbody = $table.find("> tbody");
				var sort = null;
				var sort_asc = false;
				var $sortable_th = $ths.filter(".sortable.sort0, .sortable.sort1");
				if ($sortable_th.length > 0) {
					sort = $sortable_th.data("target_field");
					sort_asc = $sortable_th.hasClass("sort0");
				}
				$tbody.html("");
				var have_picture = <?php echo($have_picture ? 'true' : 'false'); ?>;
				$tbody.append("<tr><td class='text-center' colspan='" + $ths.length + "'><strong><?php echo( ___ ( "Yükleniyor" )) ?></strong></td></tr>");
				var linklenebilir = <?php echo($selected_modul[ "linklenebilir" ] == 1 ? 'true' : 'false'); ?>;
				$.get("data.php", {
					modul_id: <?php echo($selected_modul_id); ?>,
					sort_field: sort,
					sort_asc: sort_asc ? 'asc' : 'desc',
					page: module_current_page,
					rpp: module_rpp,
					search_kw: module_search_kw != null ? module_search_kw : ""
				}, function (response) {
					var $jx = $.parseJSON(response);
					$("#toplamkayit").html($jx.total_record_count);
					update_pagination_n_rpp($jx.current_page, $jx.total_page, $jx.record_per_page);
					var $j = $jx.entries;
					$tbody.html("");
					if ($j.length > 0) {
						$.each($j, function (i) {
							// console.log($j[i]);
							checkbox_color_index = 0;
							var $tr = $("<tr />");
							var protect = parseInt($j[i]['protected']);
							var editable = $.inArray(protect, [0, 2]) != -1;
							var deletable = $.inArray(protect, [0, 3]) != -1;
							$tr.data("protected", protect);
							$tr.data("UID", $j[i].UID);
							$ths.each(function (thi) {
								th_type = $ths.eq(thi).data('thtype');
								var $td = $('<td />');
								var target_field = $ths.eq(thi).data("target_field");
								switch (th_type) {
									case 'uid_checkbox':
										if (deletable) {
											$td.html($('<input />').attr({
												'type': 'checkbox',
												'name': 'ids[]'
											}).addClass('uid_checkbox').val($j[i].UID));
										} else {
											$td.html("&nbsp;");
										}
										break;
									case 'plain':
										if (target_field == module_name_field) {
											var $a = $("<a />");
											$a.attr({
												'href': "<?php echo(parse_panel_url ( array_merge ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "islem" => "duzenle" ) , $link_suffix_array ) )); ?>&id=" + $tr.data("UID")
											}).html($j[i][target_field]);
											$a.appendTo($td);
											var ___parents = Arasbil.parents_renderer($j[i]['___parents']);
											if (___parents !== null) {
												$td.prepend(___parents);
											}
										} else {
											$td.html($j[i][target_field]);
										}
										break;
									case 'link' :
										$td.html($("<a />").attr({
											href: $j[i][target_field]['link'],
											target: '_blank'
										}).html($j[i][target_field]['ad']));
										break;
									case 'date':
										var t = $j[i][target_field].split(/[- :]/);
										//var d = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
										$td.html(t[2] + "." + t[1] + "." + t[0] + " " + t[3] + ":" + t[4]).addClass('text-nowrap');
										break;
									case 'checkbox':
										if (false) {
											var $input = $('<input />');
											$input.attr({
												type: 'checkbox'
											});
											$input.addClass('custom colored ' + checkbox_color_array[checkbox_color_index])
											$input.appendTo($td);
											if (parseInt($j[i][target_field]) == 1) {
												$input.attr("checked", "checked");
											}
											//$td.addClass('md-checkbox');
											checkbox_color_index++;
										} else {
											var $span = $('<span class="fa" />');
											if (parseInt($j[i][target_field]) === 1) {
												$span.addClass('fa-check text-success');
											} else {
												$span.addClass('fa-remove text-danger');
											}
											$td.append($span).addClass("text-center");
										}
										break;
									case 'text_update':
										if (editable) {
											var $input = $('<input />');
											$input.attr({
												type: 'text'
											}).addClass("form-control").val($j[i][target_field]);
											$input.appendTo($td)
										} else {
											$td.html($j[i][target_field]);
											$td.addClass('text-center');
										}
										break;
									case 'buttons':
										var $a_base = $("<a />").html($('<span />')).addClass('');
										$a = $a_base.clone();
										$a.attr({
                                            'style':'margin-right:10px;',
											'href': "<?php echo(parse_panel_url ( array_merge ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "islem" => "duzenle" ) , $link_suffix_array ) )); ?>&id=" + $tr.data("UID")
										}).addClass("btn btn-outline-success rounded-0");
										if (editable) {
											$a.attr({
												title: '<?php echo(___ ( "Düzenle" )); ?>'
											}).find('span').addClass('fa fa-pencil');
										} else {
											$a.attr({
												title: '<?php echo(___ ( "Görüntüle" )); ?>'
											}).find('span').addClass('fa fa-eye');
										}
										$td.append($a, "");
										/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
										if (have_picture) {
											$a = $a_base.clone();
											$a.attr({
                                                'style':'margin-right:10px;',
												'href': "<?php echo(parse_panel_url ( array_merge ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "islem" => "resim" ) , $link_suffix_array ) )); ?>&id=" + $tr.data("UID"),
												'title': '<?php echo(___ ( "Görseller" )); ?>'
											}).addClass('btn btn-outline-warning rounded-0').find('span').addClass('fa fa-photo');
											$td.append($a, "");
										}
										/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
										if (linklenebilir) {
											$a = $a_base.clone();
											$a.attr({
                                                'style':'margin-right:10px;',
												'href': $j[i]['___link'],
												'target': '_blank',
												'title': '<?php echo(___ ( "Önizleme" )); ?>'
											}).addClass('btn btn-outline-primary rounded-0').find('span').addClass('fa fa-location-arrow');
											$td.append($a, "");
										}
										if (deletable) {
											$a = $a_base.clone();
											$a.attr({
                                                'style':'margin-right:10px;',
												'href': "<?php echo(parse_panel_url ( array_merge ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "islem" => "liste" ) , $link_suffix_array ) )); ?>&sil=" + $tr.data("UID"),
												'title': '<?php echo(___ ( "Sil" )); ?>'
											}).addClass("btn btn-outline-danger rounded-0 sil_btn").find('span').addClass('fa fa-remove');
											$td.append($a, "");
										}
										$td.addClass('text-center text-nowrap btntd');
										break;
								}
								$td.appendTo($tr);
							});
							$tr.appendTo($tbody);
						});
						btn_actions();
					} else {
						$tbody.append("<tr><td class='text-center' colspan='" + $ths.length + "'><strong><?php echo( ___ ( "Kayıt bulunamadı" )) ?></strong></td></tr>");
					}
				});
			}

			function change_pagin(oge) {
				var val = 0;
				if ($(oge).is("select")) {
					val = $(oge).val();
				} else {
					val = oge;
				}
				$(".pagination select").val(val);
				module_current_page = parseInt(val);
				get_module_table_datas();
			}

			function change_rpp(oge) {
				var $this = $(oge);
				if ($this.is('select')) {
					module_rpp = $this.val();
				} else {
					$this.parent().find('a').removeClass('selected');
					$this.addClass("selected");
					module_rpp = parseInt($this.html());
				}
				get_module_table_datas();
			}

			function update_pagination_n_rpp(current_page, total_page, record_per_page) {
				$pagination = $(".pagination");
				if (total_page < 2) {
					$pagination.css("display", "none");
				} else {
					$pagination.css("display", "");
					$pagination.find("select").each(function () {
						var $select = $(this);
						$select.html("");
						for (dng = 1; dng <= total_page; dng++) {
							$select.append($('<option />').html(dng));
						}
						$select.val(current_page);
					});
					var $arrow_left = $pagination.find('.prev_');
					$arrow_left.css("display", current_page > 1 ? "" : "none");
					var $arrow_right = $pagination.find('.next_');
					$arrow_right.css("display", current_page < total_page ? "" : "none");
				}
			}

			function table_input_change() {
				var $this = $(this);
				if ($this.is(':checkbox') && $this.hasClass('uid_checkbox')) {
					var is_allchecker = $this.hasClass('all');
					var $tbody_uid_checkbox = $("#mtablea tbody input.uid_checkbox");
					if (is_allchecker) {
						if ($this.is(':checked')) {
							$tbody_uid_checkbox.attr('checked', 'checked');
						} else {
							$tbody_uid_checkbox.removeAttr('checked');
						}
					} else {
						var total_checkboxes = $tbody_uid_checkbox.length;
						var total_checkeds = $tbody_uid_checkbox.filter(':checked').length;
						var total_uncheckeds = $tbody_uid_checkbox.filter(':not(:checked)').length;
						var $minput = $("#mtablea thead tr th input.uid_checkbox");
						console.log(total_checkboxes);
						console.log(total_checkeds);
						if (total_checkboxes == total_checkeds) {
							$minput.attr('checked', 'checked');
						} else {
							$minput.removeAttr('checked');
						}
					}
				} else {
					var $td = $this.parents('td:eq(0)');
					var $tr = $td.parent();
					var $table = $tr.parents('table:eq(0)');
					var td_i = $tr.find('td').index($td);
					var UID = $tr.data('UID');
					var field = $table.find('thead th:eq(' + td_i + ")").data('target_field');
					var table = "<?php echo($selected_modul[ "tablo_adi" ]); ?>";
					var x = {};
					x.action = "table_update_field_value2";
					x.table = table;
					x.id = UID;
					x.field = field;
					x.deger = $this.is(":checkbox") ? $this.is(":checked") ? 1 : 0 : $this.val();
					$.post("ajax.php", x, function () {
						$this.pulsate({
							color: "#45B6AF",
							repeat: false,
							reach: 10,
							speed: 150
						});
					});
				}
			}

			function btn_actions() {
				$("a.sil_btn").on("click", function (e) {
					var x = confirm("<?php echo(___ ( "Bu kaydı silmek istiyor musunuz ?" )); ?>");
					return x;
				});
			}
		</script>
	</div>
</div>