<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action\Page;


use CDSRC\EmpirePuzzles\Service\ReportBuilderService;

class TitanReport extends AbstractActionResolver
{

    /**
     * @param string $action
     *
     * @return array
     */
    public function resolve($action)
    {
        switch ($action) {
            case 'get-report':
                try {
                    (new ReportBuilderService())->sendReport();
                    exit;
                } catch (\Exception $e) {
                    header("HTTP/1.0 404 Not Found");
                    header("X-REASON: " . $e->getMessage());
                    exit;
                }
                break;
        }
        return [];
    }

}