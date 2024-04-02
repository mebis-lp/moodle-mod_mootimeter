<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defined caches used internally by the plugin.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'answers' => [
        'mode' => cache_store::MODE_APPLICATION,
    ],
];

$plugininfotools = new mod_mootimeter\plugininfo\mootimetertool;
$enabledtools = $plugininfotools->get_enabled_plugins();

foreach ($enabledtools as $tool => $toolname) {
    // Hook to do further actions depending on mtmt tool.
    $classname = "\mootimetertool_" . $tool . "\\" . $tool;
    $toolhelper = new $classname();
    $tooldefinitions = $toolhelper->get_tool_cachedefinition();
    if (!empty($tooldefinitions)) {
        $definitions = array_merge($definitions, $tooldefinitions);
    }
}
