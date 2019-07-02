<?php

use TowerUIX\Http\Controller;

class Hakkimizda extends Controller {

    public function index() {
        $Helpers = new \TowerUIX\Src\Helpers();

        $this->View("Hakkimizda");
    }

}
