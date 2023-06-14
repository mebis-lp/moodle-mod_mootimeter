import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    var elements = document.getElementsByClassName("mtmt-delayed-store");
    var pageid = document.getElementById('mtmt_question_section').dataset.pageid;

    Array.from(elements).forEach(function (element) {
        element.addEventListener('keyup', delay(function () {
            var aoid = this.parentElement.dataset.aoid;

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
