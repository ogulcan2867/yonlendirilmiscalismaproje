<div class="card card-shadow mb-4">
    <dl class="accordion">
        <?php
        $custom_status_message_text = array();
        $m_islem = isset ($_GET["m_islem"]) && in_array($_GET["m_islem"], array("ekle", "duzenle")) ? $_GET["m_islem"] : null;

        $menu_datalar = array();
        if ($m_islem == "duzenle" && isset ($_GET["id"])) {
            $menu_datalar = fetch_to_array("select * from `o_menuler` where UID={$_GET[ "id" ]}");
            if (is_null($menu_datalar) || ( int )$menu_datalar["root_id"] == 0) {
                $m_islem = null;
                $menu_datalar = array();
            }
        }

        if (is_null($m_islem)) {

            if (isset ($_GET["sil"])) {
                // dizi degilse de diziye cevirip dizi gibi isliyecegiz
                $_POST["ids"] = isset ($_POST["ids"]) ? $_POST["ids"] : array($_GET["sil"]);
                $ids = $_POST["ids"];
                foreach ($ids as $id) {
                    // sayisal bir deger degilse bir sonraki ogeye gec
                    if (!is_numeric($id)) {
                        continue;
                    }

                    // kayit var mi, yoksa 10'luk log at
                    $veri = fetch_one("o_menuler", "UID", $id, "id");
                    if (!is_null($veri) && $veri != false) {
                        $query_string = sprintf('delete from `o_menuler` where `UID`=%1$d', $id);
                        if (mysql_query($query_string)) {
                            if (mysql_affected_rows() > 0) {
                                $status_message = 1;
                            }
                        } else {
                            log(mysql_error(), 3);
                        }
                    } else {
                        arg_log("record not found in '$modul_tablo_adi' with UID '{$id}'", 10);
                    }
                }
                echo '<script>swal("İşlem Başarılı!", "Menü başarıyla silinmiştir.", "success");</script>';
            }
            ?>
            <?php
            function write_menu_item($root_id, $basamak, $top_root)
            {
                $menuler_query_string = sprintf('select *, GROUP_CONCAT(`dil_id` SEPARATOR \',\') AS `f_dil_ids` from `o_menuler` where `root_id`=%1$d group by UID order by `sira` asc', $root_id);
                $menuler_query = mysql_query($menuler_query_string);
                while ($menuler = mysql_fetch_array($menuler_query)) {
                    $f_dil_ids = explode(",", $menuler["f_dil_ids"]);
                    ?>
                    <tr class="step_<?php echo($basamak) ?>">
                        <td>
                            <?php
                            if ($basamak > 0) {
                                for ($mix = 0; $mix < $basamak - 1; $mix++) {
                                    echo('<span style="display: inline-block; width: 15px">&nbsp;</span>');
                                }
                                echo('<span style="display: inline-block; width: 15px;"><span class="fa fa-chevron-right text-info"></span></span>');
                            }

                            if (in_array(dil_id, $f_dil_ids)) {
                                if ($menuler["dil_id"] == dil_id) {
                                    echo($menuler["ad"]);
                                } else {
                                    echo(fetch("select `ad` from `o_menuler` where `UID`={$menuler[ "UID" ]} and dil_id=1 limit 1"));
                                }
                            } else {
                                $f_dil_id_vals = array();
                                for ($f_dil_id_i = 0; $f_dil_id_i < count($f_dil_ids); $f_dil_id_i++) {
                                    if ($f_dil_ids[$f_dil_id_i] == $menuler["dil_id"]) {
                                        $f_dil_id_vals[$f_dil_ids[$f_dil_id_i]] = $menuler["ad"];
                                    } else {
                                        $f_dil_id_vals[$f_dil_ids[$f_dil_id_i]] = fetch("select `ad` from `o_menuler` where `UID`={$menuler[ "UID" ]} and dil_id={$f_dil_ids[ $f_dil_id_i ]} limit 1");
                                    }
                                }
                                $f_dil_string = "";
                                foreach ($f_dil_id_vals as $f_dil_id_k => $f_dil_id_v) {
                                    $f_dil_string .= "\n" . $_LANGUAGES[$f_dil_id_k]["ad"] . " -> " . $f_dil_id_v;
                                }
                                $f_dil_string = trim($f_dil_string, "\n");
                                echo("<abbr title='{$f_dil_string}' class='dib pr lang_abbr'><span class='dib pa'>&nbsp;</span>[Türkçe Karşılığı Girilmemiş]</abbr>");
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (( int )$menuler["modul_id"] == 0) {
                                echo(___("Bağlantı"));
                            } else {
                                echo(___(fetch_one("d_moduller", "id", $menuler["modul_id"], "ad")));
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $checked = $menuler["yeni_pencere"] == 1;
                            if ($checked) {
                                ?>
                                <div class="fa text-success fa-check"></div>
                                <?php
                            } else {
                                ?>
                                <div class="fa text-danger fa-remove"></div>
                                <?php
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            echo("<input type='text' value='{$menuler[ "sira" ]}' class='form-control text-center' onchange='table_update_field_value({t:\"o_menuler\", i:{$menuler[ "UID" ]}, sF: \"sira\"}, this)' />");
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($basamak + 1 < $top_root["derinlik"]) {
                                ?><a
                                href="<?php echo(parse_panel_url(array("menu" => "", "m_islem" => "ekle", "id" => $menuler["UID"]))); ?>"
                                class="btn btn-outline-primary rounded-0"
                                style="margin-right: 10px"
                                title="<?php echo(___("Ekle")); ?>"><span
                                            class="fa fa-plus"></span></a><?php
                            }
                            if (( int )$menuler["duzenlenebilir"] == 1) {
                                ?><a
                                href="<?php echo(parse_panel_url(array("menu" => "", "m_islem" => "duzenle", "id" => $menuler["UID"]))); ?>"
                                class="btn btn-outline-success rounded-0"
                                style="margin-right: 10px" title="<?php echo(___("Düzenle")); ?>"><span
                                            class="fa fa-pencil"></span></a><?php
                            }
                            if (( int )$menuler["duzenlenebilir"] == 1) {
                                ?><a
                                href="<?php echo(parse_panel_url(array("menu" => "", "sil" => $menuler["UID"]))); ?>"
                                onclick="return confirm('<?php echo(___("Bu öğeyi silmek istiyor musunuz ?")); ?>');"
                                class="btn btn-outline-danger rounded-0" title="<?php echo(___("Kalıcı olarak sil")); ?>"><span
                                            class="fa fa-remove"></span></a>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    write_menu_item($menuler["UID"], $basamak + 1, $top_root);
                }

            }
            $ana_menuler_query = mysql_query("select * from `o_menuler` where `root_id`=0 order by `sira` asc");
            while ($ana_menuler = mysql_fetch_array($ana_menuler_query)) { ?>
                <dt>
                    <a>
                        <div class="card-header border-0">
                            <div class="custom-title-wrap bar-pink">
                                <div class="custom-title"><?php echo($ana_menuler["ad"]) ?></div>
                            </div>
                        </div>
                    </a>
                </dt>
                <dd>
                    <div class="card-body">

                        <a href="<?php echo(parse_panel_url(array("menu" => "", "m_islem" => "ekle", "id" => $ana_menuler["UID"]))); ?>"
                           class="btn btn-circle btn-success"><i
                                    class="fa fa-plus"></i> <?php echo(___("Kayıt Ekle")); ?></a>
                        <br/>
                        <br/>
                        <table id="mtablea" class="table table-striped table-bordered table-full-width table-hover"
                               summary="">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 35%;">
                                    <?php echo(___("Ad")); ?>
                                </th>
                                <th class="text-center" style="width: 20%;">
                                    <?php echo(___("Bağlantı")); ?>
                                </th>
                                <th class="text-center" style="width: 5%;">
                                    <?php echo(___("Yeni Sekme")); ?>
                                </th>
                                <th class="text-center" style="width: 10%;" class="taci">
                                    <?php echo(___("Sıra")); ?>
                                </th>
                                <th class="text-center" style="width: 15%;">
                                    <?php echo(___("İşlem")); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            write_menu_item($ana_menuler["UID"], 0, $ana_menuler);
                            ?>
                            </tbody>
                        </table>
                    </div>
                </dd>
            <?php }
        ?>
    </dl>
</div>
       <?php } else {
        $ITEM_ID = 0;
        define("is_edit", $m_islem == "duzenle");

        if (isset ($_GET["msg"])) {
            switch (( int )$_GET["msg"]) {
                case 1:
                    echo '<script>swal("İşlem Başarılı!", "Menü başarıyla eklenmiştir.", "success");</script>';
                    break;
                case 2:
                    echo '<script>swal("İşlem Başarılı!", "Menü başarıyla güncellenmiştir.", "success");</script>';
                    break;
            }
        }


        $step_for_page_while = array();
        if (is_edit) {
            $ITEM_ID = intval($_GET["id"]);

            // dil sayisi kadar sayfayi dondurucez. bu sebeplen asagidaki kodlar biraz karisiktir ,
            // ama degistirmeyin
            // ------------------
            // ilk eklenen en ustte ardindan diger eklenmisler diller tablosundaki
            // siraya gore gelsin, eklenmemisler en alta gelsin diye ilk once id sirali olarak
            // dil idlerini cekip diziye aliyoruz
            $language_ids_of_founded_records = array();
//		$record_query = mysql_query ( "select dil_id from `o_menuler` where UID={$ITEM_ID} order by id asc limit 100" );
            $record_query = mysql_query("select ax.dil_id from `o_menuler` as ax, o_diller as dil where ax.UID={$ITEM_ID} and dil.id=ax.dil_id and dil.aktif=1 order by id asc limit 100");
            while ($record = mysql_fetch_array($record_query, MYSQL_ASSOC)) {
                $language_ids_of_founded_records[] = $record["dil_id"];
                $step_for_page_while[] = $record["dil_id"];
            }

            while ($langs = mysql_fetch_array($lang_query)) {
                if (in_array($langs["id"], $language_ids_of_founded_records)) {
                    continue;
                }
                $step_for_page_while[] = $langs["id"];
            }
            unset ($langs, $language_ids_of_founded_records, $record, $record_query);

            mysql_data_seek($lang_query, 0);
        } else {
            // sayfa 1 kere calisacak sonrasinda duzenlemeye giricek vs
            $step_for_page_while[] = ( int )isset ($_POST["____dil_id"]) ? $_POST["____dil_id"] : dil_id;
        }

        for ($step_for_page_while_i = 0, $step_for_page_while_m = count($step_for_page_while); $step_for_page_while_i < $step_for_page_while_m; $step_for_page_while_i++) {
            $dil_id = $step_for_page_while[$step_for_page_while_i];
            $have_this_langauge = false;
            if (is_edit) {
                $old_datas = fetch_to_array("select * from `o_menuler` where UID={$ITEM_ID} and dil_id={$dil_id} limit 1");
                if ($old_datas != false) {
                    $have_this_langauge = true;
                }
            } else {
                $old_datas["yeni_pencere"] = 0;
                $old_datas["modul_id"] = 0;
                $old_datas["sira"] = intval(fetch("select max(sira) from `o_menuler` where `root_id`={$_GET[ "id" ]}")) + 1;
            }

            if (isset ($_POST["form_submit"])) {
                $mqb = new qb ('o_menuler');
                $table_fields = array();
                $table_values = array();

                $mqb->add_write('ad', guvenlik($_POST["ad"]));

                if (is_edit) {
                    $untranslatable_default_values = fetch_to_array("select * from `o_menuler` where UID={$ITEM_ID} order by id asc");
                }

                if (!is_edit || (is_edit && $step_for_page_while_i == 0 && $dil_id == $_POST["____dil_id"])) {
                    $mqb->add_write('yeni_pencere', guvenlik($_POST["yeni_pencere"]));
                    $mqb->add_write('modul_id', guvenlik($_POST["modul_id"]));
                    $mqb->add_write('url', (isset ($_POST["url"]) && $_POST["modul_id"] == 0 ? guvenlik($_POST["url"]) : ""));
                    $mqb->add_write('sira', guvenlik($_POST["sira"]));
                    $mqb->add_write('x_id', isset ($_POST["record"]) && is_numeric($_POST["record"]) ? $_POST["record"] : null);
                    if (!is_edit) {
                        $mqb->add_write('root_id', $_GET["id"]);
                    }
                } else {
                    $mqb->add_write('yeni_pencere', $untranslatable_default_values["yeni_pencere"]);
                    $mqb->add_write('modul_id', $untranslatable_default_values["modul_id"]);
                    $mqb->add_write('sira', $untranslatable_default_values["sira"]);
                    $mqb->add_write('x_id', is_numeric($untranslatable_default_values["x_id"]) && intval($untranslatable_default_values["modul_id"]) > 0 ? $untranslatable_default_values["x_id"] : null);
                    $mqb->add_write('url', isset ($_POST["url"]) && $untranslatable_default_values["modul_id"] == 0 ? guvenlik($_POST["url"]) : "");
                    $mqb->add_write('root_id', $untranslatable_default_values["root_id"]);
                }

                if ((!is_edit || (is_edit && !$have_this_langauge)) && $dil_id == $_POST["____dil_id"]) {
                    $mqb->add_write('dil_id', $_POST["____dil_id"]);

                    if (is_edit) {
                        $mqb->add_write('UID', $ITEM_ID);
                    }
                    $query_string = $mqb->insert();
                    if (false) {
                        var_dump($_POST);
                        echo("<hr>");
                        var_dump($query_string);
                        exit ();
                    }
                    //echo $query_string;
                    $query = mysql_query($query_string) or die (mysql_error());
                    if (is_edit) {

                    } else {
                        $LID = mysql_insert_id();
                        mysql_query(sprintf('update `o_menuler` set UID=%1$d where id=%1$d limit 1', $LID));
                    }
                    yonlendir(parse_panel_url(array("menu" => "", "m_islem" => "duzenle", "id" => is_edit ? $ITEM_ID : $LID, "msg" => is_edit ? 2 : 1)));
                } elseif (is_edit && $dil_id == $_POST["____dil_id"]) {
                    $mqb->add_condition('UID', $ITEM_ID);
                    $mqb->add_condition('dil_id', $dil_id);
                    $query_string = $mqb->update();
                    if (false) {
                        var_dump($_POST);
                        echo("<hr>");
                        var_dump($query_string);
                        exit ();
                    }
                    //echo $query_string;
                    mysql_query($query_string) or die (mysql_error() . "<br>" . $query_string);
                    // diger menu kayitlarini guncellemek icin
                    $lr = fetch_to_array(sprintf('select * from `o_menuler` where `UID`=%1$d and `dil_id`=%2$d limit 1', $ITEM_ID, $dil_id));
                    $modul_id = ( int )$lr["modul_id"];
                    $x_id = ( int )$lr["x_id"];
                    $query_string = "update `o_menuler` set `modul_id`={$modul_id},  `x_id`=" . ($x_id == 0 ? "NULL" : $x_id) . ", `url`=IF(`x_id` > 0, '', `url`) where UID={$ITEM_ID}";
                    mysql_query($query_string) or die (mysql_error() . "<br>" . $query_string);

                    yonlendir(parse_panel_url(array("menu" => "", "m_islem" => "duzenle", "id" => $ITEM_ID, "msg" => 2)));
                }
            }
            ?>

            <dt>
                <a>
                    <div class="card-header border-0">
                        <div class="custom-title-wrap bar-pink">
                            <div class="custom-title">
                                <?php
                                $show_expand_layer = true;
                                if (is_edit) {
                                    $dil_ad = fetch_one("o_diller", "id", $dil_id, "ad");
                                    if (!$have_this_langauge) {
                                        $anakayit = fetch_to_array("select * from `o_menuler` where `UID`={$ITEM_ID} order by dil_id asc limit 1");
                                        if (( int )$anakayit["modul_id"] == 0 || (( int )$anakayit["modul_id"] > 0 && ( int )fetch(sprintf('select count(*) from `%1$s` where `UID`=%2$d and `dil_id`=%3$d', fetch_one("d_moduller", "id", $anakayit["modul_id"], "tablo_adi"), $anakayit["x_id"], $dil_id)) > 0)) {
                                            echo("$dil_ad ");
                                            echo(___("Dilini Oluştur"));
                                        } else {
                                            $show_expand_layer = false;
                                            echo("$dil_ad ");
                                            echo(___("Dilinde Oluşturulamaz"));
                                        }
                                    } else {
                                        echo("$dil_ad ");
                                        echo(___("Güncelle"));
                                    }
                                } else {
                                    echo(___("Menü Ekleme"));
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </a>
            </dt>
            <dd>
                <div class="card-body form">
                    <?php if ($show_expand_layer) { ?>
                        <form action="" method="post" id="modul_detay_lang<?php echo($dil_id); ?>"
                              class="form-horizontal" enctype="multipart/form-data">
                            <div class="form-body">
                                <input type="hidden" name="form_submit" value="">
                                <?php
                                if (!is_edit) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Dil")); ?>
                                        </label>
                                        <div class="col-md-9">
                                            <select name="____dil_id" class="form-control short" id="____dil_id"
                                                    onchange='modul_id_change($("#modul_id").val());'>
                                                <?php
                                                while ($lang = mysql_fetch_array($lang_query)) {
                                                    printf('<option value="%1$d">%2$s</option>', $lang["id"], $lang["ad"]);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Üst Menü")); ?>
                                        </label>
                                        <div class="col-md-9">
                                            <?php
                                            $ust_menu = fetch_to_array("select * from `o_menuler` where `UID`={$_GET[ "id" ]} order by `dil_id` asc limit 1");
                                            echo($ust_menu["ad"]);
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                } else {
                                    printf('<input type="hidden" name="____dil_id" id="____dil_id" value="%1$d" />', $dil_id);
                                }
                                ?>
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">
                                        <?php echo(___("Ad")); ?>
                                    </label>
                                    <div class="col-md-9">
                                        <?php
                                        $value = isset ($old_datas["ad"]) ? $old_datas["ad"] : null;
                                        ?>
                                        <input type='text' class='req form-control input-medium' maxlength='150'
                                               name='ad' value='<?php echo($value); ?>'/>
                                    </div>
                                </div>
                                <?php
                                if ($step_for_page_while_i == 0) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Yeni Pencere")); ?>
                                        </label>
                                        <div class="col-md-9">
                                            <?php
                                            $yeni_pencere_0 = $old_datas["yeni_pencere"] == 0 ? " checked='checked'" : null;
                                            $yeni_pencere_1 = $old_datas["yeni_pencere"] == 1 ? " checked='checked'" : null;
                                            ?>
                                            <div class="radio-list">
                                                <label class="radio-inline"><input type='radio' name='yeni_pencere'
                                                                                   class="iCheck-square-green"
                                                                                   id='yeni_pencere_1'
                                                                                   value='1'<?php echo($yeni_pencere_1); ?> /> <?php echo(___("Evet")); ?>
                                                </label>
                                                <label class="radio-inline"><input type='radio' name='yeni_pencere'
                                                                                   class="iCheck-square-green"
                                                                                   id='yeni_pencere_0'
                                                                                   value='0'<?php echo($yeni_pencere_0); ?> /> <?php echo(___("Hayır")); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row ">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Modül")); ?>
                                        </label>
                                        <div class="col-md-4">
                                            <script type="text/javascript">
                                                function menu_modul_changed() {
                                                    var select = $('#modul_kayitlar');
                                                    select.html('');
                                                    var modulID = parseInt($("#modul_id").val());
                                                    var modulkayit_div = $(".modulkayit_div");
                                                    var modulbaglanti_div = $(".modulbaglanti_div");

                                                    if (modulID == 0) {
                                                        //Bağlantı Göster
                                                        modulbaglanti_div.css({'display':'flex'});
                                                        modulkayit_div.css({'display':'none'});
                                                    }else{
                                                        //Modül Göster
                                                        modulkayit_div.css({'display':'flex'});
                                                        modulbaglanti_div.css({'display':'none'});
                                                        var selected = modulID == <?php echo($old_datas["modul_id"]) ?> ? <?php echo(isset ($old_datas["x_id"]) && is_numeric($old_datas["x_id"]) ? $old_datas["x_id"] : 0) ?> : -1;
                                                        $.post("ajax.php", {
                                                            action: "menu",
                                                            modul_id: modulID,
                                                            dil_id: <?php echo($dil_id) ?>,
                                                            selected: selected,
                                                        }, function (callback) {
                                                            var $callback = $.parseJSON(callback);
                                                            $.each( $callback, function(i, obj) {
                                                                $('<option value="'+obj.id+'">' + obj.text + '</option>')
                                                                    .appendTo(select);

                                                            });
                                                        });
                                                    }
                                                }
                                                $(document).ready(function () {
                                                    menu_modul_changed();
                                                });
                                            </script>
                                            <select name="modul_id" class="form-control input-short" id="modul_id"
                                                    onchange="menu_modul_changed()">
                                                <option value="0"><?php echo(___("Bağlantı")); ?></option>
                                                <?php
                                                $modules_query = mysql_query("select * from `d_moduller` where `name_field_prop_id`>0 and `linklenebilir`=1 order by ad asc");
                                                while ($modules = mysql_fetch_array($modules_query)) {
                                                    if (( int )fetch(sprintf('select count(*) from `%1$s` where `deleted`=0', $modules["tablo_adi"])) == 0) {
                                                        continue;
                                                    }
                                                    $selected = $old_datas["modul_id"] == $modules["id"] ? " selected='selected'" : null;
                                                    printf('<option value="%1$d"%3$s>%2$s</option>', $modules["id"], $modules["ad"], $selected);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row modulkayit_div">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Kayıt")); ?>
                                        </label>
                                        <div class="col-md-9">
                                            <select name="record" id="modul_kayitlar" class="form-control">

                                            </select>
                                        </div>
                                    </div>
                                    <?PHP
                                }
                                ?>
                                <?php
                                // baglanti durumu ilk sayfada ve modul 0 'a esitse gelecek
                                // silinecek bir yorum satiri
                                if ($step_for_page_while_i == 0 || (isset ($old_datas["modul_id"]) && ( int )$old_datas["modul_id"] == 0)) {
                                    ?>
                                    <div class="form-group row modulbaglanti_div">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Bağlantı")); ?>
                                        </label>
                                        <div class="col-md-9">
                                            <?php
                                            $value = isset ($old_datas["url"]) ? $old_datas["url"] : null;
                                            ?>
                                            <input type='text' class='form-control input-medium' maxlength='200'
                                                   name='url' value='<?php echo($value) ?>'/>
                                        </div>
                                    </div>
                                    <?PHP
                                }
                                ?>
                                <?php
                                if ($step_for_page_while_i == 0) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label">
                                            <?php echo(___("Sıra")); ?>
                                        </label>
                                        <div class="col-md-3">
                                            <input type='text' class='req form-control text-center numeric '
                                                   maxlength='5' name='sira' value='<?php echo($old_datas["sira"]); ?>'/>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline-success rounded-0"
                                        onclick="$('#modul_detay_lang<?php echo($dil_id); ?>').submit_form();"></span><?php echo(___("Kaydet")); ?></button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </dd>

            <?php
        }
        ?>
<?php } ?>