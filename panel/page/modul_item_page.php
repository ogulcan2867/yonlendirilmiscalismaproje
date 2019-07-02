<dl id="modul_item_page" class="card card-shadow mb-4">
    <div class="accordion">
        <?php
        $dump_post_etc = !true;
        $ITEM_ID = 0;
        $status_message = isset ($_GET["sm"]) && is_numeric($_GET["sm"]) && in_array($_GET["sm"], array(1, 2)) ? ( int )$_GET["sm"] : -1;
        define("is_edit", $action == "duzenle");

        $have_picture = fetch("select count(*) from `d_modul_resim_tipler` where `modul_id`={$selected_modul_id}") > 0;

        $CKEditor_index = 1;

        // fckeditor initialize
        //include_once("js/fckeditor/fckeditor.php");
        //$sBasePath = "js/fckeditor/";
        // sayfa kendisini tekrar edecek ama diller icin bir while olusturmak gerekyiro. aksi takdirde eski paneldeki,
        // ayni sayfayi include etmek zorunda olacagiz ki farkli bir yonteme gitmekte fayda var :/

        function tag_relations($x_id, $modul_id, $dil_id)
        {
            $selected_tag_ids = array();

            $posted_tag_ids = array(); //isset ( $_POST[ "tagid" ] ) ? $_POST[ "tagid" ] : array();
            $posted_tag_names = array(); //isset ( $_POST[ "tagad" ] ) ? $_POST[ "tagad" ] : array();
            if (isset ($_POST["tagggg"]) && is_array($_POST["tagggg"])) {
                foreach ($_POST["tagggg"] as $tx) {
                    $tx1 = explode('|', $tx);
                    $posted_tag_ids[] = count($tx1) == 1 ? 0 : $tx1[0];
                    $posted_tag_names[] = count($tx1) == 1 ? $tx1[0] : $tx1[1];
                }
            }
            //$selected_tags_query = mysql_query(sprintf('select `etiket_id` from `o_etiket_iliskiler` where `x_id`=%1$d and `modul_id`=%2$d', $x_id, $modul_id)) or die(mysql_error());
            $selected_tags_query = mysql_query(sprintf('select tag.`id` from `o_etiket_iliskiler` rel, `o_etiketler` as tag where rel.`x_id`=%1$d and rel.`modul_id`=%2$d and tag.`id`=rel.`etiket_id` and tag.`dil_id`=%3$d', $x_id, $modul_id, $dil_id)) or die (mysql_error());
            while ($selected_tags = mysql_fetch_array($selected_tags_query)) {
                $selected_tag_ids[] = $selected_tags[0];
            }

            for ($i = 0, $m = count($posted_tag_ids); $i < $m; $i++) {
                if ($posted_tag_ids[$i] == 0) {

                    // ayni dil ve isimde baska etiket var mi diye kontrol ediyoruz,
                    // kullanıcı belki autocomplete e sokturmamis olabilir
                    $exist_tag_id = ( int )fetch(sprintf('select `id` from `o_etiketler` where `dil_id`=%1$d and `ad`="%2$s"', $dil_id, guvenlik($posted_tag_names[$i])));

                    // kontrolden gelen eger sifirdan buyukse var demektir
                    // dogal olarak yeni eklemek yerine varolanin id sini kullaniyoruz
                    if ($exist_tag_id > 0) {
                        $posted_tag_ids[$i] = $exist_tag_id;
                    } else {
                        mysql_query(sprintf('insert into `o_etiketler` (`dil_id`, `ad`) values(%1$d, "%2$s")', $dil_id, guvenlik($posted_tag_names[$i]))) or die (mysql_error());
                        $LID = mysql_insert_id();
                        $posted_tag_ids[$i] = $LID;
                    }
                }
            }

            $inserts = array_diff($posted_tag_ids, $selected_tag_ids);
            foreach ($inserts as $tag_id) {
                mysql_query(sprintf('insert into `o_etiket_iliskiler` (`x_id`, `modul_id`, `etiket_id`) values(%1$d, %2$d, %3$d)', $x_id, $modul_id, $tag_id));
            }

            $deletes = array_diff($selected_tag_ids, $posted_tag_ids);
            foreach ($deletes as $tag_id) {
                mysql_query(sprintf('delete from `o_etiket_iliskiler` where `x_id`=%1$d and `modul_id`=%2$d and `etiket_id`=%3$d', $x_id, $modul_id, $tag_id)) or die (mysql_error());
            }

        }

        // $field_name		= file inputunun name attribute'u
        // $x_id			= upload edilecek kaydin UID'si
        // $modul_id		= upload edilecek kaydin modul id'si
        // $dil_id		= 0: tümü | 0+ spesifik dil
        // $permission		= "ARG_UP_" ile baslayan sabitler (core.php)
        //					veya izin verilecek mime type'larin dizisi (tek de olsa
        //					dizi hâlinde gonderilmelidir)
        function modul_upload_files($field_name, $x_id, $modul_id, $dil_id, $permission = 0)
        {
            $allowed_mimes = is_array($permission) ? $permission : is_numeric($permission) ? get_mime_types($permission) : get_mime_types(0);
            if (isset ($_FILES) && isset ($_FILES[$field_name])) {
                if (strlen($_FILES[$field_name]["name"]) > 0 && $_FILES[$field_name]["error"] == 0) {
                    if (in_array($_FILES[$field_name]["type"], $allowed_mimes)) {

                        $modul = fetch_to_array(sprintf('select * from `d_moduller` where `id`=%1$d limit 1', $modul_id));
                        $file_extension = get_file_extension($_FILES[$field_name]["name"]);

                        // moduldeki item, halihazirda dosya varsa silmeye calisicaz
                        $old_file = fetch(sprintf('select `%1$s` from `%2$s` where `UID`=%3$d and `dil_id`=%4$d limit 1', $field_name, $modul["tablo_adi"], $x_id, $dil_id));
                        if (!empty ($old_file)) {
                            @unlink(_DIR_FILE_UPLOADS_ . $old_file);
                        }

                        // > file name
                        if (( int )$modul["name_field_prop_id"] > 0) {
                            $f_i = 0;
                            $permalink = fetch(sprintf('select `permant` from `o_permanents` where `modul_id`=%1$d and `x_id`=%2$d and `dil_id`=%3$d order by `varsayilan` desc limit 1', $modul_id, $x_id, $dil_id));
                            $name_field = fetch(sprintf('select tablo_field from d_modul_ozellikler where id=%1$d limit 1', $modul["name_field_prop_id"]));
                            $kayit = new entry ($modul_id, $x_id, $dil_id);
                            $mfilename = $permalink != "" ? $permalink : $kayit->$name_field;
                            while (true) {
                                $f_i++;
                                $f_suffix = $f_i == 0 ? null : "_" . $f_i;
                                if (!file_exists(_DIR_FILE_UPLOADS_ . $mfilename . $f_suffix . "." . $file_extension)) {
                                    $mfilename .= $f_suffix;
                                    break;
                                }
                            }
                            $new_file_name = $mfilename;
                        } else {
                            $f_i = 0;
                            while (true) {
                                $f_i++;
                                $new_file_name = "{$modul_id}_{$x_id}_{$dil_id}_{$f_i}";
                                if (!file_exists(_DIR_FILE_UPLOADS_ . $new_file_name . "." . $file_extension)) {
                                    break;
                                }
                            }
                        }
                        $new_file_name = $new_file_name . "." . $file_extension;
                        // < file name
                        move_uploaded_file($_FILES[$field_name]["tmp_name"], _DIR_FILE_UPLOADS_ . $new_file_name);
                        mysql_query(sprintf('update `%1$s` set `%2$s`="%3$s" where `UID`=%4$d %5$s', $modul["tablo_adi"], $field_name, $new_file_name, $x_id, $dil_id == 0 ? null : " and `dil_id`={$dil_id}")) or die (mysql_error());
                    } else {
                        die (___("Geçersiz dosya biçimi") . " : " . $_FILES[$field_name]["type"]);
                    }
                }
            }

        }

        function modul_upload_all_files($modul_id, $x_id, $dil_id)
        {
            $file_fields_query = mysql_query(sprintf('select * from `d_modul_ozellikler` where `modul_id`=%1$d and `tip`=6', $modul_id));
            while ($file_fields = mysql_fetch_array($file_fields_query)) {
                modul_upload_files($file_fields["tablo_field"], $x_id, $modul_id, $dil_id, $file_fields["alt_tip"]);
            }

        }

        function modul_relation_data_write($active_modul_id, $relative_to_modul_id, $x_id)
        {
            $selected_ids = array();
            $posteds = isset ($_POST["relation{$relative_to_modul_id}"]) ? is_array($_POST["relation{$relative_to_modul_id}"]) ? $_POST["relation{$relative_to_modul_id}"] : array($_POST["relation{$relative_to_modul_id}"]) : array();


            //$selected_id_query = mysql_query(sprintf('select `y_id` from `o_iliskiler` where `x_id`=%1$d and `modul_id`=%2$d', $x_id, $modul_id)) or die(mysql_error());
            $selected_id_query = mysql_query(sprintf('select `y_id` from `o_iliskiler` where `x_id`=%1$d and `x_modul_id`=%2$d and `y_modul_id`=%3$d', $x_id, $active_modul_id, $relative_to_modul_id)) or die (mysql_error());
            while ($selected_id = mysql_fetch_array($selected_id_query)) {
                $selected_ids[] = $selected_id[0];
            }


            $inserts = array_diff($posteds, $selected_ids);
            foreach ($inserts as $n_id) {
                mysql_query(sprintf('insert into `o_iliskiler` (`x_id`, `x_modul_id`, `y_id`, `y_modul_id`) values(%1$d, %2$d, %3$d, %4$d)', $x_id, $active_modul_id, $n_id, $relative_to_modul_id)) or die (mysql_error());
            }

            $deletes = array_diff($selected_ids, $posteds);
            foreach ($deletes as $n_id) {
                mysql_query(sprintf('delete from `o_iliskiler` where `x_id`=%1$d and `x_modul_id`=%2$d and `y_id`=%3$d and `y_modul_id`=%4$d', $x_id, $active_modul_id, $n_id, $relative_to_modul_id)) or die (mysql_error());
            }

        }

        $step_for_page_while = array();
        if (is_edit) {
            $ITEM_ID = intval($_GET["id"]);

            mysql_query(sprintf('update `%1$s` set `okundu` = 1 where `UID`= %2$d ', $selected_modul["tablo_adi"], $ITEM_ID));

            // dil sayisi kadar sayfayi dondurucez. bu sebeplen asagidaki kodlar biraz karisiktir ,
            // ama degistirmeyin
            // ------------------
            // ilk eklenen en ustte ardindan diger eklenmisler diller tablosundaki
            // siraya gore gelsin, eklenmemisler en alta gelsin diye ilk once id sirali olarak
            // dil idlerini cekip diziye aliyoruz
            $language_ids_of_founded_records = array();
            $record_query = mysql_query("select ax.dil_id from `{$selected_modul[ "tablo_adi" ]}` as ax, o_diller as dil where ax.UID={$ITEM_ID} and ax.deleted=0 and dil.id=ax.dil_id and dil.aktif=1 order by id asc limit 100");
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

        // baglantili modullerde secimi zorunlu olan modullerin tablolarini kontrol edip
        // eger data yoksa patlamasi icin islem yapiyoruz
        $required_module_dont_have_item_modul = null;
        $required_modules_query = mysql_query(sprintf('select * from `d_modul_iliskiler` where `modul_id`=%1$d and `is_required`=1', $selected_modul_id));
        while ($required_modules = mysql_fetch_array($required_modules_query, MYSQLI_ASSOC)) {
            $have_record = ( int )fetch(sprintf('select count(*) from `%1$s` where `deleted`=0', fetch_one("d_moduller", "id", $required_modules["relative_to"], "tablo_adi"))) > 0;
            if (!$have_record) {
                $required_module_dont_have_item_modul = $required_modules;
                break;
            }
        }


        if (is_array($required_module_dont_have_item_modul)) {
            dump_message(sprintf('%2$s <strong>%1$s</strong> %3$s.', fetch_one("d_moduller", "id", $required_modules["relative_to"], "ad"), ___("Devam etmek için"), ___("modülüne kayıt eklemeniz gerekmektedir")), 4);
        } else {
            for ($step_for_page_while_i = 0, $step_for_page_while_m = count($step_for_page_while); $step_for_page_while_i < $step_for_page_while_m; $step_for_page_while_i++) {
                $dil_id = $step_for_page_while[$step_for_page_while_i];
                $have_this_langauge = false;
                // eger duzenleme ise aktif dilin kayitlarini getiriyoruz
                if (is_edit) {
                    $old_datas = fetch_to_array("select * from `{$selected_modul[ "tablo_adi" ]}` where UID={$ITEM_ID} and dil_id={$dil_id} limit 1");
                    if ($old_datas != false) {
                        $have_this_langauge = true;
                        $relation_query = mysql_query("select modul.id as modulID, rel.ad, rel.is_required, rel.multi_select, modul.tablo_adi, modul.name_field_prop_id from d_modul_iliskiler as rel, d_moduller as modul where rel.modul_id={$selected_modul_id} and modul.id=rel.relative_to and modul.name_field_prop_id>0 order by rel.sira asc");
                        while ($relation = mysql_fetch_array($relation_query)) {
                            if ($relation["multi_select"] == 1) {
                                $old_datas["relation{$relation[ "modulID" ]}"] = array();
                                $selected_id_query = mysql_query(sprintf('select `y_id` from `o_iliskiler` where `x_id`=%1$d and `x_modul_id`=%2$d and `y_modul_id`=%3$d', $ITEM_ID, $selected_modul_id, $relation["modulID"])) or die (mysql_error());
                                while ($selected_id = mysql_fetch_array($selected_id_query)) {
                                    $old_datas["relation{$relation[ "modulID" ]}"][] = $selected_id[0];
                                }
                            } else {
                                $value = fetch(sprintf('select y_id from o_iliskiler where x_modul_id=%1$d and x_id=%2$d and y_modul_id=%3$d limit 1', $selected_modul_id, $ITEM_ID, $relation["modulID"]));
                                $old_datas["relation{$relation[ "modulID" ]}"] = intval($value);
                            }
                        }
                    }
                } else {
                    $old_datas["root_id"] = 0;
                }

                $invisible_fields = array();
                if (is_edit) {
                    $fields_query = mysql_query("select * from d_modul_ozellikler where modul_id={$selected_modul_id} and tip=9 order by sira asc");
                    while ($fields = mysql_fetch_array($fields_query)) {
                        //if($old_datas[fetch_one("d_modul_ozellikler", "id", $fields["parametre1"], "tablo_field")]
                        if ($old_datas[$fields["tablo_field"]] == 1 && $UG->seviye != 1) {
                            $invisible_fields[] = $fields["parametre1"];
                        }
                    }
                }

                // yazma yetkisi yoksa ve olusmamis bir dile ise bir sonrakine devam ediyoruz
                // sonucta varolmayan bir dilin kaydını gostermemize gerek yok
                if (!$_WRITE_PERMISSION && $step_for_page_while_i > 0 && !$have_this_langauge) {
                    continue;
                }

                if (isset ($_POST["form_submit"]) && $_WRITE_PERMISSION && $dil_id == $_POST["____dil_id"]) {
                    $untranslatable_default_values = array();
                    // ekleme veya guncelleme icin tablo alanlari ve degerlrei icin birer dizi olusturuyoruz
                    $table_fields = array(); // tablo alanlari
                    $table_values = array(); // degerleri
                    // cevirilemez alanlar, eger ana kayitsa ve update ise kullanilmasi icin setleniyor
                    // verinin diger dillerinde de cevirilemez alanlari update edicez yetim kalmasinlar
                    $untranslatable_fields = array();
                    $untranslatable_values = array();

                    // kayit ceviri ise ilk kayittan varolan degerleri almak icin diziye aktariyoruz
                    if (is_edit) {
                        $untranslatable_default_values = fetch_to_array("select * from `{$selected_modul[ "tablo_adi" ]}` where UID={$ITEM_ID} order by id asc");
                    }

                    //$untranslatable_fields = array_merge($untranslatable_fields, $invisible_fields);
                    foreach ($invisible_fields as $temp) {
                        $field_name = fetch_one("d_modul_ozellikler", "id", $temp, "tablo_field");
                        $untranslatable_fields[] = "`" . $field_name . "`";
                        $untranslatable_values[] = sprintf('\'%1$s\'', $untranslatable_default_values[$field_name]);
                    }

                    // eger kendine bagli ise yani root olma ihtimali varsa update ve insert icin data tanimliyoruz
                    if (( int )$selected_modul["kendine_baglilik"] > 0) {
                        if (isset ($_POST["root_id"])) {
                            $table_fields[] = "root_id";
                            $table_values[] = (is_edit && isset ($_POST["root_id"])) || (!is_edit && isset ($_POST["root_id"])) ? $_POST["root_id"] : $untranslatable_default_values["root_id"];
                        }
                    }

                    // modul ozelliklerinden tablo alanlarini getiriyoruz
                    $fields_qb = new qb ('d_modul_ozellikler');
                    $fields_qb->add_condition('modul_id', $selected_modul_id);
                    $fields_qb->add_order('sira');
                    hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/QUERY_PARSE/' . $selected_modul_id, $selected_modul_id);
                    $fields_query = mysql_query($fields_qb->select());
                    hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/BEFORE_PARSE/' . $selected_modul_id, $selected_modul_id);

                    $name_field_name = null;
                    $name_field_value = null;
                    while ($fields = mysql_fetch_array($fields_query)) {
                        if (in_array($fields["tip"], array(6, 7))) {
                            continue;
                        }
                        if ($fields["is_master"] == 1) {
                            if ($UG->seviye != 1) {
                                continue;
                            }
                        }
                        if (in_array($fields["tablo_field"], $untranslatable_fields)) {
                            continue;
                        }

                        $table_fields[] = "`{$fields[ "tablo_field" ]}`";

                        $is_translatable_field = is_translatable_field($fields);
                        // var_dump($is_translatable_field);
                        if (!$is_translatable_field) {
                            $untranslatable_fields[] = '`' . $fields["tablo_field"] . '`';
                        }

                        if (($is_translatable_field && is_edit) || ($step_for_page_while_i == 0 && is_edit) || !is_edit) {
                            guvenlik_($_POST[$fields["tablo_field"]]);
                            $value = $_POST[$fields["tablo_field"]];

                            if ($fields["tip"] == 0 && $fields["alt_tip"] == 3) {
                                $temp = explode(".", $value);
                                $value = date("Y-m-d H:i:s", mktime(0, 0, 0, $temp[1], $temp[0], $temp[2]));
                            }

                            // eger cevirilebilir falan bir alansa ad alanini da burda kontrol edip setleyecegiz
                            if ($selected_modul["name_field_prop_id"] == $fields["id"]) {
                                $name_field_name = $fields["tablo_field"];
                            }
                        } else {
                            $value = guvenlik($untranslatable_default_values[$fields["tablo_field"]]);
                        }

                        $is_numeric = ($fields["tip"] == 0 && in_array($fields["alt_tip"], array(1, 2))) || in_array($fields["tip"], array(2, 3, 4));
                        if ($is_numeric) {
                            $value = is_numeric($value) ? floatval($value) : 0;
                        } else {
                            //$value = "'{$value}'";
                        }

                        hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/PARSE/' . $selected_modul_id . '/' . $fields['id'], $selected_modul_id, $fields['id']);
                        $value = "'{$value}'";

                        if ($fields["tip"] == 5) {
                            $value = richtext_editor_getvalue($value);
                        }

                        if (!$is_translatable_field) {
                            $untranslatable_values[] = $value;
                        }

                        $table_values[] = $value;

                        // eger ad alani buysa ad degerini setliyoruz
                        if ($selected_modul["name_field_prop_id"] == $fields["id"]) {
                            $name_field_value = $value;
                        }

                        if ($fields["tip"] == 8) {
                            $table_fields[] = "`{$fields[ "parametre1" ]}`";
                            $table_values[] = "'" . ($_POST[$fields["tablo_field"]] == 0 ? guvenlik($_POST["{$fields[ "parametre1" ]}__url"]) : $_POST["{$fields[ "parametre1" ]}"]) . "'";
                        }
                    }

                    hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/AFTER_PARSE/' . $selected_modul_id, $selected_modul_id);

                    if (isset ($_POST["protected"])) {
                        $table_fields[] = "protected";
                        $untranslatable_fields[] = '`protected`';
                        if ($UG->seviye == 1) {
                            $table_values[] = $_POST["protected"];
                        } else {
                            if (is_edit) {
                                $table_values[] = $old_datas["protected"];
                            } else {
                                $table_values[] = 0;
                            }
                        }
                        $untranslatable_values[] = $table_values [count($table_values) - 1];
                    }
                    if (isset ($_POST["publishing"])) {
                        $table_fields[] = "publishing";
                        $table_values[] = $_POST["publishing"];
                    }


                    //
                    // debug icin, silmeyin bulunsun
                    //
                    if ($dump_post_etc) {
                        echo("<pre>");
                        var_dump($_POST);
                        echo("--------------------\n");
                        for ($dng = 0, $m = count($table_values); $dng < $m; $dng++) {
                            echo("{$table_fields[ $dng ]} => {$table_values[ $dng ]}\n");
                        }
                        echo("--------------------\n");
                        for ($dng = 0, $m = count($untranslatable_fields); $dng < $m; $dng++) {
                            echo("{$untranslatable_fields[ $dng ]} => {$untranslatable_values[ $dng ]}\n");
                        }
                        echo("</pre>");
                        unset ($dng, $m);
                        exit;
                    }


                    // yeni ekleme sayfasiysa veya duzenlemeyse veya bu dile ait cevirisi yoksa
                    if (!is_edit || (is_edit && !$have_this_langauge)) {
                        $table_fields[] = "dil_id";
                        $table_values[] = $_POST["____dil_id"];

                        // eger duzenleme sayfasindan gelme bir kayit ise varolan bir kaydin cevirisidir diyoruz
                        if (is_edit) {
                            $table_fields[] = "UID";
                            $table_values[] = $ITEM_ID;
                        }

                        $query = mysql_query("insert into `{$selected_modul[ "tablo_adi" ]}` (" . implode(", ", $table_fields) . ") values(" . implode(", ", $table_values) . ")") or die (mysql_error());
                        if ($query) {
                            // ceviri ise yeniden relation atayamayiz yoksa kayitlar bozulur
                            // e tabi bir de UID setlemenin bir alemi yok...
                            if (!is_edit) {
                                // son eklenen kaydin id'sini alip UID olarak setliyoruz
                                $LID = mysql_insert_id();
                                mysql_query(sprintf('insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)', $U->ID, $selected_modul_id, $LID, 1)) or die (mysql_error());
                                mysql_query("update `{$selected_modul[ "tablo_adi" ]}` set UID={$LID} where id={$LID} limit 1") or die (mysql_error());


                                $relation_query = mysql_query("select modul.id as modulID, rel.ad, rel.is_required, rel.multi_select, modul.tablo_adi, modul.name_field_prop_id from d_modul_iliskiler as rel, d_moduller as modul where rel.modul_id={$selected_modul_id} and modul.id=rel.relative_to and modul.name_field_prop_id>0 order by rel.sira asc");
                                while ($relation = mysql_fetch_array($relation_query)) {
                                    modul_relation_data_write($selected_modul_id, $relation["modulID"], is_edit ? $ITEM_ID : $LID);
                                    /* $value = intval($_POST["relation{$relation["modulID"]}"]);
									  //die($value);
									  if ($value > 0) {
									  mysql_query("insert into o_iliskiler (x_modul_id, x_id, y_modul_id, y_id) values({$selected_modul_id}, {$LID}, {$relation["modulID"]}, {$value})") or die(mysql_error());
									  }
									 *
									 */
                                }
                            }

                            // permanent link atamaca
                            if (!is_null($name_field_value) && $selected_modul["linklenebilir"] == 1) {
                                //set_permalink ( $selected_modul_id , is_edit ? $ITEM_ID : $LID , $dil_id , $name_field_value );
                                $posted_permanent = trim(guvenlik($_POST["___permalink"]));
                                $np = $posted_permanent == "" ? $name_field_value : $posted_permanent;
                                set_permalink($selected_modul_id, is_edit ? $ITEM_ID : $LID, $dil_id, $np);
                            }

                            if (( int )$selected_modul["etiketler_aktif"] == 1) {
                                tag_relations(is_edit ? $ITEM_ID : $LID, $selected_modul_id, $dil_id);
                            }

                            modul_upload_all_files($selected_modul_id, is_edit ? $ITEM_ID : $LID, $dil_id);
                            hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/AFTER_INSERT/' . $selected_modul_id, $selected_modul_id, is_edit ? $ITEM_ID : $LID, $dil_id);
                            //yonlendir(parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "sm" => is_edit ? 2 : 3)));
                            yonlendir(parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "duzenle", "id" => is_edit ? $ITEM_ID : $LID)));
                        } else {
                            echo("Bir hata oluştu ki ?!" . mysql_error());
                        }
                    } else { // kayit varsa guncelle
                        // guncelle ama ben tiklandiysam guncelle yoksa niye zora kosuyon ki ?
                        if ($dil_id == $_POST["____dil_id"]) {
                            $sqlstring = "";
                            for ($i = 0, $m = count($table_fields); $i < $m; $i++) {
                                if (!is_null($table_fields[$i])) {
                                    $sqlstring .= ", {$table_fields[ $i ]}={$table_values[ $i ]}";
                                }
                            }
                            global $___UNUPDATED_ENTRY;
                            $___UNUPDATED_ENTRY = new entry ($selected_modul_id, $ITEM_ID, $dil_id);
                            $sqlstring = "update `{$selected_modul[ "tablo_adi" ]}` set " . trim($sqlstring, ",") . " where UID={$ITEM_ID} and dil_id={$dil_id} limit 1";
                            mysql_query($sqlstring) or die (mysql_error());

                            // permanent link atamaca
                            if (!is_null($name_field_value) && $selected_modul["linklenebilir"] == 1) {
                                //set_permalink ( $selected_modul_id , $ITEM_ID , $dil_id , $name_field_value );
                                $posted_permanent = trim(guvenlik($_POST["___permalink"]));
                                $np = $posted_permanent == "" ? $name_field_value : $posted_permanent;
                                set_permalink($selected_modul_id, is_edit ? $ITEM_ID : $LID, $dil_id, $np);
                            }

                            if (( int )$selected_modul["etiketler_aktif"] == 1) {
                                tag_relations(is_edit ? $ITEM_ID : $LID, $selected_modul_id, $dil_id);
                            }

                            $untranslatable_fields[] = "u_date";
                            $untranslatable_values[] = "CURRENT_TIMESTAMP()";
                            if ($step_for_page_while_i == 0 && count($untranslatable_fields) > 0) {
                                $sqlstring = "";
                                for ($i = 0, $m = count($untranslatable_fields); $i < $m; $i++) {
                                    $sqlstring .= ", {$untranslatable_fields[ $i ]}={$untranslatable_values[ $i ]}";
                                }

                                $sqlstring = "update `{$selected_modul[ "tablo_adi" ]}` set " . trim($sqlstring, ",") . " where UID={$ITEM_ID}";
                                mysql_query($sqlstring) or die (mysql_error() . "<hr />" . $sqlstring);
                            }


                            $relation_query = mysql_query("select modul.id as modulID, rel.ad, rel.is_required, rel.multi_select, modul.tablo_adi, modul.name_field_prop_id from d_modul_iliskiler as rel, d_moduller as modul where rel.modul_id={$selected_modul_id} and modul.id=rel.relative_to and modul.name_field_prop_id>0 order by rel.sira asc");
                            while ($relation = mysql_fetch_array($relation_query)) {
                                modul_relation_data_write($selected_modul_id, $relation["modulID"], $ITEM_ID);
                                //die($value);
                                if (false) { // kullanilmiyor, function a baglandi ama kalsin lasim olabilir :/
                                    if ($relation["multi_select"] == 1) { // multiselect oluncaya gore olacak islemler
                                        var_dump($_POST["relation{$relation[ "modulID" ]}"]);
                                        exit ();
                                    } else {
                                        $value = intval($_POST["relation{$relation[ "modulID" ]}"]);

                                        if ($old_datas["relation{$relation[ "modulID" ]}"] != $value) {
                                            mysql_query(sprintf('delete from o_iliskiler where x_modul_id=%1$d and x_id=%2$d and y_modul_id=%3$d', $selected_modul_id, $ITEM_ID, $relation["modulID"])) or die (mysql_error());
                                            if ($value > 0) {
                                                mysql_query("insert into o_iliskiler (x_modul_id, x_id, y_modul_id, y_id) values({$selected_modul_id}, {$ITEM_ID}, {$relation[ "modulID" ]}, {$value})") or die (mysql_error());
                                            }
                                        }
                                    }
                                }
                            }
                            mysql_query(sprintf('insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)', $U->ID, $selected_modul_id, $ITEM_ID, 2)) or die (mysql_error());
                            modul_upload_all_files($selected_modul_id, $ITEM_ID, $dil_id);
                            hooks_run('PANEL/MODUL/ITEM/FIELD/SUBMIT/AFTER_UPDATE/' . $selected_modul_id, $selected_modul_id, is_edit ? $ITEM_ID : $LID, $dil_id);
                            //yonlendir ( parse_panel_url ( array( "modulID" => $root_modul_id , "smodulID" => $sub_modul_id , "sm" => is_edit ? 2 : 3 ) ) );
                            yonlendir(parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "duzenle", "id" => is_edit ? $ITEM_ID : $LID)));
                        }
                    }
                }
                ?>
                <dt>
                    <a>
                        <div class="card-header border-0">
                            <div class="custom-title-wrap bar-pink">
                                <div class="custom-title">
                                    <?php
                                    if ($_WRITE_PERMISSION) {
                                        if (is_edit) {
                                            $dil_ad = fetch_one("o_diller", "id", $dil_id, "ad");
                                            if (!$have_this_langauge) {
                                                echo("$dil_ad ");
                                                echo(___("Dilini Oluştur"));
                                            } else {
                                                echo("$dil_ad ");
                                                echo(___("Güncelle"));
                                            }
                                        } else {
                                            echo(___("Kayıt Ekleme"));
                                        }
                                    } else {
                                        $dil_ad = fetch_one("o_diller", "id", $dil_id, "ad");
                                        echo("{$dil_ad} ");
                                        echo(___("Görüntüle"));
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </dt>
                <dd>
                    <div class="card-body form">
                        <form action="" method="post" id="modul_detay_lang<?php echo($dil_id); ?>"
                              class="form-horizontal form-bordered" enctype="multipart/form-data">
                            <div class="form-body">
                                <?php
                                if ($UG->seviye == 1 && $step_for_page_while_i == 0) {
                                    $protected = is_edit ? $old_datas["protected"] : 0;
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___("Korumalı Kayıt")); ?></label>
                                        <div class="col-md-9">
                                            <div class="radio-list">
                                                <?PHP
                                                $proctection_types = array();
                                                $proctection_types[0] = ___("Koruma Yok");
                                                $proctection_types[1] = ___("Tam Koruma");
                                                $proctection_types[2] = ___("Düzenleyebilir");
                                                $proctection_types[3] = ___("Silebilir");
                                                foreach ($proctection_types as $ptk => $ptv) {
                                                    printf('<label for="protected_%1$d_%2$d" class="radio-inline" style="margin-right: 10px;" ><input type="radio" name="protected" id="protected_%1$d_%2$d" class="iCheck-square-green" value="%1$d"%3$s /> %4$s</label>', $ptk, $dil_id, $protected == $ptk ? " checked='checked'" : null, $ptv);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                                if ($_WRITE_PERMISSION) {
                                    $publishing = is_edit ? $old_datas["publishing"] : 0;
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___("Yayınlanma Durumu")); ?></label>
                                        <div class="col-md-9">
                                            <div class="radio-list">
                                                <?PHP
                                                $publishing_types = array();
                                                $publishing_types[1] = ___("Yayında");
                                                $publishing_types[0] = ___("Yayında Değil");
                                                foreach ($publishing_types as $ptk => $ptv) {
                                                    printf('<label for="publishing_%1$d_%2$d" class="radio-inline" style="margin-right: 10px;" ><input class="iCheck-square-green" type="radio" name="publishing" id="publishing_%1$d_%2$d" value="%1$d"%3$s /> %4$s</label>', $ptk, $dil_id, $publishing == $ptk ? " checked='checked'" : null, $ptv);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                                if (!is_edit && mysql_num_rows($lang_query) > 1) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___("Dil")); ?></label>
                                        <div class="col-md-9">
                                            <select class="form-control input-medium" name="____dil_id">
                                                <?php
                                                while ($lang = mysql_fetch_array($lang_query)) {
                                                    printf('<option value="%1$d">%2$s</option>', $lang["id"], $lang["ad"]);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php
                                } else {
                                    printf('<input type="hidden" name="____dil_id" value="%1$d" />', $dil_id);
                                }
                                ?>

                                <?php
                                if (( int )$selected_modul["kendine_baglilik"] > 0 && $step_for_page_while_i == 0) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___($selected_modul["kendine_baglilik_ad"])); ?></label>
                                        <div class="col-md-9">
                                            <?php
                                            $goptions = array();
                                            $table_name = $selected_modul['tablo_adi'];
                                            $name_field = $selected_modul["name_field"];
                                            $main_modul_id = $selected_modul_id;
                                            $target_modul_id = $main_modul_id;
                                            table_get_items_json(0, 0, $selected_modul["kendine_baglilik"]);

                                            //self_relation ( 0 , $old_datas[ "root_id" ] , , 0 );
                                            $goptions = array_merge(array(array('id' => 0, 'text' => '--', 'parents' => array())), $goptions);
                                            ?>
                                            <select name="root_id"
                                                    id="root_id<?php echo($step_for_page_while_i); ?>"
                                                    class="form-control input-large"></select>
                                            <script type="text/javascript">
                                                $(document).ready(function () {
                                                    $("#root_id<?php echo($step_for_page_while_i); ?>").select2({
                                                        data: <?php echo(json_encode($goptions)) ?>,
                                                        templateResult: Arasbil.select2modulItemTemplate
                                                    });
                                                    <?php
                                                    if ( $old_datas["root_id"] > 0 ) {
                                                    ?>
                                                    $("#root_id<?php echo($step_for_page_while_i); ?>").val(<?php echo($old_datas["root_id"]) ?>).trigger('change');
                                                    <?php
                                                    }
                                                    ?>
                                                });
                                            </script>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>

                                <?php
                                if ($step_for_page_while_i == 0) {

                                    $relation_query = mysql_query("select modul.id as modulID, rel.ad, rel.is_required, rel.multi_select, modul.tablo_adi, modul.name_field_prop_id from d_modul_iliskiler as rel, d_moduller as modul where rel.modul_id={$selected_modul_id} and modul.id=rel.relative_to and modul.name_field_prop_id>0 order by rel.sira asc");
                                    while ($relation = mysql_fetch_array($relation_query)) {
                                        ?>
                                        <div class="form-group row">
                                            <label class="col-md-3 col-form-label"><?php echo(___($relation["ad"])); ?></label>
                                            <div class="col-md-9">
                                                <?php
                                                $goptions = array();
                                                $table_name = $relation["tablo_adi"];
                                                $name_field = fetch("select tablo_field from d_modul_ozellikler where id={$relation[ "name_field_prop_id" ]} limit 1");
                                                $main_modul_id = $selected_modul_id;
                                                $target_modul_id = $relation['modulID'];
                                                table_get_items_json(0, 0);
                                                $select_classes = array();
                                                $select_attributes = array();
                                                $name = 'relation' . $relation["modulID"];
                                                $id = 'relation' . $relation["modulID"];
                                                $select_classes[] = 'no_js';
                                                if ($relation["is_required"] == 1) {
                                                    $select_classes[] = 'req';
                                                } else {
                                                    $optional_option = array('id' => 0, 'text' => ___('Opsiyonel'), 'parents' => array());
                                                    $goptions = array_merge(array($optional_option), $goptions);
                                                }
                                                if ($relation["multi_select"] == 1) {
                                                    $select_attributes[] = 'multiple="multiple"';
                                                    $select_classes[] = 'input-xlarge';
                                                    $name .= '[]';
                                                } else {
                                                    $select_classes[] = 'input-large';
                                                }

                                                printf('<select name="%1$s" id="%2$s" class="form-control"%4$s></select>', $name, $id, implode(' ', $select_classes), implode(' ', $select_attributes));
                                                ?>
                                                <script type="text/javascript">
                                                    $(document).ready(function () {
                                                        var selectrelation<?php echo($relation["modulID"]); ?> = $('#relation<?php echo($relation["modulID"]); ?>');
                                                        var $callback = <?php echo(json_encode($goptions)) ?>;
                                                        $.each( $callback, function(i, obj) {
                                                            $('<option value="'+obj.id+'">' + obj.text + '</option>')
                                                                .appendTo(selectrelation<?php echo($relation["modulID"]); ?>);

                                                        });
                                                    });
                                                </script>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>

                                <?php
                                $fields_query = mysql_query("select * from d_modul_ozellikler where modul_id={$selected_modul_id} order by sira asc");
                                // "cevirilebilir olmayan alanlari gostereyim mi" degiskeni
                                $show_untranslatable_fields = !is_edit || (is_edit && $step_for_page_while_i == 0);
                                while ($fields = mysql_fetch_array($fields_query)) {
                                    // eger textboxsa ve numeric bir alansa
                                    //	veya radio, checkbox, combobox ise bir sonrakine atla
                                    if (!$show_untranslatable_fields && !is_translatable_field($fields)) {
                                        //printf('<input type="hidden" name= />')
                                        continue;
                                    }
                                    if ($fields["is_master"] == 1) {
                                        if ($UG->seviye != 1) {
                                            continue;
                                        }
                                    }
                                    if (in_array($fields["id"], $invisible_fields)) {
                                        if ($UG->seviye != 1) {
                                            continue;
                                        }
                                    }
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___($fields["ad"])); ?></label>
                                        <?PHP
                                        $classes = array();
                                        $classes[] = "col-md-9";
                                        if (false) {
                                            if (!$_WRITE_PERMISSION) {

                                            } else {
                                                if ($fields['tip'] == 0 && in_array($fields['alt_tip'], array(0))) {
                                                    $classes[] = "col-md-4";
                                                } elseif ($fields['tip'] == 1) {
                                                    $classes[] = "col-md-8";
                                                } elseif ($fields['tip'] == 5) {
                                                    $classes[] = "col-md-9";
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="<?php echo(implode(' ', $classes)) ?>">
                                            <?php
                                            $yardim_ac = "";
                                            if ($fields["yardim_aciklamasi"] != "") {
                                                $yardim_ac = "<div class='help-block'>" . str_replace("\n", '<br/>', ___($fields["yardim_aciklamasi"])) . "</div>";
                                            }
                                            if (!$_WRITE_PERMISSION) { // modulde yazma izni yoksa
                                                $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                switch ($fields["tip"]) {
                                                    case 0:
                                                        switch ($fields["alt_tip"]) {
                                                            case 0:
                                                            case 1:
                                                            case 2:
                                                                echo($value);
                                                                break;
                                                            case 3:
                                                                echo(date("d.m.Y", strtotime($value)));
                                                                break;
                                                        }
                                                        break;
                                                    case 1:
                                                        echo(str_replace(chr(13), "<br />", $value));
                                                        break;
                                                    case 2:
                                                        echo(___(intval($value) == 0 ? "Seçili Değil" : "Seçili"));
                                                        break;
                                                    case 3:
                                                    case 4:
                                                        echo(fetch(sprintf('select `etiket` from `d_modul_ozellik_secenekler` where `prop_id`=%1$d and `deger`=%2$d limit 1', $fields["id"], $value)));
                                                        break;
                                                    case 5:
                                                        echo(htmlspecialchars_decode($value));
                                                        break;
                                                    case 6:
                                                        echo($value);
                                                        break;
                                                    case 7:
                                                        echo($value);
                                                        break;
                                                    case 8:
                                                        $modul_id = ( int )$value;
                                                        $value = $old_datas[$fields["parametre1"]];
                                                        if ($modul_id == 0) {
                                                            printf('<a href="%1$s" target="_blank">%2$s</a>', $value, ___("Bağlantı"));
                                                        } else {
                                                            $modul = fetch_to_array(sprintf('select * from `d_moduller` where `id`=%1$d limit 1', $modul_id));
                                                            $record = fetch_to_array(sprintf('select * from `%1$s` where `UID`=%2$d order by `dil_id` asc limit 1', $modul["tablo_adi"], $value));
                                                            $name_field = fetch_one("d_modul_ozellikler", "id", $modul["name_field_prop_id"], "tablo_field");
                                                            if ($UG->check_permission($modul_id, null, 0)) {
                                                                printf('<a href="%1$s" target="_blank">%2$s</a>', parse_panel_url(array("modulID" => $modul_id, "smodulID" => -1, "islem" => "duzenle", "id" => $record["UID"])), $record[$name_field]);
                                                            } else {

                                                            }
                                                        }
                                                        break;
                                                }
                                            } else { // module yazma izni varsa
                                                $classes = "";
                                                if ($fields["is_required"] == 1) {
                                                    $classes .= " req";
                                                }
                                                if (in_array(intval($fields['tip']), array(0, 1, 4,))) {
                                                    $classes .= " form-control";
                                                }
                                                switch ($fields["tip"]) {
                                                case 0: // textbox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                switch ($fields["alt_tip"]) {
                                                    case 0:
                                                        $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                        echo("<input type='text' class='{$classes} input-xlarge' maxlength='{$fields[ "maxlength" ]}' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                        break;
                                                    case 1:
                                                        $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                        $classes .= " numeric text-center ";
                                                        echo("<input type='text' class='{$classes} input-xsmall numeric' maxlength='5' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                        break;
                                                    case 2:
                                                        $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                        echo("<input type='text' class='{$classes} input-xsmall currency' maxlength='10' name='{$fields[ "tablo_field" ]}' value='{$value}'/>");
                                                        break;
                                                case 3:
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    if (!is_null($value)) {
                                                        $value = date("d.m.Y", strtotime($value));
                                                    }
                                                    $classes .= " date-picker ";
                                                    //echo("<input type='text' class='{$classes} input-small' name='{$fields[ "tablo_field" ]}' value='{$value}' />");
                                                    ?>
                                                    <div class="row">
                                                        <div class="col-md-3">

                                                            <div class="input-group date dpYears" data-date-viewmode="years" data-date-format="dd-mm-yyyy" data-date="<?php echo($value == "" ? date("d.m.Y") : $value); ?>">
                                                                <input type="text" class="form-control" name="<?PHP echo($fields["tablo_field"]); ?>" aria-describedby="dp-ig">
                                                                <div class="input-group-append">
                                                                    <button id="dp-ig" class="btn btn-outline-secondary" type="button"><i class="fa fa-calendar f14"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                break;
                                                }
                                                break;
                                                case 1: // textarea ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    echo("<textarea class='{$classes} medium' name='{$fields[ "tablo_field" ]}' style='height: 150px;'>{$value}</textarea>");
                                                    break;
                                                case 2: // checkbox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $checked = isset ($old_datas[$fields["tablo_field"]]) && $old_datas[$fields["tablo_field"]] == 1 ? " checked='checked'" : null;
                                                    echo('<div class="checkbox-list"><label class="checkbox-inline">');
                                                    echo("<input class='iCheck-square-green' type='checkbox' name='{$fields[ "tablo_field" ]}'{$checked} value='1' />");
                                                    echo('</label></div>');
                                                    break;
                                                case 3: // radio button ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $selected_item = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                    $sorgu = mysql_query("select * from d_modul_ozellik_secenekler where prop_id={$fields[ "id" ]}") or die (mysql_error());
                                                    echo('<div class="radio-list">');
                                                    while ($okut = mysql_fetch_array($sorgu)) {
                                                        $checked = $selected_item == $okut["deger"] ? " checked='checked'" : null;
                                                        echo('<label class="radio-inline" style="margin-right: 10px;">');
                                                        echo("<input class='iCheck-square-green' type='radio' name='{$fields[ "tablo_field" ]}' id='{$fields[ "tablo_field" ]}_{$okut[ "deger" ]}' value='{$okut[ "deger" ]}'{$checked} /><label style='padding-left: 5px' for='{$fields[ "tablo_field" ]}_{$okut[ "deger" ]}'>" . ___($okut["etiket"]) . "</label> ");
                                                        echo('</label>');
                                                    }
                                                    echo('</div>');
                                                    break;
                                                case 4: // combobox ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $selected_item = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                    echo("<select name='{$fields[ "tablo_field" ]}' class='form-control input-medium'>");
                                                    $sorgu = mysql_query("select * from d_modul_ozellik_secenekler where prop_id={$fields[ "id" ]}") or die (mysql_error());
                                                    while ($okut = mysql_fetch_array($sorgu)) {
                                                        $selected = $selected_item == $okut["deger"] ? " selected='selected'" : null;
                                                        echo("<option value='{$okut[ "deger" ]}'{$selected}>" . ___($okut["etiket"]) . "</option>");
                                                    }
                                                    echo("</select>");
                                                    break;
                                                case 5: // fck editor ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                $value = richtext_editor_setvalue($value);
                                                ?>
                                                    <textarea class="clear"
                                                              style="display: none"
                                                              id="ta_summernote<?php echo($CKEditor_index) ?>"
                                                              name="<?php echo($fields["tablo_field"]) ?>"><?php echo($value); ?></textarea>
                                                    <div id="summernote<?php echo($CKEditor_index); ?>"><?php echo(htmlspecialchars_decode($value)); ?></div>
                                                    <script>
                                                        $("#summernote<?php echo($CKEditor_index); ?>").summernote({
                                                            height: 300,
                                                            lang: 'tr',
                                                        }).on('summernote.change', function (customEvent, contents, $editable) {
                                                            $("#ta_summernote<?php echo($CKEditor_index); ?>").val($('#summernote<?php echo($CKEditor_index); ?>').summernote('code'));
                                                        });
                                                    </script>
                                                <?php
                                                $CKEditor_index++;
                                                break;
                                                case 6: // dosya ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    if (!is_null($value)) {
                                                        printf('<a href="%1$s%2$s">%2$s</a><br />', _DIR_FILE_UPLOADS_, $value);
                                                    }
                                                    echo("<input type='file' class='{$classes}' name='{$fields[ "tablo_field" ]}' />");
                                                    break;
                                                case 7: // sabit deger ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                    $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : null;
                                                    echo '<input type="text" class="form-control" value="'.$value.'" disabled>';
                                                    break;
                                                case 8: // link ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                $value = isset ($old_datas[$fields["tablo_field"]]) ? $old_datas[$fields["tablo_field"]] : 0;
                                                $value2 = isset ($old_datas[$fields["parametre1"]]) ? $old_datas[$fields["parametre1"]] : null;
                                                ?>
                                                    <script type="text/javascript">
                                                        function <?php echo($fields["tablo_field"]); ?>_change_<?php echo($dil_id); ?>(tur) {
                                                            var select = $('.modul_kayitlar');
                                                            select.html('');
                                                            var modulID = parseInt(tur);
                                                            var modulkayit_div = $(".modulkayit_div<?php echo($dil_id); ?>");
                                                            var modulbaglanti_div = $(".modulbaglanti_div<?php echo($dil_id); ?>");
                                                            if (modulID == 0) {
                                                                //Bağlantı Göster
                                                                modulbaglanti_div.css({'display':'flex'});
                                                                modulkayit_div.css({'display':'none'});
                                                            }else{
                                                                //Modül Göster
                                                                modulkayit_div.css({'display':'flex'});
                                                                modulbaglanti_div.css({'display':'none'});
                                                                $.post("ajax.php", {
                                                                    action: "menu",
                                                                    modul_id: modulID,
                                                                    dil_id: <?php echo($dil_id) ?>,
                                                                    name: "<?php echo($fields["parametre1"]); ?>"
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
                                                            <?php echo($fields[ "tablo_field" ]); ?>_change_<?php echo($dil_id); ?>($("#<?php echo($fields["tablo_field"] . $dil_id); ?>").val());
                                                        });</script>
                                                    <div class="grpbx style2">
                                                        <div id="<?php echo($fields["tablo_field"]); ?>_table__<?php echo($dil_id); ?>">
                                                            <div class="form-group row">
                                                                <label class="col-form-label col-md-3"><?php echo(___("Modül")); ?></label>
                                                                <div class="col-md-9">
                                                                    <select name="<?php echo($fields["tablo_field"]); ?>"
                                                                            id="<?php echo($fields["tablo_field"] . $dil_id); ?>"
                                                                            onchange="<?php echo($fields["tablo_field"]); ?>_change_<?php echo($dil_id); ?>(this.value)"
                                                                            class="form-control">
                                                                        <option value="0"><?php echo(___("Bağlantı")); ?></option>
                                                                        <?php
                                                                        $modules_query = mysql_query("select * from `d_moduller` where `name_field_prop_id`>0 and `linklenebilir`=1 order by ad asc");
                                                                        while ($modules = mysql_fetch_array($modules_query)) {
                                                                            if (( int )fetch(sprintf('select count(*) from `%1$s` where `deleted`=0', $modules["tablo_adi"])) == 0) {
                                                                                continue;
                                                                            }
                                                                            $selected = $value == $modules["id"] ? " selected='selected'" : null;
                                                                            printf('<option value="%1$d"%3$s>%2$s</option>', $modules["id"], $modules["ad"], $selected);
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="form-group modulkayit_div<?php echo($dil_id); ?> row" style="display: none;">
                                                                <label class="control-label col-md-3"><?php echo(___("Kayıt")); ?></label>
                                                                <div class="col-md-9">
                                                                    <select name="<?php echo($fields["parametre1"]) ?>"
                                                                            class="form-control modul_kayitlar"
                                                                            id="select_<?php echo($fields["tablo_field"]); ?>_<?php echo($dil_id); ?>">

                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="form-group modulbaglanti_div<?php echo($dil_id); ?> row">
                                                                <label class="control-label col-md-3"><?php echo(___("Bağlantı")); ?></label>
                                                                <div class="col-md-9">
                                                                    <?php
                                                                    $value = $value == 0 ? $value2 : "";
                                                                    echo("<input type='text' class='form-control' maxlength='200' name='{$fields[ "parametre1" ]}__url' value='{$value}' />");
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                break;
                                                case 9:  // terminator ////////////////////////////////////////////////////////////////////////////////////////////////////
                                                $checked_val = is_edit ? $old_datas[$fields["tablo_field"]] : 0;
                                                ?>
                                                    <div class="radio-list">
                                                        <label class="radio-inline">
                                                            <?php
                                                            $checked = $checked_val == 1 ? " checked='checked'" : null;
                                                            echo("<input type='radio' name='{$fields[ "tablo_field" ]}' id='{$fields[ "tablo_field" ]}_1' value='1'{$checked} /><label for='{$fields[ "tablo_field" ]}_1'>Evet</label> ");
                                                            ?>
                                                        </label>
                                                        <label class="radio-inline">
                                                            <?php
                                                            $checked = $checked_val == 0 ? " checked='checked'" : null;
                                                            echo("<input type='radio' name='{$fields[ "tablo_field" ]}' id='{$fields[ "tablo_field" ]}_0' value='0'{$checked} /><label for='{$fields[ "tablo_field" ]}_0'>Hayır</label> ");
                                                            ?>
                                                        </label>
                                                    </div>
                                                    <?php
                                                    break;
                                                }
                                            }
                                            echo $yardim_ac;
                                            ?>
                                        </div>
                                    </div>
                                    <?PHP
                                    if ($selected_modul["name_field_prop_id"] == $fields["id"] && $selected_modul["linklenebilir"] == 1) {
                                        ?>
                                        <div class="form-group row">
                                            <label class="col-md-3 col-form-label"><?php echo(___("Kalıcı Bağlantı")); ?></label>
                                            <div class="col-md-9">
                                                <div class="permant_main"
                                                     id="perm<?php printf('%1$d_%2$d', $dil_id, 0) ?>">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                                <span class="input-group-text" id="basic-addon3">
                                                                    <?PHP
                                                                    if ($have_this_langauge) {
                                                                        $link = generate_link($selected_modul_id, $ITEM_ID, null, array(), $dil_id);
                                                                        $n = explode('/', $link);
                                                                        unset ($n[count($n) - 1]);
                                                                        $link = implode('/', $n);
                                                                        echo($link . '/');
                                                                    } else {
                                                                        echo(generate_link(0, 0) . '/');
                                                                    }
                                                                    $perm = $have_this_langauge ? get_permanent_link($selected_modul_id, $ITEM_ID, $dil_id) : null;
                                                                    ?>
                                                                </span>
                                                        </div>
                                                        <input type="text" class="form-control input_"
                                                               name="___permalink" value="<?PHP echo($perm); ?>"
                                                               maxlength="160"
                                                               data-new="<?php echo($have_this_langauge ? 0 : 1) ?>"
                                                               data-modul_id="<?php echo($selected_modul_id) ?>"
                                                               data-record_id="<?php echo($ITEM_ID) ?>"
                                                               data-lang_id="<?php echo($dil_id) ?>">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary dropdown-toggle"
                                                                    type="button" data-toggle="dropdown"
                                                                    aria-haspopup="true" aria-expanded="false">İşlem
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="javascript:;"
                                                                   onclick="Arasbil.permant_btn(this, 0);">Düzenle</a>
                                                                <a class="dropdown-item" href="javascript:;"
                                                                   onclick="Arasbil.permant_btn(this, 1);">Tamam</a>
                                                                <div role="separator" class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:;"
                                                                   onclick="Arasbil.permant_btn(this, 1);">Vazgeç</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    <?PHP }
                                    ?>
                                    <?php
                                }
                                ?>

                                <?php
                                // modulde etiketler aktif ise etiketleri aciyoruz
                                if (( int )$selected_modul["etiketler_aktif"] == 1) {
                                    ?>
                                    <div class="form-group row">
                                        <label class="col-md-3 col-form-label"><?php echo(___("Etiketler")); ?></label>
                                        <div class="col-md-9">
                                            <select name="tagggg[]" class="form-control"
                                                    id="tagggg<?php echo($dil_id); ?>" multiple="multiple">
                                                <?PHP
                                                if (is_edit) {
                                                    $selected_tags_query = mysql_query(sprintf('select tag.`id`, tag.`ad` from `o_etiket_iliskiler` rel, `o_etiketler` as tag where rel.`x_id`=%1$d and rel.`modul_id`=%2$d and tag.`id`=rel.`etiket_id` and tag.`dil_id`=%3$d', $ITEM_ID, $selected_modul_id, $dil_id)) or die (mysql_error());
                                                    while ($selected_tags = mysql_fetch_array($selected_tags_query, MYSQL_NUM)) {
                                                        vprintf('<option value="%1$d|%2$s" selected="selected">%2$s</option>', $selected_tags);
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <script type="text/javascript">
                                                $('#tagggg<?php echo($dil_id); ?>').select2({
                                                    tags: true,
                                                    ajax: {
                                                        url: "ajax.php?action=tag",
                                                        dataType: 'json',
                                                        delay: 250,
                                                        data: function (params) {
                                                            return {
                                                                q: params.term,
                                                                modul_id: <?php echo($selected_modul_id); ?>,
                                                                dil_id: <?php echo($dil_id); ?>
                                                            };
                                                        },
                                                        processResults: function (data) {
                                                            return {
                                                                results: data
                                                            };
                                                        },
                                                        cache: false
                                                    },
                                                });
                                            </script>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="form-actions text-right">
                                <?php
                                if ($_WRITE_PERMISSION) {
                                    ?>
                                    <input type="hidden" name="form_submit" value="">
                                    <?php
                                    if (fetch(sprintf('select count(*) from d_modul_ozellikler where modul_id=%1$d and tip!=7', $selected_modul_id)) > 0) {
                                        ?>
                                        <button type="button" class="btn btn-outline-success rounded-0"
                                                onclick="$('#modul_detay_lang<?php echo($dil_id); ?>').submit_form();">
                                            <span class="dib pa sprite icon">&nbsp;</span><?php echo(___("Kaydet")); ?>
                                        </button>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </form>
                    </div>
                </dd>
                <?php
            }
            ?>


            <?php
        }
        ?>
    </div>
</dl>
<script type="text/javascript">
    function update_CKEditor_instances() {
        try {
            for (var instanceName in CKEDITOR.instances) {
                CKEDITOR.instances[instanceName].updateElement();
            }
        } catch (e) {

        }
    }
</script>