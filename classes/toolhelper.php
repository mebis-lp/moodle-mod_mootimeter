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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use cache_exception;
use dml_exception;

/**
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class toolhelper extends \mod_mootimeter\helper {

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
     * @return bool
     */
    abstract public function delete_page_tool(object $page);

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    abstract public function get_renderer_params(object $page);

    /**
     * Get the settings column.
     *
     * @param object $page
     * @return mixed
     */
    abstract public function get_col_settings_tool(object $page);

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    abstract public function hook_after_new_page_created(object $page);

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
        global $DB;

        // Temporarily get one record to retrieve user and page information.
        $recordtemp = $record;
        if (is_array($record)) {
            $recordtemp = $record[0];
        }

        if(empty($recordtemp->pageid)){
            throw new moodle_exception('pageidmissing', 'error');
        }
        $instance = $this->get_instance_by_pageid($recordtemp->pageid);
        $cm = self::get_cm_by_instance($instance);

        if (!is_enrolled(\context_course($cm->course), $recordtemp->usermodified)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        $answerids = [];

        if ($allowmultipleanswers) {

            if ($updateexisting) {
                $params = ['pageid' => $recordtemp->pageid, 'usermodified' => $recordtemp->usermodified];
                $DB->delete_records($table, $params);
            }

            foreach ($record as $dataobject) {
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

            // Store the answer to db or update it.
            if ($updateexisting) {
                $params = ['pageid' => $dataobject->pageid, 'usermodified' => $dataobject->usermodified];
                $origrecord = $DB->get_record($table, $params);
            }

            if (!empty($origrecord)) {
                $origrecord->optionid = $dataobject->optionid;
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
}
