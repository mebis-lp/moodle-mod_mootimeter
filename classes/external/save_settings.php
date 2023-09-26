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
 * Web service to save settings.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use mod_mootimeter\page_manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to save settings.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_settings extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'settings' => new \external_value(PARAM_RAW, 'Settings as JSON string of object')
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $settings
     * @return string
     */
    public static function execute(int $pageid, string $settings): string {
        [
                'pageid' => $pageid,
                'settings' => $settings,
        ] = self::validate_parameters(self::execute_parameters(), [
                'pageid' => $pageid,
                'settings' => $settings,
        ]);

        $settings = json_decode($settings, true);

        if (!is_array($settings)) {
            throw new \coding_exception('settings has to be an array!');
        }

        $context = page_manager::get_context_for_page($pageid);
        require_capability('mod/mootimeter:moderator', $context);

        $page = page_manager::get_page($pageid);
        $lib = page_manager::get_tool_lib($page->tool);
        $settingdefs = $lib->get_tool_setting_definitions();

        $response = [];

        if (isset($settings['title'])) {
            $page->title = clean_param($settings['title'], PARAM_TEXT);
            $response['title'] = ['value' => $page->title];
        }

        if (isset($settings['question'])) {
            $page->question = clean_param($settings['question'], PARAM_TEXT);
            $response['question'] = ['value' => $page->question];
        }
        page_manager::store_page($page);

        foreach ($settingdefs as $settingdef) {
            $name = $settingdef->get_name();
            if (isset($settings[$name])) {
                $value = $settings[$name];
                $error = $settingdef->validate($value);
                if ($error) {
                    $response[$name] = ['error' => $error];
                } else {
                    $response[$name] = ['value' => $value];
                    page_manager::set_tool_config($page, $name, $value);
                }
            }
        }

        return json_encode($response);
    }

    /**
     * Describes the return structure of the service.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new external_value(PARAM_RAW, 'JSON of result');
    }
}
