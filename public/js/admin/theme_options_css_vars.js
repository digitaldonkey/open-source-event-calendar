/**
 * Theme Options, settings that may hold a CSS variable (css_var settings).
 *
 * For each .osec-css-var-color field: resolves the field's value in the browser (the page
 * carries the site's WordPress variables and the calendar's :root block), shows it in the
 * native colour input and as hex, and copies a colour picked there into the field. A reference
 * to another calendar setting is resolved with the value currently in that field, so edits show
 * before saving. A "Use default" link (colour and size fields) writes the theme default back.
 * Loaded by AdminPageThemeOptions::enqueue_css_var_preview().
 */
( function () {
    const fields = document.querySelectorAll( '.osec-css-var-color' );
    if ( ! document.querySelector( '.osec-css-var-value' ) ) {
        return;
    }
    // Hidden element the browser resolves each value on; the native colour input takes only hex.
    const probe = document.createElement( 'span' );
    probe.hidden = true;
    document.body.appendChild( probe );

    /**
     * Converts any computed colour (rgb(), color(srgb …), oklch(), …) to #rrggbb: the browser
     * draws it on a one-pixel canvas and the pixel is read back.
     *
     * @param {string} colour Computed colour.
     * @return {string} Hex colour, or the input if it cannot be drawn.
     */
    const canvas = document.createElement( 'canvas' );
    canvas.width = canvas.height = 1;
    const context = canvas.getContext( '2d', { willReadFrequently: true } );
    function toHex( colour ) {
        context.clearRect( 0, 0, 1, 1 );
        context.fillStyle = '#000';
        context.fillStyle = colour;
        context.fillRect( 0, 0, 1, 1 );
        const [ r, g, b ] = context.getImageData( 0, 0, 1, 1 ).data;
        return '#' + [ r, g, b ].map( ( n ) => n.toString( 16 ).padStart( 2, '0' ) ).join( '' );
    }

    /**
     * Sets the calendar's own --osec-* properties from the current field values, so a
     * reference to another setting resolves to what is being edited.
     */
    function applyFieldValues() {
        fields.forEach( ( field ) => {
            const input = field.querySelector( '.osec-css-var-value' );
            const name = input.getAttribute( 'data-osec-css-var' );
            if ( name ) {
                document.documentElement.style.setProperty( '--' + name, input.value.trim() );
            }
        } );
    }

    /**
     * Updates every colour input and resolved line.
     */
    function refresh() {
        applyFieldValues();
        fields.forEach( ( field ) => {
            const input = field.querySelector( '.osec-css-var-value' );
            const picker = field.querySelector( '.osec-css-var-picker' );
            const resolved = field.closest( '.ai1ec-form-group' ).querySelector( '.osec-css-var-resolved' );
            probe.style.backgroundColor = '';
            probe.style.backgroundColor = input.value.trim();
            const computed = getComputedStyle( probe ).backgroundColor;
            // An invalid value leaves the probe transparent.
            if ( /^rgba\(.*,\s*0\)$/.test( computed ) || 'transparent' === computed ) {
                resolved.textContent = '= (no colour)';
                return;
            }
            const hex = toHex( computed );
            picker.value = /^#[0-9a-f]{6}$/i.test( hex ) ? hex : '#000000';
            resolved.textContent = '= ' + hex;
        } );
    }

    // "Use default" writes the theme default back into the field (colour and size settings).
    document.querySelectorAll( '.osec-css-var-use-default' ).forEach( ( link ) => {
        link.addEventListener( 'click', function ( event ) {
            event.preventDefault();
            const input = this.closest( '.ai1ec-form-group' ).querySelector( '.osec-css-var-value' );
            input.value = this.getAttribute( 'data-osec-default' );
            input.dispatchEvent( new Event( 'input' ) );
        } );
    } );

    fields.forEach( ( field ) => {
        const input = field.querySelector( '.osec-css-var-value' );
        field.querySelector( '.osec-css-var-picker' ).addEventListener( 'input', function () {
            input.value = this.value;
            refresh();
        } );
        input.addEventListener( 'input', refresh );
    } );
    refresh();
}() );
