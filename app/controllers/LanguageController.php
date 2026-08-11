<?php
namespace Controllers;

use Core\Config;
use Core\Controller;
use Core\Lang;

/**
 * Switches the interface language and returns the visitor to where they were.
 * Shared page — coordinate with the group before changing.
 */
class LanguageController extends Controller
{
    public function switch(string $code): void
    {
        if (in_array($code, Config::get('locales', ['en']), true)) {
            Lang::set($code);
        }
        $this->back();
    }
}
