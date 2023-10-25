import { call as fetchMany } from 'core/ajax';
import Log from 'core/log';

export const init = () => {
    var obj = document.getElementById('mootimeterstate');

    if (!obj) {
        return;
    }

    setInterval(() => {
        getPagelist();
    }, 5000);

    /**
     * Store the value.
     */
    function getPagelist() {
        var pageid = document.getElementById('mootimeterstate').dataset.pageid;
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPagelist(pageid, cmid);
    }
};

/**
  * Call to store input value
  * @param {int} pageid
  * @param {int} cmid
  * @returns {array}
  */
const ReloadPagelist = (
    pageid,
    cmid
) => fetchMany([{
    methodname: 'mod_mootimeter_get_pages_list',
    args: {
        pageid,
        cmid
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {int} cmid
 */
const execReloadPagelist = async (pageid, cmid) => {
    const response = await ReloadPagelist(pageid, cmid);
    window.console.log(response);
    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
        // document.getElementById('mootimeter-pages-list').innerHTML = response.pagelist;


        // var element = document.getElementById(uniqueID);
        // var dataset = element.dataset;
        // var iconid = dataset.iconid;

        // document.getElementById(iconid).classList.remove(dataset.iconenabled);
        // document.getElementById(iconid).classList.remove(dataset.icondisabled);

        // if (response.newstate == 1) {
        //     document.getElementById(iconid).classList.add(dataset.iconenabled);
        //     document.getElementById(uniqueID).setAttribute('data-original-title', dataset.tooltipdisabled);
        // }

        // if (response.newstate != 1) {
        //     document.getElementById(iconid).classList.add(dataset.icondisabled);
        //     document.getElementById(uniqueID).setAttribute('data-original-title', dataset.tooltipenabled);
        // }

        // // To force the webservice to pull all results.
        // let nodelastupdated = document.getElementById('mootimeterstate');
        // nodelastupdated.setAttribute('data-lastupdated', 0);

    }
};