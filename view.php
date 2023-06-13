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
$action = optional_param('a', "", PARAM_ALPHA);
$paramtool = optional_param('tool', "", PARAM_ALPHA);
$pageid = optional_param('pageid', 0, PARAM_INT);

// Set the pageid pageparam for $PAGE object.
if($pageid){
    $pageparams['pageid'] = $pageid;
}

$paramtitle = optional_param('title', "", PARAM_ALPHA);

// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('mootimeter', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('mootimeter', array('id' => $m), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mootimeter', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

// Set the cm pageparam for $PAGE object.
$pageparams['id'] = $cm->id;

// Check if user is logged in.
require_login($course, true, $cm);

$helper = new \mod_mootimeter\helper();

if (!empty($action) && $action == "storepage") {

    // Store page.
    $record = new stdClass();
    $record->id = $pageid;
    $record->tool = $paramtool;
    $record->instance = $cm->instance;
    $record->title = $paramtitle;
    $record->description = optional_param('description', "", PARAM_TEXT);
    $pageid = $helper->store_page($record);

    // Get all settingparams from tool.
    $page = $helper->get_page($pageid);
    $parameters = $helper->get_tool_settings_parameters($page);

    // Store pages tool config.
    $helper->store_tool_config($page);

    // Reload page.
    redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $pageid]));
}

if (!empty($pageid)) {
    $page = $helper->get_page($pageid);
}

$mt = new \mod_mootimeter\plugininfo\mootimetertool();

$pages = $helper->get_pages($cm->instance);

$modulecontext = context_module::instance($cm->id);

$event = \mod_mootimeter\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mootimeter', $moduleinstance);
$event->trigger();



$PAGE->set_url('/mod/mootimeter/view.php', $pageparams);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$cmid = $PAGE->url->get_param('id');

echo $OUTPUT->header();

$paramspages = $helper->get_pages_template($pages, $pageid);
$params = [
    'containerclasses' => "border rounded",
    'mootimetercolright' => "border-left ",
    'mootimetercard' => 'border rounded',
    'cmid' => $cmid,
    'pages' => $paramspages,
];

if ((!empty($action) && $action == 'editpage') || (!empty($action) && $action == 'addpage') || !empty($pageid)) {
    $enabledtools = $mt->get_enabled_plugins();
    $tools = [];
    foreach ($enabledtools as $key => $tool) {
        $tooltemp = [];
        $tooltemp['pix'] = "tools/" . $tool . "/pix/" . $tool . ".svg";
        $tooltemp['name'] = get_string('pluginname', 'mootimetertool_' . $tool);
        $tooltemp['tool'] = $tool;
        if (!empty($page)) {
            $tooltemp['selected'] = ($tool == $page->tool) ? "selected" : "";
        }
        $tools[] = $tooltemp;
    }


    $editformparams = [
        'cmid' => $cmid,
        'pageid' => $pageid,
        'title' => $paramtitle,
        'tool' => $tool,
        'tools' => $tools,
        'accordionwrapperid' => 'settingswrapper'
    ];

    if (!empty($pageid)) {
        $editformparams['title'] = $page->title;
        $editformparams['description'] = $page->description;
        $editformparams['toolsettings'] = $helper->get_tool_settings($page);
    }

    $params['settings'] = $OUTPUT->render_from_template("mod_mootimeter/form_edit_page", $editformparams);

    if (!empty($page)) {
        $params['pagecontent'] = $helper->get_rendered_page_content($page, $cm, false);
    }

    $params['isediting'] = $PAGE->user_is_editing();
}


//  print_R($params);die;
echo $OUTPUT->render_from_template("mod_mootimeter/edit_screen", $params);

echo $OUTPUT->footer();
