import Ajax from 'core/ajax';
import { exception as displayException } from 'core/notification';
import Templates from 'core/templates';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    var elements = document.getElementsByClassName("mtmt-delayed-store");
    var pageid = document.getElementById('mtmt_question_section').dataset.pageid;

    // var answer = document.getElementById('mootimeter_type_answer').value;

    Array.from(elements).forEach(function (element) {
        element.addEventListener('keyup', delay(function (event) {
            var aoid = this.parentElement.dataset.aoid;
            window.console.log(this.id + " => pid: " + pageid + " => " + this.value + "=>" + aoid + " wird ausgeführt.");

            Ajax.call([{
                methodname: 'mootimetertool_quiz_store_answeroption',
                args: {
                    pageid: pageid,
                    aoid: aoid,
                    value: this.value,
                    id: this.id
                },
                fail: notification.exception,
            }]);

        }, 1000));
    });

    /**
     * Delay a callback for ms.
     * @param callback
     * @param ms {int}
     * @returns {object}
     */
    function delay(callback, ms) {
        var timer = 0;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }
};
