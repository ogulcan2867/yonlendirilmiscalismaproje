<?php
session_start ();
require_once ("lib/core.php");
require_once ("lib/ext/swift_mail/swift_required.php");
$R->init ();
$U = new user();
if ( $U->is_loggedIn () ) {
    yonlendir ( "." );
}
$mesaj = null;
$mesajtur = 0;
if ( isset ( $_GET[ "lng" ] ) && is_numeric ( $_GET[ "lng" ] ) ) {
    if ( intval ( fetch ( sprintf ( 'select * from o_diller where id=%1$d and pdil_aktif=1' , $_GET[ "lng" ] ) ) ) > 0 ) {
        $_SESSION[ "panel_dil" ] = ( int ) $_GET[ "lng" ];
        yonlendir ( "giris.php" );
    }
}
$time_protection_minutes = 5;
$time_protection_try_count = 3;
$time_protection_next_try = 0;
if ( isset ( $_POST[ "email" ] ) ) {
    $may_i_try = true;
    $in_protection = array();
    //unset($_SESSION[ 'login_tries' ]);
    if ( isset ( $_SESSION[ 'login_tries' ] ) ) {
        $tries = explode ( ',' , $_SESSION[ 'login_tries' ] );
        foreach ( $tries as $try ) {
            $try = ( int ) $try;
            if ( $try > time () - (60 * $time_protection_minutes) ) {
                $in_protection[] = $try;
            }
        }
    }
    if ( count ( $in_protection ) >= $time_protection_try_count ) {
        $may_i_try = false;
    }
    if ( $may_i_try ) {
        $in_protection[] = time ();
        $_SESSION[ 'login_tries' ] = implode ( ',' , $in_protection );
    }
    sort ( $in_protection );
    $time_protection_next_try = ($time_protection_minutes * 60) - (time () - $in_protection[ 0 ]);

    if ( !$may_i_try ) {
        $mesaj = ___ ( "{$time_protection_try_count} deneme hakkınızı doldurdunuz." ) . " " . $time_protection_next_try . " " . ___ ( "Saniye sonra tekrar deneyiniz" );
    } else {
        $kad = guvenlik ( $_POST[ "email" ] );
        $sifre = md5 ( $_POST[ "password" ] );
        try {
            if ( $U->is_userExists ( $kad ) ) {
                $U->get_userByEmail ( $kad );
                $UG = new usergroup();
                $UG->get_byID ( $U->uyeGrupID );
                if ( $U->sifre == $sifre ) {
                    $_SESSION[ "panel_dil" ] = $_POST[ "lng" ];
                    $U->set_session ();
                    mysql_query ( sprintf ( 'insert into p_logs (user_id, modul_id, record_id, action) values(%1$d, %2$d, %3$d, %4$d)' , $U->ID , 0 , 0 , 0 ) ) or die ( mysql_error () );
                    unset ( $_SESSION[ 'login_tries' ] );
                    yonlendir ( "." );
                } else {
                    $mesaj = ___ ( "Girmiş olduğunuz bilgiler geçersizdir. Lütfen kontrol ederek tekrar giriniz." );
                }
            } else {
                $mesaj = ___ ( "Girmiş olduğunuz bilgiler geçersizdir. Lütfen kontrol ederek tekrar giriniz." );
            }
        } catch ( exception $e ) {
            $mesaj = ___ ( $e->getMessage () );
        }
    }
}
$base = process_url ( get_current_page_url () , 1 ) . "/";
if ( !defined ( "site_base_href" ) ) {
    define ( "site_base_href" , $base );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="">
    <!--favicon icon-->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <title>Giriş Yap | Moda Ortopedi</title>
    <!--web fonts-->
    <link href="//fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700,800" rel="stylesheet">
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
    <!--bootstrap styles-->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!--icon font-->
    <link href="assets/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/vendor/dashlab-icon/dashlab-icon.css" rel="stylesheet">
    <link href="assets/vendor/simple-line-icons/css/simple-line-icons.css" rel="stylesheet">
    <link href="assets/vendor/themify-icons/css/themify-icons.css" rel="stylesheet">
    <link href="assets/vendor/weather-icons/css/weather-icons.min.css" rel="stylesheet">
    <!--custom scrollbar-->
    <link href="assets/vendor/m-custom-scrollbar/jquery.mCustomScrollbar.css" rel="stylesheet">
    <!--jquery dropdown-->
    <link href="assets/vendor/jquery-dropdown-master/jquery.dropdown.css" rel="stylesheet">
    <!--jquery ui-->
    <link href="assets/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
    <!--iCheck-->
    <link href="assets/vendor/icheck/skins/all.css" rel="stylesheet">
    <!--custom styles-->
    <link href="assets/css/main.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="assets/vendor/html5shiv.js"></script>
    <script src="assets/vendor/respond.min.js"></script>
    <![endif]-->
</head>
<body class="login-bg">
<div class="container">
    <div class="row">
        <div class="col-xl-12 d-lg-flex align-items-center">
            <div class="login-form">
                <h4 class="text-uppercase text-purple text-center mb-5"><?PHP echo(___ ( "HESABINIZA GİRİŞ YAPIN" )) ?></h4>
                <?php
                if ( !is_null ( $mesaj ) ) {
                    $msax = $mesajtur == 0 ? 'danger' : 'success';
                    echo("<div id='login_message' class='alert alert-{$msax}'>{$mesaj}</div>");
                }
                ?>
                <form action="#" method="POST">
                    <div class="form-group">
                        <input type="email" class="form-control" placeholder="<?PHP echo(___ ( "E-Posta" )) ?>" name="email">
                    </div>
                    <div class="form-group mb-4">
                        <input type="password" class="form-control" placeholder="<?PHP echo(___ ( "Şifre" )) ?>" name="password">
                    </div>

                    <div class="form-group clearfix">
                        <button type="submit" class="btn btn-purple float-right"><?PHP echo(___ ( "GİRİŞ" )) ?></button>
                    </div>
                </form>
            </div>
            <div class="login-promo basic-gradient  text-white position-relative">
                <div class="login-promo-content text-center">
                    <a href="#" class="mb-4 d-block">
                        <img class="pr-3" src="assets/img/logo-icon.png" srcset="assets/img/logo-icon@2x.png 2x" alt="">
                        <span class="text-uppercase weight800 text-white f18">Moda Ortopedi</span>
                    </a>
                    <h1 class="text-white">Moda Ortopedi</h1>
                    <p>Web Sitesi Yönetim Paneli</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/vendor/popper.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/vendor/jquery-dropdown-master/jquery.dropdown.js"></script>
<script src="assets/vendor/m-custom-scrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="assets/vendor/icheck/skins/icheck.min.js"></script>
<!--[if lt IE 9]>
<script src="assets/vendor/modernizr.js"></script>
<![endif]-->
<script src="assets/js/scripts.js"></script>
</body>
</html>