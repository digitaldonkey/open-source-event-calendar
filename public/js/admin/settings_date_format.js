/**
 * Settings -> General, Osec frontend date formats.
 *
 * A preset fills the custom field, focusing the custom field selects "Custom",
 * and the preview follows the custom field (like core's, which binds only to its own field names).
 */
jQuery( function ( $ ) {
    $( '.osec-date-format' ).each( function () {
        const fieldset = $( this ),
            customFormat = fieldset.find( '.osec-date-format-custom' ),
            example = fieldset.find( '.osec-date-format-example' ),
            spinner = fieldset.find( '.osec-date-format-spinner' );

        fieldset.find( 'input[data-osec-format]' ).on( 'change', function () {
            customFormat.val( $( this ).attr( 'data-osec-format' ) ).trigger( 'input' );
        } );

        customFormat.on( {
            focus: function () {
                fieldset.find( '.osec-date-format-custom-radio' ).prop( 'checked', true );
            },
            input: function () {
                // Debounce while typing.
                clearTimeout( customFormat.data( 'timer' ) );
                customFormat.data( 'timer', setTimeout( function () {
                    if ( ! customFormat.val() ) {
                        return;
                    }
                    spinner.addClass( 'is-active' );
                    $.post( ajaxurl, { action: 'date_format', date: customFormat.val() }, function ( d ) {
                        spinner.removeClass( 'is-active' );
                        example.text( d );
                    } );
                }, 500 ) );
            },
        } );
    } );
} );
