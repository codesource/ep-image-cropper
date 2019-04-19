<?php
/**
 * @copyright Copyright (c) 2019 Code-Source
 */


use CDSRC\EmpirePuzzles\Controller\MainController;

spl_autoload_register(function ($name) {
    require_once('../classes/' . str_replace(['CDSRC\\EmpirePuzzles\\', '\\'], ['', '/'], $name) . '.php');
});

$controller = new MainController();
$controller->render();