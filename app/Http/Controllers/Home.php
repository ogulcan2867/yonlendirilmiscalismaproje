<?php

use TowerUIX\Http\Controller;

class Home extends Controller {

    public function index() {
        $Helpers = new \TowerUIX\Src\Helpers();

        $this->View("Home",
        [
            'AnasayfaKutular'=> $Helpers->HomeBox(),
            'Sayilar'=> $Helpers->HomeCount(),
            'Slider'=> $Helpers->HomeSlider(),
        ]);
    }

}
