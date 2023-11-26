<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function mootimeter_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_mootimeter into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_mootimeter_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function mootimeter_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('mootimeter', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_mootimeter in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_mootimeter_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function mootimeter_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('mootimeter', $moduleinstance);
}

/**
 * Deletes a mootimeter instance with all related pages and tool settings.
 *
 * @param int $id Id of the mootimeter instance.
 * @return bool true if successful.
 */
function mootimeter_delete_instance($id) {
    global $DB;
    $pages = $DB->get_fieldset_sql('SELECT id FROM {mootimeter_pages} WHERE instance = :id', ['id' => $id]);

    $helper = new \mod_mootimeter\helper();
    foreach ($pages as $page) {
        $helper->delete_page($page);
    }

    return $DB->delete_records('mootimeter', ['id' => $id]);
}

/**
 * Is a given scale used by the instance of mod_mootimeter?
 *
 * This function returns if a scale is being used by one mod_mootimeter
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_mootimeter instance.
 */
function mootimeter_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('mootimeter', ['id' => $moduleinstanceid, 'grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_mootimeter.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_mootimeter instance.
 */
function mootimeter_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('mootimeter', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_mootimeter instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function mootimeter_grade_item_update($moduleinstance, $reset = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [];
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('/mod/mootimeter', $moduleinstance->course, 'mod', 'mod_mootimeter', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_mootimeter instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function mootimeter_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        '/mod/mootimeter',
        $moduleinstance->course,
        'mod',
        'mootimeter',
        $moduleinstance->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Update mod_mootimeter grades in the gradebook.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function mootimeter_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = [];
    grade_update('/mod/mootimeter', $moduleinstance->course, 'mod', 'mod_mootimeter', $moduleinstance->id, 0, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @package     mod_mootimeter
 * @category    files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function mootimeter_get_file_areas($course, $cm, $context) {
    return [];
}

/**
 * File browsing support for mod_mootimeter file areas.
 *
 * @package     mod_mootimeter
 * @category    files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info Instance or null if not found.
 */
function mootimeter_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_mootimeter file areas.
 *
 * @package     mod_mootimeter
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_mootimeter's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function mootimeter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = []) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);
    send_file_not_found();
}

/**
 * Extends the global navigation tree by adding mod_mootimeter nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $mootimeternode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function mootimeter_extend_navigation($mootimeternode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_mootimeter settings.
 *
 * This function is called when the context for the page is a mod_mootimeter module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $mootimeternode {@see navigation_node}
 */
function mootimeter_extend_settings_navigation($settingsnav, $mootimeternode = null) {
}

/**
 * Trigger the course_module viewed event.
 *
 * @param object $moduleinstance
 * @param object $ctx
 * @param object $course
 * @return void
 * @throws coding_exception
 * @throws ddl_exception
 */
function mootimeter_trigger_event_course_module_viewed(object $moduleinstance, object $ctx, object $course): void {
    $event = \mod_mootimeter\event\course_module_viewed::create(
        [
            'objectid' => $moduleinstance->id,
            'context' => $ctx,
        ]
    );
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('mootimeter', $moduleinstance);
    $event->trigger();
}

/**
 * Routing the callbacks for inplace editing to the specific tools.
 *
 * @param mixed $itemtype
 * @param mixed $itemid
 * @param mixed $newvalue
 * @return string|void
 */
function mootimeter_inplace_editable($itemtype, $itemid, $newvalue) {

    list($pageid, $answerid) = explode("_", $itemid);
    $instance = \mod_mootimeter\helper::get_instance_by_pageid($pageid);
    $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);
    $modulecontext = \context_module::instance($cm->id);

    \core_external\external_api::validate_context($modulecontext);

    list($tool, $type) = explode("_", $itemtype);

    $classname = "\mootimetertool_" . $tool . "\\" . $tool;

    if (!class_exists($classname)) {
        return "Class '" . $tool . "' is missing in tool " . $tool;
    }

    $toolhelper = new $classname();

    return $toolhelper->handle_inplace_edit($type, $itemid, $newvalue);
}
