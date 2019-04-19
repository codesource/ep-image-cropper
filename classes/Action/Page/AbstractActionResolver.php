<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action\Page;


abstract class AbstractActionResolver
{
    /**
     * @param string $action
     *
     * @return array
     */
    abstract public function resolve($action);
}