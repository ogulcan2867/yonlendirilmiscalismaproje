<div class="card card-shadow mb-4">
    <dl class="accordion">
        <?php
        $custom_status_message_text = array();
        if (isset ($_GET["msg"])) {
            switch (( int )$_GET["msg"]) {
                case 1:
                    echo '<script>swal("İşlem Başarılı!", "Ayarlar başarıyla güncellenmiştir.", "success");</script>';
                    break;
            }
        }
        $CKEditor_index = 1;
        $step_for_page_while_i = 0;
        $language_count = count($_LANGUAGES);
        foreach ($_LANGUAGES as $dil_id => $LANG) {
            $show_untranslatable_fields = $step_for_page_while_i == 0;
            $groups_query_string = sprintf('select d_ayar_gruplar.* from d_ayar_gruplar, d_ayar_alanlar where d_ayar_gruplar.id=d_ayar_alanlar.grup_id %1$s and  d_ayar_gruplar.id = %2$d group by d_ayar_gruplar.id', $show_untranslatable_fields ? null : " and d_ayar_alanlar.tercumelenebilir=1  ", $secili_tab);
            $groups_query = mysql_query($groups_query_string);
            if (mysql_num_rows($groups_query) == 0) {
                continue;
            }

            $fields = array();
            $fields_query = mysql_query(sprintf('select * from d_ayar_alanlar %s', $show_untranslatable_fields == false ? " where tercumelenebilir=1" : null));
            while ($fields_ = mysql_fetch_array($fields_query, MYSQL_ASSOC)) {
                $fields[$fields_["tablo_field"]] = $fields_;
            }
            // kullanmadigimiz degiskenleri kaldiriyoruz direk
            unset ($fields_, $fields_query);

            // ayarlari kaydetme islemi : dil == postlanana esitse (whileda
            // donerken kontrol ediyoruz bi daha bi daha fieldlari bul vs ugrasmayalim diye)
            if (isset ($_POST["form_submit"]) && $dil_id == $_POST["____dil_id"]) {
                foreach ($fields as $field) {
                    $is_numeric = ($field["tip"] == 0 && in_array($field["alt_tip"], array(1, 2))) || in_array($field["tip"], array(2, 3, 4));
                    if (isset ($_POST[$field["tablo_field"]])) {
                        $deger = guvenlik($_POST[$field["tablo_field"]]);
                        if ($is_numeric && !is_numeric($deger)) {
                            $deger = 0;
                        }
                    } else {
                        $deger = $is_numeric ? 0 : "";
                        continue;
                    }

                    $check = fetch(sprintf('select count(*) from `o_ayarlar` where `dil_id`=%2$d and `ayar_key`="%3$s"', $deger, $field["tercumelenebilir"] == 0 ? 0 : $dil_id, $field["tablo_field"])) > 0;
                    if ($check) {
                        $update_query_string = sprintf('update `o_ayarlar` set `deger`="%1$s" where `dil_id`=%2$d and `ayar_key`="%3$s"', $deger, $field["tercumelenebilir"] == 0 ? 0 : $dil_id, $field["tablo_field"]) or die (mysql_error());
                        $update_query = mysql_query($update_query_string);
                    } else {
                        $insert_query_string = sprintf('insert into `o_ayarlar` (`deger`, `dil_id`, `ayar_key`) values("%1$s", %2$d, "%3$s")', $deger, $field["tercumelenebilir"] == 0 ? 0 : $dil_id, $field["tablo_field"]);
                        $insert_query = mysql_query($insert_query_string);
                    }
                }
                yonlendir("?ayarlar=&msg=1&tab=" . $_POST["tab"]);
            }

            $old_datas = array();
            $old_datas_keys = array();
            foreach ($fields as $field) {
                $old_datas_keys[] = $field["tablo_field"];
            }

            $old_datas_query = mysql_query(sprintf('select * from `o_ayarlar` where `dil_id` in (0, %1$d) and `ayar_key` in ("%2$s")', $dil_id, implode('", "', $old_datas_keys)));
            while ($old_datas_ = mysql_fetch_array($old_datas_query)) {
                $old_datas[$old_datas_["ayar_key"]] = $old_datas_["deger"];
            }

            foreach ($fields as $field) {
                $is_numeric = ($field["tip"] == 0 && in_array($field["alt_tip"], array(1, 2))) || in_array($field["tip"], array(2, 3, 4));
                if (!isset ($old_datas[$field["tablo_field"]])) {
                    if ($is_numeric) {
                        $old_datas[$field["tablo_field"]] = 0;
                    } else {
                        $old_datas[$field["tablo_field"]] = "";
                    }
                }
            }
            // kullanmadigimiz degiskenleri kaldiriyoruz direk
            unset ($fields, $field, $old_datas_keys, $old_datas_);
            ?>
            <dt>
                <a>
                    <div class="card-header border-0">
                        <div class="custom-title-wrap bar-pink">
                            <div class="custom-title"><?php echo($LANG["ad"]); ?></div>
                        </div>
                    </div>
                </a>
            </dt>
            <dd>
                <div class="card-body form">
                    <form action="" method="post" id="modul_detay_lang<?php echo($dil_id); ?>"
                          enctype="multipart/form-data" class="form-horizontal" role="form">
                        <input type="hidden" name="tab" value="<?php echo $secili_tab; ?>"/>
                        <?php
                        printf('<input type="hidden" name="____dil_id" value="%1$d" />', $dil_id);
                        $groups_count = mysql_num_rows($groups_query);
                        $site_baslik = "";
                        $site_aciklamasi = "";
                        while ($groups = mysql_fetch_array($groups_query)) {
                            if ($groups_count > 1) {
                                ?>
                                <h2 class="grp_seperator"><?php echo(___($groups["ad"])); ?></h2>
                                <?php
                            }
                            ?>
                            <div class="form-body">
                                <?php
                                $fields_query = mysql_query("select * from d_ayar_alanlar where grup_id={$groups[ "id" ]} " . ($show_untranslatable_fields == false ? " and tercumelenebilir=1" : null) . " order by sira asc");
                                // "cevirilebilir olmayan alanlari gostereyim mi" degiskeni
                                while ($fields = mysql_fetch_array($fields_query)) {
                                    if ($fields["tablo_field"] == "site_baslik") {
                                        $site_baslik = $old_datas[$fields["tablo_field"]];
                                    }
                                    if ($fields["tablo_field"] == "site_aciklamasi") {
                                        $site_aciklamasi = $old_datas[$fields["tablo_field"]];
                                    }
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">
                                            <?php echo(___($fields["ad"])); ?>
                                        </label>
                                        <div class="col-sm-9">
                                            <?php
                                            $classes = "";
                                            if ($fields["is_required"] == 1) {
                                                $classes .= " req";
                                            }
                                            if (in_array(intval($fields['tip']), array(0, 1, 4))) {
                                                $classes .= " form-control";
                                            }
                                            $yardim_ac = "";
                                            if ($fields["yardim_aciklamasi"] != "") {
                                                $yardim_ac = "<span class='help-block'>" . ___($fields["yardim_aciklamasi"]) . "</span>";
                                            }
                                            switch ($fields["tip"]) {
                                                case 0: // textbox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    switch ($fields["alt_tip"]) {
                                                        case 0:
                                                            $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                            echo("<input type='text' class='{$classes}' maxlength='{$fields[ "maxlength" ]}' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                            break;
                                                        case 1:
                                                            $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                            $classes .= "numeric tac";
                                                            echo("<input type='text' class='{$classes}' maxlength='5' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                            break;
                                                        case 2:
                                                            $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                            echo("<input type='text' class='{$classes}' maxlength='10' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                            break;
                                                        case 3:
                                                            $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                            if (!is_null($value)) {
                                                                $value = date("d.m.Y", strtotime($value));
                                                            }
                                                            $classes .= "datepicker";
                                                            echo("<input type='text' class='{$classes}' readonly='readobly' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                            break;
                                                    }
                                                    break;
                                                case 1: // textarea ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    echo("<textarea class='{$classes}' name='{$fields[ "tablo_field" ]}' rows='4'>{$value}</textarea>");
                                                    break;
                                                case 2: // checkbox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $checked = isset ($old_datas[$fields["tablo_field"]]) && $old_datas[$fields["tablo_field"]] == 1 ? " checked='checked'" : null;
                                                    echo("<input type='checkbox' name='{$fields[ "tablo_field" ]}'{$checked} value='1' />");
                                                    break;
                                                case 3: // radio button ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $selected_item = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                    $sorgu = mysql_query("select * from d_ayar_alan_secenekler where alan_id={$fields[ "id" ]}") or die (mysql_error());
                                                    while ($okut = mysql_fetch_array($sorgu)) {
                                                        $checked = $selected_item == $okut["deger"] ? " checked='checked'" : null;
                                                        echo("<input type='radio' name='{$fields[ "tablo_field" ]}' id='{$fields[ "tablo_field" ]}_{$okut[ "deger" ]}' value='{$okut[ "deger" ]}'{$checked} /><label for='{$fields[ "tablo_field" ]}_{$okut[ "deger" ]}'>" . ___($okut["etiket"]) . "</label> ");
                                                    }
                                                    break;
                                                case 4: // combobox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $selected_item = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                    echo("<select name='{$fields[ "tablo_field" ]}' style='min-width: 240px;'>");
                                                    $sorgu = mysql_query("select * from d_ayar_alan_secenekler where alan_id={$fields[ "id" ]}") or die (mysql_error());
                                                    while ($okut = mysql_fetch_array($sorgu)) {
                                                        $selected = $selected_item == $okut["deger"] ? " selected='selected'" : null;
                                                        echo("<option value='{$okut[ "deger" ]}'{$selected}>" . ___($okut["etiket"]) . "</option>");
                                                    }
                                                    echo("</select>");
                                                    break;
                                                case 5: // fck editor ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    ?>
                                                    <textarea class="hidden clear"
                                                              style="display: none"
                                                              id="ta_summernote<?php echo($CKEditor_index) ?>"
                                                              name="<?php echo($fields["tablo_field"]) ?>"><?php echo($value); ?></textarea>
                                                    <div id="summernote<?php echo($CKEditor_index); ?>"><?php echo(htmlspecialchars_decode($value)); ?></div>
                                                    <script>
                                                        $("#summernote<?php echo($CKEditor_index); ?>").summernote({
                                                            height: 300,
                                                            lang: '<?php echo(dil_id == 1 ? 'tr-TR' : 'en-US') ?>',
                                                            toolbar: [
                                                                ['style', ['style']],
                                                                ['font', ['bold', 'italic', 'underline', 'clear']],
                                                                ['fontname', ['fontname']],
                                                                ['color', ['color']],
                                                                ['para', ['ul', 'ol', 'paragraph']],
                                                                ['height', ['height']],
                                                                ['table', ['table']],
                                                                ['insert', ['link', 'picture', 'hr']]
                                                            ],
                                                        }).on('summernote.change', function (customEvent, contents, $editable) {
                                                            $("#ta_summernote<?php echo($CKEditor_index); ?>").val($('#summernote<?php echo($CKEditor_index); ?>').summernote('code'));

                                                        });
                                                    </script>
                                                    <?php
                                                    $CKEditor_index++;
                                                    break;
                                                case 6: // dosya ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    break;
                                            }
                                            echo $yardim_ac;
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                                if ($site_baslik != "" && $site_aciklamasi != "" && $secili_tab == 1) {
                                    ?>
                                    <tr>
                                        <td colspan="2">&nbsp;</td>
                                        <td>
                                            <a data-toggle="modal"
                                               data-target="#google_gorunum"><?php echo(___("Girdiğiniz değerlere göre sitenizin Google'da şu şekilde listelenecektir.")); ?></a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </div>
                            <?php
                        }
                        ?>
                        <input type="hidden" name="form_submit" value="">
                    </form>
                </div>
                <button type="button" class="btn btn-outline-success form-pill"
                        onclick="$('#modul_detay_lang<?php echo($dil_id); ?>').submit_form();"><?php echo(___("Kaydet")); ?></button>
            </dd>
            <?php
            $step_for_page_while_i++;
        }
        ?>
    </dl>
</div>