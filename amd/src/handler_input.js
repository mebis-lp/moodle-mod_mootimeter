import { call as fetchMany } from 'core/ajax';

export const init = () => {

    // Get all up elements.
    var inputs = document.getElementsByClassName('mootimeter-input mootimeterfullwidth mootimeter_settings_selector');

    for (let i = 0; i < inputs.length; i++) {
        // Remove old listener if exists.
        inputs[i].removeEventListener("keyup", mootimeterStoreInput);
        // Finally add the new listener.
        inputs[i].addEventListener("keyup", mootimeterStoreInput);
    }

    /**
     * Store the value.
     */
    function mootimeterStoreInput() {
        var id = this.id;
        var pageid = this.dataset.pageid;
        var ajaxmethode = this.dataset.ajaxmethode;
        var inputname = this.dataset.name;
        var inputvalue = document.getElementById(id).value;
        execStoreInputValue(ajaxmethode, pageid, inputname, inputvalue);
    }
};

/**
 * Call to store input value
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @returns
 */
const storeInputValue = (
    ajaxmethode,
    pageid,
    inputname,
    inputvalue
) => fetchMany([{
    methodname: ajaxmethode,
    args: {
        pageid,
        inputname,
        inputvalue
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 */
const execStoreInputValue = async (ajaxmethode, pageid, inputname, inputvalue) => {
    const response = await storeInputValue(ajaxmethode, pageid, inputname, inputvalue);
    if (response.code != 200) {
        window.console.log(response.string);
    }
};