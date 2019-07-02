<?php
$ITEM_ID = $item_id;
$record = fetch_to_array(sprintf('select * from `%1$s` where `deleted`=0 and `UID`=%2$d order by `dil_id` asc limit 1', $selected_modul["tablo_adi"], $ITEM_ID));

$process_crop = false;
$status_message = -1;
$custom_status_message_text = null;


if (isset ($_GET["d_p"]) && $_WRITE_PERMISSION) {
    $photo_which_deleted = fetch_to_array(sprintf('select * from `o_resimler` where `id` = %1$d limit 1', $_GET["d_p"]));
    if ($photo_which_deleted != null && $photo_which_deleted != false) {
        $photo_type = fetch_to_array(sprintf('select * from `d_modul_resim_tipler` where `id`=%1$d limit 1', $photo_which_deleted["tip_id"]));
        $picture_sizes_query = mysql_query(sprintf('select * from `d_modul_resim_boyutlar` where `resim_tip_id`=%1$d order by `en` desc', $photo_type["id"])) or die (mysql_error());
        while ($picture_sizes = mysql_fetch_array($picture_sizes_query)) {
            @unlink(_DIR_UPLOADS_ . "{$selected_modul[ "tablo_adi" ]}/{$picture_sizes[ "klasor_ad" ]}/{$photo_which_deleted[ "dosya_adi" ]}");
        }
    }
    mysql_query(sprintf('insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)', $U->ID, $selected_modul_id, $ITEM_ID, 4)) or die (mysql_error());
    mysql_query(sprintf('delete from `o_resimler` where `id` = %1$d limit 1', $_GET["d_p"]));
    yonlendir(parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "resim", "id" => $ITEM_ID)));
}

// croptan gelen datalari alip islemece
if (isset ($_POST["crop"]) && $_WRITE_PERMISSION) {
    $resimYol = $_POST["f"];
    // modulde bir ada alani varsa permanent linki olusmus demektir, olumussa da bunun adini kullanarak dosya adi ureticez
    $have_permanent_link = fetch(sprintf('select `permant` from `o_permanents` where `modul_id`=%1$d and `x_id`=%2$d order by `varsayilan` desc limit 1', $selected_modul_id, $ITEM_ID));
    if ($have_permanent_link != false && $have_permanent_link != null && !empty ($have_permanent_link)) {
        $resimDosyaAd = floor(rand(100000, 999999)) . "_" . $have_permanent_link . "." . get_file_extension($resimYol);
    } else {
        $resimDosyaAd = floor(rand(100000, 999999)) . "_" . time() . "." . get_file_extension($resimYol);
    }


    $picture_sizes_query = mysql_query(sprintf('select * from `d_modul_resim_boyutlar` where `resim_tip_id`=%1$d order by `en` desc', $_POST["pt"])) or die (mysql_error());
    while ($picture_sizes = mysql_fetch_array($picture_sizes_query)) {
        $resim = new resim();
        $resim->kaynak = _DIR_TEMP_UPLOADS_ . $resimYol;
        $resim->kirp($_POST['x'], $_POST['y'], $_POST['w'], $_POST['h']);
        $wgen = $picture_sizes["en"];
        $wyuk = $picture_sizes["boy"];
        $kaynak["yol"] = _DIR_UPLOADS_ . "/{$selected_modul[ "tablo_adi" ]}/{$picture_sizes[ "klasor_ad" ]}/";
        if ($wgen > 0 && $wyuk > 0) {
            $resim->boyutlandir($wgen, $wyuk);
            $resim->hedef = $kaynak["yol"] . $resimDosyaAd;
            $resim->kaydet();
        } elseif ($wgen > 0 || $wyuk > 0) {
            $een = $_POST['w'];
            $eboy = $_POST['h'];
            //echo("een={$een}, eboy={$eboy}<br />");
            $yen = 0;
            $yboy = 0;
            if ($wgen > 0) {
                $yen = $wgen;
                $yboy = $yen / $een * $eboy;
            } elseif ($wgen > 0) {
                $yboy = $wyuk;
                $yen = $yboy / $eboy * $een;
            }
            //echo("en={$yen}, boy={$yboy}<br />");
            //echo $kaynak["yol"] . "<br />";
            $resim->boyutlandir(round($yen), round($yboy));
            $resim->hedef = $kaynak["yol"] . $resimDosyaAd;
            $resim->kaydet();
        } else {
            $resim->hedef = $kaynak["yol"] . $resimDosyaAd;
            $resim->kaydet();
        }
        $resim->oldur();
    }
    $new_sira = intval(fetch(sprintf('select max(`sira`) from `o_resimler` where `modul_id`=%1$d and `x_id`=%2$d and `tip_id`=%3$d', $selected_modul_id, $ITEM_ID, $_POST["pt"]))) + 1;
    mysql_query(sprintf('insert into `o_resimler` (`modul_id`, `x_id`, `tip_id`, `dosya_adi`, `sira`, `ad`, `aciklama`) values(%1$d, %2$d, %3$d, "%4$s", %5$d, "%6$s", "%7$s")', $selected_modul_id, $ITEM_ID, $_POST["pt"], $resimDosyaAd, $new_sira, guvenlik($_POST["ad"]), guvenlik($_POST["aciklama"]))) or die (mysql_error());
    mysql_query(sprintf('insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)', $U->ID, $selected_modul_id, $ITEM_ID, 5)) or die (mysql_error());
    //exit();
    @unlink($resim->kaynak);
    //$resim->oldur ();
    yonlendir(parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "resim", "id" => $ITEM_ID)));
}


// dosya gonderildiyse crop islemi icin hazirlamaca
if (isset ($_FILES["dosya"]) && $_WRITE_PERMISSION) {
    $dosya = $_FILES["dosya"];
    if ($dosya["error"] == 0) {
        if (in_array($dosya["type"], array("image/jpeg", "image/pjpeg", "image/jpg", "image/pjpg", "image/gif", "image/png"))) {
            // ebat kontrolu icin resim nesnesi yaratiyoruz ve degiskene atayip
            // RAM'i bosa harcamasin diye oldurup unset ediyoruz
            $resim = new resim();
            $resim->kaynak = $dosya["tmp_name"];
            $width = $resim->genislik();
            $height = $resim->yukseklik();
            $resim->oldur();
            unset ($resim);

            // kontrol icin resim tip id'sinde en buyuk olani buluyoruz
            // bu sekilde resimler kalitesiz gorunmeyecek
            $tip_id = $_POST["type_id"];
            $min_width_height_array = fetch_to_array(sprintf('select en, boy from d_modul_resim_boyutlar where resim_tip_id=%1$d order by en desc', $tip_id));
            $min_width = ( int )$min_width_height_array["en"];
            $min_height = ( int )$min_width_height_array["boy"];
            if ($width < $min_width || $height < $min_height) {
                $status_message = 2;
                $custom_status_message_text = ___("Yüklemiş olduğunuz resimin ebatları, minimum ebatlardan küçüktür.");
            } else {
                $new_name = "tmp_" . floor(rand(125125, 999999)) . "_" . time() . "." . get_file_extension($dosya["name"]);
                if (move_uploaded_file($dosya["tmp_name"], _DIR_TEMP_UPLOADS_ . $new_name)) {
                    $process_crop = array("dosya" => $new_name, "type_id" => $tip_id, "ad" => $_POST["ad"], "aciklama" => $_POST["aciklama"]);
                } else {
                    $status_message = 2;
                    $custom_status_message_text = ___("Beklenmedik bir hata gerçekleşti.");
                }
            }
        } else {
            $status_message = 2;
            $custom_status_message_text = ___("Geçersiz resim formatı");
        }
    } else {
        $status_message = 2;
        $custom_status_message_text = ___("Geçersiz resim formatı");
    }
}
?>
<?php
if ($status_message != -1) {
    switch ($status_message) {
        case 2:
            dump_message($custom_status_message_text, 4);
            break;
    }
}
$picture_types_query = mysql_query("select `id`, `ad`, `maksimum` from `d_modul_resim_tipler` where `modul_id`={$selected_modul_id} and (select count(*) from `d_modul_resim_boyutlar` where `resim_tip_id`=`d_modul_resim_tipler`.`id`) > 0 order by `sira` asc");
$picture_type_count = mysql_num_rows($picture_types_query);
if ($_WRITE_PERMISSION) {
    ?>
    <div class="card card-shadow mb-4">
        <div class="card-header border-0">
            <div class="custom-title-wrap bar-pink">
                <div class="custom-title"><?php echo(___("Fotoğraf Ekleme")); ?></div>
            </div>
        </div>
        <div class="card-body form">
            <form action="#cropbox" method="post" enctype="multipart/form-data" class="form-horizontal">
                <div class="form-body">
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            <?php echo(___("Kayıt")); ?>
                        </label>
                        <div class="col-md-9 input-group">
                            <?php
                            printf('
<div class="input-group-prepend">
    <span class="input-group-text" id="basic-addon1"><a href="%1$s"><i class="fa fa-share"></i></a></span>
</div>
<input disabled class="form-control" aria-describedby="basic-addon1" value="%2$s">', parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "duzenle", "id" => $ITEM_ID)), isset ($selected_modul["name_field"]) ? $record[$selected_modul["name_field"]] : "Kayıt");
                            ?>
                        </div>
                    </div>
                    <div class="form-group row"
                         style="<?php echo($picture_type_count == 1 ? "display: none;" : null) ?>">
                        <label class="col-md-3 col-form-label">
                            <?php echo(___("Tür")); ?>
                        </label>
                        <div class="col-md-9">
                            <select name='type_id' id="picture_type_id" class="form-control input-medium"
                                    onchange="set_picture_min_size_info();">
                                <?php
                                while ($picture_type = mysql_fetch_array($picture_types_query)) {
                                    $min_width_height_array = fetch_to_array(sprintf('select en, boy from d_modul_resim_boyutlar where resim_tip_id=%1$d order by en desc', $picture_type["id"]));
                                    $pic_count = ( int )fetch(sprintf('select count(*) from `o_resimler` where `modul_id`=%1$d and `x_id`=%2$d and `tip_id`=%3$d', $selected_modul_id, $ITEM_ID, $picture_type["id"]));
                                    printf('<option value="%1$d" min_width="%3$d" min_height="%4$d" max_pic="%5$d" pic_count="%6$d">%2$s</option>', $picture_type["id"], ___($picture_type["ad"]), $min_width_height_array["en"], $min_width_height_array["boy"], $picture_type["maksimum"], $pic_count);
                                }
                                ?>
                            </select>
                            <script type="text/javascript">
                                $(document).ready(function () {
                                    set_picture_min_size_info();
                                });
                                function set_picture_min_size_info() {
                                    var $combo = $("#picture_type_id");
                                    var $option = $combo.find("option:selected");
                                    var max_pic = parseInt($option.attr("max_pic"));
                                    var pic_count = parseInt($option.attr("pic_count"));
                                    var $button_holder = $("#button_holder");
                                    var $text_holder = $("#text_holder");
                                    $("#picture_min_width").html($option.attr("min_width"));
                                    $("#picture_min_height").html($option.attr("min_height"));
                                    if (max_pic != 0 && pic_count >= max_pic) {
                                        $button_holder.css("display", "none");
                                        $text_holder.css("display", "");
                                        if ($combo.find("option").length > 1) {
                                            $text_holder.html("<?php echo(___("Bu fotoğraf türünden eklenebilecek en fazla fotoğrafı eklemişsiniz. Başka fotoğraf ekleyemezsiniz. Değiştirmek isterseniz silip yeniden ekleyebilirsiniz.")); ?>");
                                        } else {
                                            $text_holder.html("<?php echo(___("Eklenebilecek en fazla fotoğrafı eklemişsiniz. Başka fotoğraf ekleyemezsiniz. Değiştirmek isterseniz silip yeniden ekleyebilirsiniz.")); ?>");
                                        }
                                    } else {
                                        $button_holder.css("display", "");
                                        $text_holder.css("display", "none");
                                    }
                                }
                            </script>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            <?php echo(___("Ad")); ?>
                        </label>
                        <div class="col-md-9">
                            <input type="text" name="ad" class="form-control input-medium"/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            <?php echo(___("Açıklama")); ?>
                        </label>
                        <div class="col-md-9">
                            <textarea name="aciklama" class="form-control input-large"
                                      style=" height: 125px;"></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            <?php echo(___("Dosya")); ?>
                        </label>
                        <div class="col-md-9">
                            <input type="file" name="dosya"/>
                            <br/>
                            <?php
                            $max_dosya_boyutu = str_replace(array("M", "G", "K"), array(" MB", " GB", " KB"), ini_get("post_max_size"));
                            printf('%1$s <strong>%2$s</strong> %3$s <span id="picture_min_size_info">%4$s <strong><span id="picture_min_width"></span></strong>%5$s <strong><span id="picture_min_height"></span></strong>%6$s', ___("Dosya en fazla"), $max_dosya_boyutu, ___("olabilir."), ___("Resim ebatları en az"), ___("px genişliğinde,"), ___("px yüksekliğinde olmalıdır."));
                            ?>
                        </div>
                    </div>
                    <div id="button_holder" style="display: none;">
                        <button type="button" onclick="$(this).parents('form').submit();"
                                class="btn btn-outline-success"><?php echo(___("Yükle")); ?></button>
                    </div>
                    <div id="text_holder" style="display: none;">

                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
}
?>

<div class="card card-shadow mb-4">
    <dl class="accordion">
        <?php
        mysql_data_seek($picture_types_query, 0);
        while ($picture_type = mysql_fetch_array($picture_types_query)) {
            $photos_query = mysql_query("select `id`, `dosya_adi`, `sira`, `ad` from `o_resimler` where `tip_id`={$picture_type[ "id" ]} and `x_id`={$ITEM_ID} order by sira asc limit 150");
            if (mysql_num_rows($photos_query) == 0) {
                continue;
            }
            ?>
            <?php
            if ($picture_type_count > 1) {
                ?>
                <dt>
                    <a>
                        <div class="card-header border-0">
                            <div class="custom-title-wrap bar-pink">
                                <div class="custom-title"><?php echo(___($picture_type["ad"])); ?></div>
                            </div>
                        </div>
                    </a>
                </dt>
                <?php
            }
            ?>
            <dd>
                <div class="card-body">
                    <div class='row form-horizontal form-row-stripped'>
                        <?php
                        $smallest_in_picture_type = fetch_to_array(sprintf('select `en`, `boy`, `klasor_ad`, `ad` from `d_modul_resim_boyutlar` where `resim_tip_id`=%1$d order by `en` asc limit 1', $picture_type["id"]));
                        $path = _DIR_UPLOADS_ . $selected_modul["tablo_adi"] . "/" . $smallest_in_picture_type["klasor_ad"] . "/";
                        while ($photos = mysql_fetch_array($photos_query)) {
                            $sil_url = parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "resim", "id" => $ITEM_ID, "d_p" => $photos["id"]));
                            printf('
                        <div class="col-md-3">
                            <div class="card box grey" style="padding: 10px">
                                <div class="card-title">
                                    <div class="caption" style="float: left">
                                    <div class="row" >
                                        <div class="col-sm-4">
                                            <input type="text" class="text-center form-control numeric" value="%3$d" ' . ($_WRITE_PERMISSION ? 'onchange="photo_sira(%1$d, this);"' : 'disabled="disabled" readonly="readonly"') . ' /></div>
                                        </div>
                                    </div>
                                    <div class="tools" style="float: right">
                                    %5$s
                                    </div>
                                </div>
                                <div class="portlet-body ">
                                    <img src="%4$s%2$s" class="img-responsive img-thumbnail" alt="" />
                                    <br />
                                    <br />
                                    <input type="text" class="form-control uff" data-uf_field="ad" data-uf_tablename="o_resimler" data-uf_id="%1$s" data-uf_idfield="id" value="%6$s" />
                                </div>
                            </div>
                        </div>
                ', $photos["id"], $photos["dosya_adi"], $photos["sira"], $path, $_WRITE_PERMISSION ? sprintf('<div class="text-right">
                        <a href="%1$s" onclick="return confirm(\'%2$s\');" class="btn btn-danger form-pill">
                            <span class="fa fa-remove"></span>
                        </a>
                    </div>', $sil_url, ___("Bu resmi kalıcı olarak silmek istiyor musunuz ?")) : null, $photos['ad']);
                        }
                        ?>
                    </div>
                </div>
            </dd>
            <?php
        }
        ?>
    </dl>
</div>


<?php
/*
  // test icin kullanilabilir
  $process_crop = array();
  $process_crop["type_id"] = "1";
  $process_crop["dosya"] = "tmp_220798_1375080918.jpg";
 */
if ($process_crop != false) {
    $min_width_height_array = fetch_to_array(sprintf('select en, boy from d_modul_resim_boyutlar where resim_tip_id=%1$d order by en desc', $process_crop["type_id"]));
    $min_width = ( int )$min_width_height_array["en"];
    $min_height = ( int )$min_width_height_array["boy"];
    ?>
    <div class="card card-shadow mb-4">

        <div class="card-header border-0">
            <div class="custom-title-wrap bar-pink">
                <div class="custom-title"><?php echo(___("Fotoğraf Kırpma")); ?></div>
            </div>
        </div>
        <div id="crop_portled_body" class="card-body">
            <script type="text/javascript">
                $(document).ready(function () {
                    <?php
                    $oran = $min_width != 0 && $min_height != 0 ? $min_width / $min_height : 0;
                    echo("$('#cropbox').Jcrop({");
                    if ($oran <> 0) {
                        echo("aspectRatio: {$oran},");
                    }
                    echo('boxWidth: $("#crop_portled_body").width() - 8,');
                    echo('onSelect: updateCoords,');
                    echo('setSelect:[0,0,2000,0] });');
                    ?>

                });

                function updateCoords(c) {
                    $('#x').val(c.x);
                    $('#y').val(c.y);
                    $('#w').val(c.w);
                    $('#h').val(c.h);
                    if (parseInt($('#w').val())) {
                        $("#onizleme").attr("src", "ajax.php?action=crop&yol=" + $("#cropbox").attr("src") + "&x=" + c.x + "&y=" + c.y + "&w=" + c.w + "&h=" + c.h + "&gen=<?php echo($min_width); ?>&yuk=<?php echo($min_height); ?>");
                        if ($("#onizleme").css("display") == "none") {
                            $("#onizleme").fadeIn(500, function () {
                                $("#onizleme").css("display", "block");
                            });
                        }
                    } else {
                        $("#onizleme").css("display", "none");
                    }
                }


                function checkCoords() {
                    if (parseInt($('#w').val()))
                        return true;
                    alert('<?php echo(___("Lütfen önce kırpmak istediğiniz bölgeyi seçin...")) ?>');
                    return false;
                }
                ;
            </script>
            <style type="text/css">
                #crop_container {
                    padding: 3px;
                    border: 1px #c8c8c8 solid;
                    display: inline-block;
                }

                #onizleme {
                    padding: 10px;
                    border: 2px solid #e6e5e4;
                }
            </style>
            <form action="" method="post" onsubmit="return checkCoords();">
                <div id="crop_container">
                    <img src="<?php echo(_DIR_TEMP_UPLOADS_ . $process_crop["dosya"]); ?>" id="cropbox"/>
                </div>
                <input type="hidden" id="x" name="x"/>
                <input type="hidden" id="y" name="y"/>
                <input type="hidden" id="w" name="w"/>
                <input type="hidden" id="h" name="h"/>
                <input type="hidden" name="ad" value="<?php echo($process_crop["ad"]); ?>"/>
                <input type="hidden" name="aciklama" value="<?php echo($process_crop["aciklama"]); ?>"/>
                <input type="hidden" id="f" name="f" value="<?php echo($process_crop["dosya"]); ?>"/>
                <input type="hidden" id="pt" name="pt" value="<?php echo($process_crop["type_id"]); ?>"/>
                <input type="hidden" name="crop" value=""/>
                <div class="col-sm-12" style="margin: 20px 0">
                    <button onclick="$(this).parents('form').submit()"
                            class="btn btn-outline-success form-pill"><?php echo(___("Resmi Kırp")); ?></button>
                </div>
            </form>
            <img src="" style="display: none; max-width: 100%;" id="onizleme"/>
        </div>
    </div>
    <?php
}
?>
<script type="text/javascript">
    $(document).ready(function () {
        $("input.uff").bind('change', field_change);
    });

    function field_change() {
        var $this = $(this);
        var UID = $this.data('uf_id');
        var field = $this.data('uf_field');
        var id_field = $this.data('uf_idfield');
        var table = $this.data('uf_tablename');
        var x = {};
        x.action = "table_update_field_value2";
        x.table = table;
        x.id = UID;
        x.field = field;
        x.id_field = id_field;
        x.deger = $this.is(":checkbox") ? $this.is(":checked") ? 1 : 0 : $this.val();
        $.post("ajax.php", x, function () {
            toastr.options = Arasbil.toastr_options("block_top");
            toastr['success']("<?php echo(___ ( "Fotoğraf İsimlendirme İşlemi Başarılı" )); ?>")
        });
    }
    
    function photo_sira(id,input) {
        var x = {};
        x.action = "photo_order";
        x.i = id;
        x.o = $(input).val();
        $.post("ajax.php", x, function () {
            toastr.options = Arasbil.toastr_options("block_top");
            toastr['success']("<?php echo(___ ( "Fotoğraf Sıralama İşlemi Başarılı" )); ?>")
        });
    }
</script>