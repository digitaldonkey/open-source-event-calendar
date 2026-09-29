/**
 * Poll if Element is visible on page
 *
 * Waiting for 60 * 60000 = 60min.
 *
 * @param id
 * @param timeout
 * @returns {Promise<unknown>}
 */
const runIfElementIsVisible = (id, timeout = 60 * 60000) => {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();
        const tryQuery = () => {
            const elem = window.document.getElementById(id);
            // elem not hidden.
            if (elem && elem.offsetParent !== null) {
                resolve(elem); // Found the element
            }
            else if (Date.now() - startTime > timeout) {
                resolve(null); // Timeout expired.
            }
            else {
                // check again every 500ms.
                setTimeout(tryQuery, 500);
            }
        }
        tryQuery();
    });
};
