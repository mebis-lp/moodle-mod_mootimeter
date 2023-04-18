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
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */

namespace mod_mootimeter;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use cache_exception;
use core\plugininfo\base, core_plugin_manager, moodle_url;
use dml_exception;
use stdClass;

/**
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */
abstract class toolhelper {

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    public abstract function insert_answer(object $page, $answer);

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public abstract function get_renderer_params(object $page);

    /**
     * Get the settings definitions.
     *
     * @param object $page
     * @return array
     */
    public abstract function get_tool_setting_definitions(object $page);

    /**
     * Get renderes setting output.
     *
     * @param mixed $page
     * @return string
     */
    public function get_tool_settings($page): string {
        global $OUTPUT;

        $settings = $this->get_tool_setting_definitions($page);
        return $OUTPUT->render_from_template("mod_mootimeter/settings", $settings);
    }

    /**
     * Get all tool settings parameters.
     *
     * @param object $page
     * @return array
     * @throws coding_exception
     */
    public function get_tool_settings_parameters(object $page): array {

        $settings = $this->get_tool_setting_definitions($page);

        $parameters = [];
        foreach ($settings['settingsarray'] as $setting) {

            foreach ($setting as $key => $value) {
                switch ($key) {
                    case 'text':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_TEXT),
                        ];
                        break;
                    case 'number':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_INT),
                        ];
                        break;
                    case 'select':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_TEXT),
                        ];
                        break;
                    case 'checkbox':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_INT),
                        ];
                        break;
                }
            }
        }
        return $parameters;
    }

    /**
     * Get the config of a pages tool.
     *
     * @param object|int $page
     * @param string $name
     * @return string|object
     */
    public function get_tool_config(object|int $pageorid, string $name = ""): string|object {
        global $DB;

        if (is_object($pageorid)) {
            $pageorid = $pageorid->id;
        }

        $conditions = ['pageid' => $pageorid];

        if (!empty($name)) {
            $conditions['name'] = $name;

            return $DB->get_field('mootimeter_tool_settings', 'value', $conditions);
        }

        return (object)$DB->get_records_menu('mootimeter_tool_settings', $conditions, '', 'name, value');
    }

    /**
     * Checks if a select option is selected.
     *
     * @param int $optionid
     * @param object $config
     * @param string $attribute
     * @return bool
     */
    public function is_option_selected(int $optionid, object $config, string $attribute): bool {

        if (empty($config->{$attribute})) {
            return false;
        }

        if ($config->{$attribute} == $optionid) {
            return true;
        }

        return false;
    }
}
