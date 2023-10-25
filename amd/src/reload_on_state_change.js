/**
 * Listen to a state change. If changed, reload the page.
 */

import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';


export const init = (statename) => {
    var obj = document.getElementById('mootimeterstate');

    if (!obj) {
        window.console.log('mootimeterstate not found');
        return;
    }

    if (!obj.dataset.pageid) {
        window.console.log('Pageid not set in mootimeterstate');
        return;
    }

    if (!statename) {
        window.console.log('Statename not set in methods argument');
        return;
    }
    setInterval(function() {
        getState(obj.dataset.pageid, statename);
    }, 1000);
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @param {string} statename
 * @returns {string}
 */
const execGetState = (
    pageid,
    statename,
) => fetchMany([{
    methodname: 'mod_mootimeter_get_state',
    args: {
        pageid,
        statename,
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {int} pageid
 * @param {string} statename
 */
const getState = async(pageid, statename) => {
    const response = await execGetState(pageid, statename);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        if (!document.getElementById('mootimeterstate').dataset[statename]) {
            document.getElementById('mootimeterstate').setAttribute('data-' + statename, response.state);
        }

        if (document.getElementById('mootimeterstate').dataset[statename] != response.state) {
            location.reload();
        }
    }
};