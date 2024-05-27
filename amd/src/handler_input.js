import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';

export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("focusin", mootimeterlockstore);

    obj.addEventListener("focusout", mootimeterStoreInput);

    /**
     * Store the value.
     * @returns {mixed}
     */
    function mootimeterStoreInput() {
        const id = obj.id;
        const pageid = obj.dataset.pageid;
        const ajaxmethode = obj.dataset.ajaxmethode;
        const inputname = obj.dataset.name;
        const inputvalue = document.getElementById(id).value;
        const thisDataset = JSON.stringify(obj.dataset);

        var mtmstate = document.getElementById('mootimeterstate');
        delete mtmstate.dataset.lockpagereload;

        return execStoreInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    }

    /**
     * Callback to lock pagereload until focusout event is triggered.
     */
    function mootimeterlockstore() {
        var mtmstate = document.getElementById('mootimeterstate');
        mtmstate.setAttribute('data-lockpagereload', 1);
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
const storeInputValue = (
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
const execStoreInputValue = async(ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {
    let response = null;
    try {
        response = await storeInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    } catch (err) {
        displayException(err);
    }
    if (response.code != 200) {
        Log.error(response.string);
    }
    if (response.code == 200) {
        Log.info(response.string);
    }
};
