<?php
if(@$_GET["islem"]=="kayitekle"){
if($_POST)
{
    if($_POST["post"]=="true")
    {
        $Degisken = $_POST["degisken"];
        $Turkce = $_POST["turkce"];
        $Ingilice = $_POST["ingilizce"];

        $KontrolSQL = mysql_query("SELECT * FROM o_dil_ceviriler WHERE kelime='".$Degisken."'");
        if(mysql_num_rows($KontrolSQL)>0)
        {
            echo "<script type='text/javascript'>alert('Kelime Zaten Ekli.')</script>";
            echo "<script type='text/javascript'>window.location = '?ceviriler='</script>";

        }else {
            mysql_query("INSERT INTO o_dil_ceviriler(dil_id,kelime,anlam) VALUES (1,'$Degisken','')");


            echo "<script type='text/javascript'>window.location = '?ceviriler='</script>";

        }


    }
}
}
if(@$_GET["islem"]=="sil")
{
    $KontrolSQL = mysql_query("SELECT * FROM o_dil_ceviriler WHERE id='".$_GET["ID"]."'");
    $KontrolDeger = mysql_fetch_array($KontrolSQL);
    mysql_query("DELETE FROM o_dil_ceviriler WHERE kelime='".$KontrolDeger["kelime"]."' ");

}
$dil_id1 = ( int )fetch("select id from o_diller where aktif=1 order by sira asc limit 1");
$dil_id2 = ( int )fetch("select id from o_diller where aktif=1 and id != {$dil_id1} order by sira asc limit 1");
if (isset ($_GET["d1"])) {
    if (fetch_one("o_diller", "id", $_GET["d1"], "ad") != false) {
        $dil_id1 = ( int )$_GET["d1"];
    }
}
if (isset ($_GET["d2"])) {
    if (fetch_one("o_diller", "id", $_GET["d2"], "ad") != false) {
        $dil_id2 = ( int )$_GET["d2"];
    }
}
$second_lang = $dil_id2 != 0 && $dil_id1 != $dil_id2;
if (isset ($_POST["kelime"]) && !empty ($_POST["kelime"])) {
    $_POST["kelime"] = guvenlik($_POST["kelime"]);
    $_POST["kelime"] = str_replace(array("{", "}"), array(null, null), $_POST["kelime"]);
    $_POST["kelime"] = trim($_POST["kelime"]);
    if (!empty ($_POST["kelime"])) {
        if (fetch_one("o_dil_ceviriler", "kelime", $_POST["kelime"], "dil_id") == false) {
            mysql_query("insert into o_dil_ceviriler (kelime, dil_id) values('{$_POST[ "kelime" ]}', {$dil_id1})");
            yonlendir(parse_panel_url(array("ceviriler" => "", "d1" => $dil_id1, "d2" => $dil_id2, "msg" => 1)));
        } else {
            yonlendir(parse_panel_url(array("ceviriler" => "", "d1" => $dil_id1, "d2" => $dil_id2, "msg" => 2)));
        }
    }
}
if (isset ($_GET["msg"])) {
    switch (( int )$_GET["msg"]) {
        case 1:
            echo '<script>swal("İşlem Başarılı!", "Kelime eklenmiştir", "success");</script>';
            break;
        case 2:
            echo '<script>swal("İşlem Başarılı!", "Kelime zaten eklenmiş", "success");</script>';

            break;
    }
}


if (@$_GET["ekle"]) {

    ?>
    <form action="?ceviriler=&islem=kayitekle" method="post">
        <table class="table table-bordered table-striped">
            <tr style="background-color: #FFEBEB">
                <td style="width: 20%;"><input type="text" class="form-control" name="degisken"
                                               placeholder="Değişken İsmini Giriniz"></td>

                <td>
                    <button type="submit" class="btn tooltips green-meadow" style="float: right"><i
                            class="fa fa-plus"></i> Ekle
                    </button>
                </td>

            </tr>


<input type="hidden" name="post" value="true">
        </table>
    </form>


    <?php

}else {
    echo '<a href="?ceviriler=&ekle=yenikelime" class="btn btn-circle btn-success">
<i class="fa fa-plus"></i> Kayıt Ekleme</a>';
}

?>

<br>
<div class="card card-shadow mb-4" style="margin-top:20px">
    <div class="card-body">
        <table class="table table-bordered table-striped" summary="" id="lang_table">
            <thead>
            <tr>
                <th style="width: 20%;"><?php echo(___("Kelime")); ?></th>
                <th style="width: 40%;">
                    <?php
                    if (count($_LANGUAGES) > 2) {
                        ?>
                        <select onchange="ddegis(1, this);" class="form-control">
                            <?php
                            foreach ($_LANGUAGES as $lk => $lv) {
                                printf('<option value="%1$d"%3$s>%2$s</option>', $lk, $lv["ad"], $lk == $dil_id1 ? " selected='selected'" : null);
                            }
                            ?>
                        </select>
                        <?php
                    } else {
                        echo($_LANGUAGES[$dil_id1]["ad"]);
                    }
                    ?>
                </th>
                <?php
                if ($second_lang) {
                    ?>
                    <th style="width: 80%;">
                        <?php
                        if (count($_LANGUAGES) > 2) {
                            ?>
                            <select onchange="ddegis(2, this);" class="form-control">
                                <?php
                                foreach ($_LANGUAGES as $lk => $lv) {
                                    printf('<option value="%1$d"%3$s>%2$s</option>', $lk, $lv["ad"], $lk == $dil_id2 ? " selected='selected'" : null);
                                }
                                ?>
                            </select>
                            <?php
                        } else {
                            echo($_LANGUAGES[$dil_id2]["ad"]);
                        }
                        ?>
                    </th>
                    <?php
                }
                ?>
                <th>

                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            $query = mysql_query("select * from `o_dil_ceviriler` group by `kelime` order by `kelime` asc");
            while ($okut = mysql_fetch_array($query)) {
                ?>
                <tr class="vati">
                    <td>
                        <?php echo($okut["kelime"]); ?>
                    </td>
                    <td>
                        <?php
                        $value = fetch(sprintf('select `anlam` from `o_dil_ceviriler` where `kelime`="%1$s" and `dil_id`=%2$s limit 1', $okut["kelime"], $dil_id1));
                        if ($value == false) {
                            $int = true;
                        } else {
                            $int = strpos($value, "\n") === false;
                        }
                        if ($int) {
                            ?>
                            <input type="text" class="tali form-control" dil_id="<?php echo($dil_id1); ?>"
                                   kelime="<?php echo($okut["kelime"]) ?>" value="<?php echo($value); ?>"/>
                            <?php
                        } else {
                            ?>
                            <textarea class="tali form-control" dil_id="<?php echo($dil_id1); ?>"
                                      kelime="<?php echo($okut["kelime"]) ?>"><?php echo($value); ?></textarea>
                            <?php
                        }
                        ?>
                    </td>
                    <?php
                    if ($second_lang) {
                        ?>
                        <td>
                            <?php
                            $value = fetch(sprintf('select `anlam` from `o_dil_ceviriler` where `kelime`="%1$s" and `dil_id`=%2$s limit 1', $okut["kelime"], $dil_id2));
                            if ($value == false) {
                                $int = true;
                            } else {
                                $int = strpos($value, "\n") === false;
                            }
                            if ($int) {
                                ?>
                                <input type="text" class="tali form-control" dil_id="<?php echo($dil_id2); ?>"
                                       kelime="<?php echo($okut["kelime"]) ?>" value="<?php echo($value); ?>"/>
                                <?php
                            } else {
                                ?>
                                <textarea class="tali form-control" dil_id="<?php echo($dil_id2); ?>"
                                          kelime="<?php echo($okut["kelime"]) ?>"><?php echo($value); ?></textarea>
                                <?php
                            }
                            ?>
                        </td>
                        <?php
                    }
                    ?>
                    <td>

                        <a class="btn btn-outline-danger rounded-0 sil_btn" href="?ceviriler=&islem=sil&ID=<?=$okut[0]?>" title="" data-original-title="Sil"><span class="fa fa-remove"></span></a>

                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <script type="text/javascript">
            function ddegis(dl, obje) {
                var hrb = dl == 1 ? "<?php echo(parse_panel_url(array("ceviriler" => "", "d2" => $dil_id2, "d1" => ""))); ?>" : "<?php echo(parse_panel_url(array("ceviriler" => "", "d1" => $dil_id1, "d2" => ""))); ?>";
                hrb += $(obje).val()
                location.href = hrb;
            }
            $(document).ready(function () {
                set_anlam_inpts();
            });
            function set_anlam_inpts() {
                $("#lang_table :input:not(.setted)").bind("change", function () {
                    var $this = $(this);
                    $.post("ajax.php", {
                        action: "tercume",
                        dil_id: $this.attr("dil_id"),
                        kelime: $this.attr("kelime"),
                        anlam: $this.val()
                    })
                }).bind("keydown", function (e) {
                    if (e.keyCode == 13) {
                        var $this = $(this);
                        if ($this.is("textarea")) {
                            return;
                        }
                        var $ta = $("<textarea />");
                        var ca = ["dil_id", "kelime", "class", "style"]
                        $.each(ca, function (i, o) {
                            $ta.attr(o, $this.attr(o));
                        });
                        $ta.val($this.val() + "\n");
                        $ta.insertBefore($this);
                        $this.remove();
                        $ta.focus();
                        $ta.removeClass("setted");
                        //$ta.select();
                        set_anlam_inpts();
                        return false;
                    }
                }).bind("focus", function () {
                    $(this).css("background-color", "");
                }).bind("blur xxx", function () {
                    var $this = $(this);
                    var val = $.trim($this.val());
                    if (val == "") {
                        $(this).css("background-color", "#FFEBEB");
                    } else {
                        $(this).css("background-color", "");
                    }
                }).addClass("setted").trigger("xxx");
            }
        </script>
    </div>
</div>
