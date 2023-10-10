import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Get all up elements.
    var switches = document.getElementsByClassName('mootimeter-checkbox-switch');
    window.console.log(switches);
    for (let i = 0; i < switches.length; i++) {
        // Remove old listener if exists.
        switches[i].removeEventListener("click", store);
        // Finally add the new listener.
        switches[i].addEventListener("click", store);
        window.console.log(switches[i]);

    }

    /**
     * Store the value.
     * @param {*} obj
     * @param {*} id
     */
    function store() {
        var id = this.id;
        window.console.log(id);

        var pageid = this.dataset.pageid;
        var ajaxmethode = this.dataset.ajaxmethode;
        var inputname = this.dataset.name;
        var inputvalue = 0;

        if (document.getElementById(id).checked) {
            inputvalue = 1;
        }

        Ajax.call([{
            methodname: ajaxmethode,
            args: { pageid: pageid, inputname: inputname, inputvalue: inputvalue },
            fail: notification.exception,
        }]);
    }
}