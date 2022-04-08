<?php

namespace CarstenWalther\DynDNS\Utility;

/**
 * DebugUtility
 */
class DebugUtility
{
    /**
     * @param mixed $variable
     * @param bool $die
     * @param string|null $title
     * @return string
     */
    public static function var_dump($variable, bool $die = true, string $title = null): string
    {
        $output = '<div class="debugger-utility">';
        if ($title) {
            $output .= '    <p><strong>' . $title . '</strong></p>';
        }
        $output .= '    <pre>';
        $output .= print_r($variable, true);
        $output .= '    </pre>';
        $output .= '</div>';

        echo $output . (new self)->css();

        if ($die) {
            die();
        }

        return '';
    }

    /**
     * @return string
     */
    protected function css(): string
    {
        $css[] = '.debugger-utility { padding: 0; margin: 20px; border: 1px solid #dedede; border-radius: 4px; overflow: hidden; }';
        $css[] = '.debugger-utility p { padding: 0 20px; margin: 20px 0; }';
        $css[] = '.debugger-utility pre { padding: 20px; margin: 0; background-color: #dedede; }';

        return '<style>' . implode('', $css) . '</style>';
    }
}
