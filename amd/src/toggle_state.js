import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

/**
 * Initializing this module.
 * @param {*} ids consists of two elements [containerid, iconid]
 */
export const init = (ids) => {

    const containerid = ids[0];

    var obj = document.getElementById(containerid);

    if (!document.getElementById(containerid)) {
        return;
    }

    obj.addEventListener("click", toggleState);

    /**
     * Store the value.
     */
    function toggleState() {
        var pageid = this.dataset.pageid;
        var containerid = this.id;
        var iconid = this.dataset.iconid;
        exectoggleState(pageid, containerid, iconid);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {string} statename
 * @returns {mixed}
 */
const storeInputValue = (
    pageid,
    statename
) => fetchMany([{
    methodname: 'mod_mootimeter_toggle_state',
    args: {
        pageid,
        statename
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {string} containerid
 * @param {string} iconid
 */
const exectoggleState = async(pageid, containerid, iconid) => {
    var statename = document.getElementById(containerid).dataset.togglename;
    const response = await storeInputValue(pageid, statename);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        var element = document.getElementById(containerid);
        var dataset = element.dataset;

        document.getElementById(iconid).classList.remove(dataset.iconenabled);
        document.getElementById(iconid).classList.remove(dataset.icondisabled);

        if (response.newstate == 1) {
            document.getElementById(iconid).classList.add(dataset.iconenabled);
            document.getElementById(containerid).setAttribute('data-original-title', dataset.tooltipdisabled);
        }

        if (response.newstate != 1) {
            document.getElementById(iconid).classList.add(dataset.icondisabled);
            document.getElementById(containerid).setAttribute('data-original-title', dataset.tooltipenabled);
        }

        // To force the webservice to pull all results.
        let nodelastupdated = document.getElementById('mootimeterstate');
        nodelastupdated.setAttribute('data-lastupdated', 0);

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        dataset.useUrlParams = 1;
        reloadPage(dataset.pageid, cmid, dataset);
    }
};