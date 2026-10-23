/**
 * Events -> All Events: the event date under the title wherever the "Event date/time" column is not shown: in the
 * compact layout (below 783 px), where WordPress hides every column but the title until a row is expanded, and when
 * the column is unticked in Screen Options.
 *
 * WordPress prints nothing between the title and the excerpt that could carry markup, so each row gets a copy of its
 * "Event date/time" cell here, right after the title; osec-admin-page-all-events.less decides when the copy shows.
 * Quick Edit replaces a row with a fresh one from the server, so rows are filled again whenever the list changes.
 */
( () => {
    const list = document.getElementById('the-list');
    if (!list) {
        return;
    }

    /** Adds the date copy to every row that has a date cell and no copy yet. */
    const fill = () => {
        list.querySelectorAll('tr').forEach((row) => {
            const cell = row.querySelector('td.column-osec_event_date');
            const title = row.querySelector('.column-primary > strong');
            if (!cell || !title || row.querySelector('.osec-row-date')) {
                return;
            }
            const copy = document.createElement('div');
            copy.className = 'osec-row-date';
            cell.childNodes.forEach((node) => copy.append(node.cloneNode(true)));
            title.after(copy);
        });
    };

    fill();
    new MutationObserver(fill).observe(list, { childList: true });
})();
