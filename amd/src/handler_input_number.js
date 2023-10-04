import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Get all up elements.
    var ups = document.getElementsByClassName('mootimeter-number-input-btn-up');

    for (let i = 0; i < ups.length; i++) {
        // Remove old listener if exists.
        ups[i].removeEventListener("click", count_up);
        // Finally add the new listener.
        ups[i].addEventListener("click", count_up);
    }

    // Get all down elements.
    var downs = document.getElementsByClassName('mootimeter-number-input-btn-down');

    for (let i = 0; i < downs.length; i++) {
        // Remove old listener if exists.
        downs[i].removeEventListener("click", count_down);
        // Finally add the new listener.
        downs[i].addEventListener("click", count_down);
    }

    /**
     * Count num input up.
     */
    function count_up() {
        var id = this.dataset.id;
        document.getElementById(id).value = Math.floor(document.getElementById(id).value) + 1;
        store(this, id);
    }

    /**
     * Count num input down.
     */
    function count_down() {
        var id = this.dataset.id;
        document.getElementById(id).value = Math.floor(document.getElementById(id).value) - 1;
        store(this, id);
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

        Ajax.call([{
            methodname: ajaxmethode,
            args: {pageid: pageid, inputname: inputname, inputvalue: inputvalue},
            fail: notification.exception,
        }]);
    }
};