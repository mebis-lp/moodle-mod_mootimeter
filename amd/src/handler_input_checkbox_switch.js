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
     */
    function store() {
        const id = this.id;

        const pageid = this.dataset.pageid;
        const ajaxmethod = this.dataset.ajaxmethod;
        const inputname = this.dataset.name;
        let inputvalue = 0;
        const thisDataset = JSON.stringify(this.dataset);

        if (document.getElementById(id).checked) {
            inputvalue = 1;
        }
        return setCbState(ajaxmethod, pageid, inputname, inputvalue, thisDataset);
    }
};

/**
 * Executes the call to store cb state.
 * @param {string} ajaxmethod
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns
 */
const execSetCbState = (
    ajaxmethod,
    pageid,
    inputname,
    inputvalue,
    thisDataset
) => fetchMany([{
    methodname: ajaxmethod,
    args: {
        pageid,
        inputname,
        inputvalue,
        thisDataset
    },
}])[0];

/**
 * Store cb state.
 * @param {string} ajaxmethod
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 */
const setCbState = async(ajaxmethod, pageid, inputname, inputvalue, thisDataset) => {
    const response = await execSetCbState(ajaxmethod, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        Log.error(response.string);
    }
};