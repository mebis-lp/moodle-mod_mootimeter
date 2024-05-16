import {call as fetchMany} from 'core/ajax';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }
    obj.addEventListener("mouseup", store);

    /**
     * Create new page.
     * @returns {mixed}
     */
    function store() {
        const tool = this.dataset.name;
        const instance = this.dataset.instance;

        return storeNewPage(tool, instance);
    }
};

/**
 * Call to create a new instance
 * @param {string} tool
 * @param {int} instance
 * @returns {mixed}
 */
const createNewPage = (
    tool,
    instance,
) => fetchMany([{
    methodname: 'mod_mootimeter_add_new_page',
    args: {
        tool,
        instance,
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {string} tool
 * @param {int} instance
 */
const storeNewPage = async(tool, instance) => {
    const response = await createNewPage(tool, instance);
    reloadPage(response.pageid, response.cmid);

    // Scroll to top if a new page is created.
    document.getElementById("mootimeter-main-container").scrollIntoView();
};