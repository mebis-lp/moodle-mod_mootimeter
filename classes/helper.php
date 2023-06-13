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
 * The mod_mootimeter helper class.
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
 * The mod_mootimeter helper class.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */
class helper {

    /**
     * Insert or update a page record.
     *
     * @param object $record
     * @return int pageid
     */
    public function store_page(object $record) {
        global $DB;

        if (!empty($record->id)) {

            $origrecord = $DB->get_record('mootimeter_pages', ['id' => $record->id]);
            $origrecord->title = $record->title;
            $origrecord->description = $record->description;
            $origrecord->tool = $record->tool;
            $origrecord->timemodified = time();

            $DB->update_record('mootimeter_pages', $origrecord);
            return $origrecord->id;
        }

        return $DB->insert_record('mootimeter_pages', $record, true);
    }

    /**
     * Get all pages of an instance.
     *
     * @param int $instanceid
     * @param bool $asarray
     * @return mixed
     */
    public function get_pages(int $instanceid) {
        global $DB;
        return $DB->get_records('mootimeter_pages', ['instance' => $instanceid]);
    }

    /**
     * Get page object.
     *
     * @param int $pageid
     * @param int $instanceid
     * @return mixed
     * @throws dml_exception
     */
    public function get_page(int $pageid, int $instanceid = 0) {
        global $DB;
        $params = ['id' => $pageid];
        if (!empty($instanceid)) {
            $params['instance'] = $instanceid;
        }
        return $DB->get_record('mootimeter_pages', $params);
    }

    /**
     * Get pages array for renderer.
     *
     * @param mixed $pages
     * @return array
     */
    public function get_pages_template($pages, $pageid) {
        $temppages = [];
        foreach ($pages as $page) {
            $temppages[] = [
                'title' => $page->title,
                'pix' => "tools/" . $page->tool . "/pix/" . $page->tool . ".svg",
                'active' => ($page->id == $pageid) ? "active" : "",
                'pageid' => $page->id,
            ];
        }
        return $temppages;
    }

    /**
     * Get rendered page content.
     *
     * @param object $page
     * @param object $cm
     * @param bool $withwrapper
     * @return string
     */
    public function get_rendered_page_content(object $page, object $cm, bool $withwrapper = true): string {
        global $OUTPUT, $PAGE;

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_renderer_params')) {
            return "Method 'get_renderer_params' is missing in tool helper class " . $page->tool;
        }

        $params = [
            'containerclasses' => "border rounded",
            'mootimetercolright' => "border-left ",
            'mootimetercard' => 'border rounded',
            'pageid' => $page->id,
            'cmid' => $cm->id,
            'title' => s($page->title),
            'description' => s($page->description),
            'isediting' => $PAGE->user_is_editing(),
        ];
        $params = array_merge($params, $toolhelper->get_renderer_params($page));

        if ($withwrapper) {
            return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_wrapper", $params);
        }
        return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_content", $params);
    }

    /**
     * Get all setting definitions of a page.
     *
     * @param object $page
     * @return string
     * @throws coding_exception
     */
    public function get_tool_settings(object $page): string {

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            throw new \coding_exception("Class '" . $page->tool . "' is missing in tool " . $page->tool);
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_tool_settings')) {
            throw new \coding_exception("Method 'get_tool_settings' is missing in tool helper class " . $page->tool);
        }

        return $toolhelper->get_tool_settings($page);
    }

    /**
     * Get all tool settings parameters.
     *
     * @param object $page
     * @return array
     * @throws coding_exception
     */
    public function get_tool_settings_parameters(object $page): array {

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            throw new \coding_exception("Class '" . $page->tool . "' is missing in tool " . $page->tool);
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_tool_settings_parameters')) {
            throw new \coding_exception("Method 'get_tool_settings_parameters' is missing in tool helper class " . $page->tool);
        }

        return $toolhelper->get_tool_settings_parameters($page);
    }

    /**
     * Store all tool page config settings during a form submit.
     *
     * @param object $page
     * @return void
     * @throws coding_exception
     */
    public function store_tool_config(object $page): void {
        $parameters = $this->get_tool_settings_parameters($page);

        foreach ($parameters as $parameter) {
            $this->set_tool_config($page,  $parameter['name'], $parameter['value']);
        }
    }

    /**
     * Set a single config value.
     *
     * @param object|int $pageorid
     * @param string $name
     * @param string $value
     * @return void
     * @throws dml_exception
     */
    public function set_tool_config(object|int $pageorid, string $name, string $value): void {
        global $DB, $PAGE;

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        $conditions = [
            'tool' => $page->tool,
            'pageid' => $page->id,
            'name' => $name
        ];

        if (empty($record = $DB->get_record('mootimeter_tool_settings', $conditions))) {
            $dataobject = (object)$conditions;
            $dataobject->value = $value;
            $DB->insert_record('mootimeter_tool_settings', $dataobject);
            return;
        }

        $record->value = $value;
        $DB->update_record('mootimeter_tool_settings', $record);
    }

    /**
     * Get the tools config.
     *
     * @param mixed $page
     * @return array
     */
    public function get_tool_config($page) {
        global $DB;

        $conditions = [
            'tool' => $page->tool,
            'pageid' => $page->id,
        ];
        $configs = $DB->get_records('mootimeter_tool_settings', $conditions);

        $returnconfig = [];

        foreach ($configs as $config) {
            $returnconfig[$config['name']] = $config['value'];
        }

        return $returnconfig;
    }
}
