import Templates from 'core/templates';
import notification from 'core/notification';

export const init = () => {
    // Live type the content of question input box to heading.
    document.getElementById('mootimeter_question').addEventListener('keyup', function () {
        document.getElementById("mootimeter_question_div").innerHTML = this.value;
    });

    // Live type the content of question input box to heading.
    document.getElementById('mootimeter_addpage').addEventListener('click', function () {
        var action = this.dataset.action;
        var cmid = this.dataset.cmid;
        location.href = 'view.php?id=' + cmid + '&a=' + action;
    });
};
