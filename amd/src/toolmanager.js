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

import notification from 'core/notification';
import * as Util from "mod_mootimeter/util";
import Templates from 'core/templates';
import {get_string as getString, get_strings as getStrings} from "core/str";

/**
 * @typedef {{id: int, tool: string, title: string, question: string,
 * sortindex: int, config: {[key: string]: any}, toolInstance?: Tool}} Page
 */

/**
 * A cool tool Manager.
 *
 * @module     mod_mootimeter/toolmanager
 * @copyright  2023 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ToolManagerClass {

    /** @type {{[key: string]: {constructor: Tool.constructor, label: string, settings: any}}} */
    tools = {};
    /** @type {{[key: number]: Page}} */
    pages = {};
    /** @type {int} */
    instanceid;
    /** @type {boolean} */
    isEditing;
    settings;

    elRoot;
    /** @type HTMLElement */
    elPagesCol
        /** @type HTMLElement */;
    elEditCol;
    /** @type HTMLElement */
    elContentCol;

    strings;

    async prefetch() {
        import('mod_mootimeter/settings/select');
        import('mod_mootimeter/settings/setting');
        import('mod_mootimeter/settings/textarea');
        Templates.prefetchTemplates([
            'mod_mootimeter/settings/select',
            'mod_mootimeter/settings/textarea',
            'mod_mootimeter/settings_column'
        ]);
        getStrings([
            {key: 'pagetype', component: 'mod_mootimeter'},
            {key: 'title', component: 'mod_mootimeter'},
            {key: 'question', component: 'mod_mootimeter'},
            {key: 'pagesettings', component: 'mod_mootimeter'},
            {key: 'toolsettings', component: 'mod_mootimeter'},
        ]);
    }

    async init(tools, pages, instanceid, isEditing) {
        this.prefetch();

        for (let tool of tools) {
            this.tools[tool.name] = {
                constructor: await import(`mootimetertool_${tool.name}/tool`),
                settings: tool.settings,
                label: tool.label
            };
        }
        this.pages = pages;
        this.instanceid = instanceid;
        this.isEditing = isEditing;

        this.elRoot = document.querySelector('.mootimetercontainer');
        this.elPagesCol = this.elRoot.querySelector('.mootimetercolpages');
        this.elEditCol = this.elRoot.querySelector('.mootimetercoledit');
        this.elContentCol = this.elRoot.querySelector('.mootimetercolcontent');

        this.elPagesCol.onclick = (e) => {
            const element = e.target.closest('.mootimeter_pages_li');
            if (element && element.dataset.pageid) {
                this.route(element.dataset.pageid);
            }
        };

        const params = new URLSearchParams(location.search);
        if (params.has('pageid')) {
            await this.route(params.get('pageid'), params.has('results'));
        }
    }

    async route(pageID, results = false) {
        const newUrl = new URL(location);
        newUrl.search = '';
        newUrl.searchParams.set('pageid', pageID);
        if (results) {
            newUrl.searchParams.set('results', '1');
        }

        const page = this.pages[pageID];
        if (!page) {
            location = newUrl;
        }

        if (!page.toolInstance) {
            page.toolInstance = new this.tools[page.tool].constructor(page, this.isEditing);
        }

        history.pushState({}, null, newUrl);

        this.elPagesCol.querySelectorAll('.mootimeter_pages_li.active')
            .forEach(x => x.classList.remove('active'));
        this.elPagesCol.querySelector(`.mootimeter_pages_li[data-pageid="${page.id}"]`)?.classList?.add('active');

        this.elContentCol.replaceChildren('plz wait');

        const promises = [];

        if (results) {
            promises.push(page.toolInstance.renderResult());
        } else {
            promises.push(page.toolInstance.render());
        }

        if (this.isEditing) {
            this.elEditCol.replaceChildren('plz wait');
            promises.push(this.renderSettings(page));
        }

        const [documentFragment, settingsFragment] = await Promise.all(promises);

        this.elContentCol.replaceChildren(...documentFragment.children);

        if (settingsFragment) {
            this.elEditCol.replaceChildren(...settingsFragment.children);
        }
    }

    /**
     *
     * @param {Page} page
     * @returns {Promise<HTMLElement>}
     */
    async renderSettings(page) {
        const settings = this.tools[page.tool].settings;


        const tools = {};
        for (let key in this.tools) {
            tools[key] = this.tools[key].label;
        }

        const firstsettings = [
            {
                module: 'mod_mootimeter/settings/select',
                config: {
                    elementname: 'tool',
                    id: 'id_tool',
                    label: await getString('pagetype', 'mod_mootimeter'),
                    value: page.tool,
                    options: tools
                }
            }, {
                module: 'mod_mootimeter/settings/textarea',
                config: {
                    elementname: 'title',
                    id: 'id_title',
                    label: await getString('title', 'mod_mootimeter'),
                    value: page.title,
                    paramtype: 'text'
                }
            }, {
                module: 'mod_mootimeter/settings/textarea',
                config: {
                    elementname: 'question',
                    id: 'id_question',
                    label: await getString('question', 'mod_mootimeter'),
                    value: page.question,
                    paramtype: 'text'
                }
            }
        ];

        const content = await Util.renderTemplate('mod_mootimeter/settings_column', {
            accordionwrapperid: 'accordionwrapper',
            instancename: page.title,
            pageid: page.id,
            section: [{
                sectionid: 'generalsettings',
                title: await getString('pagesettings', 'mod_mootimeter')
            }, {
                sectionid: 'toolsettings',
                title: await getString('toolsettings', 'mod_mootimeter')
            }]
        });

        window.console.log({
            accordionwrapperid: 'accordionwrapper',
            instancename: page.title,
            pageid: page.id,
            section: [{
                sectionid: 'generalsettings',
                title: await getString('pagesettings', 'mod_mootimeter')
            }, {
                sectionid: 'toolsettings',
                title: await getString('toolsettings', 'mod_mootimeter')
            }]
        });

        content.querySelector('#generalsettings .card-body')
            .append(...(await this.renderSettingsAccordion(firstsettings, page)).children);

        content.querySelector('#toolsettings .card-body')
            .append(...(await this.renderSettingsAccordion(settings, page.config)).children);

        return content;
    }

    async renderSettingsAccordion(settings, data) {
        const content = document.createElement('div');

        const promises = [];
        for (const setting of settings) {
            let name = setting.config.name;
            let value = data[name] ?? setting.config.initialvalue;
            promises.push(
                import(setting.module).then(async constructor => {
                    const controller = new constructor(setting.config, value);
                    const node = await controller.renderSetting();
                    return {controller: controller, node: node};
                }).catch(notification.exception)
            );
        }

        this.settings = [];

        const resolved = await Promise.all(promises);
        for (let i = 0; i < promises.length; i++) {
            const {controller, node} = resolved[i];
            content.append(...node.children);
            this.settings.push({controller, node});
        }

        return content;
    }
}

/** @type ToolManagerClass */
export let ToolManager;

export const init = async(tools, pages, instanceid, isEditing) => {
    ToolManager = new ToolManagerClass();
    await ToolManager.init(tools, pages, instanceid, isEditing);
};