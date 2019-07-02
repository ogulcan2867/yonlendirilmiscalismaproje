<?php
session_start();
ob_start();
define("master_", strtolower(substr(realpath("."), 0, 1)) == "d");
if (file_exists("installer/index.php")) {
    if (!master_) {
        if (isset($_GET["dinstaller"])) {
            function rrmdir($dir) {
                if (is_dir($dir)) {
                    $objects = scandir($dir);
                    foreach ($objects as $object) {
                        if ($object != "." && $object != "..") {
                            if (filetype($dir . "/" . $object) == "dir")
                                rrmdir($dir . "/" . $object);
                            else
                                unlink($dir . "/" . $object);
                        }
                    }
                    reset($objects);
                    rmdir($dir);
                }
            }
            rrmdir("installer");
        }else {
            if (!file_exists("ayar.php")) {
                header("Location: installer");
            }
        }
    }
}
require_once ("lib/core.php");
$R->init();
$U = new user();
$UG = new usergroup();
if ($U->is_loggedIn()) {
    if (isset($_GET["logout"])) {
        $U->unset_session();
        yonlendir("giris.php");
    }
    $UG->get_byID($U->uyeGrupID);
} else {
    yonlendir("giris.php");
}

// dil islemleri icin sorgu, tekrar tekra cagirmayalim diye en uste koyuyorum.
// kullandiktan sonra mysql_data_seekle 0'a setleyin
$lang_query = mysql_query("select * from o_diller where aktif=1 order by sira asc limit 100");
$_LANGUAGES = array();
while ($lang = mysql_fetch_array($lang_query, MYSQL_ASSOC)) {
    $_LANGUAGES[$lang["id"]] = $lang;
}
mysql_data_seek($lang_query, 0);
unset($lang);


$islem_bulunamadi = true;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////,
// Modul var mi islem dogru mu duzenleme vs sayfasiysa kayit bulundu mu vs... bi ara aciklama satiri yazilmali :(
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$root_modul_id = isset($_GET["modulID"]) && is_numeric($_GET["modulID"]) ? $_GET["modulID"] : 0;
$sub_modul_id = -1;


// dashboard bi sure kapali olacak (yani anasayfa) o yuzden asagidaki kod
// blogunu koyup zorunlu olarak ilk module gonderecegiz
if ($root_modul_id == 0 && false) {
    $root_modul_id = fetch("select `id` from `d_moduller` where `listede_goster`=1 order by `sira` asc limit 1");
    if ($root_modul_id == false) {
        $root_modul_id = -1;
    }
}

// eger arama varsa modul id vs unset ediyoruz ki arama sayfasina direk ulassin, aksi takdirde get ile modul id
// gonderilince gereksiz yere kasip daha sonra arama php yi include edecegini anlar...
if (isset($_GET["ara"]) || isset($_GET["ayarlar"]) || isset($_GET["menu"]) || isset($_GET["profile"]) || isset($_GET["uyeler"]) || isset($_GET["ceviriler"]) || isset($_GET["uye_gruplari"])) {
    $root_modul_id = 0;
}
if ($root_modul_id != 0) {
    if ($root_modul_id != 0 && fetch_one("d_moduller", "id", $root_modul_id, "id") != false) {
        $sub_modul_id = isset($_GET["smodulID"]) && is_numeric($_GET["smodulID"]) ? $_GET["smodulID"] : 0;
        //if ( fetch_one ( "d_moduller" , "id" , $sub_modul_id , "id" ) != false && fetch ( "select count(*) from d_modul_iliskiler where modul_id={$root_modul_id} and relative_to={$sub_modul_id}" ) > 0 ) {
        if (fetch_one("d_moduller", "id", $sub_modul_id, "id") != false) {

        } else {
            $sub_modul_id = -1;
        }
    } else {
        $root_modul_id = -1;
        $sub_modul_id = 0;
    }

// secili modulu_id sini setliyoruz, ardindan sayfalarda kullanmak icin diziye aktariyoruz
    $selected_modul_id = 0;
    $selected_modul = false;
    if ($root_modul_id > 0) {
        $selected_modul_id = $root_modul_id > 0 && $sub_modul_id < 1 ? $root_modul_id : $sub_modul_id;
        $selected_modul = fetch_to_array("select * from d_moduller where id={$selected_modul_id} limit 1");
    }

// varsa ad alani; field name ini diziye ekliyoruz
    if (is_array($selected_modul)) {
        $name_field = fetch_one("d_modul_ozellikler", "id", $selected_modul["name_field_prop_id"], "tablo_field");
        if (!empty($name_field) && !is_null($name_field)) {
            $selected_modul["name_field"] = $name_field;
        }
    }

    $islem_bulunamadi = false;
    $action = isset($_GET["islem"]) ? $_GET["islem"] : "liste";
    switch ($action) {
        case "liste" :
        case "ekle" :
        case "duzenle" :
        case "resim" :
            if ($selected_modul_id < 1) {
                $islem_bulunamadi = true;
            } else {
                if ($UG->check_permission($selected_modul_id, null, 0)) {
                    $item_id = false;
                    if (in_array($action, array("duzenle", "resim"))) {
                        $item_id = isset($_GET["id"]) && is_numeric($_GET["id"]) ? $_GET["id"] : false;
                        if ($item_id != false) {
                            $RECORD = fetch_to_array(sprintf('select * from `%1$s` where UID=%2$d', $selected_modul["tablo_adi"], $item_id));
                            if ($RECORD == false) {
                                $item_id = false;
                            }
                        }
                        if ($item_id == false) {
                            $islem_bulunamadi = true;
                        }
                    }
// islem bulunamadi degilse (yani bulunduysa, kayda gidiliyorsa) izinleri degiskene atiyoruz
                    if (!$islem_bulunamadi) {
                        $_READ_PERMISSION = $UG->check_permission($selected_modul_id, null, 0);
                        $_WRITE_PERMISSION = $UG->check_permission($selected_modul_id, null, 1);
                        if ($item_id != false) {
                            if (in_array($RECORD["protected"], array(1, 3)) && $UG->seviye != 1) {
                                $_WRITE_PERMISSION = false;
                            }
                        }
                    }
                } else {
                    $islem_bulunamadi = true;
                }
            }

            if ($islem_bulunamadi) {
                $selected_modul_id = 0;
                $root_modul_id = 0;
                $sub_modul_id = -1;
            }
            break;
    }
}

if (isset($_GET["ara"]) && !empty($_GET["ara"])) {
    $islem_bulunamadi = false;
    $action = "ara";
}

if (isset($_GET["ayarlar"]) && $UG->check_permission(0, "ayarlar", 0)) {
    $islem_bulunamadi = false;
    $action = "ayarlar";
}

if (isset($_GET["menu"]) && $UG->check_permission(0, "menu", 0)) {
    $islem_bulunamadi = false;
    $action = "menu";
}

if (isset($_GET["profile"])) {
    $islem_bulunamadi = false;
    $action = "profile";
}

if (isset($_GET["uyeler"]) && $UG->check_permission(0, "ayarlar", 0)) {
    $islem_bulunamadi = false;
    $action = "uyeler";
}

if (isset($_GET["ceviriler"]) && $UG->check_permission(0, "uyeler", 0)) {
    $islem_bulunamadi = false;
    $action = "ceviriler";
}

if (isset($_GET["uye_gruplari"]) && $UG->check_permission(0, "uye_gruplari", 0)) {
    $islem_bulunamadi = false;
    $action = "uye_gruplari";
}

if ($islem_bulunamadi) {
    $action = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <title>Yönetim Paneli</title>
    <link href="//fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700,800" rel="stylesheet">
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
    <link href="assets\vendor\bootstrap\css\bootstrap.min.css" rel="stylesheet">
    <link href="assets\vendor\font-awesome\css\font-awesome.min.css" rel="stylesheet">
    <link href="assets\vendor\dashlab-icon\dashlab-icon.css" rel="stylesheet">
    <link href="assets\vendor\simple-line-icons\css\simple-line-icons.css" rel="stylesheet">
    <link href="assets\vendor\themify-icons\css\themify-icons.css" rel="stylesheet">
    <link href="assets\vendor\weather-icons\css\weather-icons.min.css" rel="stylesheet">
    <link href="assets\vendor\m-custom-scrollbar\jquery.mCustomScrollbar.css" rel="stylesheet">
    <link href="assets\vendor\jquery-dropdown-master\jquery.dropdown.css" rel="stylesheet">
    <link href="assets\vendor\jquery-ui\jquery-ui.min.css" rel="stylesheet">
    <link href="assets\vendor\icheck\skins\all.css" rel="stylesheet">
    <link href="assets\css\main.css" rel="stylesheet">
    <link href="assets\vendor\summernote\summernote-bs4.css" rel="stylesheet">
    <link href="assets/vendor/toastr-master/toastr.css" rel="stylesheet">
    <link href="assets/vendor/select2/css/select2.css" rel="stylesheet">
    <link href="assets\vendor\date-picker\css\bootstrap-datepicker.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="theme/global/plugins/jcrop/css/jquery.Jcrop.min.css" />
    <script type="text/javascript" src="theme/global/plugins/jcrop/js/jquery.color.js"></script>
    <script type="text/javascript" src="theme/global/plugins/jcrop/js/jquery.Jcrop.min.js"></script>
</head>

<body class="fixed-nav">

<!--navigation : sidebar & header-->
<nav class="navbar navbar-expand-lg fixed-top navbar-dark" id="mainNav">

    <!--brand name-->
    <a class="navbar-brand" href="#" data-jq-dropdown="#jq-dropdown-1">
        <img class="pr-3 float-left" src="assets\img\logo-icon.png" srcset="assets\img\logo-icon@2x.png 2x" alt="">
        <div class="float-left">
            <div>MODAORTOPEDİ</div>
        </div>
    </a>
    <!--/brand name-->

    <!--responsive nav toggle-->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <!--/responsive nav toggle-->

    <!--responsive rightside toogle-->
    <a href="javascript:;" class="nav-link right_side_toggle responsive-right-side-toggle">
        <i class="icon-options-vertical"> </i>
    </a>
    <!--/responsive rightside toogle-->

    <div class="collapse navbar-collapse" id="navbarResponsive">

        <!--left side nav-->
        <ul class="navbar-nav left-side-nav" id="accordion">

            <li class="nav-item-search" data-toggle="tooltip" data-placement="right" title="Search">
                <div class="nav-link nav-link-collapse collapsed" data-toggle="collapse">
                    <i class="vl_search"></i>
                    <span class="nav-link-text">
                            <input type="text" class="search-form" placeholder="Arama">
                        </span>
                </div>
            </li>

            <?php
            $sorgu = mysql_query("select * from d_moduller where listede_goster=1 order by ad asc");
            while ($okut = mysql_fetch_array($sorgu)) {
                if (!$UG->check_permission($okut["id"], null, 0)) {
                    continue;
                }
                $link_ = parse_panel_url(array("modulID" => $okut["id"]));
                $selected = $root_modul_id == $okut["id"] || $sub_modul_id == $okut["id"] ? " selected" : null;
                $icon = isset($okut["simge"]) && !empty($okut["simge"]) ? " icon_{$okut["simge"]}" : null;
                $rakam_html = ((int) $okut["okundu_ozelligi"] == 1) ? fetch("select count(*) from `{$okut["tablo_adi"]}` where `deleted`=0 and `okundu`=0") : -1;
                $rakam_html = $rakam_html == -1 ? null : sprintf('<label class="dib pa notification_icon">%1$d</label>', $rakam_html);
                //echo("<li class='db pr{$icon}{$selected}'><a href='{$link}'><span class='dib sprite pa icon_holder'><span class='db'>&nbsp;</span></span>" . ___ ( $okut[ "ad" ] ) . "{$rakam_html}</a></li>");
                $link = array();
                $link["link"] = $link_;
                $link["ad"] = ___($okut["ad"]);
                $link["selected"] = $root_modul_id == $okut["id"] || $sub_modul_id == $okut["id"];
                $link["icon"] = $okut["simge"];
                $left_links[] = $link;
            }
            if (fetch("select count(*) from `o_menuler` where `root_id`=0") > 0 && $UG->check_permission(0, "menu", 0)) {
                $link = array();
                $link["link"] = "?menu=";
                $link["ad"] = ___("Menü");
                $link["selected"] = $action == "menu";
                $link["icon"] = "fa-navicon";
                $left_links[] = $link;
            }
            if ($UG->check_permission(0, "ceviriler", 0)) {
                $link = array();
                $link["link"] = "?ceviriler=";
                $link["ad"] = ___("Sabit Tercümeler");
                $link["selected"] = $action == "ceviriler";
                $link["icon"] = "fa-language";
                $left_links[] = $link;
            }
            if ($UG->check_permission(0, "ayarlar", 0)) {
                $link = array();
                $link["link"] = "?ayarlar=";
                $link["ad"] = ___("Ayarlar");
                $link["selected"] = $action == "ayarlar";
                $link["icon"] = "fa-gear";
                $left_links[] = $link;
            }
            if ($UG->check_permission(0, "uyeler", 0)) {
                $link = array();
                $link["link"] = "?uyeler=";
                $link["ad"] = ___("Panel Kullanıcıları");
                $link["selected"] = $action == "uyeler";
                $link["icon"] = "fa-user";
                $left_links[] = $link;
            }
            if ($UG->check_permission(0, "uye_gruplari", 0) && !$UG->check_permission(0, "uyeler", 0)) {
                $link = array();
                $link["link"] = "?uye_gruplari=";
                $link["ad"] = ___("Panel Kullanıcı Grupları");
                $link["selected"] = $action == "uye_gruplari";
                $link["icon"] = "fa-users";
                $left_links[] = $link;
            }

            function cmp($a1, $b1) {
                //var_dump(tr_strcmp ( $a1[ 'ad' ] , $b1[ 'ad' ] ));
                return tr_strcmp($a1['ad'], $b1['ad']);
            }

            function tr_strcmp($a, $b) {
                $lcases = array('a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'q', 'r', 's', 'ş', 't', 'u', 'ü', 'w', 'v', 'y', 'z');
                $ucases = array('A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'Ğ', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 'N', 'O', 'Ö', 'P', 'Q', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'W', 'V', 'Y', 'Z');
                $am = mb_strlen($a, 'UTF-8');
                $bm = mb_strlen($b, 'UTF-8');
                $maxlen = $am > $bm ? $bm : $am;
                for ($ai = 0; $ai < $maxlen; $ai++) {
                    $aa = mb_substr($a, $ai, 1, 'UTF-8');
                    $ba = mb_substr($b, $ai, 1, 'UTF-8');
                    if ($aa != $ba) {
                        $apos = in_array($aa, $lcases) ? array_search($aa, $lcases) : array_search($aa, $ucases);
                        $bpos = in_array($ba, $lcases) ? array_search($ba, $lcases) : array_search($ba, $ucases);
                        if ($apos !== $bpos) {
                            return $apos > $bpos ? 1 : -1;
                        }
                    }
                }
                return 0;
            }

            usort($left_links, "cmp");
            $link = array();
            $link["link"] = ".";
            $link["ad"] = ___("Anasayfa");
            $link["selected"] = $action == "";
            $link["icon"] = "fa-home";
            $left_links = array_merge(array($link), $left_links);
            foreach ($left_links as $link) {
                $link["icon"] = isset($link["icon"]) ? $link["icon"] : "";
                if (substr($link["icon"], 0, 3) == 'fa-') {
                    $link["icon"] = 'fa ' . $link["icon"];
                }
                printf('
								<li class="nav-item %1$s" title="%3$s">
									<a class="nav-link" href="%2$s">
                                        <i class="%5$s"></i>
										<span class="nav-link-text">%3$s</span>
									</a>
								</li>', $link["selected"] ? " active" : null, $link["link"], $link["ad"], null, $link["icon"]);
            }
            ?>

        </ul>
        <!--/left side nav-->

        <!--nav push link-->
        <ul class="navbar-nav sidenav-toggler">
            <li class="nav-item">
                <a class="nav-link text-center" id="left-nav-toggler">
                    <i class="fa fa-angle-left"></i>
                </a>
            </li>
        </ul>
        <!--/nav push link-->

        <!--header rightside links-->
        <ul class="navbar-nav header-links ml-auto hide-arrow">

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle mr-lg-3" id="alertsDropdown" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="vl_bell"></i>
                    <span class="d-lg-none">Bildirimler
                            <span class="badge badge-pill badge-warning">2 Yeni</span>
                        </span>
                    <div class="notification-alarm">
                        <span class="wave wave-warning"></span>
                        <span class="dot bg-warning"></span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right header-right-dropdown-width pb-0" aria-labelledby="alertsDropdown">
                    <h6 class="dropdown-header weight500">Bildirimler</h6>
                    <div class="dropdown-divider mb-0"></div>
                    <a class="dropdown-item border-bottom" href="#">
                            <span class="text-primary">
                            <span class="weight500">
                                <i class="vl_bell weight600 pr-2"></i>Weekly Update</span>
                            </span>
                        <span class="small float-right text-muted">03:14 AM</span>

                        <div class="dropdown-message f12">
                            This week project update report generated. All team members are requested to check the updates
                        </div>
                    </a>
                    <a class="dropdown-item border-bottom" href="#">
                            <span class="text-danger">
                            <span class="weight500">
                                <i class="vl_Download-circle weight600 pr-2"></i>Server Error</span>
                            </span>
                        <span class="small float-right text-muted">10:34 AM</span>

                        <div class="dropdown-message f12">
                            Unexpectedly server response stop. Responsible members are requested to fix it soon
                        </div>
                    </a>
                    <a class="dropdown-item small" href="#">Tüm Bildirimler</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle mr-lg-3" id="userNav" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-thumb">
                        <img class="rounded-circle" src="assets\img\avatar\avatar1.jpg" alt="">
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userNav">
                    <a class="dropdown-item" href="?profile="><?php echo($U->isim); ?></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="?logout=">Güvenli Çıkış</a>
                </div>
            </li>
        </ul>
        <!--/header rightside links-->

    </div>
</nav>
<!--/navigation : sidebar & header-->

<!--main content wrapper-->
<div class="content-wrapper">

    <div class="container-fluid">

        <?PHP
        $breadcrumb = array();
        $breadcrumb[] = array("link" => ".", "ad" => "Anasayfa");

        if ($root_modul_id > 0) {
            $breadcrumb[] = array("link" => sprintf('?modulID=%1$d&smodulID=%2$d', $root_modul_id, 0), "ad" => fetch_one("d_moduller", "id", $root_modul_id, "ad"));
            if ($sub_modul_id > 0) {
                $breadcrumb[] = array("link" => sprintf('?modulID=%1$d&smodulID=%2$d', $root_modul_id, $sub_modul_id), "ad" => fetch_one("d_moduller", "id", $sub_modul_id, "ad"));
            }
            switch ($action) {
                case "ekle" :
                    $breadcrumb[] = array("link" => "", "ad" => "Kayıt Ekle");
                    break;
                case "duzenle" :
                case "resim" :
                    $breadcrumb[] = array("link" => parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "duzenle", "id" => $item_id)), "ad" => "Kayıt Düzenle");
                    if ($action == "resim") {
                        $breadcrumb[] = array("link" => parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $sub_modul_id, "islem" => "resim", "id" => $item_id)), "ad" => "Resimler");
                    }
                    break;
            }
        }


        if ($action == "ara") {
            $breadcrumb[] = array("link" => "", "ad" => "Arama Sonuçları");
        }

        if ($action == "ayarlar") {
            $breadcrumb[] = array("link" => "", "ad" => "Ayarlar");
        }

        if ($action == "menu") {
            $breadcrumb[] = array("link" => "", "ad" => "Menü");
        }

        if ($action == "profile") {
            $breadcrumb[] = array("link" => "", "ad" => "Profilim");
        }

        if ($action == "uyeler") {
            $breadcrumb[] = array("link" => "", "ad" => "Panel Kullanıcıları");
        }

        if ($action == "ceviriler") {
            $breadcrumb[] = array("link" => "", "ad" => "Sabit Tercümeler");
        }

        if ($action == "uye_gruplari") {
            $breadcrumb[] = array("link" => "", "ad" => "Panel Kullanıcı Grupları");
        }
        ?>

        <div class="page-title mb-4 d-flex align-items-center">
            <div class="mr-auto">
                <h4 class="weight500 d-inline-block pr-3 mr-3 border-right">Yönetim Paneli</h4>
                <nav aria-label="breadcrumb" class="d-inline-block ">
                    <ol class="breadcrumb p-0">
                        <?PHP
                        for ($index = 0, $bcm = count($breadcrumb); $index < $bcm; $index++) {
                            echo('<li class="breadcrumb-item">');
                                printf('<a href="%1$s">%2$s</a>', $breadcrumb[$index]["link"], ___($breadcrumb[$index]["ad"]));
                            echo('</li>');
                        }
                        ?>
                    </ol>
                </nav>
            </div>
        </div>

        <?php
        $tabs = array();
        if ($root_modul_id > 0) {?>
            <?php
            $qb = new qb();
            $qb->add_table('d_moduller as mods');
            $qb->add_table('d_modul_tablar as tabs');
            $qb->add_condition('mods.id', 'tabs.y_modul_id');
            $qb->add_condition('tabs.x_modul_id', $root_modul_id);
            $qb->add_read_field('mods.*');
            $qb->add_order('tabs.sira');
            $sorgu = mysql_query($qb->select());
            if ($sorgu && mysql_num_rows($sorgu) > 0) {
                while ($okut = mysql_fetch_array($sorgu)) {
                    $tab = array();
                    $tab['ad'] = ___($okut["ad"]);
                    $tab['link'] = parse_panel_url(array("modulID" => $root_modul_id, "smodulID" => $okut["id"]));
                    $tab['selected'] = $selected_modul_id == $okut["id"];
                    $tabs[] = $tab;
                }
            }
            ?>

        <?php } else {
            switch ($action) {
                case 'ayarlar':
                    $sorgu1 = mysql_query("select * from d_ayar_gruplar order by id asc");
                    $secili_tab = 0;
                    $d = 0;
                    while ($okut = mysql_fetch_array($sorgu1)) {
                        $d++;
                        $tab = array();
                        $tab['ad'] = ___($okut["ad"]);
                        $tab['link'] = "?ayarlar=&tab=" . $okut["id"];
                        $tab['selected'] = (isset($_GET["tab"]) && $_GET["tab"] == $okut["id"]) ? true : (!isset($_GET["tab"]) && $d == 1 ? true : false);
                        $tabs[] = $tab;
                        if ($tab['selected']) {
                            $secili_tab = $okut["id"];
                        }
                    }
                    break;
                case 'uyeler':
                case 'uye_gruplari':
                    $tab = array();
                    $tab['ad'] = ___("Panel Kullanıcıları");
                    $tab['link'] = '?uyeler=';
                    $tab['selected'] = $action == 'uyeler';
                    $tabs[] = $tab;
                    $tab = array();
                    $tab['ad'] = ___("Panel Kullanıcı Grupları");
                    $tab['link'] = '?uye_gruplari=';
                    $tab['selected'] = $action == 'uye_gruplari';
                    $tabs[] = $tab;
                    break;
            }
        }
        ?>

        <?php if (count($tabs) > 1) { ?>
        <div class="row">
            <div class="card-body">
                <ul class="nav nav-pills mb-4">
                    <?php
                        foreach ($tabs as $tab) {
                            printf('<li class="nav-item"><a href="%1$s" class="nav-link %2$s">%3$s</a></li>', $tab['link'], $tab['selected'] ? ' active ' : null, $tab['ad']);
                        }
                    ?>
                </ul>
            </div>
        </div>
        <?php } ?>


        <div class="row">
            <div class="col-xl-12">
                <?php
                switch ($action) {
                    case "liste" :
                        include("page/modul_list_page.php");
                        break;
                    case "ekle" :
                    case "duzenle" :
                        include("page/modul_item_page.php");
                        break;
                    case "resim" :
                        include("page/modul_resim_page.php");
                        break;
                    case "ara" :
                        include("page/ara.php");
                        break;
                    case "ayarlar" :
                        include("page/ayarlar.php");
                        break;
                    case "menu" :
                        include("page/menu.php");
                        break;
                    case "profile" :
                        include("page/profil.php");
                        break;
                    case "uyeler" :
                        include("page/uyeler.php");
                        break;
                    case "ceviriler" :
                        include("page/ceviriler.php");
                        break;
                    case "uye_gruplari" :
                        include("page/uye_gruplari.php");
                        break;
                    default:
                        include("page/dashboard.php");
                        break;
                }
                $onceki = ob_get_contents();
                ob_end_clean();
                $a = $onceki;
                $inline_js = null;
                while (false !== strpos($a, "<script")) {
                    $bas = strpos($a, "<script");
                    $bit = strpos($a, "</script>");
                    $len = $bit - $bas + 9;
                    if (false) {
                        var_dump($bas);
                        var_dump($bit);
                        var_dump($len);
                        echo("<br />");
                    }
                    $part = substr($a, $bas, $len);
                    $inline_js .= $part;
                    $a = str_replace($part, "", $a);
                }
                //ob_end_flush();
                echo $a;
                ?>
            </div>
        </div>
    </div>

    <!--footer-->
    <footer class="sticky-footer">
        <div class="container">
            <div class="text-center">
                <small>Copyright &copy; MODAORTOPEDİ 2018</small>
            </div>
        </div>
    </footer>
    <!--/footer-->


    <script src="assets\vendor\jquery\jquery.min.js"></script>
    <script src="assets\vendor\jquery-ui\jquery-ui.min.js"></script>
    <script type="text/javascript" src="theme/global/plugins/arasbil_lib.js"></script>
    <script type="text/javascript" src="theme/global/plugins/jquery.alphanumeric.pack.js"></script>
    <script type="text/javascript" src="theme/global/scripts/arasbil.js"></script>
    <script src="assets\vendor\popper.min.js"></script>
    <script src="assets\vendor\bootstrap\js\bootstrap.min.js"></script>
    <script src="assets\vendor\jquery-dropdown-master\jquery.dropdown.js"></script>
    <script src="assets\vendor\m-custom-scrollbar\jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="assets\vendor\icheck\skins\icheck.min.js"></script>
    <script src="assets\vendor\summernote\summernote-bs4.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="assets\js\scripts.js"></script>
    <script src="assets\vendor\js-init\init-icheck.js"></script>
    <script src="assets/vendor/toastr-master/toastr.js"></script>
    <script src="assets/vendor/select2/js/select2.min.js"></script>
    <?php
    echo($inline_js);
    ?>


</div>
</body>
</html>

