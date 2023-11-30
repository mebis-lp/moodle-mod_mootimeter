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
use moodle_exception;
use required_capability_exception;

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
     * Get a tools answer column.
     * @param object|int $pageorid
     * @return string
     */
    public function get_tool_answer_column(object|int $pageorid): string {

        $page = $pageorid;
        if (!is_object($page)) {
            $page = $this->get_page($page);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'get_answer_column' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_answer_column();
    }

    /**
     * Get a tools answer table.
     * @param object|int $pageorid
     * @return string
     */
    public function get_tool_answer_table(object|int $pageorid): string {

        $page = $pageorid;
        if (!is_object($page)) {
            $page = $this->get_page($page);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }
        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
            return "Method 'get_answer_table' is missing in tool helper class " . $page->tool;
        }
        return $toolhelper->get_answer_table();
    }

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
     * Get parameters to render pagecontent.
     *
     * @param object $cm
     * @param object $page
     * @param bool $withwrapper
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_rendered_page_content_params(object $cm, object $page, bool $withwrapper = true): array {
        global $USER;

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return ['pagecontent' => ['error' => "Class '" . $page->tool . "' is missing in tool " . $page->tool]];
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_renderer_params')) {
            return ['pagecontent' => ['error' => "Method 'get_renderer_params' is missing in tool helper class " . $page->tool]];
        }

        $isediting = false;
        if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) && !empty($USER->editing)) {
            $isediting = $USER->editing;
        }

        $params = [
            'pageid' => $page->id,
            'cmid' => $cm->id,
            'question' => s(self::get_tool_config($page, 'question')),
            'isediting' => $isediting,
            'withwrapper' => $withwrapper,
            'template' => "mootimetertool_" . $page->tool . "/view_content2",
            'sp' => ['r' => 0, 'o' => 0],
        ];

        $params['pagecontent'] = array_merge($params, $toolhelper->get_renderer_params($page));

        return $params;
    }

    /**
     * Get rendered page content.
     *
     * @param object $cm
     * @param object $page
     * @param bool $withwrapper
     * @param string $dataset
     * @return string
     */
    public function get_rendered_page_content(object $cm, object $page, bool $withwrapper = true, string $dataset = ""): string {
        global $OUTPUT;

        $params = $this->get_rendered_page_content_params($cm, $page, $withwrapper, $dataset);

        return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_content2", $params['pagecontent']);
    }

    /**
     * Get all params to render the whole page content.
     *
     * @param int $cmid
     * @param int $pageid
     * @param bool $withwrapper
     * @param string $dataset
     * @return array
     * @throws moodle_exception
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_page_content_params(int $cmid, int $pageid, bool $withwrapper = true, string $dataset = ""): array {

        $dataset = json_decode($dataset);
        list($course, $cm) = get_course_and_cm_from_cmid($cmid);
        $page = $this->get_page($pageid);
        $contentmenudefaultparams = [];

        if (empty($pageid)) {
            if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
                $params['pagecontent'] = \mod_mootimeter\helper_add_page::get_view_content_new_page_params($cm);
            } else {
                $contentstring = get_string('please_select_a_page', 'mod_mootimeter');
                if (empty($this->get_pages($cm->instance))) {
                    $contentstring = get_string('no_pages_header', 'mod_mootimeter');
                }
                $params['pagecontent'] = \mod_mootimeter\helper_add_page::get_view_empty_content_params($contentstring);
            }
            // If no page is selected, no more templates (especially the contentmenu) is needed.
            return $params;
        } else if (empty($dataset->action)) {

            // Get params of the content section.
            if (!empty($dataset->r)) {
                $paramscontent['pagecontent'] = $this->get_result_page_params($cm, $page);
                $contentmenudefaultparams = ['sp' => ['r' => 1, 'o' => 0]];
            } else if (!empty($dataset->o)) {
                $paramscontent['pagecontent'] = $this->get_answer_overview_params($cm, $page);
                $contentmenudefaultparams = ['sp' => ['o' => 1, 'r' => 0]];
            } else {
                $paramscontent = $this->get_rendered_page_content_params($cm, $page, $withwrapper);
                $contentmenudefaultparams = ['sp' => ['o' => (int)!empty($dataset->o), 'r' => (int)!empty($dataset->r)]];
            }
        } else if (!empty($dataset->action)) {
            switch ($dataset->action) {
                case 'addpage':
                    $paramscontent['pagecontent'] = \mod_mootimeter\helper_add_page::get_view_content_new_page_params($cm);
                    $nosettingscol = true;
                    break;
                case 'showansweroverview':
                    $paramscontent['pagecontent'] = $this->get_answer_overview_params($cm, $page);
                    $contentmenudefaultparams = ['sp' => ['o' => 1, 'r' => 0]];
                    break;
                case 'showresults':
                    $paramscontent['pagecontent'] = $this->get_result_page_params($cm, $page);
                    $contentmenudefaultparams = ['sp' => ['r' => 1, 'o' => 0]];
                    break;
            }
        } else if (count($this->get_pages($cm->instance)) == 0) {
            $contentstring = get_string('no_pages_header', 'mod_mootimeter');
            $params['pagecontent'] = \mod_mootimeter\helper_add_page::get_view_empty_content_params($contentstring);
        }

        $paramscontent['pageid'] = $page->id;

        // Get params of content menu section.
        $paramscontentmenu = $this->get_content_menu_params($page, $contentmenudefaultparams);

        // Get params of the settings column.
        $paramscolsettings = $this->get_col_settings_params($page);

        // Merge all params and return them.
        return array_merge($paramscontent, $paramscontentmenu, $paramscolsettings);
    }

    /**
     * Get the rendered page results
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_result_page_params(object $cm, object $page): array {

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }

        $toolhelper = new $classname();

        $defaultparams = [
            'sp' => ['r' => 1],
        ];

        return $toolhelper->get_tool_result_page_params($page, $defaultparams);
    }

    /**
     * Get the rendered page results
     * @param object $cm
     * @param object $page
     * @return string
     */
    public function get_result_page(object $cm, object $page): string {
        global $OUTPUT;

        $params = $this->get_result_page_params($cm, $page);
        return $OUTPUT->render_from_template($params['template'], $params);
    }

    /**
     * Get the params for anser overview page.
     *
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_answer_overview_params(object $cm, object $page): array {
        // This content should only be viewed with moderator capabilities.
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            redirect('view.php?id=' . $cm->id . "&pageid=" . $page->id);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return [
                'error' => "Class '" . $page->tool . "' is missing in tool " . $page->tool,
            ];
        }

        $toolhelper = new $classname();

        if (!method_exists($toolhelper, 'get_tool_answer_overview_params')) {
            return [
                'error' => "Method 'get_tool_answer_overview_params' is missing in tool helper class " . $page->tool,
            ];
        }

        return $toolhelper->get_tool_answer_overview_params($cm, $page);
    }

    /**
     * Get the rendered answer overview view.
     *
     * @param object $cm
     * @param object $page
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_rendered_answer_overview(object $cm, object $page): string {

        // This content should only be viewed with moderator capabilities.
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            redirect('view.php?id=' . $cm->id . "&pageid=" . $page->id);
        }

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return "Class '" . $page->tool . "' is missing in tool " . $page->tool;
        }

        $toolhelper = new $classname();

        return $toolhelper->get_answer_overview($cm, $page);
    }

    /**
     * Get the default parameters for content_menu.
     * @param object $page
     * @param array $params
     * @return array
     */
    public function get_content_menu_default_parameters(object $page, array $params = []): array {

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);

        if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {

            // Redirect to Answers Overview View.
            $params['icon-answer-overview'] = [
                'template' => 'mod_mootimeter/elements/snippet_content_menu',
                'icon' => 'fa-table',
                'id' => 'mtmt_show_answer_overview',
                'tooltip' => get_string('show_answer_overview', 'mod_mootimeter') . $params['sp']['o'],
                'dataset' => "data-action='showansweroverview' data-pageid='" . $page->id . "' data-cmid='" . $cm->id . "'",
            ];
            if (!empty($params['sp']['o']) && $params['sp']['o'] == 1) {
                $params['icon-answer-overview']['icon'] = 'fa-pencil-square-o';
                $params['icon-answer-overview']['tooltip'] = get_string('tooltip_show_question_page', 'mod_mootimeter');
                $params['icon-answer-overview']['dataset'] = "data-pageid='" . $page->id . "' data-cmid='" . $cm->id . "'";
            }
        }
        return $params;
    }

    /**
     * Calls tool method if exists.
     *
     * @param object $page
     * @param array $defaultparams
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_content_menu_params(object $page, $defaultparams = []): array {
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return false;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_content_menu_tool_params')) {
            return [
                'contentmenu' => [
                    'error' => "Method 'get_content_menu_tool_params' is missing in tool helper class " . $page->tool,
                ],
            ];
        }

        // This is necessary, to make it possible to inject dynamic default params during script execution.
        $defaultparams = array_merge($defaultparams, $this->get_content_menu_default_parameters($page, $defaultparams));

        $params['contentmenu'] = $toolhelper->get_content_menu_tool_params($page, $defaultparams);
        $params['contentmenu']['template'] = "mod_mootimeter/elements/snippet_content_menu";
        return $params;
    }

    /**
     * Get content menu bar.
     *
     * @param object $page
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_content_menu(object $page): string {
        global $OUTPUT;
        $params = $this->get_content_menu_params($page);
        return $OUTPUT->render_from_template("mod_mootimeter/elements/snippet_content_menu", $params['contentmenu']);
    }

    /**
     * Get the html snippet of the settings column.
     *
     * @param object $page
     * @return mixed
     */
    public function get_col_settings(object $page) {
        global $OUTPUT;

        $params = $this->get_col_settings_params($page);

        if (empty($params['colsettings'])) {
            return "";
        }

        return $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_settings", $params['colsettings']);
    }

    /**
     * Get the html snippet of the settings column.
     *
     * @param object $page
     * @return mixed
     */
    public function get_col_settings_params(object $page): array {

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;

        if (!class_exists($classname)) {
            return false;
        }

        $toolhelper = new $classname();
        if (!method_exists($toolhelper, 'get_col_settings_tool_params')) {
            return "Method 'get_col_settings_tool_params' is missing in tool helper class " . $page->tool;
        }

        $defaultparams = [
            'toolname' => get_string("pluginname", "mootimetertool_" . $page->tool),
            'pageid' => $page->id,
        ];

        return $toolhelper->get_col_settings_tool_params($page, $defaultparams);
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
            $dataobject->timemodified = time();
            $DB->insert_record('mootimeter_tool_settings', $dataobject);
            return;
        }

        $record->value = $value;
        $record->timemodified = time();
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
     * Get the modified timestamp of setting.
     *
     * @param int|object $pageorid
     * @param string $name
     * @return mixed
     * @throws dml_exception
     */
    public static function get_tool_config_timemodified(int|object $pageorid, string $name = "") {
        global $DB;

        if (is_object($pageorid)) {
            $pageorid = $pageorid->id;
        }

        $conditions = ['pageid' => $pageorid];

        if (!empty($name)) {
            $conditions['name'] = $name;
            $field = $DB->get_field('mootimeter_tool_settings', 'timemodified', $conditions);
            if (empty($field)) {
                return "";
            }
            return $field;
        }

        return (object) $DB->get_records_menu('mootimeter_tool_settings', $conditions, '', 'name, timemodified');
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
     * Store the answer.
     *
     * This methode handels all answer storage processes. $updateexisting = true only makes sense with $record is an object.
     * In combination with $allowmultipleanswers = true means that existing answers will be deleted. This is because we do not know
     * how many answers has to be updated. It can be more or less than the original answer. Therefore it is more easy to delete
     * all ansers and store the new ones.
     *
     * @param string $table
     * @param object|array $record
     * @param bool $updateexisting
     * @param string $answercolumn
     * @param bool $allowmultipleanswers
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws cache_exception
     */
    public function store_answer(
        string $table,
        object|array $record,
        bool $updateexisting = false,
        string $answercolumn = 'answer',
        bool $allowmultipleanswers =  false
    ): array {
        global $DB, $USER;

        // Temporarily get one record to retrieve user and page information.
        $recordtemp = $record;
        if (is_array($record)) {
            $recordtemp = $record[0];
        }

        if (empty($recordtemp->pageid)) {
            throw new \moodle_exception('pageidmissing', 'error');
        }

        $instanceid = $this->get_instance_by_pageid($recordtemp->pageid);
        $cm = self::get_cm_by_instance($instanceid);
        $context = \context_course::instance($cm->course);

        if (!is_enrolled($context, $USER->id)) {
            throw new \moodle_exception('notenrolledtocourse', 'error');
        }

        $answerids = [];

        if ($allowmultipleanswers) {

            if ($updateexisting) {
                $params = ['pageid' => $recordtemp->pageid, 'usermodified' => $USER->id];
                $DB->delete_records($table, $params);
            }

            foreach ($record as $dataobject) {

                // Add usermodified to dataobject.
                $dataobject->usermodified = $USER->id;

                $answerids[] = $DB->insert_record($table, $dataobject);
                $pageid = $dataobject->pageid;
            }
        }

        if (!$allowmultipleanswers) {

            // If it's an array with only one record in it. We can update the existing answer.
            if (is_array($record)) {
                $dataobject = array_pop($record);
            } else {
                $dataobject = $record;
            }

            // Add usermodified to dataobject.
            $dataobject->usermodified = $USER->id;

            // Store the answer to db or update it.
            if ($updateexisting) {
                $params = ['pageid' => $dataobject->pageid, 'usermodified' => $dataobject->usermodified];

                // This is necessary, because if one user changes the setting, there could be more than one answer already stored.
                // In this cases we want to delete all previous answers and start from scratch.
                if ($DB->count_records($table, $params) > 1) {
                    $DB->delete_records($table, $params);
                }

                $origrecord = $DB->get_record($table, $params);
            }

            if (!empty($origrecord)) {
                $origrecord->{$answercolumn} = $dataobject->{$answercolumn};
                $origrecord->timemodified = time();

                $DB->update_record($table, $origrecord);
                $answerids[] = $origrecord->id;
            }

            if (empty($origrecord)) {
                $answerids[] = $DB->insert_record($table, $dataobject);
            }
            $pageid = $dataobject->pageid;
        }

        // Recreate the cache.
        $this->clear_caches($pageid);
        $this->get_answers($table, $pageid, $answercolumn);
        $this->get_answers_grouped($table, ['pageid' => $pageid], $answercolumn);

        return $answerids;
    }

    /**
     * Delete all answers of a page.
     * @param string $table
     * @param int $pageid
     * @return bool
     * @throws dml_exception
     */
    public function delete_all_answers(string $table, int $pageid): bool {
        global $DB;

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $params = ['pageid' => $pageid];
        $return = $DB->delete_records($table, $params);
        $this->clear_caches($pageid);
        return $return;
    }

    /**
     * Delete a single answer of a page.
     *
     * @param string $table
     * @param int $pageid
     * @param int $answerid
     * @return bool
     * @throws dml_exception
     * @throws coding_exception
     * @throws required_capability_exception
     */
    public function delete_single_answer(string $table, int $pageid, int $answerid): bool {
        global $DB;

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $params = ['pageid' => $pageid, 'id' => $answerid];
        $return = $DB->delete_records($table, $params);
        $this->clear_caches($pageid);
        return $return;
    }

    /**
     * Get all pgae answers of a specific user. Using get_answers method to use the cache.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_user_answers(string $table, int $pageid, string $answercolumn = 'answer', int $userid = 0): array {
        $pageanswers = $this->get_answers($table, $pageid, $answercolumn);

        $useranswers = [];

        foreach ($pageanswers as $pageanswer) {
            if ($userid > 0 && $pageanswer->usermodified == $userid) {
                $useranswers[$pageanswer->{$answercolumn}] = $pageanswer;
            }
        }

        return $useranswers;
    }

    /**
     * Get all answers of a page.
     *
     * @param string $table
     * @param int $pageid
     * @param string $answercolumn
     * @return array
     * @throws dml_exception
     */
    public function get_answers(string $table, int $pageid, string $answercolumn = 'answer') {
        global $DB;

        $cache = \cache::make('mod_mootimeter', 'answers');
        $cachekey = 'answers_' . $pageid;
        $records = json_decode($cache->get($cachekey));

        if (empty($records)) {
            $records = $DB->get_records($table, ['pageid' => $pageid]);
            $cache->set($cachekey, json_encode($records));
        }

        return $records;
    }

    /**
     * Get all grouped and counted answers of a page.
     * @param string $table
     * @param array $params
     * @param string $answercolumn
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_answers_grouped(string $table, array $params, string $answercolumn = 'answer'): array {
        global $DB;

        $cache = \cache::make('mod_mootimeter', 'answers');
        $cachekey = 'cnt_' . $params['pageid'];
        $records = json_decode($cache->get($cachekey), true);

        if (empty($records)) {
            $sql = "SELECT $answercolumn, count(*) as cnt FROM {" . $table . "} WHERE pageid = :pageid GROUP BY $answercolumn";
            $records = $DB->get_records_sql($sql, $params);
            $cache->set($cachekey, json_encode($records));
        }

        return $records;
    }

    /**
     * Clear all caches.
     *
     * @param int $pageid
     * @return void
     * @throws coding_exception
     * @throws cache_exception
     */
    public function clear_caches(int $pageid): void {
        $cache = \cache::make('mod_mootimeter', 'answers');
        $cache->delete('answers_' . $pageid);
        $cache->delete('cnt_' . $pageid);
    }

    /**
     * Get User by id.
     * @param int $userid
     * @return object
     */
    public function get_user_by_id($userid) {
        global $DB;
        return $DB->get_record('user', ['id' => $userid]);
    }
}
