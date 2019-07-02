<?php

namespace TowerUIX\Kernel;

use TowerUIX\Kernel\Contracts\BundleInterface;
use TowerUIX\Src\Helpers;
use Twig_Loader_Filesystem;
use Twig_Environment;

class Bundle implements BundleInterface {

    private $__Loader;
    public $__Twig;

    public function __construct() {
        session_start();
        ob_start();
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        error_reporting(0);

        if($_SESSION["dil_id"])
        {
            define('dil_id', $_SESSION["dil_id"]);
        }else{
            define('dil_id', 1);
        }



        $this->__Loader = new Twig_Loader_Filesystem("views");
        $this->__Twig = new Twig_Environment($this->__Loader, []);




        if ($_SERVER['HTTP_HOST'] == "localhost") {
            $this->__Twig->addGlobal('host', "https://{$_SERVER['HTTP_HOST']}/");
            define('url', "http://localhost/modaortopedi/");
        } else {
            $this->__Twig->addGlobal('host', "http://{$_SERVER['HTTP_HOST']}/");
            define('url', "https://www.modaortopedi.com/");
        }


        /*
         * Ust Menu BEGIN
         */

        $url = array("", "");
        if (isset($_GET['url'])) {
            $url = explode("/", filter_var(rtrim($_GET['url'], "/"), FILTER_SANITIZE_URL));
        }

        $Pattern = "";
        $secili_sayfa_adi = "";
        $UstMenu = \m_Menu::find_all_by_root_id_and_dil_id(1, dil_id, array('order' => 'sira asc'));

        foreach ($UstMenu as $MenuItems) {
            $parrent = "";

            if ($MenuItems->modul_id == 0) {
                // Bağlantı Burası
                if($MenuItems->url == 'javascript:void(0)')
                {
                  $Slug = $MenuItems->url;
                }else{
                  $Slug = url .$MenuItems->url;
                }
            } else {
                //İçeriklere Gidecek
                $permant = \m_Slug::find_by_modul_id_and_x_id_and_varsayilan_and_dil_id($MenuItems->modul_id, $MenuItems->x_id, 1,dil_id);
                if ($permant->permant) {
                    $Slug = url  . $permant->permant;
                }

            }


            $FindThisSubMenu = \m_Menu::find_all_by_root_id_and_dil_id($MenuItems->uid, dil_id,array("order"=> "sira asc"));

            $SubMenus = [];
            $curl = count($url);
            $Slug1 = "";
            $sub_secim = "";
            foreach ($FindThisSubMenu as $SubMenu) {
                if ($SubMenu->modul_id != 0) {
                    $parrent = 'kategori/';
                    $permant = \m_Slug::find_by_modul_id_and_x_id_and_varsayilan_and_dil_id($SubMenu->modul_id, $SubMenu->x_id, 1,dil_id);
                    if ($permant->permant) {

                        $Slug1 = url .$parrent. $permant->permant;
                    }

                } else {
                    // Bağlantı Burası
                    $Slug1 = url .$SubMenu->url;
                }

                $SubMenus[] = [
                    'ad' => $SubMenu->ad,
                    'url' => $Slug1
                ];
                if ($curl > 1) {
                    $urlbuyuk = $url[0] . "/" . $url[1];
                    if ($Slug == $urlbuyuk) {
                        $selected = "current_page_item";
                    }
                }
            }

            $selected = "";
            if ($curl > 1) {
                if ($Slug == url) {
                    $selected = "current-menu-item current_page_item";
                }
            } else if ($curl == 1) {
                if ($url[0] == $Slug) {
                    $selected = "current-menu-item current_page_item";
                    $secili_sayfa_adi = $MenuItems->ad . " - ";
                } else if (@$url[1] == $Slug) {
                    $selected = "current-menu-item current_page_item";
                    $secili_sayfa_adi = $MenuItems->ad . " - ";
                }
            }

            $MenuData[] = [
                'ad' => $MenuItems->ad,
                'url' => $Slug,
                'sub' => $SubMenus,
                'selected' => $selected
            ];
        }
        /*
         * Ust Menu END
        */

        //Title settings BEGIN
        if ($secili_sayfa_adi == "") {
            if ($curl > 0) {
                if ($url[0] == "haberler") {
                    $secili_sayfa_adi = "haberler - ";
                    if ($curl > 1) {
                        $GetXID = \m_Slug::find_by_modul_id_and_permant_and_varsayilan_and_dil_id(3, trim($url[1]), 1,dil_id);
                        if ($GetXID) {
                            $haber_adi = \news::find_by_deleted_and_uid_and_publishing(0, $GetXID->x_id, 1);
                            $secili_sayfa_adi = $haber_adi->baslik . " - ";
                        }
                    }
                }
                if ($url[0] == "icerik") {
                    if ($curl > 1) {
                        $GetXID = \m_Slug::find_by_modul_id_and_permant_and_varsayilan_and_dil_id(2, trim($url[1]), 1,dil_id);
                        if ($GetXID) {
                            $haber_adi = \icerikler::find_by_deleted_and_uid_and_publishing(0, $GetXID->x_id, 1);
                            $secili_sayfa_adi = $haber_adi->baslik . " - ";
                        }
                    }
                }
            }
        }
        //Title settings END




        /*
         * SITE SETTİNGS BEGIN
         */
        $ayarlar = \m_Ayarlar::all();

        $ayar_dizi = array();

        foreach ($ayarlar as $value) {
            if($value->dil_id==0)
            {
                if ($value->ayar_key == "site_baslik") {
                    $ayar_dizi[$value->ayar_key] = ucwords(mb_strtolower($secili_sayfa_adi, "UTF-8")) . $value->deger;
                } else if ($value->ayar_key == "site_aciklamasi" && $description != "") {
                    $ayar_dizi[$value->ayar_key] = $description;
                } else if ($value->ayar_key == "anahtar_kelimeler" && $tags__ != "") {
                    $ayar_dizi[$value->ayar_key] = $tags__;
                } else {
                    $ayar_dizi[$value->ayar_key] = htmlspecialchars_decode($value->deger, ENT_QUOTES);
                }
            }else if(dil_id== $value->dil_id) {
                if ($value->ayar_key == "site_baslik") {
                    $ayar_dizi[$value->ayar_key] = ucwords(mb_strtolower($secili_sayfa_adi, "UTF-8")) . $value->deger;
                } else if ($value->ayar_key == "site_aciklamasi" && $description != "") {
                    $ayar_dizi[$value->ayar_key] = $description;
                } else if ($value->ayar_key == "anahtar_kelimeler" && $tags__ != "") {
                    $ayar_dizi[$value->ayar_key] = $tags__;
                } else {
                    $ayar_dizi[$value->ayar_key] = htmlspecialchars_decode($value->deger, ENT_QUOTES);
                }
            }

        }
        /*
         * SITE SETTİNGS END
         */
        $Cevir = \m_Translate::find_by_sql("SELECT * FROM o_dil_ceviriler WHERE dil_id='".dil_id."'");
        if($Cevir)
        {
            foreach ($Cevir as $Cevir) {
                $DilArray[$Cevir->kelime] = htmlspecialchars_decode($Cevir->anlam, ENT_QUOTES);
            }
        }

        define('ceviri', $DilArray);



        $FooterMenu = \m_Menu::find_all_by_root_id_and_dil_id(27, dil_id, array('order' => 'sira asc'));
        foreach ($FooterMenu as $item) {
            if ($item->modul_id != 0) {
                if($item->modul_id==6)
                {
                    $parrent='referans/';
                }elseif ($item->modul_id==7){ $parrent='urun/'; }
                else{
                    $parrent = '';
                }
                $permant = \m_Slug::find_by_modul_id_and_x_id_and_varsayilan_and_dil_id($item->modul_id, $item->x_id, 1,dil_id);
                if ($permant->permant) {
                    $Slug1 = url .$parrent. $permant->permant;
                }

            } else {
                // Bağlantı Burası
                $Slug1 = url .$item->url;
            }

            $Menuu[] = [
                'Ad' => $item->ad,
                'url' => $Slug1,
            ];
        }

        $this->__Twig->addGlobal('menu', $MenuData);
        $this->__Twig->addGlobal('url', url);
        $this->__Twig->addGlobal('ayarlar', $ayar_dizi);
        $this->__Twig->addGlobal('dil_id',dil_id);
        $this->__Twig->addGlobal('ceviri',$DilArray);
        $this->__Twig->addGlobal('footerMenu',$Menuu);




    }


}
