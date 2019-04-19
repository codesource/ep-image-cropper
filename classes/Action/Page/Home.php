<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action\Page;


use CDSRC\EmpirePuzzles\Controller\MainController;

class Home extends AbstractActionResolver
{

    /**
     * @param string $action
     *
     * @return array
     */
    public function resolve($action)
    {
        $variables = ['shortcuts' => []];
        $pages = MainController::pages();
        array_shift($pages);
        foreach ($pages as $key => $label) {
            $variables['shortcuts'][] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $variables;
    }
}