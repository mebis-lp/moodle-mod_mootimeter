import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Get all up elements.
    var switches = document.getElementsByClassName('mod_mootimeter_store_setting mootimootimeter-input');

    for (let i = 0; i < switches.length; i++) {
        // Remove old listener if exists.
        switches[i].removeEventListener("change", store);
        // Finally add the new listener.
        switches[i].addEventListener("change", store);
    }

    /**
     * Store the value.
     * @param {*} obj
     * @param {*} id
     */
    function store() {
        var id = this.id;
        var pageid = this.dataset.pageid;
        var ajaxmethode = this.dataset.ajaxmethode;
        var inputname = this.dataset.name;
        var inputvalue = document.getElementById(id).value;

        return;

        Ajax.call([{
            methodname: ajaxmethode,
            args: { pageid: pageid, inputname: inputname, inputvalue: inputvalue },
            fail: notification.exception,
        }]);
    }
}