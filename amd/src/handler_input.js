import { call as fetchMany } from 'core/ajax';

export const init = (uniqueID) => {
    var obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("keyup", mootimeterStoreInput);

    /**
     * Store the value.
     */
    function mootimeterStoreInput() {
        var id = this.id;
        var pageid = this.dataset.pageid;
        var ajaxmethode = this.dataset.ajaxmethode;
        var inputname = this.dataset.name;
        var inputvalue = document.getElementById(id).value;
        var thisDataset = JSON.stringify(this.dataset);
        execStoreInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    }
};

/**
 * Call to store input value
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns
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
const execStoreInputValue = async (ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {
    const response = await storeInputValue(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        window.console.log(response.string);
    }
    if (response.code == 200) {
        window.console.log(response.string);
    }
};