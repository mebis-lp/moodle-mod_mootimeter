import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

export const init = (uniqid) => {

    // Get all up elements.
    var visualizationElements = document.getElementsByClassName('mtmt_visualization_selector');

    for (let i = 0; i < visualizationElements.length; i++) {
        // Remove old listener if exists.
        visualizationElements[i].removeEventListener("click", store);
        // Finally add the new listener.
        visualizationElements[i].addEventListener("click", store);
    }

    /**
     * Create new page.
     */
    function store() {
        var pageid = this.dataset.pageid;
        var visualizationtypeid = this.dataset.visuid;
        storeVisualizationType(pageid, visualizationtypeid, uniqid);
    }
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @param {int} visuid
 * @returns {mixed}
 */
const execStoreVisualizationType = (
    pageid,
    visuid
) => fetchMany([{
    methodname: 'mootimetertool_quiz_store_visualizationtype',
    args: {
        pageid,
        visuid
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {int} pageid
 * @param {int} visuid
 * @param {string} uniqid
 */
const storeVisualizationType = async(pageid, visuid, uniqid) => {
    const response = await execStoreVisualizationType(pageid, visuid);
    if (response.code != 200) {
        Log.error(response.string);
    }
    if (response.code == 200) {

        var visualizationElements = document.getElementsByClassName('mtmt_visualization_selector');
        for (let i = 0; i < visualizationElements.length; i++) {
            visualizationElements[i].classList.remove("active");
        }
        document.getElementById('visualization_' + visuid + '_' + uniqid).classList.add("active");
    }
};