<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Template;


use CDSRC\EmpirePuzzles\Controller\MainController;

class SimpleTemplate
{
    /**
     * @var string
     */
    protected $page;

    /**
     * @var string
     */
    protected $layoutPath = '../resources/layouts/';

    /**
     * @var array
     */
    protected $contents = [];

    /**
     * Template constructor.
     *
     * @param $page
     */
    public function __construct($page)
    {
        $this->page = $page;
    }

    /**
     * @param array $variables
     * @return string
     */
    public function render(array $variables = [])
    {
        $contents = [
            'title' => MainController::pages()[$this->page],
            'menu' => $this->getMenu(),
            'content' => $this->getPageContent($variables),
        ];

        return $this->replace($this->getLayout('layout', $contents), $this->contents);
    }

    /**
     * @return string
     */
    protected function getMenu()
    {
        $menu = [
            'menu-entry' => [],
        ];
        foreach (MainController::pages() as $name => $label) {
            $menu['menu-entry'][] = [
                'url' => $name . '.html',
                'label' => $label,
                'class' => $this->page === $name ? 'current' : '',
            ];
        }

        return $this->getLayout('menu', $menu);
    }

    /**
     * @param array $variables
     * @return string
     */
    protected function getPageContent(array $variables = [])
    {
        return $this->getLayout($this->page, $variables);
    }

    /**
     * @param string $name
     * @param array $variables
     * @return string
     */
    protected function getLayout($name, array $variables = [])
    {
        $layoutFilename = $this->layoutPath . $name . '.html';
        if (!file_exists($layoutFilename)) {
            return '';
        }
        $matches = [];
        $content = file_get_contents($layoutFilename);
        if (preg_match_all('/<template ref="' . '(.*?)"\s*>(.*?)<\/template>/sm', $content, $matches)) {
            foreach ($matches[1] as $key => $val) {
                if (!isset($this->contents[$val])) {
                    $this->contents[$val] = '';
                }
                $this->contents[$val] .= $matches[2][$key];
            }
        }

        return $this->replace($content, $variables);
    }

    /**
     * @param string $content
     * @param string|array $value
     * @return string
     */
    protected function replace($content, $value)
    {
        if (is_array($value)) {
            $replacement = '';
            foreach ($value as $key => $val) {
                $matches = [];
                if (is_numeric($key) && is_array($val)) {
                    $replacement .= $this->replace($content, $val);
                } else if (preg_match('/<template id="' . $key . '"\s*(>(.*?)<\/template>|\/>)/sm', $content, $matches)) {
                    $content = str_replace($matches[0], $this->replace(isset($matches[2]) ? $matches[2] : '', $val), $content);
                } else if (strpos($content, '{' . $key . '}') !== false) {
                    $content = str_replace('{' . $key . '}', $val, $content);
                }
            }
            if ($replacement) {
                return $replacement;
            }

        } else {
            return $value;
        }

        return $content;
    }
}