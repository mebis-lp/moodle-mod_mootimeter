import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';
import Log from 'core/log';

export const init = (uniqueID) => {
    var obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("click", toggleteacherpermission);

    /**
     * Store the value.
     */
    function toggleteacherpermission() {
        var pageid = this.dataset.pageid;
        var uniqueID = this.dataset.iconid;

        exectoggleteacherpermission(pageid, uniqueID);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @returns
 */
const storeInputValue = (
    pageid
) => fetchMany([{
    methodname: 'mod_mootimeter_toggle_teacherpermission',
    args: {
        pageid
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {string} uniqueID
 */
const exectoggleteacherpermission = async(pageid, uniqueID) => {
    const response = await storeInputValue(pageid);
    if (response.code != 200) {
        Log.error(response.string);
    }
    if (response.code == 200) {
        Log.info(response.string);
        document.getElementById(uniqueID).classList.remove('fa-eye');
        document.getElementById(uniqueID).classList.remove('fa-eye-slash');

        const element = document.getElementById(uniqueID);

        if (response.newstate == 1) {
            const tooltipTeacherpermDisabled = await getString('tooltip_content_menu_teacherpermission_disabled', 'mod_mootimeter');
            element.classList.add('fa-eye');
            document.getElementById('toggleteacherpermission').setAttribute('data-original-title', tooltipTeacherpermDisabled);
        }

        if (response.newstate != 1) {
            const tooltipTeacherpermission = await getString('tooltip_content_menu_teacherpermission', 'mod_mootimeter');
            element.classList.add('fa-eye-slash');
            document.getElementById('toggleteacherpermission').setAttribute('data-original-title', tooltipTeacherpermission);
        }

        // To force the webservice to pull all results.
        const nodelastupdated = document.getElementById('mootimeterstate');
        nodelastupdated.setAttribute('data-lastupdated', 0);

    }
};