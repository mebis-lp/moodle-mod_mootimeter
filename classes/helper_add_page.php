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
 */

namespace mod_mootimeter;

use coding_exception;
use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mootimeter helper class to add new page.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */
class helper_add_page extends \mod_mootimeter\helper {

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

        $tools = [];
        foreach ($enabledtools as $key => $tool) {
            $tooltemp = [];
            $tooltemp['pix'] = "tools/" . $tool . "/pix/" . $tool . ".svg";
            $tooltemp['name'] = get_string('pluginname', 'mootimetertool_' . $tool);
            $tooltemp['tool'] = $tool;
            if (!empty($page)) {
                $tooltemp['selected'] = ($tool == $page->tool) ? "selected" : "";
            }
            $tools[] = $tooltemp;
        }

        $editformparams = [
            'cmid' => $cmid,
            'pageid' => $pageid,
            'title' => $paramtitle,
            'sortorder' => $paramorder,
            'tool' => $tool,
            'tools' => $tools,
            'accordionwrapperid' => 'settingswrapper',
        ];

        if (!empty($pageid)) {
            $editformparams['title'] = $page->title;
            $editformparams['sortorder'] = $page->sortorder;
            $editformparams['question'] = $page->question;
            $editformparams['toolsettings'] = $helper->get_tool_settings($page);
            $editformparams['instancename'] = $page->title;
        }

        // $params['settings'] = $OUTPUT->render_from_template("mod_mootimeter/form_edit_page", $editformparams);

        if (!empty($page)) {
            $params['snippet_content_menu'] = $helper->get_content_menu($page);
            $params['settings'] = $helper->get_col_settings($page);

            $page->isNewPage = 'isNotNewPage';
            $page->isNewPage = 'isNewPage';

            $params['has_result'] = $helper->has_result_page($page);
            if ($isresultpage) {
                $params['pagecontent'] = $helper->get_rendered_page_result($page);
            } else {
                $params['pagecontent'] = $helper->get_rendered_page_content($page, $cm, false);
            }
        }

        $params['isediting'] = $PAGE->user_is_editing();
    }
}
