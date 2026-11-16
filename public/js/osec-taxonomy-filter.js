/**
 * Calendar taxonomy filter: the category and tag filters as searchable multiselects (Tom Select).
 *
 * The templates (categories.twig / tags.twig of each theme) render one
 * <select multiple class="osec-taxonomy-filter--select"> per taxonomy. The page holds no link per term, so crawlers
 * find one filter URL instead of one per term combination. This script builds the URL when the selection changes:
 *
 *   data-href-template     The view URL with data-href-placeholder standing for this filter's term IDs; everything
 *                          else (view, date, the other filter's selection) is the current state.
 *   data-href-placeholder  The placeholder in data-href-template, e.g. "__TERM_IDS__".
 *   data-href-empty        The view URL without this filter (used when nothing is selected).
 *   data-type              How the calendar loads it ("json" on the calendar page, "jsonp" for shortcodes/blocks).
 *   data-clear-label       Optional: adds a clear-all button with this tooltip (translated; Tom Select's own is
 *                          English only). Plana sets it; vortex clears with the icon on its pill.
 *   data-remove-label      Optional: the selected terms' remove buttons get the calendar's remove icon (the same as
 *                          clear-all) and this name, "%s" replaced by the term ("Remove %s"). Plana sets it.
 *
 *   data-load-on-close     Optional: the list stays open for picking several terms, and the view loads when it
 *                          closes (click outside, Escape, Tab). Plana sets it; vortex loads on each pick (its list
 *                          is always visible inside the pill's frame, there is no "close" to wait for).
 *                          While picks are not applied, they are outlined (osec-taxonomy-filter--pending) and the
 *                          list ends with an apply bar ("Apply (n)", data-apply-label) that closes it; a status
 *                          line (data-not-applied-label) tells screen readers.
 *                          Enter closes the list too, when it would not pick a term: the search is empty and the
 *                          arrow keys were not used since the last pick (type + Enter picks, Enter again applies).
 *                          A change with the list closed (a term's remove button, clear-all, Backspace) loads at once.
 *
 *   data-search-in-list    Optional: the search field is the first line of the list (Tom Select's dropdown_input),
 *                          so the field does not grow an input when it opens. Plana sets it.
 *   data-no-results-label  The list's text when nothing matches the search (translated; Tom Select's is English).
 *   data-search-url        Optional: many terms (above OSEC_TAXONOMY_FILTER_SEARCH_THRESHOLD). The select holds only
 *                          the selected terms; typing searches the server (REST osec/v1/terms, with
 *   data-search-taxonomy   this taxonomy), and the list says data-type-to-search-label until something is typed.
 *   data-updated-label     "Calendar updated." and
 *   data-filtered-label    "Filtered by: %s." (%s = the selected terms): announced to screen readers after a filter
 *                          reloaded the view.
 *   <option value>         The term ID; data-color (category colour, hex) and data-description are optional.
 *
 * Accessibility: the select's <label> (visually hidden, "Filter by category") names Tom Select's combobox and
 * list; the list is marked multi-selectable; clear-all reacts to Enter and Space; after a filter change reloads
 * the view, the same filter gets the focus back (without opening its list).
 *
 * The view is loaded by the calendar bundle (calendar.js, with its own private jQuery): this script dispatches the
 * native event "osec:load-view" (detail: href, type) from the select, and initialises again on "osec:view-initialized",
 * which the bundle dispatches after every view load (the AJAX response replaces the filter markup).
 *
 * Usage: enqueued with Tom Select by ScriptsFrontendController::enqueue_taxonomy_filter(), which CalendarPageView calls
 * when it renders the filters. No options.
 */
(function () {
    'use strict';

    const SELECTOR = 'select.osec-taxonomy-filter--select';

    /**
     * Builds the view URL for a selection.
     *
     * @param {HTMLSelectElement} select The filter's select element.
     * @param {string[]} ids Selected term IDs.
     * @return {string} The URL to load.
     */
    function buildHref(select, ids) {
        const clean = ids.filter((id) => /^\d+$/.test(id));
        if (!clean.length) {
            return select.dataset.hrefEmpty;
        }
        return select.dataset.hrefTemplate.replace(select.dataset.hrefPlaceholder, clean.join(','));
    }

    /**
     * Returns a category colour that is safe to put into a style attribute, or ''.
     *
     * @param {string|undefined} color The option's data-color value.
     * @return {string} A hex colour or ''.
     */
    function safeColor(color) {
        return typeof color === 'string' && /^#[0-9a-f]{3,8}$/i.test(color) ? color : '';
    }

    /**
     * Returns a renderer for options or selected items: the colour swatch (categories) and the escaped name.
     *
     * @param {boolean} alignSwatch In the list of a filter with coloured terms, an uncoloured term gets an empty
     *                              swatch, so all names start at the same place.
     * @return {Function} Tom Select render function (data, escape) => HTML.
     */
    function termRenderer(alignSwatch) {
        return (data, escape) => {
            const color = safeColor(data.color);
            let swatch = '';
            if (color) {
                swatch = '<span class="ai1ec-color-swatch" style="background:' + color + '"></span>';
            } else if (alignSwatch) {
                swatch = '<span class="ai1ec-color-swatch-empty"></span>';
            }
            const title = data.description ? ' title="' + escape(data.description) + '"' : '';
            return '<div' + title + '>' + swatch + '<span class="osec-taxonomy-filter--name">'
                + escape(data.text) + '</span></div>';
        };
    }

    /** The calendar's remove icon (an SVG mask in the text colour), as on the former flyout buttons. */
    const REMOVE_ICON = '<i class="ai1ec-fa ai1ec-fa-remove" aria-hidden="true"></i>';

    /**
     * Builds a remove or clear-all button with the calendar's remove icon.
     *
     * @param {Object} data Tom Select plugin options (className, title, role, tabindex).
     * @return {HTMLElement} The button.
     */
    function iconButton(data) {
        const el = document.createElement('div');
        el.className = data.className;
        el.title = data.title;
        el.setAttribute('role', data.role || 'button');
        el.tabIndex = data.tabindex;
        el.innerHTML = REMOVE_ICON;
        return el;
    }

    /**
     * Tom Select plugins for a filter: remove buttons on the selected terms, and clear-all where the theme asks.
     *
     * @param {HTMLSelectElement} select The filter's select element.
     * @return {Object} Tom Select plugins setting.
     */
    function plugins(select) {
        const list = {remove_button: {}};
        if (select.dataset.removeLabel) {
            list.remove_button = {title: select.dataset.removeLabel, html: iconButton};
        }
        if (select.dataset.searchInList) {
            list.dropdown_input = {};
        }
        if (select.dataset.clearLabel) {
            // Clears the whole filter: one change, so one view load (with data-href-empty).
            list.clear_button = {title: select.dataset.clearLabel, html: (data) => iconButton(data).outerHTML};
        }
        return list;
    }

    /**
     * The filter whose change loaded the view: its CSS class, to give it the focus back once the new markup is
     * initialised (the AJAX response replaces the filters, and the focus would fall back to the page).
     */
    let refocusFilter = null;

    /** The live region announcing a view reloaded by a filter; outside the calendar, which the reload replaces. */
    let liveRegion = null;

    /**
     * Tells screen readers that the view changed and which terms filter it now, from the reloaded filters' labels.
     *
     * @param {ParentNode} root The calendar whose view was reloaded.
     * @return {void}
     */
    function announceView(root) {
        const selects = [...root.querySelectorAll(SELECTOR)];
        if (!selects.length || !selects[0].dataset.updatedLabel) {
            return;
        }
        const names = selects.flatMap((select) => (select.tomselect ? select.tomselect.items : [])
            .map((id) => select.tomselect.options[id] && select.tomselect.options[id].text)
            .filter(Boolean));
        let message = selects[0].dataset.updatedLabel;
        if (names.length && selects[0].dataset.filteredLabel) {
            message += ' ' + selects[0].dataset.filteredLabel.replace('%s', names.join(', '));
        }
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.className = 'osec-screen-reader-text';
            liveRegion.setAttribute('role', 'status');
            document.body.append(liveRegion);
        }
        // Emptied first, so the same message is announced again after the next reload.
        liveRegion.textContent = '';
        setTimeout(() => {
            liveRegion.textContent = message;
        }, 100);
    }

    /**
     * Names each selected term's remove button after its term ("Remove Concerts"); Tom Select's own is "Remove".
     *
     * @param {Object} widget The Tom Select instance.
     * @param {string} pattern The translated name with "%s" for the term.
     * @return {void}
     */
    function nameRemoveButtons(widget, pattern) {
        widget.control.querySelectorAll('.item').forEach((item) => {
            const option = widget.options[item.dataset.value];
            const button = item.querySelector('.remove');
            if (option && button) {
                const name = pattern.replace('%s', option.text);
                button.setAttribute('aria-label', name);
                button.title = name;
            }
        });
    }

    /**
     * Lets Enter and Space activate a div with role="button" (Tom Select's clear-all is one).
     *
     * @param {HTMLElement} button The element.
     * @return {void}
     */
    function keyboardButton(button) {
        button.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                button.click();
            }
        });
    }

    /**
     * Enter closes the list (and so loads the view) when it would not pick a term: the search is empty and the
     * arrow keys were not used since the last pick. Typing and Enter, or arrows and Enter, still pick; a pick
     * from a typed search empties the search, so the next Enter applies.
     *
     * @param {Object} widget The Tom Select instance (load-on-close filters).
     * @return {void}
     */
    function enterCloses(widget) {
        let navigated = false;
        widget.on('item_add', () => {
            navigated = false;
            // A pick from a typed search empties the search (Tom Select keeps it), so the next Enter applies.
            if (widget.control_input.value !== '') {
                widget.setTextboxValue('');
                widget.refreshOptions(false);
            }
        });
        // Capture phase: before Tom Select's own Enter handler on the search field.
        widget.control_input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                navigated = true;
            } else if (event.key === 'Enter' && widget.isOpen && !navigated && widget.control_input.value === '') {
                event.preventDefault();
                event.stopImmediatePropagation();
                widget.close();
            }
        }, true);
    }

    /**
     * Shows what is picked but not applied yet (load-on-close filters): outlines the new terms and adds an apply
     * bar at the end of the list ("Apply (n)", n = terms picked or removed since the view loaded). The bar's
     * button closes the list, which loads the view; a status line tells screen readers.
     *
     * @param {Object} widget The Tom Select instance.
     * @param {HTMLSelectElement} select The filter's select element (labels in data-*).
     * @return {void}
     */
    function pendingState(widget, select) {
        const applied = new Set(widget.getValue());
        const bar = document.createElement('div');
        bar.className = 'osec-taxonomy-filter--apply';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'osec-taxonomy-filter--apply-button';
        const status = document.createElement('span');
        status.className = 'osec-screen-reader-text';
        status.setAttribute('role', 'status');
        bar.append(button, status);
        widget.dropdown.append(bar);
        // Keep the focus in the search field (Tom Select closes the list when it leaves).
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => widget.close());

        const update = () => {
            const now = new Set(widget.getValue());
            let changes = 0;
            now.forEach((id) => {
                if (!applied.has(id)) {
                    changes++;
                }
            });
            applied.forEach((id) => {
                if (!now.has(id)) {
                    changes++;
                }
            });
            widget.control.querySelectorAll('.item').forEach((item) => {
                item.classList.toggle('osec-taxonomy-filter--pending', !applied.has(item.dataset.value));
            });
            bar.classList.toggle('is-shown', changes > 0);
            button.textContent = select.dataset.applyLabel.replace('%d', String(changes));
            status.textContent = changes > 0 ? select.dataset.notAppliedLabel : '';
        };
        widget.on('change', update);
    }

    /**
     * Tom Select settings for the server search of a filter with many terms (data-search-url): typing loads the
     * matching terms (REST osec/v1/terms), already shaped as options (value, text, description, color).
     *
     * @param {HTMLSelectElement} select The filter's select element.
     * @return {Object} Tom Select settings, empty without data-search-url.
     */
    function serverSearch(select) {
        if (!select.dataset.searchUrl) {
            return {};
        }
        return {
            load(query, callback) {
                const url = new URL(select.dataset.searchUrl, window.location.href);
                url.searchParams.set('taxonomy', select.dataset.searchTaxonomy);
                url.searchParams.set('search', query);
                fetch(url, {credentials: 'omit', headers: {Accept: 'application/json'}})
                    .then((response) => (response.ok ? response.json() : []))
                    .then((items) => callback(Array.isArray(items) ? items : []))
                    .catch(() => callback());
            },
        };
    }

    /**
     * Server search: with the search emptied (after a pick, or deleted), the list shows only "type to search", not
     * the results of earlier searches. Tom Select keeps the selected terms.
     *
     * @param {Object} widget The Tom Select instance (data-search-url filters).
     * @return {void}
     */
    function forgetResults(widget) {
        const forget = () => {
            if (widget.control_input.value === '') {
                widget.clearOptions();
                widget.refreshOptions(false);
            }
        };
        // After the pick is done (and enterCloses() emptied a typed search).
        widget.on('item_add', () => setTimeout(forget));
        widget.on('type', (query) => {
            if (query === '') {
                forget();
            }
        });
    }

    /**
     * Returns a Tom Select render function for a one-line message in the list (no results, type to search).
     *
     * @param {string} text The translated message.
     * @return {Function} Tom Select render function (data, escape) => HTML.
     */
    function message(text) {
        return (data, escape) => '<div class="no-results">' + escape(text) + '</div>';
    }

    /**
     * Turns every filter select below root into a Tom Select widget (once per element).
     *
     * @param {ParentNode} root Where to look (document, or a calendar after a view load).
     * @return {void}
     */
    function init(root) {
        if (typeof window.TomSelect !== 'function') {
            return;
        }
        root.querySelectorAll(SELECTOR).forEach((select) => {
            if (select.tomselect) {
                return;
            }
            // Only changes made by the visitor load a view, not Tom Select taking over the preselected terms.
            let ready = false;
            // The selection made while the list is open, loaded when it closes.
            let pending = null;

            /**
             * Loads the view for a selection and remembers this filter for the focus after the reload.
             *
             * @param {string[]} ids Selected term IDs.
             * @return {void}
             */
            const loadView = (ids) => {
                const filter = select.closest('.ai1ec-category-filter, .ai1ec-tag-filter');
                refocusFilter = filter && filter.classList.contains('ai1ec-tag-filter')
                    ? 'ai1ec-tag-filter' : 'ai1ec-category-filter';
                select.dispatchEvent(new CustomEvent('osec:load-view', {
                    bubbles: true,
                    detail: {href: buildHref(select, ids), type: select.dataset.type},
                }));
            };
            const widget = new window.TomSelect(select, {
                plugins: plugins(select),
                maxOptions: null,
                // Plana: pick several terms, the view loads when the list closes (onDropdownClose).
                closeAfterSelect: !select.dataset.loadOnClose,
                // With terms selected, the chips say enough; the label would run into the caret. A search field
                // in the list keeps its label ("Categories") as the hint what it searches.
                hidePlaceholder: !select.dataset.searchInList,
                onInitialize() {
                    this.wrapper.classList.add('osec-taxonomy-filter--widget');
                    // Tom Select fills the term list only when it opens; vortex shows it whenever its frame is open.
                    this.refreshOptions(false);
                },
                onChange(values) {
                    if (!ready) {
                        return;
                    }
                    const ids = Array.isArray(values) ? values : String(values).split(',').filter(Boolean);
                    // Several terms in one go (Plana): while the list is open, the view loads when it closes.
                    if (select.dataset.loadOnClose && this.isOpen) {
                        pending = ids;
                        return;
                    }
                    loadView(ids);
                },
                onDropdownClose() {
                    if (pending) {
                        const ids = pending;
                        pending = null;
                        loadView(ids);
                    }
                },
                ...serverSearch(select),
                render: {
                    // Server search: the selected terms tell nothing about colours; categories align their names.
                    option: termRenderer(Boolean(select.querySelector('option[data-color]'))
                        || select.dataset.searchTaxonomy === 'osec_events_categories'),
                    item: termRenderer(false),
                    // Translated messages; without the labels (a theme's own template), Tom Select's defaults.
                    ...(select.dataset.noResultsLabel ? {no_results: message(select.dataset.noResultsLabel)} : {}),
                    ...(select.dataset.typeToSearchLabel
                        ? {not_loading: message(select.dataset.typeToSearchLabel)} : {}),
                },
            });
            ready = true;
            widget.dropdown_content.setAttribute('aria-multiselectable', 'true');
            if (select.dataset.removeLabel) {
                nameRemoveButtons(widget, select.dataset.removeLabel);
            }
            // Plana: clear-all at the end of the filter, after the caret, as a segment of its own (a line the
            // filter's full height). Tom Select puts it inside the field; moved, it keeps its click handler.
            const clear = widget.control.querySelector('.clear-button');
            if (clear) {
                select.closest('.osec-taxonomy-filter').append(clear);
                keyboardButton(clear);
            }
            if (select.dataset.searchUrl) {
                forgetResults(widget);
            }
            if (select.dataset.loadOnClose) {
                enterCloses(widget);
                if (select.dataset.applyLabel) {
                    pendingState(widget, select);
                }
            }
            // Back from a view load started by this filter: the focus where it was, the list closed. Vortex: on
            // the filter's toggle, as its dropdown is closed after the load.
            if (refocusFilter && select.closest('.' + refocusFilter)) {
                refocusFilter = null;
                const item = select.closest('.ai1ec-dropdown');
                const toggle = item && item.querySelector(':scope > .ai1ec-dropdown-toggle');
                if (toggle) {
                    toggle.focus();
                } else {
                    widget.ignoreFocus = true;
                    widget.focus_node.focus();
                    widget.ignoreFocus = false;
                }
            }
            // Vortex: a click inside the pill's dropdown frame would reach Bootstrap's document handler,
            // which closes the frame (on touch devices it is open by click, not by hover).
            const frame = select.closest('.ai1ec-dropdown-menu');
            if (frame) {
                frame.addEventListener('click', (event) => event.stopPropagation());
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init(document));
    } else {
        init(document);
    }
    document.addEventListener('osec:view-initialized', (event) => {
        // A reload started by a filter (refocusFilter set): announced after the new filters are initialised.
        const byFilter = refocusFilter !== null;
        init(event.target);
        // Not carried over to a later reload (e.g. a view switch) when the new markup has no such filter.
        refocusFilter = null;
        if (byFilter) {
            announceView(event.target);
        } else if (liveRegion) {
            // Other reloads (navigation, view switch) announce nothing; no stale message left behind.
            liveRegion.textContent = '';
        }
    });
    // Plana: a click anywhere in a filter's frame (the icon, the padding) opens its list. Delegated: the frame
    // (.osec-taxonomy-filters--item) outlives the AJAX reloads that replace the widget inside it.
    document.addEventListener('click', (event) => {
        const item = event.target.closest('.osec-taxonomy-filters--item');
        if (!item || event.target.closest('.ts-wrapper, .clear-button')) {
            return;
        }
        const select = item.querySelector(SELECTOR);
        if (select && select.tomselect) {
            select.tomselect.focus();
            select.tomselect.open();
        }
    });
}());
