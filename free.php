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

$pageid = optional_param('pageid', 0, PARAM_INT);

if (!empty($pageid)) {
    $DB->get_record('mootimeter_pages', ['id' => $pageid], '*', MUST_EXIST);
}

$helper = new \mod_mootimeter\helper();

// if(!$helper::get_tool_config($pageid, 'enabled_no_login_mode')){
//     return;
// }

// Set the pageid pageparam for $PAGE object.
if ($pageid) {
    $page = $helper->get_page($pageid);
}

$instance = $helper::get_instance_by_pageid($page->id);
$cm = $helper::get_cm_by_instance($instance);

$modulecontext = \context_module::instance($cm->id);
$PAGE->set_url('/mod/mootimeter/free.php', ['pageid' => $pageid]);
// $PAGE->set_title(format_string($moduleinstance->name));
// $PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// $params['toolname'] = get_string("pluginname", "mootimetertool_" . $page->tool);
// $params['pageid'] = $page->id;
// $params['pagecontent'] = $helper->get_rendered_page_no_login_mode($page);


$params = [
    'containerclasses' => "border rounded",
    'mootimetercard' => 'border rounded',
    'pageid' => $page->id,
    'cmid' => $cm->id,
    'title' => s($page->title),
    'question' => s($helper::get_tool_config($page, 'question')),
    'isediting' => $PAGE->user_is_editing(),
];

$params['input_answer'] = [
    'mtm-input-id' => 'mootimeter_type_answer',
    'mtm-input-name' => 'answer',
    'dataset' => 'data-pageid="' . $page->id . '"',
];

$params['button_answer'] = [
    'mtm-button-id' => 'mootimeter_enter_answer',
    'mtm-button-text' => 'Senden',
];

?>

<html dir="ltr" lang="de" xml:lang="de" id="mbshtml" class="yui3-js-enabled" style="font-size: 18px; --bycs-topbar-height: 134px;" data-lt-installed="true">
<head>
    <link charset="utf-8" rel="stylesheet" href="styles.css">
</head>
<body class="mootimeter-no-login-body">
    <div id=" page-wrapper" class="d-print-block">
        <div class="container-fluid mootimetercontainer isNotNewPage border rounded">
            <div class="row h-100">
                <div class="mootimetercol mootimetercolcontent mootimeter-no-login-mode">
                    <div class="mootimeter-content-wrapper">

                        <?php
                        echo $OUTPUT->render_from_template("mootimetertool_" . $page->tool . "/view_content2", $params);
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>