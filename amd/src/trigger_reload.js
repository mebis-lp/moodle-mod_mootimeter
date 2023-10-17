export const init = (id) => {

    // Get all up elements.
    const ao = document.getElementById(id);

    if (!ao) {
        return;
    }

    ao.addEventListener("click", reload);
};

/**
 * Create new page.
 */
const reload = () => {
    // Not really nice but it makes sure that the reload takes place after the ws communication of other events is finished.
    setTimeout(() => {
        document.location.reload(true);
    }, 200);
};