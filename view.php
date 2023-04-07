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
 * Prints an instance of mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

// Page id to show.
$pageid = optional_param('pageid', 0, PARAM_INT);

// Get action parameter.
$action = optional_param('a', "", PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('mootimeter', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $m), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mootimeter', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$helper = new \mod_mootimeter\helper();

if (empty($pageid)) {
    $pages = $helper->get_pages($cm->instance);
    $page = array_shift($pages);
} else {
    $page = $helper->get_page($pageid, $cm->instance,);
}

$modulecontext = context_module::instance($cm->id);

$event = \mod_mootimeter\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));

$PAGE->set_url('/mod/mootimeter/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

if (empty($page)) {
    echo "No Page selected.";
    echo $OUTPUT->footer();
    die;
}

$classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
$toolhelper= new $classname();

if(!empty($action) && $action == 'insertanswer'){
    $answer = optional_param('answer', '', PARAM_TEXT);
    $toolhelper->insert_answer($page, $answer);
}

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mootimeter', $moduleinstance);
$event->trigger();

$params = [
    'containerclasses' => "border rounded",
    'mootimetercolright' => "border-left ",
    'mootimetercard' => 'border rounded',
    'pageid' => $page->id,
    'cmid' => $cm->id,
    'title' => s($page->title),
    'description' => s($page->description),
];
$params = array_merge($params, $toolhelper->get_renderer_params($page));

echo $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_wrapper", $params);

echo $OUTPUT->footer();
