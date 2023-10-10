import { call as fetchMany } from 'core/ajax';

export const init = (uniqueID) => {
    var obj = document.getElementById(uniqueID);
    obj.addEventListener("click", store);

    /**
     * Store the value.
     */
    function store() {
        var id = this.id;

        var pageid = this.dataset.pageid;
        var ajaxmethode = this.dataset.ajaxmethode;
        var inputname = this.dataset.name;
        var inputvalue = 0;
        var thisDataset = JSON.stringify(this.dataset);

        if (document.getElementById(id).checked) {
            inputvalue = 1;
        }
        window.console.log(thisDataset);
        setCbState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    }
};

/**
 * Executes the call to store cb state.
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns
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
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 */
const setCbState = async (ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {
    const response = await execSetCbState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        window.console.log(response.string);
    }
};