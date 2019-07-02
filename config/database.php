<?php

return [



    'models'    => 'App/Kernel/Models',

    /*
   |--------------------------------------------------------------------------
   | Veritabanı Bağlantıları
   |--------------------------------------------------------------------------
   |
   | Boilerplate esnek veritabanı bağlantısı sunmaktadır, istediğiniz
   | veritabanı sürücüsü ile bağlantı sağlayabilirsiniz.
   |
   */

    'driver'    => 'mysql',
    'host'      => 'localhost',
    'username'  => 'modaortopedi_root',
    'password'  => 'Ugureksi53',
    'charset'   => 'utf8',
    'schema'    => 'modaortopedi_db',


    /*
   |--------------------------------------------------------------------------
   | Redis Veritabanı
   |--------------------------------------------------------------------------
   |
   | Redis sunucunuz var ise aşağıdaki bilgileri doldurabilirsiniz.
   |
   */

    'redis' => [
    'cluster' => false,
    'default' => [
        'host' => 'localhost',
        'password' => 'REDISPASSWORD',
        'port' => 'REDISPORT',
        'database' => 0,
        ],
    ],

];
