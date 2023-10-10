import { call as fetchMany } from 'core/ajax';

export const init = () => {

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
        storeVisualizationType(pageid, visualizationtypeid);
    }
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @param {int} visuid
 * @returns
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
 */
const storeVisualizationType = async (pageid, visuid) => {
    const response = await execStoreVisualizationType(pageid, visuid);
    if (response.code != 200) {
        window.console.log(response.string);
    }
    if (response.code == 200) {

        var visualizationElements = document.getElementsByClassName('mtmt_visualization_selector');
        for (let i = 0; i < visualizationElements.length; i++) {
            visualizationElements[i].classList.remove("active");
        }
        document.getElementById('visualization_' + visuid).classList.add("active");
    }
};