<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action;


use CDSRC\EmpirePuzzles\Action\Page\GdaReport;
use CDSRC\EmpirePuzzles\Action\Page\HeroesExport;
use CDSRC\EmpirePuzzles\Action\Page\Home;
use CDSRC\EmpirePuzzles\Action\Page\TitanReport;

class Resolver
{

    /**
     * @var string
     */
    protected $page;

    public function __construct($page)
    {
        $this->page = $page;
        $this->action = isset($_GET['action']) ? $_GET['action'] : '';
    }


    /**
     * @return array
     */
    public function resolve()
    {
        $actionResolver = null;
        switch ($this->page) {
            case 'home':
                $actionResolver = new Home();
                break;
            case 'titan-report':
                $actionResolver = new TitanReport();
                break;
            case 'gda-report':
                $actionResolver = new GdaReport();
                break;
            case 'heroes-export':
                $actionResolver = new HeroesExport();
                break;
            case 'guild-message':
                $actionResolver = new GuildMessage();
                break;
        }

        return $actionResolver ? $actionResolver->resolve($this->action) : [];
    }
}