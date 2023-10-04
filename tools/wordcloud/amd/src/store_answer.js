import Ajax from 'core/ajax';
import { exception as displayException } from 'core/notification';
import Templates from 'core/templates';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    document.getElementById('mootimeter_type_answer').addEventListener("keyup", function (event) {
        if (event.code === 'Enter' || event.code === 'NumpadEnter') {
            storeAnswer();
        }
    });
    document.getElementById('mootimeter_enter_answer').addEventListener("click", function () {
        storeAnswer();
    });

    /**
    * Store the Answer
    */
    function storeAnswer() {
        var pageid = document.getElementById('mootimeter_type_answer').dataset.pageid;
        var answer = document.getElementById('mootimeter_type_answer').value;

        const context = {
            pill: answer,
            additional_class: 'mootimeter-pill-inline'
        };

        // Add the answer to the Badges list.
        Templates.renderForPromise('mod_mootimeter/elements/snippet_pill', context)
            .then(({ html, js }) => {
                Templates.appendNodeContents('#mtmt_wordcloud_pills', html, js);
                return true;
            })
            .catch((error) => displayException(error));

        // Send the answer to server.
        Ajax.call([{
            methodname: 'mootimetertool_wordcloud_store_answer',
            args: { pageid: pageid, answer: answer },
            fail: notification.exception,
        }]);

        // Empty the input field after post.
        document.getElementById('mootimeter_type_answer').value = "";

        return;
    }
};
