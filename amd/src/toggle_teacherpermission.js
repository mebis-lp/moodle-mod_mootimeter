import { call as fetchMany } from 'core/ajax';
import { get_string as getString } from 'core/str';

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
const exectoggleteacherpermission = async (pageid, uniqueID) => {
    const response = await storeInputValue(pageid);
    if (response.code != 200) {
        window.console.log(response.string);
    }
    if (response.code == 200) {
        window.console.log(response.string);
        document.getElementById(uniqueID).classList.remove('fa-eye');
        document.getElementById(uniqueID).classList.remove('fa-eye-slash');

        var element = document.getElementById(uniqueID);

        if (response.newstate == 1) {
            var tooltipTeacherpermDisabled = await getString('tooltip_content_menu_teacherpermission_disabled', 'mod_mootimeter');
            element.classList.add('fa-eye');
            document.getElementById('toggleteacherpermission').setAttribute('data-original-title', tooltipTeacherpermDisabled);
        }

        if (response.newstate != 1) {
            var tooltipTeacherpermission = await getString('tooltip_content_menu_teacherpermission', 'mod_mootimeter');
            element.classList.add('fa-eye-slash');
            document.getElementById('toggleteacherpermission').setAttribute('data-original-title', tooltipTeacherpermission);
        }

        // To force the webservice to pull all results.
        let nodelastupdated = document.getElementById('mootimeterstate');
        nodelastupdated.setAttribute('data-lastupdated', 0);

    }
};