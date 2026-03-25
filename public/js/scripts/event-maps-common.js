/**
 * Poll if Element is visible on page.admin password
 *
 * We wait for 60 * 6000 = 60min
 *
 * @param id
 * @param timeout
 * @returns {Promise<unknown>}
 */
const runIfElementIsVisible = (id, timeout = 60 * 6000) => {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();
        const tryQuery = () => {
            const elem = window.document.getElementById(id);
            // elem not hidden.
            if (elem && elem.offsetParent !== null) {
                resolve(elem); // Found the element
            }
            else if (Date.now() - startTime > timeout) {
                resolve(null); // Timeout expired
            }
            else {
                // check again every 500ms.
                setTimeout(tryQuery, 500);
            }
        }
        tryQuery();
    });
};
