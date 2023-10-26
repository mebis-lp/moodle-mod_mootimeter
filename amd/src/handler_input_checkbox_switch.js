import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener('click', store);

    /**
     * Store the value.
     *
     * @returns {mixed}
     */
    function store() {
        const id = this.id;

        const pageid = this.dataset.pageid;
        const ajaxmethode = this.dataset.ajaxmethode;
        const inputname = this.dataset.name;
        let inputvalue = 0;
        const thisDataset = JSON.stringify(this.dataset);

        if (document.getElementById(id).checked) {
            inputvalue = 1;
        }
        return setCbState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    }
};

/**
 * Executes the call to store cb state.
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns {mixed}
 */
const execSetCbState = (
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
 * Store cb state.
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 */
const setCbState = async(ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {
    const response = await execSetCbState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        Log.error(response.string);
    }
};