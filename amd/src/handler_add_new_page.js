import { call as fetchMany } from 'core/ajax';

export const init = () => {

    // Get all up elements.
    var radios = document.getElementsByClassName('mtmt-tool-selector-list');

    for (let i = 0; i < radios.length; i++) {
        // Remove old listener if exists.
        radios[i].removeEventListener("change", store);
        // Finally add the new listener.
        radios[i].addEventListener("change", store);
    }

    /**
     * Create new page.
     */
    function store() {

        var tool = this.dataset.name;
        var instance = this.dataset.instance;

        storeNewPage(tool, instance);
    }
};

/**
 * Call to create a new instance
 * @param {string} tool
 * @param {int} instance
 * @returns
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
const storeNewPage = async (tool, instance) => {
    const response = await createNewPage(tool, instance);
    window.location.href = window.location.origin
        + window.location.pathname + "?id=" + response.cmid + "&pageid=" + response.pageid;
};