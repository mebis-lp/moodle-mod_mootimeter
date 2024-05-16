import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (refreshintervall) => {

    var obj = document.getElementById('mootimeterstate');
    if (!obj) {
        window.console.log("Early exit");
        return;
    }

    if (refreshintervall < 1000) {
        refreshintervall = 1000;
    }

    initMootimeterstate();

    setInterval(() => {
        getMootimeterstate();
    }, refreshintervall);
};

/**
 * Make the call to get the Mootimeterstate
 * @param {int} pageid
 * @param {int} cmid
 * @param {string} dataset
 * @returns {array}
 */
const getMootimeterstateExecute = (
    pageid,
    cmid,
    dataset
) => fetchMany([{
    methodname: 'mod_mootimeter_get_mootimeterstate',
    args: {
        pageid,
        cmid,
        dataset
    },
}])[0];

const initMootimeterstate = async() => {

    await getMootimeterstate();

    var mtmstate = document.getElementById('mootimeterstate');
    var dataset = mtmstate.dataset;
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const cmid = urlParams.get('id');

    if (urlParams.get('r')) {
        dataset.r = urlParams.get('r');
    }

    if (urlParams.get('o')) {
        dataset.o = urlParams.get('o');
    }

    var pageid = 0;
    if (dataset.pageid) {
        pageid = dataset.pageid;
    } else if (urlParams.get('pageid')) {
        pageid = urlParams.get('pageid');
    }

    await reloadPage(pageid, cmid, dataset);
};

/**
 * Executes the call to store input value.
 */
export const getMootimeterstate = async() => {
    var mtmstate = document.getElementById('mootimeterstate');
    var dataset = mtmstate.dataset;
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const cmid = urlParams.get('id');

    if (urlParams.get('r')) {
        dataset.r = urlParams.get('r');
    }

    if (urlParams.get('o')) {
        dataset.o = urlParams.get('o');
    }

    var pageid = 0;
    if (dataset.pageid) {
        pageid = dataset.pageid;
    } else if (urlParams.get('pageid')) {
        pageid = urlParams.get('pageid');
    }

    const response = await getMootimeterstateExecute(pageid, cmid, JSON.stringify(dataset));

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
        const states = JSON.parse(response.state);

        // Set all datasets to mootimeterstate.
        for (let dataattribute in states) {
            mtmstate.setAttribute('data-' + dataattribute, states[dataattribute]);
        }

        if (mtmstate.dataset.contentchangedat_prev != mtmstate.dataset.contentchangedat) {
            mtmstate.setAttribute('data-settingschangedat_prev', 0);
            mtmstate.setAttribute('data-contentlastupdated', 0);
            reloadPage(pageid, cmid, dataset);
        }
    }

};
