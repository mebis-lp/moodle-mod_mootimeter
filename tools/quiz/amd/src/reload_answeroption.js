export const init = (uniqueId) => {
    document.getElementById('ao_text_' + uniqueId).addEventListener("input", function () {
        document.getElementById('text_ao_' + uniqueId).innerHTML = document.getElementById('ao_text_' + uniqueId).value;
    });
};