<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Controller;


use CDSRC\EmpirePuzzles\Action\Resolver;
use CDSRC\EmpirePuzzles\Service\ReportBuilderService;
use CDSRC\EmpirePuzzles\Template\SimpleTemplate;

class MainController
{

    /**
     * @var string
     */
    protected $page;

    /**
     * @var Template
     */
    protected $template;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->page = isset($_POST['page']) ? $_POST['page'] : isset($_GET['page']) ? $_GET['page'] : '';
        $pages = array_keys(self::pages());
        if (!in_array($this->page, $pages)) {
            $this->page = reset($pages);
        }
        $this->template = new SimpleTemplate($this->page);
    }

    public function render()
    {
        $resolver = new Resolver($this->page);

        echo $this->template->render($resolver->resolve());
    }

    /**
     * @return array
     */
    static public function pages()
    {
        return [
            'home' => 'Accueil',
            'titan-report' => 'Rapport sur les titans',
            'gda-report' => 'Rapport sur les GDA',
            'heroes-export' => 'Exportation des héros',
        ];
    }
}