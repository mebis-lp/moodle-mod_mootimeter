import Templates from 'core/templates';
import notification from 'core/notification';

export const init = () => {
    document.getElementById('mootimeter_question').addEventListener('keyup', function () {
        window.console.log(this.value);
        window.console.log(document.getElementById("mootimeter_question_div"));
        document.getElementById("mootimeter_question_div").innerHTML = this.value;
    });
};
