import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Get all up elements.
    var switches = document.getElementsByClassName('mootimeter-checkbox-switch');
    for (let i = 0; i < switches.length; i++) {
        // Remove old listener if exists.
        switches[i].removeEventListener("click", store);
        // Finally add the new listener.
        switches[i].addEventListener("click", store);
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
        var inputvalue = 0;
        var thisDataset = JSON.stringify(this.dataset);

        if (document.getElementById(id).checked) {
            inputvalue = 1;
        }

        Ajax.call([{
            methodname: ajaxmethode,
            args: { pageid: pageid, inputname: inputname, inputvalue: inputvalue, thisDataset },
            fail: notification.exception,
        }]);
    }
}