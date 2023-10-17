export const init = (uniqueId) => {
    document.getElementById('text_ao_' + uniqueId).addEventListener('input', function() {
        document.getElementById('text_ao_' + uniqueId).innerHTML = document.getElementById('text_ao_' + uniqueId).value;
    });
};