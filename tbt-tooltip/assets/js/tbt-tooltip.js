/**
 * TBT Tooltip — frontend behavior (progressive enhancement only).
 *
 * Desktop hover works via CSS alone; this file adds:
 *  1. Tap/click toggling for touch devices (tablets have no reliable :hover).
 *  2. Only one bubble open at a time; clicking elsewhere closes all.
 *  3. Viewport-edge repositioning via the --tbt-shift custom property.
 *
 * No jQuery, no external libraries. Listeners are delegated from the
 * document so markup injected after load (e.g. the admin live preview)
 * works without re-binding.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var EDGE_PADDING = 8;

        function closeAll(except) {
            var open = document.querySelectorAll('.tbt-tooltip-trigger.is-active');
            for (var i = 0; i < open.length; i++) {
                if (open[i] !== except) {
                    open[i].classList.remove('is-active');
                }
            }
        }

        /**
         * Keep the bubble on-screen: measure it at its default centered
         * position and, if it sticks out past the left/right viewport edge,
         * shift it via --tbt-shift (the arrow compensates in CSS so it
         * stays pointing at the trigger word).
         */
        function reposition(trigger) {
            var bubble = trigger.querySelector('.tbt-tooltip-bubble');
            if (!bubble) {
                return;
            }
            bubble.style.removeProperty('--tbt-shift');
            var rect = bubble.getBoundingClientRect();
            if (!rect.width) {
                return; // Bubble not visible; nothing to measure.
            }
            var shift = 0;
            if (rect.left < EDGE_PADDING) {
                shift = EDGE_PADDING - rect.left;
            } else if (rect.right > window.innerWidth - EDGE_PADDING) {
                shift = window.innerWidth - EDGE_PADDING - rect.right;
            }
            if (shift) {
                bubble.style.setProperty('--tbt-shift', shift.toFixed(1) + 'px');
            }
        }

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest ? event.target.closest('.tbt-tooltip-trigger') : null;
            if (!trigger) {
                closeAll(null);
                return;
            }
            var wasActive = trigger.classList.contains('is-active');
            closeAll(trigger);
            trigger.classList.toggle('is-active', !wasActive);
            if (!wasActive) {
                reposition(trigger);
            }
        });

        // On desktop the bubble opens via :hover (no click), so run the
        // edge check when the pointer enters a trigger too.
        document.addEventListener('mouseover', function (event) {
            var trigger = event.target.closest ? event.target.closest('.tbt-tooltip-trigger') : null;
            if (trigger && !(event.relatedTarget && trigger.contains(event.relatedTarget))) {
                reposition(trigger);
            }
        });
    });
})();
