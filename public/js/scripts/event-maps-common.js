/**
 * Poll if Element is visible on page.admin password
 *
 * We wait for 30 * 6000 = 30min
 *
 * @param id
 * @param timeout
 * @returns {Promise<unknown>}
 */
const runIfElementIsVisible = (id, timeout = 30 * 6000) => {
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
                setTimeout(tryQuery, 500); // check again
            }
        }
        tryQuery();
    });
};
