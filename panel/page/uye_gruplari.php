<?php
$m_islem = isset ( $_GET[ "m_islem" ] ) && in_array ( $_GET[ "m_islem" ] , array( "ekle" , "duzenle" ) ) ? $_GET[ "m_islem" ] : null;

$uye_datalar = array();
$uye_grubu = new usergroup();
if ( $m_islem == "duzenle" && isset ( $_GET[ "id" ] ) ) {
	$uye_datalar = fetch_to_array ( "select * from `p_uye_gruplari` where id={$_GET[ "id" ]}" );
	if ( is_null ( $uye_datalar ) ) {
		$m_islem = null;
		$uye_datalar = array();
	} else {
		$uye_grubu->get_byID ( $_GET[ "id" ] );
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
			$veri = fetch_one ( "p_uye_gruplari" , "id" , $id , "id" );
			if ( !is_null ( $veri ) && $veri != false ) {
				$query_string = sprintf ( 'delete from `p_uye_gruplari` where `id`=%1$d' , $id );
				if ( mysql_query ( $query_string ) ) {
					if ( mysql_affected_rows () > 0 ) {
						$status_message = 1;
					}
				} else {
					log ( mysql_error () , 3 );
				}
			} else {
				arg_log ( "record not found in '$modul_tablo_adi' with id '{$id}'" , 10 );
			}
		}
        echo '<script>swal("İşlem Başarılı!", "Üye grubu başarıyla silinmiştir.", "success");</script>';

    }
	?>
    <div class="card card-shadow mb-4">
        <div class="card-body">
			<a href="<?php echo(parse_panel_url ( array( "uye_gruplari" => "" , "m_islem" => "ekle" ) )); ?>" class="btn btn-circle btn-success"><i class="fa fa-plus"></i> <?php echo(___ ( "Kayıt Ekle" )); ?></a>
			<br />
			<br />
			<table id="mtablea" class="table table-striped table-bordered table-full-width table-hover" summary="">
				<thead>
					<tr>
						<th style="width: 45%;">
							<?php echo(___ ( "İsim" )); ?>
						</th>
						<th class="text-center" style="width: 30%;">
							<?php echo(___ ( "Kullanıcı Sayısı" )); ?>
						</th>
						<th class="text-center" style="width: 25%; min-width: 100px;">
							<?php echo(___ ( "İşlem" )); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
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
						?>
						<tr>
							<td>
								<?php
								echo($uye_gruplari[ "ad" ]);
								?>
							</td>
							<td class="text-center">
								<?php
								echo(fetch ( sprintf ( 'select count(*) from `p_uyeler` where `uye_grup_id`=%1$d' , $uye_gruplari[ "id" ] ) ));
								?>
							</td>
							<td class="text-center">
								<a href="<?php echo(parse_panel_url ( array( "uye_gruplari" => "" , "m_islem" => "duzenle" , "id" => $uye_gruplari[ "id" ] ) )); ?>" class="btn btn-outline-success rounded-0" title="<?php echo(___ ( 'Düzenle' )) ?>" style="margin-right: 20px"><span class="fa fa-pencil"></span></a><?php
								if ( ( int ) $uye_gruplari[ "id" ] != $UG->ID ) {
									?><a href="<?php echo(parse_panel_url ( array( "uye_gruplari" => "" , "sil" => $uye_gruplari[ "id" ] ) )); ?>" class="btn btn-outline-danger rounded-0 sil_btn " title="<?php echo(___ ( "Kalıcı olarak sil" )); ?>"><span class="fa fa-remove"></span></a>
										<?php
									}
									?>
							</td>
						</tr>
						<?php
					}
					?>
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
                echo '<script>swal("İşlem Başarılı!", "Üye grubu başarıyla eklenmiştir.", "success");</script>';
                break;
			case 2:
                echo '<script>swal("İşlem Başarılı!", "Üye grubu bilgileri başarıyla güncellenmiştir.", "success");</script>';
				break;
		}
	}


	if ( is_edit ) {
		$ITEM_ID = intval ( $_GET[ "id" ] );
	}
	$have_this_langauge = false;
	if ( is_edit ) {
		$old_datas = fetch_to_array ( "select * from `p_uye_gruplari` where id={$ITEM_ID} limit 1" );
		if ( $old_datas != false ) {
			$have_this_langauge = true;
		}
	} else {
		
	}

	if ( isset ( $_POST[ "form_submit" ] ) ) {
		$uye_grubu->ad = $_POST[ "ad" ];

		if ( !is_edit ) {
			if ( $UG->seviye == 1 ) {
				// sadece argenova 1. seviyededir ve 2-3 ekleyebilir, post'dan veri alicaz
				$uye_grubu->seviye = $_POST[ "seviye" ];
			} elseif ( $UG->seviye == 2 ) {
				// argenovanin olusturmus oldugu ajans kullanicisi sadece 3 ekleyebilir
				$uye_grubu->seviye = 3;
			} else {
				// 3. seviyenin her ekledigi 4'tur
				$uye_grubu->seviye = 4;
			}
		}

		$permissions = array();
		for ( $izin_i = 0; $izin_i < count ( $_POST[ "izin" ] ); $izin_i++ ) {
			$modul_id = $_POST[ "id" ][ $izin_i ];
			$izin_ad = $_POST[ "izin" ][ $izin_i ];
			$permission = array();
			$permission[ "okuma" ] = ( int ) isset ( $_POST[ "{$izin_ad}okuma" ] );
			$permission[ "yazma" ] = ( int ) isset ( $_POST[ "{$izin_ad}yazma" ] );
			if ( $permission[ "yazma" ] == 1 ) {
				$permission[ "okuma" ] = 1;
			}
			if ( !is_numeric ( $modul_id ) ) {
				$permissions[ 0 ][ $modul_id ] = $permission;
			} else {
				$permissions[ $modul_id ] = $permission;
			}
		}
		if ( false ) {
			echo("<pre>");
			var_dump ( $permissions );

			echo("</pre>");
			exit ();
		}
		$uye_grubu->permissions = $permissions;
		if ( !is_edit ) {
			$uye_grubu->save ();
			yonlendir ( parse_panel_url ( array( "uye_gruplari" => "" , "m_islem" => "duzenle" , "id" => $uye_grubu->ID , "msg" => 1 ) ) );
		} else {
			$uye_grubu->update ();
			/*
			  echo("<pre>");
			  var_dump($_POST);
			  echo("</pre>");
			  exit; */
			yonlendir ( parse_panel_url ( array( "uye_gruplari" => "" , "m_islem" => "duzenle" , "id" => $uye_grubu->ID , "msg" => 2 ) ) );
		}
	}
	?>
    <div class="card card-shadow mb-4">
        <div class="card-header border-0">
            <div class="custom-title-wrap bar-pink">
                <div class="custom-title">
                    <?php
                    if ( is_edit ) {
                        echo(___ ( "Panel Kullanıcı Grubunu Düzenle" ));
                    } else {
                        echo(___ ( "Panel Kullanıcı Grubu Ekleme" ));
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
							<?php echo(___ ( "Ad" )); ?>
						</label>
						<div class="col-md-9">
							<?php
							$value = $uye_grubu->ad;
							echo("<input type='text' class='req form-control input-medium' maxlength='150' name='ad' value='{$value}' />");
							?>
						</div>
					</div>
					<?php
					if ( !is_edit && $UG->seviye == 1 ) {
						?>
						<div class="form-group row">
							<label class="col-md-3 col-form-label">
								<?php echo(___ ( "Seviye" )); ?>
							</label>
							<div class="col-md-9">
								<div class="radio-list">
									<?php
									printf ( '<div style="margin-right:20px;float:left;"><input class="iCheck-flat-green" type="radio" name="seviye" value="%1$d" id="seviye%1$d"%2$s /><label class="  control-label"> %3$s</label></div>' , 2 , " checked='checked'" , ___ ( "Ajans" ) );
									printf ( '<div style="margin-right:20px;float:left;"><input class="iCheck-flat-green" type="radio" name="seviye" value="%1$d" id="seviye%1$d"%2$s /><label class="  control-label"> %3$s</label></div>' , 3 , null , ___ ( "Müşteri" ) );
									?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
							<?php echo(___ ( "Yetkiler / İzinler" )); ?>
						</label>
						<div class="col-md-9">
							<script type="text/javascript">
								$(document).ready(function () {
									$("input:checkbox").bind("click", function () {
										var $this = $(this);
										var $td = $this.parents("td:eq(0)");
										var $tr = $td.parent();
										var $tds = $tr.find("> td");
										var tdi = $tds.index($td);
										if ($this.is(":checked")) {
											if (tdi > 1) {
												$tds.filter(":lt(" + tdi + ")").each(function () {
													var $cb = $(this).find("input:checkbox");
													if (!$cb.is(":checked")) {
														$cb.prop("checked", "checked").trigger("tihla");
													}
												})
											}
										}
										if (!$this.is(":checked")) {
											if (tdi < 3) {
												$tds.filter(":gt(" + tdi + ")").each(function () {
													var $cb = $(this).find("input:checkbox");
													if ($cb.is(":checked")) {
														$cb.removeProp("checked").trigger("tihla");
													}
												});
											}
										}
									})
								});
							</script>
							<table class="table" summary="" id="perm_list">
								<thead>
									<tr>
										<th style="width: 70%;">
											<?php echo(___ ( "Modül" )); ?>
										</th>
										<th class="text-center" style="width: 10%; min-width: 60px;">
											<?php echo(___ ( "Okuma" )); ?>
										</th>
										<th class="text-center" style="width: 10%; min-width: 60px;">
											<?php echo(___ ( "Yazma" )); ?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$moduller_query_string = 'select `id`, `ad` from `d_moduller` order by `sira` asc';
									$moduller_query = mysql_query ( $moduller_query_string );
									while ( $moduller = mysql_fetch_array ( $moduller_query , MYSQL_ASSOC ) ) {
										if ( !($UG->check_permission ( $moduller[ "id" ] , null , 0 ) || $UG->check_permission ( $moduller[ "id" ] , null , 1 )) ) {
											continue;
										}
										$isim = "modul_{$moduller[ "id" ]}_";
										?>
                                        <tr>
											<td>
												<?php
												printf ( '<input type="hidden" name="izin[]" value="%1$s" class="iCheck-square-green"/><input type="hidden" name="id[]" value="%2$s" />' , $isim , $moduller[ "id" ] );
												echo(___ ( $moduller[ "ad" ] ));
												?>
											</td>
											<td class="text-center">
												<?php
												$checkbox = $UG->check_permission ( $moduller[ "id" ] , null , 0 );
												if ( $checkbox ) {
													$checked = $uye_grubu->check_permission ( $moduller[ "id" ] , null , 0 );
													printf ( '<input type="checkbox" class="custom colored blue iCheck-square-green" name="%1$sokuma" id="%1$sokuma" value="1"%2$s />' , $isim , $checked ? " checked='checked'" : null  );
												} else {
													echo("&nbsp;");
												}
												?> 
											</td>
											<td class="text-center">
												<?php
												$checkbox = $UG->check_permission ( $moduller[ "id" ] , null , 1 );
												if ( $checkbox ) {
													$checked = $uye_grubu->check_permission ( $moduller[ "id" ] , null , 1 );
													printf ( '<input type="checkbox" class="custom colored red iCheck-square-green" name="%1$syazma" id="%1$syazma" value="1"%2$s />' , $isim , $checked ? " checked='checked'" : null  );
												} else {
													echo("&nbsp;");
												}
												?> 
											</td>
										</tr>
										<?php
									}

									$sabit_moduller = array();
									$sabit_moduller[ "menu" ] = "Menü";
									$sabit_moduller[ "ceviriler" ] = "Sabit Tercümeler";
									$sabit_moduller[ "ayarlar" ] = "Ayarlar";
									$sabit_moduller[ "uyeler" ] = "Panel Kullanıcıları";
									$sabit_moduller[ "uye_gruplari" ] = "Panel Kullanıcı Grupları";
									foreach ( $sabit_moduller as $sabit_moduller_k => $sabit_moduller_v ) {
										if ( !($UG->check_permission ( 0 , $sabit_moduller_k , 0 ) || $UG->check_permission ( 0 , $sabit_moduller_k , 1 ) || $UG->check_permission ( 0 , $sabit_moduller_k , 2 )) ) {
											continue;
										}
										$isim = "sabit_{$sabit_moduller_k}_";
										?>
										<tr>
											<td>
												<?php
												printf ( '<input type="hidden" name="izin[]" value="%1$s" class="iCheck-square-green"/><input type="hidden" name="id[]" value="%2$s" />' , $isim , $sabit_moduller_k );
												echo(___ ( $sabit_moduller_v ));
												?>
											</td>
											<td class="text-center">
												<?php
												$checkbox = false; //$UG->check_permission(0, $sabit_moduller_k, 0);
												if ( $checkbox ) {
													$checked = $uye_grubu->check_permission ( 0 , $sabit_moduller_k , 0 );
													printf ( '<input type="checkbox" class="custom colored blue iCheck-square-green" name="%1$sokuma" id="%1$sokuma" value="1"%2$s />' , $isim , $checked ? " checked='checked'" : null  );
												} else {
													echo("&nbsp;");
												}
												?> 
											</td>
											<td class="text-center">
												<?php
												$checkbox = $UG->check_permission ( 0 , $sabit_moduller_k , 1 );
												if ( $checkbox ) {
													$checked = $uye_grubu->check_permission ( 0 , $sabit_moduller_k , 1 );
													printf ( '<input type="checkbox" class="custom colored red iCheck-square-green" name="%1$syazma" id="%1$syazma" value="1"%2$s />' , $isim , $checked ? " checked='checked'" : null  );
												} else {
													echo("&nbsp;");
												}
												?> 
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="form-actions">
					<button type="button" class="btn btn-outline-success form-pill" onclick="$('#modul_detay_lang').submit_form();"><?php echo(___ ( "Kaydet" )); ?></button>
				</div>
			</form>
		</div>
	</div>

<?php } ?>
