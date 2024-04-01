import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("click", mootimeterStoreData);

    /**
     * Store the value.
     * @returns {mixed}
     */
    function mootimeterStoreData() {
        const mtmstate = document.getElementById('mootimeterstate').dataset;
        const pageid = mtmstate.pageid;
        const ajaxmethode = obj.dataset.ajaxmethode;
        const inputname = obj.dataset.name;
        const inputvalue = obj.dataset.value;
        const thisDataset = JSON.stringify(obj.dataset);
        return execStoreDataValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    }
};

/**
 * Call to store input value
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns {mixed}
 */
const storeDataValue = (
    ajaxmethode,
    pageid,
    inputname,
    inputvalue,
    thisDataset
) => fetchMany([{
    methodname: ajaxmethode,
    args: {
        pageid,
        inputname,
        inputvalue,
        thisDataset
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 */
const execStoreDataValue = async(ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {

    const response = await storeDataValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
        Log.info(response.string);
    }

    let options = [];
    if (response.options) {
        options = JSON.parse(response.options);
    }

    if (response.reload == true || options.reload == true) {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        reloadPage(urlParams.get('pageid'), urlParams.get('id'));
    }
};
