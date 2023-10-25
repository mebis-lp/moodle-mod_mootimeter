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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use cache_exception;
use dml_exception;

/**
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class toolhelper extends \mod_mootimeter\helper {

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    abstract public function insert_answer(object $page, $answer);

    /**
     * Delete Page
     *
     * @param object $page
     * @return bool
     */
    abstract public function delete_page_tool(object $page);

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    abstract public function get_renderer_params(object $page);

    /**
     * Get the settings column.
     *
     * @param object $page
     * @return mixed
     */
    abstract public function get_col_settings_tool(object $page);

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    abstract public function hook_after_new_page_created(object $page);

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
