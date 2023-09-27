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
 * Web service to create a new page.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_mootimeter\page_manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to create a new page.
 *
 * @package     mod_mootimeter
 * @copyright   2023 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_page extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The instance to create a page for.'),
            'tool' => new \external_value(PARAM_ALPHANUMEXT, 'The chosen page type')
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $instanceid
     * @param string $tool
     */
    public static function execute(int $instanceid, string $tool) {
        global $DB;
        [
                'instanceid' => $instanceid,
                'tool' => $tool,
        ] = self::validate_parameters(self::execute_parameters(), [
                'instanceid' => $instanceid,
                'tool' => $tool,
        ]);

        $cmid = get_coursemodule_from_instance('mootimeter', $instanceid)->id;
        $context = context_module::instance($cmid);
        require_capability('mod/mootimeter:moderator', $context);

        // Get toollib to ensure tool exists.
        page_manager::get_tool_lib($tool);

        $maxsortorder = $DB->get_field_sql('SELECT MAX(sortorder) FROM {mootimeter_pages}');

        // Store page.
        $record = new stdClass();
        $record->tool = $tool;
        $record->instance = $instanceid;
        $record->title = '';
        $record->sortorder = $maxsortorder + 1;
        $record->question = optional_param('question', "", PARAM_RAW);
        return [
            'pageid' => page_manager::store_page($record),
            'sortindex' => $record->sortindex
        ];
    }

    /**
     * Describes the return structure of the service.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new external_single_structure([
            'pageid' => new external_value(PARAM_INT, 'ID of created page'),
            'sortindex' => new external_value(PARAM_INT, 'The sortindex')
        ]);
    }
}
