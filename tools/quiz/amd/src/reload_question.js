export const init = () => {
    document.getElementById('mtm_input_question').addEventListener('input', () => {
        document.getElementById('mtm_question').innerHTML = document.getElementById('mtm_input_question').value;
    });
};