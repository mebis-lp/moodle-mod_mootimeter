import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);
    let typingTimer;
    let doneTypingInterval = 350;

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("keyup", eventHandler);

    /**
     * Eventhandler
     */
    function eventHandler() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(mootimeterStoreInput, doneTypingInterval);
    }

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
        return execStoreInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
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
    const response = await storeInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        Log.error(response.string);
    }
    if (response.code == 200) {
        Log.info(response.string);
    }
};