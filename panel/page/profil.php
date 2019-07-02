<?php
//$old_datas = fetch_to_array("select * from `pr_uy`")
$custom_status_message_text = array();

if ( isset ( $_GET[ "msg" ] ) ) {
	switch ( ( int ) $_GET[ "msg" ] ) {
		case 1:
			dump_message ( ___ ( "Bilgileriniz başarıyla güncellenmiştir." ) );
			break;
		case 2:
			dump_message ( ___ ( "Şifreniz başarıyla güncellenmiştir." ) );
			break;
		case 3:
			dump_message ( ___ ( "Girmiş olduğunuz şifreler eşleşmiyor." ) , 4 );
			break;
	}
}

$UN = new user();
$UN->get_byID ( $U->ID );
if ( isset ( $_POST[ "form_submit" ] ) ) {
	if ( isset ( $_POST[ "isim" ] ) ) {
		guvenlik_ ( $_POST[ "isim" ] );
		$UN->isim = $_POST[ "isim" ];
		$UN->update ();
		yonlendir ( parse_panel_url ( array( "profile" => "" , "msg" => 1 ) ) );
	}
	if ( isset ( $_POST[ "sifre" ] ) ) {
		if ( $_POST[ "sifre" ] == $_POST[ "sifre2" ] ) {
			$UN->set_password ( $_POST[ "sifre" ] );
			yonlendir ( parse_panel_url ( array( "profile" => "" , "msg" => 2 ) ) );
		} else {
			yonlendir ( parse_panel_url ( array( "profile" => "" , "msg" => 3 ) ) );
		}
	}
}
?>
<div class="row">
	<div class="col-md-6">
		<div class="portlet box green">
			<div class="portlet-title">
				<div class="caption">
					<?php echo(___ ( "Bilgileriniz" )); ?>
				</div>
			</div>
			<div class="portlet-body form">
				<form action="" method="post" id="profil_form1" class="form-horizontal" enctype="multipart/form-data">
					<input type="hidden" name="form_submit" value="">
					<div class="form-body">
						<div class="form-group">
							<label class="col-md-3 control-label"><?php echo(___ ( "İsim" )); ?></label>
							<div class="col-md-9">
								<?php
								$value = $UN->isim;
								echo("<input type='text' class='req form-control short' maxlength='150' name='isim' value='{$value}' />");
								?>
							</div>
						</div>
					</div>
					<div class="form-actions">
						<div class="row">
							<div class="col-md-offset-3 col-md-9">
								<button type="button" class="btn green" onclick="$('#profil_form1').submit_form();"><?php echo(___ ( "Kaydet" )); ?></button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="portlet box red">
			<div class="portlet-title">
				<div class="caption">
					<?php echo(___ ( "Şifre Değiştir" )); ?>
				</div>
			</div>
			<div class="portlet-body form">
				<form action="" method="post" id="profil_form2" class="form-horizontal" enctype="multipart/form-data">
					<input type="hidden" name="form_submit" value="1">
					<div class="form-body">
						<div class="form-group">
							<label class="col-md-3 control-label"><?php echo(___ ( "Şifre" )); ?></label>
							<div class="col-md-9">
								<?php
								echo("<input type='password' class='req form-control short' maxlength='20' name='sifre' value='' />");
								?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label"><?php echo(___ ( "Şifre" )); ?> (<?php echo(___ ( "Tekrar" )); ?>)</label>
							<div class="col-md-9">
								<?php
								echo("<input type='password' class='req form-control short' maxlength='20' name='sifre2' value='' />");
								?>
							</div>
						</div>
					</div>
					<input type="hidden" name="form_submit" value="">
					<div class="form-actions">
						<div class="row">
							<div class="col-md-offset-3 col-md-9">
								<button type="button" class="btn red" onclick="$('#profil_form2').submit_form();"><?php echo(___ ( "Şifremi Değiştir" )); ?></button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
