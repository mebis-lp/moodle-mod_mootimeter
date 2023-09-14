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
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */

namespace mod_mootimeter;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use cache_exception;
use core\plugininfo\base, core_plugin_manager, moodle_url;
use dml_exception;
use stdClass;

/**
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 */
abstract class toolhelper {

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    abstract public function insert_answer(object $page, $answer);

    /**
     * Delete Page
     *
     * @param object $page
     * @param mixed $answer
     * @return bool
     */
    abstract public function delete_page(object $page);

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    abstract public function get_renderer_params(object $page);

    /**
     * Get the settings definitions.
     *
     * @param object $page
     * @return array
     */
    abstract public function get_tool_setting_definitions(object $page);

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    abstract public function hook_after_new_page_created(object $page);

    /**
     * Get renderes setting output.
     *
     * @param mixed $page
     * @return string
     */
    public function get_tool_settings($page): string {
        global $OUTPUT;

        $settings = $this->get_tool_setting_definitions($page);
        return $OUTPUT->render_from_template("mod_mootimeter/settings", $settings);
    }

    /**
     * Get all tool settings parameters.
     *
     * @param object $page
     * @return array
     * @throws coding_exception
     */
    public function get_tool_settings_parameters(object $page): array {

        $settings = $this->get_tool_setting_definitions($page);

        $parameters = [];

        if (empty($settings['settingsarray'])) {
            return $parameters;
        }

        foreach ($settings['settingsarray'] as $setting) {

            foreach ($setting as $key => $value) {
                switch ($key) {
                    case 'text':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_TEXT),
                        ];
                        break;
                    case 'number':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_INT),
                        ];
                        break;
                    case 'select':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_TEXT),
                        ];
                        break;
                    case 'checkbox':
                        $parameters[$setting['name']] = [
                            'tool' => $page->tool,
                            'type' => $key,
                            'pageid' => $page->id,
                            'name' => $setting['name'],
                            'value' => optional_param($setting['name'], "", PARAM_INT),
                        ];
                        break;
                }
            }
        }
        return $parameters;
    }

    /**
     * Get the config of a pages tool.
     *
     * @param object|int $page
     * @param string $name
     * @return string|object
     */
    public function get_tool_config(object|int $pageorid, string $name = ""): string|object {
        global $DB;

        if (is_object($pageorid)) {
            $pageorid = $pageorid->id;
        }

        $conditions = ['pageid' => $pageorid];

        if (!empty($name)) {
            $conditions['name'] = $name;

            return $DB->get_field('mootimeter_tool_settings', 'value', $conditions);
        }

        return (object)$DB->get_records_menu('mootimeter_tool_settings', $conditions, '', 'name, value');
    }

    /**
     * Checks if a select option is selected.
     *
     * @param int $optionid
     * @param object $config
     * @param string $attribute
     * @return bool
     */
    public function is_option_selected(int $optionid, object $config, string $attribute): bool {

        if (empty($config->{$attribute})) {
            return false;
        }

        if ($config->{$attribute} == $optionid) {
            return true;
        }

        return false;
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
     * Store the answer.
     *
     * @param string $table
     * @param object $record
     * @return int
     */
    public function store_answer(string $table, object $record, bool $updateexisting = false, string $answercolumn = 'answer'): int {
        global $DB;

        // In case of anwsers by the guest user change usermodified to something random  so multiple users can anwser (non permanent workaround)
        if ($record->usermodified == 1) {
            $record->usermodified = time() + random_int(1, 10000);

            $answerid = $DB->insert_record($table, $record);
            return $answerid;
        }

        // Store the answer to db or update it.
        if ($updateexisting) {
            $params = ['pageid' => $record->pageid, 'usermodified' => $record->usermodified];
            $origrecord = $DB->get_record($table, $params);
        }

        if (!empty($origrecord)) {
            $origrecord->optionid = $record->optionid;
            $origrecord->timemodified = time();

            $DB->update_record($table, $origrecord);
            $answerid = $origrecord->id;
        }

        if (empty($origrecord)) {
            $answerid = $DB->insert_record($table, $record);
        }

        // Recreate the cache.
        $this->clear_caches($record->pageid);
        $this->get_answers($table, $record->pageid, $answercolumn);
        $this->get_answers_grouped($table, ['pageid' => $record->pageid], $answercolumn);

        return $answerid;
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
     *
     * @param array $params
     * @return array
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
     * Get the lastupdated timestamp.
     *
     * @param int $pageid
     * @return int
     */
    public function get_last_update_time(int $pageid, string $tool): int {
        global $DB;

        $records = $DB->get_records('mtmt_'.$tool.'_answers', ['pageid' => $pageid], 'timecreated DESC', 'timecreated', 0, 1);

        if (empty($records)) {
            return 0;
        }
        $record = array_shift($records);
        return $record->timecreated;
    }
}
