import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (uniqueID) => {
    var up = document.getElementById('up_' + uniqueID);
    var down = document.getElementById('down_' + uniqueID);

    if (up) {
        up.addEventListener("click", countUp);
    }

    if (down) {
        down.addEventListener("click", countDown);
    }

    /**
     * Count num input up.
     */
    function countUp() {
        var id = this.dataset.id;
        if (Math.floor(document.getElementById(id).dataset.min) <= Math.floor(document.getElementById(id).value) + 1) {
            document.getElementById(id).value = Math.floor(document.getElementById(id).value) + 1;
            store(this, id);
        }
    }

    /**
     * Count num input down.
     */
    function countDown() {
        var id = this.dataset.id;
        if (Math.floor(document.getElementById(id).dataset.min) <= Math.floor(document.getElementById(id).value) - 1) {
            document.getElementById(id).value = Math.floor(document.getElementById(id).value) - 1;
            store(this, id);
        }
    }

    /**
     * Store the value.
     * @param {*} obj
     * @param {*} id
     */
    function store(obj, id) {
        var pageid = obj.dataset.pageid;
        var ajaxmethode = obj.dataset.ajaxmethode;
        var inputname = obj.dataset.name;
        var inputvalue = document.getElementById(id).value;
        var thisDataset = JSON.stringify(obj.dataset);
        setINState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
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
const execSetINState = (
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
const setINState = async(ajaxmethode, pageid, inputname, inputvalue, thisDataset) => {
    const response = await execSetINState(ajaxmethode, pageid, inputname, inputvalue, thisDataset);
    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        reloadPage(pageid, cmid, thisDataset);
    }
};