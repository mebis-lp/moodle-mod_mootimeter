import { call as fetchMany } from 'core/ajax';
import Log from 'core/log';

export const init = (uniqueID) => {
    var obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("click", toggleState);

    /**
     * Store the value.
     */
    function toggleState() {
        var pageid = this.dataset.pageid;
        var uniqueID = this.id;

        exectoggleState(pageid, uniqueID);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {string} statename
 * @returns
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
 * @param {string} uniqueID
 */
const exectoggleState = async (pageid, uniqueID) => {
    var statename = document.getElementById(uniqueID).dataset.togglename;
    const response = await storeInputValue(pageid, statename);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        // var element = document.getElementById(uniqueID);
        // var dataset = element.dataset;
        // var iconid = dataset.iconid;

        // document.getElementById(iconid).classList.remove(dataset.iconenabled);
        // document.getElementById(iconid).classList.remove(dataset.icondisabled);

        // if (response.newstate == 1) {
        //     document.getElementById(iconid).classList.add(dataset.iconenabled);
        //     document.getElementById(uniqueID).setAttribute('data-original-title', dataset.tooltipdisabled);
        // }

        // if (response.newstate != 1) {
        //     document.getElementById(iconid).classList.add(dataset.icondisabled);
        //     document.getElementById(uniqueID).setAttribute('data-original-title', dataset.tooltipenabled);
        // }

        // // To force the webservice to pull all results.
        // let nodelastupdated = document.getElementById('mootimeterstate');
        // nodelastupdated.setAttribute('data-lastupdated', 0);

    }
};