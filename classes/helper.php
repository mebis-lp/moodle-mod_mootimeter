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
}
