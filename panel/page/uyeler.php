<?php
$m_islem = isset ( $_GET[ "m_islem" ] ) && in_array ( $_GET[ "m_islem" ] , array( "ekle" , "duzenle" ) ) ? $_GET[ "m_islem" ] : null;

$uye_datalar = array();
if ( $m_islem == "duzenle" && isset ( $_GET[ "id" ] ) ) {
	$uye_datalar = fetch_to_array ( "select * from `p_uyeler` where id={$_GET[ "id" ]}" );
	if ( is_null ( $uye_datalar ) ) {
		$m_islem = null;
		$uye_datalar = array();
	}
}

if ( is_null ( $m_islem ) ) {

	if ( isset ( $_GET[ "sil" ] ) ) {
		// dizi degilse de diziye cevirip dizi gibi isliyecegiz
		$_POST[ "ids" ] = isset ( $_POST[ "ids" ] ) ? $_POST[ "ids" ] : array( $_GET[ "sil" ] );
		$ids = $_POST[ "ids" ];
		foreach ( $ids as $id ) {
			// sayisal bir deger degilse bir sonraki ogeye gec
			if ( !is_numeric ( $id ) ) {
				continue;
			}

			// kayit var mi, yoksa 10'luk log at
			$uye = fetch_to_array ( sprintf ( 'select * from `p_uyeler` where id=%1$d limit 1' , $id ) );
			$veri = $uye == false ? false : true;
			$grup = fetch_to_array ( sprintf ( 'select * from `p_uye_gruplari` where `id`=%1$d limit 1' , $uye[ "uye_grup_id" ] ) );
			if ( !is_null ( $veri ) && $veri != false ) {
				$sil = false;
				switch ( $UG->seviye ) {
					case 1:
						$sil = in_array ( $grup[ "seviye" ] , array( 2 , 3 ) );
						break;
					case 2:
						$sil = in_array ( $grup[ "seviye" ] , array( 3 ) );
						break;
					case 3:
						$sil = in_array ( $grup[ "seviye" ] , array( 4 ) );
						break;
				}
				if ( $sil ) {
					$query_string = sprintf ( 'delete from `p_uyeler` where `id`=%1$d' , $id );
					if ( mysql_query ( $query_string ) ) {
						if ( mysql_affected_rows () > 0 ) {
							$status_message = 1;
						}
					} else {
						log ( mysql_error () , 3 );
					}
				}
			} else {
				arg_log ( "record not found in '$modul_tablo_adi' with id '{$id}'" , 10 );
			}
		}
        echo '<script>swal("İşlem Başarılı!", "Kullanıcı başarıyla silinmiştir.", "success");</script>';

    }
	?>
    <div class="card card-shadow mb-4">
		<div class="card-body">
			<a href="<?php echo(parse_panel_url ( array( "uyeler" => "" , "m_islem" => "ekle" ) )); ?>" class="btn btn-circle btn-success"><i class="fa fa-plus"></i> <?php echo(___ ( "Kayıt Ekle" )); ?></a>
			<br />
			<br />
			<table id="mtablea" class="table table-striped table-bordered table-full-width table-hover" summary="">
				<thead>
					<tr>
						<th style="width: 30%;">
							<?php echo(___ ( "E-Posta" )); ?>
						</th>
						<th style="width: 20%;">
							<?php echo(___ ( "İsim" )); ?>
						</th>
						<th style="width: 25%;">
							<?php echo(___ ( "Kullanıcı Grubu" )); ?>
						</th>
						<th class="text-center" style="width: 25%; min-width: 100px;">
							<?php echo(___ ( "İşlem" )); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$uyeler_query_string = sprintf ( 'select uye.*, grup.ad as grup_ad  from `p_uyeler` as uye, `p_uye_gruplari` as grup where grup.id=uye.uye_grup_id %1$s order by grup.seviye asc, `uye`.`isim` asc' , $sart );
					$uyeler_query = mysql_query ( $uyeler_query_string );
                    while ( $uyeler = mysql_fetch_array ( $uyeler_query ) ) { ?>
                            <tr>
                                <td>
                                    <div class='name_field'>
                                        <?php
                                        echo($uyeler[ "email" ]);
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    echo($uyeler[ "isim" ]);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo($uyeler[ "grup_ad" ]);
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    ?>
                                    <a href="<?php echo(parse_panel_url ( array( "uyeler" => "" , "m_islem" => "duzenle" , "id" => $uyeler[ "id" ] ) )); ?>" class="btn btn-outline-success rounded-0" style="margin-right: 20px" title="<?php echo(___ ( 'Düzenle' )) ?>"><span class="fa fa-pencil"></span></a><?php
                                    if ( ( int ) $uyeler[ "uye_grup_id" ] != 1 ) {
                                        ?><a href="<?php echo(parse_panel_url ( array( "uyeler" => "" , "sil" => $uyeler[ "id" ] ) )); ?>" class="btn btn-outline-danger rounded-0 sil_btn" title="<?php echo(___ ( "Kalıcı olarak sil" )); ?>"><span class="fa fa-remove"></span></a>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                    <?php } ?>

				</tbody>
			</table>
			<script type="text/javascript">
				$(document).ready(function () {
					$("a.sil_btn").on("click", function (e) {
						var x = confirm("<?php echo(___ ( "Bu kaydı silmek istiyor musunuz ?" )); ?>");
						return x;
					});
				});
			</script>
		</div>
	</div>
	<?php
} else {
	$ITEM_ID = 0;
	define ( "is_edit" , $m_islem == "duzenle" );

	if ( isset ( $_GET[ "msg" ] ) ) {
		switch ( ( int ) $_GET[ "msg" ] ) {
			case 1:
                echo '<script>swal("İşlem Başarılı!", "Kullanıcı başarıyla eklenmiştir.", "success");</script>';
                break;
			case 2:
                echo '<script>swal("İşlem Başarılı!", "Kullanıcı bilgileri başarıyla güncellenmiştir.", "success");</script>';
                break;
		}
	}


	if ( is_edit ) {
		$ITEM_ID = intval ( $_GET[ "id" ] );
	}
	$have_this_langauge = false;
	if ( is_edit ) {
		$old_datas = fetch_to_array ( "select * from `p_uyeler` where id={$ITEM_ID} limit 1" );
		if ( $old_datas != false ) {
			$have_this_langauge = true;
		}
	} else {
		
	}

	if ( isset ( $_POST[ "form_submit" ] ) ) {
		$table_fields = array();
		$table_values = array();

		$table_fields[] = "isim";
		$table_values[] = '"' . guvenlik ( $_POST[ "isim" ] ) . '"';

		$table_fields[] = "uye_grup_id";
		$table_values[] = '"' . $_POST[ "uye_grup_id" ] . '"';

		if ( is_edit ) {
			if ( !empty ( $_POST[ "sifre" ] ) && $_POST[ "sifre" ] == $_POST[ "sifre2" ] ) {
				$table_fields[] = "sifre";
				$table_values[] = 'md5("' . guvenlik ( $_POST[ "sifre" ] ) . '")';
			}
		} else {
			$table_fields[] = "email";
			$table_values[] = '"' . guvenlik ( $_POST[ "email" ] ) . '"';

			$table_fields[] = "sifre";
			$table_values[] = 'md5("' . guvenlik ( $_POST[ "sifre" ] ) . '")';

			$table_fields[] = "aktif";
			$table_values[] = 1;
		}

		if ( !is_edit ) {

			$query_string = "insert into `p_uyeler` (" . implode ( ", " , $table_fields ) . ") values(" . implode ( ", " , $table_values ) . ")";
			if ( false ) {
				var_dump ( $_POST );
				echo("<hr>");
				var_dump ( $query_string );
				exit ();
			}

			$query = mysql_query ( $query_string ) or die ( mysql_error () );
			$LID = mysql_insert_id ();
			yonlendir ( parse_panel_url ( array( "uyeler" => "" , "m_islem" => "duzenle" , "id" => $LID , "msg" => 1 ) ) );
		} else {
			$query_string = "";
			for ( $i = 0 , $m = count ( $table_fields ); $i < $m; $i++ ) {
				$query_string .= ", {$table_fields[ $i ]}={$table_values[ $i ]}";
			}
			if ( false ) {
				var_dump ( $_POST );
				echo("<hr>");
				var_dump ( $query_string );
				exit ();
			}
			$query_string = "update `p_uyeler` set " . trim ( $query_string , "," ) . " where id={$ITEM_ID} limit 1";
			mysql_query ( $query_string ) or die ( mysql_error () );


			yonlendir ( parse_panel_url ( array( "uyeler" => "" , "m_islem" => "duzenle" , "id" => $ITEM_ID , "msg" => 2 ) ) );
		}
	}
	?>
    <div class="card card-shadow mb-4">
        <div class="card-header border-0">
            <div class="custom-title-wrap bar-pink">
                <div class="custom-title">
                    <?php
                    if ( is_edit ) {
                        echo(___ ( "Panel Kullanıcısını Düzenle" ));
                    } else {
                        echo(___ ( "Panel Kullanıcısı Ekleme" ));
                    }
                    ?>
                </div>
            </div>
        </div>
		<div class="card-body form">
			<form action="" method="post" id="modul_detay_lang" enctype="multipart/form-data" class="form-horizontal" role="form">
				<input type="hidden" name="form_submit" value="">
				<div class="form-body">
					<div class="form-group row">
						<label class="col-md-3 col-form-label">
							<?php echo(___ ( "Kullanıcı Grubu" )); ?>
						</label>
						<div class="col-md-9">
							<select name="uye_grup_id" class="form-control input-medium">
								<?php
								$value = isset ( $old_datas[ "uye_grup_id" ] ) ? $old_datas[ "uye_grup_id" ] : null;
								switch ( $UG->seviye ) {
									case 1:
										$sart = " and seviye in (2, 3) ";
										break;
									case 2:
										$sart = " and seviye = 3 ";
										break;
									case 3:
										$sart = " and seviye = 4 ";
										break;
								}
								$uye_gruplari_query_string = sprintf ( 'select * from `p_uye_gruplari` where `goster`=1 %1$s order by ad asc' , $sart );
								$uye_gruplari_query = mysql_query ( $uye_gruplari_query_string );
								while ( $uye_gruplari = mysql_fetch_array ( $uye_gruplari_query ) ) {
									$sec = $value == $uye_gruplari[ "id" ] ? " selected='selected'" : null;
									printf ( '<option value="%1$d"%3$s>%2$s</option>' , $uye_gruplari[ "id" ] , $uye_gruplari[ "ad" ] , $sec );
								}
								?>
							</select>
						</div>
					</div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
							<?php echo(___ ( "Ad" )); ?>
						</label>
						<div class="col-md-9">
							<?php
							$value = isset ( $old_datas[ "isim" ] ) ? $old_datas[ "isim" ] : null;
							?>
							<input type='text' class='req form-control input-medium' maxlength='150' name='isim' value='<?php echo($value); ?>' />
						</div>
					</div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
							<?php echo(___ ( "E-Posta" )); ?>
						</label>
						<div class="col-md-9">
							<?php
							if ( !is_edit ) {
								$value = isset ( $old_datas[ "email" ] ) ? $old_datas[ "email" ] : null;
								echo("<input type='text' class='req form-control input-medium' maxlength='150' name='email' value='{$value}' />");
							} else {
								echo($old_datas[ "email" ]);
							}
							?>
						</div>
					</div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
							<?php echo(___ ( "Şifre" )); ?>
						</label>
						<div class="col-md-9">
							<?php
							$req = is_edit ? null : "req";
							echo("<input type='password' class='{$req} form-control input-medium' maxlength='25' name='sifre' />");
							?>
						</div>
					</div>
					<?php
					if ( is_edit ) {
						?>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">
								<?php echo(___ ( "Şifre" )); ?> (<?php echo(___ ( "Tekrar" )); ?>)
							</label>
							<div class="col-md-9">
								<input type='password' class='form-control input-medium' maxlength='25' name='sifre2' />
							</div>
						</div>
						<?PHP
					}
					?>
				</div>
			</form>
            <div class="form-actions">
                <button type="button" class="btn btn-outline-success form-pill" onclick="$('#modul_detay_lang').submit_form();"><?php echo(___ ( "Kaydet" )); ?></button>
            </div>
		</div>
	</div>
<?php } ?>
