<?php

use TowerUIX\Http\Controller;

class Kategori extends Controller {

    public function index($param='') {
        $Helpers = new \TowerUIX\Src\Helpers();
        $this->View("Urunler",
        [
            'Kategori' =>$Helpers->Kategori($param),
            'Urunler'=>$Helpers->Kategoriurun($param),
        ]); 
    }

}
