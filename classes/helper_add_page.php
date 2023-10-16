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
 * The mod_mootimeter helper class to add new page.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use dml_exception;

/**
 * The mod_mootimeter helper class to add new page.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_add_page extends \mod_mootimeter\helper {

    /**
     * Get the view_content snippet for new_page.
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_view_content_new_page() {
        global $OUTPUT, $PAGE;

        $mt = new \mod_mootimeter\plugininfo\mootimetertool();
        $enabledtools = $mt->get_enabled_plugins();

        $params = [];

        foreach ($enabledtools as $key => $tool) {
            $params['tools'][] = [
                'id' => $tool,
                'tool' => $tool,
                'name' => get_string('pluginname', 'mootimetertool_' . $tool),
                'description' => get_string('tool_description_short', 'mootimetertool_' . $tool),
                'pix' => "tools/" . $tool . "/pix/" . $tool . ".svg",
                'additional_class' => 'mtmt-tool-selector-list',
                'dataset' => 'data-name="' . $tool . '" data-instance="' . $PAGE->cm->instance . '"',
            ];
        }

        $content = $OUTPUT->render_from_template('mod_mootimeter/add_new_page', $params);

        return $content;
    }
}
