<div class="card card-shadow mb-4">
    <div class="card-header border-0">
        <div class="custom-title-wrap bar-danger">
            <div class="custom-title"><?php echo(___ ( "Son İşlemler" )); ?></div>
        </div>
    </div>
    <div class="card-body" style="height: 400px;overflow-y: scroll;">
        <ul class="list-unstyled base-timeline activity-timeline">
            <?PHP
            $uyeler = array();
            $moduller = array();
            $qb = new qb();
            $qb->add_table ( 'p_uyeler as u' );
            $qb->add_table ( 'p_uye_gruplari as g' );
            $qb->add_table ( 'p_logs as l' );
            $qb->add_condition ( 'u.uye_grup_id' , 'g.id' );
            $qb->add_condition ( 'g.seviye' , $UG->ID , '>=' );
            $qb->add_condition ( 'l.user_id' , 'u.id' );
            $qb->add_read_field ( 'l.*' );
            $qb->limit = 25;
            $qb->add_order ( 'l.c_date' , false );
            $sorgu = mysql_query ( $qb->select () );
            while ( $r = mysql_fetch_array ( $sorgu ) ) {
                $r[ "user_id" ] = ( int ) $r[ "user_id" ];
                $r[ "modul_id" ] = ( int ) $r[ "modul_id" ];
                $r[ "record_id" ] = ( int ) $r[ "record_id" ];
                if ( !isset ( $uyeler[ $r[ "user_id" ] ] ) ) {
                    $uyeler[ $r[ "user_id" ] ] = fetch_to_array ( sprintf ( 'select * from p_uyeler where id=%1$d limit 1' , $r[ "user_id" ] ) );
                }
                if ( !isset ( $moduller[ $r[ "modul_id" ] ] ) ) {
                    $moduller[ $r[ "modul_id" ] ] = fetch_to_array ( sprintf ( 'select * from d_moduller where id=%1$d limit 1' , $r[ "modul_id" ] ) );
                }
                if ( $r[ "modul_id" ] > 0 && $r[ "record_id" ] > 0 ) {
                    $kayit = fetch_to_array ( sprintf ( 'select * from `%2$s` where deleted=0 and id=%1$d limit 1' , $r[ "record_id" ] , $moduller[ $r[ "modul_id" ] ][ "tablo_adi" ] ) );
                    $kayit_exist = $kayit !== false;
                }
                $icon = "bell-o";
                $iconrenk = "label-success";
                $yazi = null;
                switch ( $r[ "action" ] ) {
                    case 0:
                        $yazi = sprintf ( '<strong class="c">%1$s</strong> panele giriş yaptı' , $uyeler[ $r[ "user_id" ] ][ "isim" ] );
                        $icon = 'key';
                        break;
                    case 1:
                        if ( $kayit_exist ) {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülüne <a href="%3$s" target="_blank" class="c">kayıt</a> ekledi.' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] , parse_panel_url ( array( "modulID" => $r[ "modul_id" ] , "islem" => "duzenle" , "id" => $r[ "record_id" ] ) ) );
                        } else {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülüne kayıt ekledi. <strong>(Kayıt mevcut değil)</strong>' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] );
                        }
                        $icon = 'plus';
                        break;
                    case 2:
                        if ( $kayit_exist ) {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki <a href="%3$s" target="_blank" class="c">kaydı</a> güncelledi.' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] , parse_panel_url ( array( "modulID" => $r[ "modul_id" ] , "islem" => "duzenle" , "id" => $r[ "record_id" ] ) ) );
                        } else {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki kaydı güncelledi. <strong>(Kayıt mevcut değil)</strong>' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] );
                        }
                        $icon = 'edit';
                        break;
                    case 3:
                        $yazi = sprintf ( '<strong class="c">%1$s</strong>, <strong class="c">%2$s</strong> modülündeki bir kaydı sildi.' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] );
                        $icon = 'remove';
                        break;
                    case 4:
                        if ( $kayit_exist ) {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki <a href="%3$s" target="_blank" class="c">kaydın</a> bir resmini sildi.' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] , parse_panel_url ( array( "modulID" => $r[ "modul_id" ] , "islem" => "resim" , "id" => $r[ "record_id" ] ) ) );
                        } else {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki bir kaydın resmini sildi. <strong>(Kayıt mevcut değil)</strong>' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] );
                        }
                        $icon = 'remove';
                        break;
                    case 5:
                        if ( $kayit_exist ) {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki <a href="%3$s" target="_blank" class="c">kayda</a> resim ekledi.' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] , parse_panel_url ( array( "modulID" => $r[ "modul_id" ] , "islem" => "resim" , "id" => $r[ "record_id" ] ) ) );
                        } else {
                            $yazi = sprintf ( '%1$s, <strong class="c">%2$s</strong> modülündeki kayda resim ekledi. <strong>(Kayıt mevcut değil)</strong>' , $uyeler[ $r[ "user_id" ] ][ "isim" ] , $moduller[ $r[ "modul_id" ] ][ "ad" ] );
                        }
                        $icon = 'gallery';
                        break;
                    case 6:
                        break;
                }
                ?>

                <li>
                    <div class="timeline-icon">
                        <img src="assets\img\icon\<?php echo($icon); ?>.png">
                    </div>
                    <div class="base-timeline-info">
                        <?PHP echo($yazi); ?>
                    </div>
                    <small class="text-muted">
                        <?php
                        $passed = time () - strtotime ( $r[ "c_date" ] );
                        $saniye = $passed;
                        $gun = floor ( $saniye / 86400 );
                        $saniye -= $gun * 86400;
                        $saat = floor ( $saniye / 3600 );
                        $saniye -= $gun * 3600;
                        $dakika = floor ( $saniye / 60 );
                        $saniye -= $gun * 60;
                        if ( $gun > 0 ) {
                            echo($gun . ' gün ');
                        } elseif ( $saat > 0 ) {
                            echo($saat . ' saat ');
                        } elseif ( $dakika > 0 ) {
                            echo($dakika . ' dakika ');
                        } else {
                            echo($saniye . ' saniye ');
                        }
                        ?>
                    </small>
                </li>
                <?php
            }
            ?>


        </ul>
    </div>
</div>