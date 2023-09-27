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

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {get_string as getString} from "core/str";
import {ToolManager} from "mod_mootimeter/toolmanager";
import Ajax from "core/ajax";
import notification from "core/notification";

/**
 * A cool tool Manager.
 *
 * @module     mod_mootimeter/newpage
 * @copyright  2023 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates an empty Page for the given Tool
 * @param {string} tool
 */
async function createEmptyPage(tool) {
    const {pageid} = await Ajax.call([{
        methodname: 'mod_mootimeter_create_page',
        args: {
            instanceid: ToolManager.instanceid,
            tool: tool
        },
        fail: notification.exception,
    }])[0];
    // TODO Rerender Pageslist.
    window.location.search = '?pageid=' + pageid;
}

/**
 * Handles the modal and the creation of a new page.
 */
export async function handleNewPage() {
    const html = Templates.render('mod_mootimeter/settings/select', {
        name: 'choosepagetype',
        id: 'id_choosepagetype',
        label: '',
        options: Object.entries(ToolManager.tools).map(([key, {label}]) => {return {value: key, label};})
    });
    const modal = await ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: await getString('choosepagetype', 'mod_mootimeter'),
        body: html
    });
    modal.getRoot().on(ModalEvents.save, async (e) => {
        const tool = e.currentTarget.querySelector('#id_choosepagetype').value;
        await createEmptyPage(tool);
    });
    modal.show();
}