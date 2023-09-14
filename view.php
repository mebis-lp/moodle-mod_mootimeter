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

$helper = new \mod_mootimeter\helper();

if ($action) {
    require_capability('mod/mootimeter:moderator', $modulecontext);
    require_sesskey();

    switch ($action) {
        case 'deletepage':
            $page = $helper->get_page($pageid);
            $success = $helper->delete_page($page);
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
            $pageid = $helper->store_page($record);

            // Get all settingparams from tool.
            $page = $helper->get_page($pageid);
            $parameters = $helper->get_tool_settings_parameters($page);

            // Store pages tool config.
            $helper->store_tool_config($page);

            // Reload page.
            redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $pageid]));

    }
}

$mt = new \mod_mootimeter\plugininfo\mootimetertool();

$pages = $helper->get_pages($cm->instance);

$event = \mod_mootimeter\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mootimeter', $moduleinstance);
$event->trigger();

$PAGE->requires->js_call_amd('mod_mootimeter/toolmanager', 'init', [['quiz', 'wordcloud'], [], 3]);

echo $OUTPUT->header();

$context = [
    'containerclasses' => "border rounded",
    'mootimetercard' => 'border rounded',
    'cmid' => $cm->id,
    'pages' => $helper->get_pages_template($pages, $pageid),
    'results' => $results
];

$tools = [];
foreach ($mt->get_enabled_plugins() as $key => $tool) {
    $tooltemp = [];
    $tooltemp['pix'] = "tools/" . $tool . "/pix/" . $tool . ".svg";
    $tooltemp['name'] = get_string('pluginname', 'mootimetertool_' . $tool);
    $tooltemp['tool'] = $tool;
    if ($page) {
        $tooltemp['selected'] = ($tool == $page->tool) ? "selected" : "";
    }
    $tools[] = $tooltemp;
}
$editformcontext = [
    'cmid' => $cm->id,
    'pageid' => $pageid,
    'title' => $paramtitle,
    'sortorder' => $paramorder,
    'tool' => $tool,
    'tools' => $tools,
    'accordionwrapperid' => 'settingswrapper',
    'sesskey' => sesskey()
];

if (!empty($pageid)) {
    $editformcontext['title'] = $page->title;
    $editformcontext['sortorder'] = $page->sortorder;
    $editformcontext['question'] = $page->question;
    $editformcontext['toolsettings'] = $helper->get_tool_settings($page);
    $editformcontext['instancename'] = $page->title;
}

$context['settings'] = $OUTPUT->render_from_template("mod_mootimeter/form_edit_page", $editformcontext);

if (!empty($page)) {

    $context['has_result'] = $helper->has_result_page($page);
    if (!$results) {
        $context['redirect_string'] = get_string("show_results", "mod_mootimeter");
        $context['redirect_result'] = new moodle_url("view.php", ["m" => $page->instance, "pageid" => $page->id, "results" => true]);
    } else {
        $context['redirect_string'] = get_string("show_options", "mod_mootimeter");
        $context['redirect_result'] = new moodle_url("view.php", ["m" => $page->instance, "pageid" => $page->id, "results" => false]);
        $context['pagecontent'] = $helper->get_rendered_page_result($page);
    }

    if (empty($context['pagecontent'])) {
        $context['pagecontent'] = $helper->get_rendered_page_content($page, $cm, false);
    }
}

$context['isediting'] = $PAGE->user_is_editing();

// Hide Pages col if it is not needed at all.
if ($PAGE->user_is_editing() || count($pages) > 1) {
    $context['showpagescol'] = true;
}

echo $OUTPUT->render_from_template("mod_mootimeter/main_screen", $context);

echo $OUTPUT->footer();
