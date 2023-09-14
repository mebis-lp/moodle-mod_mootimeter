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
 * Web service to get all answers.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use mod_mootimeter\page_manager;
use tool_brickfield\manager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * Web service to get all answers.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_show_results_state extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {

        [
            'pageid' => $pageid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
        ]);

        $page = page_manager::get_page($pageid);

        $wordcloud = new \mootimetertool_wordcloud\wordcloud();
        $newstate = $wordcloud->toggle_show_results_state($page);

        switch ($newstate) {
            case 0:
                return ['buttontext' => get_string('show_results', 'mootimetertool_wordcloud')];
                break;
            case 1:
                return ['buttontext' => get_string('hide_results', 'mootimetertool_wordcloud')];
                break;
        }

        return ['buttontext' => "ERROR"];
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'buttontext' => new external_value(PARAM_TEXT, 'Text of teacher permission button')
            ],
            'Information to toggle teacher permission button'
        );
    }
}
