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

    public function get_pagelist_html(object|int $pageorid) {
        global $OUTPUT, $USER, $PAGE;


        $helper = new \mod_mootimeter\helper();

        if (!is_object($pageorid)) {
            $page = $helper->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        $instance = $helper::get_instance_by_pageid($page->id);
        $cm = $helper::get_cm_by_instance($instance);
        $modulecontext = \context_module::instance($cm->id);
        $PAGE->set_context($modulecontext);

        // Check if the user is enrolled to the course to wich the instance belong to.
        // If not, the user is not allowed to view the page list.
        $context = \context_course::instance($cm->course);
        if (!is_enrolled($context, $USER->id)) {
            throw new \moodle_exception('notenrolledtocourse', 'error');
        }

        $pages = $helper->get_pages($cm->instance);

        $temppages = [];
        $pagenumber = 1;
        foreach ($pages as $pagerow) {
            $uniqid = uniqid('mtmt_page_');
            $temppages['pageslist'][] = [
                'title' => $pagerow->title,
                'pix' => "tools/" . $pagerow->tool . "/pix/" . $pagerow->tool . ".svg",
                'active' => ($pagerow->id == $page->id) ? "active" : "",
                'pageid' => $pagerow->id,
                'sortorder' => $pagerow->sortorder,
                'pagenumber' => $pagenumber,
                'width' => "35px",
                'cmid' => $cm->id,
                'id' => $uniqid,
            ];
            $PAGE->requires->js_call_amd('mod_mootimeter/change_page', 'init', [$uniqid]);

            $pagenumber++;
        }

        return $OUTPUT->render_from_template("mod_mootimeter/elements/snippet_page_list", $temppages);
    }
}
