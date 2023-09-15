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

    /** @type {{[key: string]: Tool.constructor}} */
    tools = {};
    /** @type {{[key: number]: Page}} */
    pages = {};
    /** @type {int} */
    instanceid;
    /** @type {boolean} */
    isEditing;

    elRoot;
    /** @type HTMLElement */
    elPagesCol
    /** @type HTMLElement */;
    elEditCol;
    /** @type HTMLElement */
    elContentCol;

    async init(tools, pages, instanceid, isEditing) {
        for (let tool of tools) {
            this.tools[tool] = await import(`mootimetertool_${tool}/tool`);
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
            page.toolInstance = new this.tools[page.tool](page, this.isEditing);
        }

        history.pushState({}, null, newUrl);

        this.elPagesCol.querySelectorAll('.mootimeter_pages_li.active')
            .forEach(x => x.classList.remove('active'));
        this.elPagesCol.querySelector(`.mootimeter_pages_li[data-pageid="${page.id}"]`)?.classList?.add('active');

        this.elContentCol.replaceChildren('plz wait');

        const documentFragment = results ?
            await page.toolInstance.renderResult() :
            await page.toolInstance.render();

        this.elContentCol.replaceChildren(...documentFragment.children);
    }
}

/** @type */
export let ToolManager;

export const init = async(tools, pages, instanceid, isEditing) => {
    ToolManager = new ToolManagerClass();
    await ToolManager.init(tools, pages, instanceid, isEditing);
};