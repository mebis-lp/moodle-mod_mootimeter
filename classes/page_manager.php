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

use context_module;

/**
 * The mod_mootimeter helper class.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */
class page_manager {

    /**
     * Returns the tool lib for the given tool.
     *
     * @param string $tool
     * @return toollib
     */
    public static function get_tool_lib(string $tool): toollib {
        $classname = "\mootimetertool_" . $tool . "\\" . $tool;
        if (!class_exists($classname)) {
            throw new \coding_exception("Class $classname is missing for mootimeter tool $tool.");
        }

        $object = new $classname();
        if (!($object instanceof toollib)) {
            throw new \coding_exception("Class $classname does not extend \\mod_mootimeter\\toollib.");
        }

        return $object;
    }

    /**
     * Insert or update a page record.
     *
     * @param object $record
     * @return int pageid
     */
    public static function store_page(object $record) {
        global $DB;

        if (!empty($record->id)) {

            $origrecord = $DB->get_record('mootimeter_pages', ['id' => $record->id]);
            $origrecord->title = $record->title;
            $origrecord->question = $record->question;
            $origrecord->tool = $record->tool;
            $origrecord->timemodified = time();
            $origrecord->sortorder = $record->sortorder;
            $DB->update_record('mootimeter_pages', $origrecord);
            return $origrecord->id;
        }
        $record->timecreated = time();
        $pageid = $DB->insert_record('mootimeter_pages', $record, true);

        // Hook to do further actions depending on mtmt tool.
        $toolhelper = self::get_tool_lib($record->tool);

        $record->id = $pageid;
        $toolhelper->hook_after_new_page_created($record);

        return $pageid;
    }

    /**
     * Get all pages of an instance.
     *
     * @param int $instanceid
     * @return mixed
     */
    public static function get_pages(int $instanceid) {
        global $DB;
        return $DB->get_records('mootimeter_pages', ['instance' => $instanceid], 'sortorder');
    }

    /**
     * Get page object.
     *
     * @param int $pageid
     * @param int $instanceid
     * @return mixed
     */
    public static function get_page(int $pageid, int $instanceid = 0) {
        global $DB;
        $params = ['id' => $pageid];
        if (!empty($instanceid)) {
            $params['instance'] = $instanceid;
        }
        return $DB->get_record('mootimeter_pages', $params);
    }

    /**
     * Returns the context for a given pageid.
     * @param int $pageid
     * @return context_module
     */
    public static function get_context_for_page(int $pageid): context_module {
        $instanceid = self::get_instanceid_for_pageid($pageid);
        $cmid = get_coursemodule_from_instance('mootimeter', $instanceid)->id;
        return context_module::instance($cmid);
    }

    /**
     * Returns the instance id for a given pageid.
     * @param int $pageid
     * @return int
     */
    public static function get_instanceid_for_pageid(int $pageid): int {
        global $DB;
        return $DB->get_field_sql('SELECT DISTINCT instance FROM {mootimeter_pages} WHERE id = :pageid', ['pageid' => $pageid]);
    }

    /**
     * Get pages array for renderer.
     *
     * @param mixed $pages
     * @return array
     */
    public static function get_pages_template($pages, $pageid) {
        $temppages = [];
        foreach ($pages as $page) {
            $temppages[] = [
                'title' => $page->title,
                'pix' => "tools/" . $page->tool . "/pix/" . $page->tool . ".svg",
                'active' => ($page->id == $pageid) ? "active" : "",
                'pageid' => $page->id,
                'sortorder' => $page->sortorder,
            ];
        }
        return $temppages;
    }

    public static function has_result_page(object $page): bool {
        $toolhelper = self::get_tool_lib($page->tool);
        return $toolhelper->has_result_page();
    }

    /**
     * Get all setting definitions of a page.
     *
     * @param object $page
     * @return string
     */
    public static function get_tool_settings(object $page): string {
        $toolhelper = self::get_tool_lib($page->tool);
        return $toolhelper->get_tool_settings($page);
    }

    /**
     * Get all tool settings parameters.
     *
     * @param object $page
     * @return array
     */
    public static function get_tool_settings_parameters(object $page): array {
        $toolhelper = self::get_tool_lib($page->tool);
        return $toolhelper->get_tool_settings_parameters($page);
    }

    /**
     * Store all tool page config settings during a form submit.
     *
     * @param object $page
     * @return void
     */
    public static function store_tool_config(object $page): void {
        $parameters = self::get_tool_settings_parameters($page);

        foreach ($parameters as $parameter) {
            self::set_tool_config($page, $parameter['name'], $parameter['value']);
        }
    }

    /**
     * Set a single config value.
     *
     * @param object|int $pageorid
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function set_tool_config(object|int $pageorid, string $name, string $value): void {
        global $DB;

        if (!is_object($pageorid)) {
            $page = self::get_page($pageorid);
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
    public static function get_tool_config($page) {
        global $DB;

        $conditions = [
            'tool' => $page->tool,
            'pageid' => $page->id,
        ];
        $configs = $DB->get_records('mootimeter_tool_settings', $conditions);

        $returnconfig = [];

        foreach ($configs as $config) {
            $returnconfig[$config->name] = $config->value;
        }

        return $returnconfig;
    }

    /**
     * Delete the page for the current subplugin.
     * @param $page
     * @return bool
     */
    public static function delete_page($page): bool {
        global $DB;

        $toolhelper = self::get_tool_lib($page->tool);
        $success = $toolhelper->delete_page($page);
        $DB->delete_records('mootimeter_pages', array('id' => $page->id));
        $DB->delete_records('mootimeter_tool_settings', array('pageid' => $page->id));
        return $success;
    }

}
