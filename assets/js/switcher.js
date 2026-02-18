/**
 * EDM Switcher (frontend)
 *
 * Responsibilities:
 *  - Toggle `body.edm-dark`
 *  - Persist preference to localStorage key (default: 'edm_dark_mode')
 *  - Respect `prefers-color-scheme` when user hasn't chosen a preference
 *  - Expose a small API: EDM.toggle(), EDM.set(on|off), EDM.get()
 *
 * This file is intentionally small and dependency-free.
 */
( function () {
    'use strict';

    // Localize fallback (set by PHP via wp_localize_script)
    var LS_KEY = ( typeof EDM_SWITCHER !== 'undefined' && EDM_SWITCHER.ls_key ) ? EDM_SWITCHER.ls_key : 'edm_dark_mode';

    // Helpers
    function setBodyDark( isDark ) {
        if ( isDark ) {
            document.body.classList.add( 'edm-dark' );
        } else {
            document.body.classList.remove( 'edm-dark' );
        }
    }

    function readSavedPref() {
        try {
            return localStorage.getItem( LS_KEY );
        } catch ( e ) {
            return null;
        }
    }

    function savePref( val ) {
        try {
            localStorage.setItem( LS_KEY, val );
        } catch ( e ) {
            // ignore
        }
    }

    function systemPrefersDark() {
        return window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
    }

    // Initialize on DOMContentLoaded (or immediately if already loaded)
    function init() {
        var saved = readSavedPref();

        if ( saved === 'on' ) {
            setBodyDark( true );
        } else if ( saved === 'off' ) {
            setBodyDark( false );
        } else {
            // No user preference -> respect system
            setBodyDark( systemPrefersDark() );
        }

        // If user hasn't set a preference, react to system changes
        if ( saved === null && window.matchMedia ) {
            try {
                var mq = window.matchMedia( '(prefers-color-scheme: dark)' );
                // Add listener — when system changes, update class
                if ( typeof mq.addEventListener === 'function' ) {
                    mq.addEventListener( 'change', function ( e ) {
                        setBodyDark( !!e.matches );
                    } );
                } else if ( typeof mq.addListener === 'function' ) {
                    mq.addListener( function ( e ) {
                        setBodyDark( !!e.matches );
                    } );
                }
            } catch ( e ) {
                // ignore
            }
        }
    }

    // Public API
    var EDM = {
        toggle: function () {
            var isDarkNow = document.body.classList.contains( 'edm-dark' );
            var next = ! isDarkNow;
            setBodyDark( next );
            savePref( next ? 'on' : 'off' );
            return next;
        },
        set: function ( onOrOff ) {
            var val = ( onOrOff === true || onOrOff === 'on' ) ? 'on' : 'off';
            setBodyDark( val === 'on' );
            savePref( val );
            return val === 'on';
        },
        get: function () {
            // returns 'on' | 'off' | null
            return readSavedPref();
        },
        reset: function () {
            try {
                localStorage.removeItem( LS_KEY );
            } catch ( e ) {}
            // apply system preference immediately
            setBodyDark( systemPrefersDark() );
        }
    };

    // Expose in window
    if ( typeof window !== 'undefined' ) {
        window.EDM = window.EDM || EDM;
    }

    // Boot
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();
