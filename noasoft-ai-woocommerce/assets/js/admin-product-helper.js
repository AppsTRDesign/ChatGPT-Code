(function( $ ) {
    'use strict';

    function getEditorContent( fallback ) {
        if ( window.wp && wp.data && wp.data.select ) {
            var editorStore = wp.data.select( 'core/editor' );
            if ( editorStore && editorStore.getEditedPostContent ) {
                return editorStore.getEditedPostContent() || fallback;
            }
        }

        var classic = document.getElementById( 'content' );
        if ( classic ) {
            return classic.value || fallback;
        }

        return fallback;
    }

    $( function() {
        var settings = window.NoaSoftProductHelper || {};
        var $box = $( '#noasoft-ai-product-helper' );

        if ( ! $box.length ) {
            return;
        }

        var selectors = settings.fields || {};

        function getTitle() {
            var fieldId = selectors.title || '';
            var field = fieldId ? document.getElementById( fieldId ) : null;
            var value = field && field.value ? field.value : '';
            return value || settings.product_title || '';
        }

        function toggleLoading( state ) {
            $box.toggleClass( 'is-loading', state );
            $box.find( '.spinner' ).toggleClass( 'is-active', !! state );
        }

        function applyContent( content ) {
            if ( typeof content.seo_title !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_seo_title' ).val( content.seo_title );
            }
            if ( typeof content.focus_keyword !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_focus_keyword' ).val( content.focus_keyword );
            }
            if ( typeof content.seo_description !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_seo_description' ).val( content.seo_description );
            }
            if ( typeof content.short_description !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_short_description' ).val( content.short_description );
            }
            if ( typeof content.long_description !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_long_description' ).val( content.long_description );
            }
            if ( typeof content.use_cases !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_use_cases' ).val( content.use_cases );
            }
            if ( typeof content.tags !== 'undefined' ) {
                $box.find( '#noasoft_ai_helper_tags' ).val( content.tags );
            }

            if ( content.benefits ) {
                var benefitsText = Array.isArray( content.benefits ) ? content.benefits.join( '\n' ) : content.benefits;
                $box.find( '#noasoft_ai_helper_benefits' ).val( benefitsText );
            }

            if ( content.features ) {
                var featuresText = Array.isArray( content.features ) ? content.features.join( '\n' ) : content.features;
                $box.find( '#noasoft_ai_helper_features' ).val( featuresText );
            }
        }

        function gatherPayload() {
            return {
                seo_title: $box.find( '#noasoft_ai_helper_seo_title' ).val(),
                focus_keyword: $box.find( '#noasoft_ai_helper_focus_keyword' ).val(),
                seo_description: $box.find( '#noasoft_ai_helper_seo_description' ).val(),
                short_description: $box.find( '#noasoft_ai_helper_short_description' ).val(),
                long_description: $box.find( '#noasoft_ai_helper_long_description' ).val(),
                use_cases: $box.find( '#noasoft_ai_helper_use_cases' ).val(),
                benefits: $box.find( '#noasoft_ai_helper_benefits' ).val().split( '\n' ).filter( Boolean ),
                features: $box.find( '#noasoft_ai_helper_features' ).val().split( '\n' ).filter( Boolean ),
                tags: $box.find( '#noasoft_ai_helper_tags' ).val()
            };
        }

        function notify( message, type ) {
            if ( window.NoaSoftToast && window.NoaSoftToast.show ) {
                window.NoaSoftToast.show( message, type );
            }
        }

        $box.on( 'click', '.noasoft-ai-generate', function( event ) {
            event.preventDefault();
            if ( $box.hasClass( 'is-loading' ) ) {
                return;
            }

            toggleLoading( true );

            var payload = {
                action: 'noasoft_ai_product_helper_generate',
                nonce: settings.nonce,
                product_id: settings.product_id || 0,
                title: getTitle(),
                idea: $box.find( '#noasoft_ai_helper_idea' ).val(),
                short_description: $box.find( '#noasoft_ai_helper_short_description' ).val(),
                description: getEditorContent( settings.description || '' ),
                tags: $box.find( '#noasoft_ai_helper_tags' ).val()
            };

            $.post( settings.ajax_url, payload )
                .done( function( response ) {
                    if ( response && response.success && response.data && response.data.content ) {
                        applyContent( response.data.content );
                        notify( settings.messages ? settings.messages.success : 'OK', 'success' );
                    } else {
                        notify( settings.messages ? settings.messages.error : 'Error', 'error' );
                    }
                } )
                .fail( function() {
                    notify( settings.messages ? settings.messages.error : 'Error', 'error' );
                } )
                .always( function() {
                    toggleLoading( false );
                } );
        } );

        $box.on( 'click', '.noasoft-ai-apply', function( event ) {
            event.preventDefault();
            if ( $box.hasClass( 'is-loading' ) ) {
                return;
            }

            toggleLoading( true );
            $.post( settings.ajax_url, {
                action: 'noasoft_ai_product_helper_apply',
                nonce: settings.nonce,
                product_id: settings.product_id || 0,
                payload: gatherPayload()
            } )
                .done( function( response ) {
                    if ( response && response.success ) {
                        notify( settings.messages ? settings.messages.apply_success : 'Saved', 'success' );
                    } else {
                        notify( response && response.data && response.data.message ? response.data.message : ( settings.messages ? settings.messages.apply_error : 'Error' ), 'error' );
                    }
                } )
                .fail( function() {
                    notify( settings.messages ? settings.messages.apply_error : 'Error', 'error' );
                } )
                .always( function() {
                    toggleLoading( false );
                } );
        } );
    } );
})( jQuery );
