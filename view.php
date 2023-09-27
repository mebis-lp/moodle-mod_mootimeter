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

use mod_mootimeter\local\settings\setting;
use mod_mootimeter\page_manager;
use mod_mootimeter\plugininfo\mootimetertool;

require(__DIR__ . '/../../config.php');
global $DB, $OUTPUT, $PAGE;
require_once(__DIR__ . '/lib.php');

// Course module id.
$m = optional_param('m', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);
$page = $id = null;

$urlparams = [];

if ($pageid) {
    $page = $DB->get_record('mootimeter_pages', ['id' => $pageid], '*', MUST_EXIST);
    $m = $page->instance;
    $urlparams['pageid'] = $pageid;
} else {
    $id = optional_param('id', 0, PARAM_INT);
    $urlparams['id'] = $id;
}

if ($id) {
    $cm = get_coursemodule_from_id('mootimeter', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $m), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mootimeter', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

// Check if user is logged in.
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/mootimeter/view.php', $urlparams);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$action = optional_param('a', "", PARAM_ALPHA);
$paramtool = optional_param('tool', "", PARAM_ALPHA);
$results = optional_param('results', false, PARAM_BOOL);
$paramtitle = optional_param('title', "", PARAM_ALPHA);
$paramorder = optional_param('sortorder', "", PARAM_INT);

if ($action) {
    require_capability('mod/mootimeter:moderator', $modulecontext);
    require_sesskey();

    switch ($action) {
        case 'deletepage':
            $page = page_manager::get_page($pageid);
            $success = page_manager::delete_page($page);
            if ($success) {
                redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'a' => 'editpage']));
            } else {
                redirect($PAGE->url, get_string('deleteerror', 'mod_mootimeter'), null, \core\output\notification::NOTIFY_ERROR);
            }
            break;
        case 'storepage':
            // Store page.
            $record = new stdClass();
            $record->id = $pageid;
            $record->tool = $paramtool;
            $record->instance = $cm->instance;
            $record->title = $paramtitle;
            $record->sortorder = $paramorder;
            $record->question = optional_param('question', "", PARAM_RAW);
            $pageid = page_manager::store_page($record);

            // Get all settingparams from tool.
            $page = page_manager::get_page($pageid);
            $parameters = page_manager::get_tool_settings_parameters($page);

            // Store pages tool config.
            page_manager::store_tool_config($page);

            // Reload page.
            redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $pageid]));

    }
}

$pages = page_manager::get_pages($cm->instance);
foreach ($pages as $p) {
    $p->config = page_manager::get_tool_config($p);
}

$event = \mod_mootimeter\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mootimeter', $moduleinstance);
$event->trigger();

$tools = [];
foreach (mootimetertool::get_enabled_plugins() as $tool) {
    $lib = page_manager::get_tool_lib($tool);
    $tools[] = [
        'name' => $tool,
        'label' => get_string('pluginname', "mootimetertool_$tool"),
        'settings' => array_map(function (setting $x) {
            return $x->get_config();
        }, $lib->get_tool_setting_definitions())
    ];
}

$PAGE->requires->js_call_amd('mod_mootimeter/toolmanager', 'init', []);

echo $OUTPUT->header();

// Stupid hack because this *is* the best way to pass the data to JS.
echo html_writer::div('', '', [
    'id' => 'mootimeterroot',
    'data-data' => json_encode([
        'tools' => $tools,
        'pages' => $pages,
        'instanceid' => $moduleinstance->id,
        'isEditing' => $PAGE->user_is_editing()
    ])
]);

$context = [
    'cmid' => $cm->id,
    'pages' => page_manager::get_pages_template($pages, $pageid),
    'results' => $results
];

$context['isediting'] = $PAGE->user_is_editing();

// Hide Pages col if it is not needed at all.
if ($PAGE->user_is_editing() || count($pages) > 1) {
    $context['showpagescol'] = true;
}

echo $OUTPUT->render_from_template("mod_mootimeter/main_screen", $context);

echo $OUTPUT->footer();
