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
 * The mod_mootimeter helper class for pagelist.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\local;

use dml_exception;
use coding_exception;
use moodle_exception;

/**
 * The mod_mootimeter helper class for pagelist.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pagelist {

    /**
     * Get the pagelist parameters for rendering.
     *
     * @param int $cmid
     * @param object|int $pageselected
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_pagelist_params(int $cmid, object|int $pageselected): array {
        global $USER, $PAGE;

        $helper = new \mod_mootimeter\helper();

        $modulecontext = \context_module::instance($cmid);
        $PAGE->set_context($modulecontext);
        $cm = get_coursemodule_from_id('mootimeter', $cmid, 0, false, MUST_EXIST);

        // Check if the user is enrolled to the course to wich the instance belong to.
        // If not, the user is not allowed to view the page list.
        $context = \context_course::instance($cm->course);
        if (!is_enrolled($context, $USER->id)) {
            throw new \moodle_exception('notenrolledtocourse', 'error');
        }

        $pages = $helper->get_pages($cm->instance);

        $temppages = [];
        $pagenumber = 1;
        $maxtimecreated = 0;

        foreach ($pages as $pagerow) {
            $uniqid = uniqid('mtmt_page_');
            $pixrawurl = '/mod/mootimeter/tools/' . $pagerow->tool . '/pix/' . $pagerow->tool . '.svg';
            $temppages['pageslist'][] = [
                'toolicon' => (new \moodle_url($pixrawurl))->out(true),
                'active' => ($pagerow->id == $pageselected) ? "active" : "",
                'pageid' => $pagerow->id,
                'pagenumber' => $pagenumber,
                'width' => "35px",
                'cmid' => $cm->id,
                'id' => $uniqid,
                'tooltip' => mb_strimwidth($helper::get_tool_config($pagerow, 'question'), 0, 40, '...'),
            ];
            $PAGE->requires->js_call_amd('mod_mootimeter/change_page', 'init', [$uniqid]);

            if ($maxtimecreated < $pagerow->timecreated) {
                $maxtimecreated = $pagerow->timecreated;
            }

            $pagenumber++;
        }
        $temppages['pagelisttime'] = $maxtimecreated;
        \mod_mootimeter\local\mootimeterstate::add_mootimeterstate('pagelisttime', $maxtimecreated);

        return $temppages;
    }

    /**
     * Get the pagelist html output.
     *
     * @param int $cmid
     * @param object|int $pageselected
     * @return string
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_pagelist_html(int $cmid, object|int $pageselected): string {
        global $OUTPUT;

        $pagelistparams = $this->get_pagelist_params($cmid, $pageselected);
        return $OUTPUT->render_from_template("mod_mootimeter/elements/snippet_page_list", $pagelistparams);
    }
}
