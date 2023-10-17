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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use dml_exception;

/**
 * The mod_mootimeter helper class.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var int Webservice returning error code - OK */
    const ERRORCODE_OK = 200;
    /** @var int Webservice returning error code - Empty Answer */
    const ERRORCODE_EMPTY_ANSWER = 1000;
    /** @var int Webservice returning error code - Too Many Answers */
    const ERRORCODE_TO_MANY_ANSWERS = 1001;
    /** @var int Webservice returning error code - Duplicate Answers */
    const ERRORCODE_DUPLICATE_ANSWER = 1002;

    /**
     * Insert or update a page record.
     *
     * @param object $record
     * @return int pageid
     */
    public function store_page(object $record): int|string {
        global $DB;

        $instance = $record->instance;
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        if (!empty($record->id)) {
            $origrecord = $DB->get_record('mootimeter_pages', ['id' => $record->id]);
            $origrecord->title = $record->title;
            $origrecord->tool = $record->tool;
            $origrecord->timemodified = time();
            $origrecord->sortorder = $record->sortorder;
            $DB->update_record('mootimeter_pages', $origrecord);
            return $origrecord->id;
        }
        $record->timecreated = time();
        $pageid = $DB->insert_record('mootimeter_pages', $record, true);

        // Hook to do further actions depending on mtmt tool.
        $classname = "\mootimetertool_" . $record->tool . "\\" . $record->tool;
        if (!class_exists($classname)) {
            return "Class '" . $record->tool . "' is missing in tool " . $record->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'get_renderer_params' is missing in tool helper class " . $record->tool;
        }

        $record->id = $pageid;
        $toolhelper->hook_after_new_page_created($record);

        return $pageid;
    }

    /**
     * Store one single page detail.
     *
     * @param object|int $pageorid
     * @param string $column
     * @param string $value
     * @return void
     * @throws dml_exception
     */
    public function store_page_detail(object|int $pageorid, string $column, string $value) {

        if (is_object($pageorid)) {
            $pageid = $pageorid->id;
        } else {
            $pageid = $pageorid;
        }

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $dataobject = $this->get_page($pageid);
        $dataobject->{$column} = $value;

        $this->store_page($dataobject);
    }

    /**
     * Get all pages of an instance.
     *
     * @param int $instanceid
     * @return mixed
     */
    public function get_pages(int $instanceid) {
        global $DB;
        return $DB->get_records('mootimeter_pages', ['instance' => $instanceid], 'id ASC');
    }

    /**
     * Get mootimeter page object.
     *
     * @param int $pageid
     * @return mixed
     * @throws dml_exception
     */
    public function get_page(int $pageid) {
        global $DB;
        $params = ['id' => $pageid];
        return $DB->get_record('mootimeter_pages', $params);
    }

    /**
     * Get instance by mootimeter pageid
     * @param mixed $pageid
     * @return int
     */
    public static function get_instance_by_pageid($pageid): int {
        global $DB;
        return $DB->get_field('mootimeter_pages', 'instance', ['id' => $pageid], IGNORE_MISSING);
    }

    /**
     * Validate that the pageid is pertinent to this mootimeter instance.
     * @param int $pageid the pageid we want to validate
     * @param array $myinstancepages list of the page records of a mootimeter instance
     * @return bool true if the page belongs to the instance
     */
    public static function validate_page_belongs_to_instance($pageid, $myinstancepages): bool {
        return in_array($pageid, array_column($myinstancepages, "id")) === true;
    }

    /**
     * Get the course module object by mootimeter instance.
     * @param int $instance
     * @return object
     */
    public static function get_cm_by_instance(int $instance): object {
        global $DB;

        $module = $DB->get_record('modules', ['name' => 'mootimeter']);

        return $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $instance]);
    }

    /**
     * Get pages array for renderer.
     *
     * @param array $pages
     * @param int $pageid
     * @return array
     */
    public function get_pages_template(array $pages, int $pageid) {
        $temppages = [];
        $pagenumber = 1;
        foreach ($pages as $page) {
            $temppages[] = [
                'title' => $page->title,
                'pix' => "tools/" . $page->tool . "/pix/" . $page->tool . ".svg",
                'active' => ($page->id == $pageid) ? "active" : "",
                'pageid' => $page->id,
                'sortorder' => $page->sortorder,
                'pagenumber' => $pagenumber,
                'width' => "35px",
            ];
            $pagenumber++;
        }
        return $temppages;
    }

    /**
     * Get next page sortorder
     * @param int $instanceid
     * @return int
     * @throws dml_exception
     */
    public function get_page_next_sortorder(int $instanceid): int {
        global $DB;

        $records = $DB->get_records('mootimeter_pages', ['instance' => $instanceid], 'sortorder DESC', '*', 0, 1);

        $lastrecord = array_shift($records);

        $sortorder = 0;
        if (!empty($lastrecord->sortorder)) {
            $sortorder = $lastrecord->sortorder;
        }

        return $sortorder + 1;
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
            'mootimetercard' => 'border rounded',
            'pageid' => $page->id,
            'cmid' => $cm->id,
            'title' => s($page->title),
            'question' => s(self::get_tool_config($page, 'question')),
            'isediting' => $PAGE->user_is_editing(),
        ];
        $params = array_merge($params, $toolhelper->get_renderer_params($page));

        if ($withwrapper) {
            return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_wrapper", $params);
        }
        return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_content2", $params);
    }

    /**
     * Get the rendered page results
     * @param object $page
     * @return string
     */
    public function get_rendered_page_result(object $page): string {

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_result_page')) {
            return "Method 'get_result_page' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_result_page($page);
    }

    /**
     * Calls tool method if exists.
     *
     * @param object $page
     * @return mixed
     */
    public function get_content_menu(object $page) {
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return false;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_content_menu_tool')) {
            return "Method 'get_content_menu_tool' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_content_menu_tool($page);
    }

    /**
     * Get the html snippet of the settings column.
     *
     * @param object $page
     * @return mixed
     */
    public function get_col_settings(object $page) {
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return false;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_col_settings_tool')) {
            return "Method 'get_col_settings_tool' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_col_settings_tool($page);
    }

    /**
     * Get all setting definitions of a page.
     *
     * @param object $page
     * @return string
     * @throws coding_exception
     * @deprecated
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
     * @deprecated
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
     * Set a single config value.
     *
     * @param object|int $pageorid
     * @param string $name
     * @param string $value
     * @return void
     * @throws dml_exception
     */
    public function set_tool_config(object|int $pageorid, string $name, string $value): void {
        global $DB;

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        $conditions = [
            'tool' => $page->tool,
            'pageid' => $page->id,
            'name' => $name,
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
     * @param int|object $pageorid
     * @param string $name
     * @return mixed
     * @throws dml_exception
     */
    public static function get_tool_config($pageorid, $name = "") {
        global $DB;

        if (is_object($pageorid)) {
            $pageorid = $pageorid->id;
        }

        $conditions = ['pageid' => $pageorid];

        if (!empty($name)) {
            $conditions['name'] = $name;
            $field = $DB->get_field('mootimeter_tool_settings', 'value', $conditions);
            if (empty($field)) {
                return "";
            }
            return $field;
        }

        return (object) $DB->get_records_menu('mootimeter_tool_settings', $conditions, '', 'name, value');
    }

    /**
     * Delete the page for the current subplugin.
     * @param int|object $pageorid
     * @return bool
     */
    public function delete_page($pageorid) {
        global $DB;

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            throw new \coding_exception("Class '" . $page->tool . "' is missing in tool " . $page->tool);
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'delete_page_tool')) {
            throw new \coding_exception("Method 'delete_page_tool' is missing in tool helper class " . $page->tool);
        }
        // Call tool specific deletion processes.
        $toolhelper->delete_page_tool($page);

        // Call mootimeter-core deletion processes.
        $DB->delete_records('mootimeter_pages', ['id' => $page->id]);
        $DB->delete_records('mootimeter_tool_settings', ['pageid' => $page->id]);
        return true;
    }

    /**
     * Toggle the show results teacher permission state.
     *
     * @param object $page
     * @return int
     */
    public function toggle_teacherpermission_state(object $page): int {
        return $this->toggle_state($page, 'showonteacherpermission');
    }

    /**
     * Toggle a state.
     *
     * @param object $page
     * @param string $statename
     * @return int
     * @throws dml_exception
     */
    public function toggle_state(object $page, string $statename): int {

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $togglestate = self::get_tool_config($page->id, $statename);

        $helper = new \mod_mootimeter\helper();
        if (empty($togglestate)) {
            // The config is not set yet. Set the value to 1.
            $helper->set_tool_config($page, $statename, 1);
            return 1;
        }

        // The config was already set. Toggle it.
        $helper->set_tool_config($page, $statename, 0);
        return 0;
    }

    /**
     * Get the content block for the case that there are no pages specified.
     * @return string
     */
    public function get_view_content_no_pages(): string {
        global $OUTPUT;

        return $OUTPUT->render_from_template("mod_mootimeter/view_no_pages", []);
    }
}
