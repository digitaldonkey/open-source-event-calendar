/**
 * Settings -> General, Osec frontend date formats.
 *
 * A preset fills the custom field, focusing the custom field selects "Custom",
 * and the preview follows the custom field (like core's, which binds only to its own field names).
 */
jQuery( function ( $ ) {
    $( '.osec-date-format' ).each( function () {
        const fieldset = $( this ),
            custom = fieldset.find( '.osec-date-format-custom' ),
            example = fieldset.find( '.example' ),
            spinner = fieldset.find( '.spinner' );

        fieldset.find( 'input[data-osec-format]' ).on( 'change', function () {
            custom.val( $( this ).attr( 'data-osec-format' ) ).trigger( 'input' );
        } );

        custom.on( 'focus', function () {
            fieldset.find( '.osec-date-format-custom-radio' ).prop( 'checked', true );
        } );

        custom.on( 'input', function () {
            // Debounce while typing.
            clearTimeout( custom.data( 'timer' ) );
            custom.data( 'timer', setTimeout( function () {
                if ( ! custom.val() ) {
                    return;
                }
                spinner.addClass( 'is-active' );
                $.post( ajaxurl, { action: 'date_format', date: custom.val() }, function ( d ) {
                    spinner.removeClass( 'is-active' );
                    example.text( d );
                } );
            }, 500 ) );
        } );
    } );
} );
