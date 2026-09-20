<?php
/**
 * Inline SVG icon set (Lucide-style, 24×24, stroke-based).
 * Required once from each layout. Use as:  <?= icon('mail') ?>
 *
 * Icons inherit currentColor, so colour them with CSS on the parent.
 */
if (!function_exists('icon')) {

    function icon(string $name, string $class = 'icon'): string
    {
        static $paths = [
            'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
            'lock'         => '<rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'eye-off'      => '<path d="M10.7 5.1A9.9 9.9 0 0 1 12 5c5 0 9 4.5 10 7a15 15 0 0 1-2.4 3.4M6.6 6.6C4 8.2 2.4 10.7 2 12c1 2.5 5 7 10 7a9.6 9.6 0 0 0 4.4-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="m2 2 20 20"/>',
            'user'         => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users'        => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16.5 5.2a3.5 3.5 0 0 1 0 6.6"/><path d="M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
            'at'           => '<circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/>',
            'phone'        => '<path d="M6.6 2.5h-3A1.5 1.5 0 0 0 2 4.2 17.5 17.5 0 0 0 19.8 22a1.5 1.5 0 0 0 1.7-1.5v-3a1.5 1.5 0 0 0-1.3-1.5 11 11 0 0 1-2.4-.6 1.5 1.5 0 0 0-1.6.4l-1.2 1.2a14 14 0 0 1-6-6L10.2 9a1.5 1.5 0 0 0 .4-1.6 11 11 0 0 1-.6-2.4 1.5 1.5 0 0 0-1.5-1.3Z"/>',
            'arrow-right'  => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'help-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
            'life-buoy'    => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.9 4.9 4.3 4.3M14.8 14.8l4.3 4.3M19.1 4.9l-4.3 4.3M9.2 14.8l-4.3 4.3"/>',
            'droplet'      => '<path d="M12 2.7 6.9 8.1a7.2 7.2 0 1 0 10.2 0Z"/>',
            'gift'         => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M4.5 12v8.5a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1V12"/><path d="M12 8v13.5"/><path d="M12 8H7.8a2.4 2.4 0 1 1 0-4.8C10.6 3.2 12 8 12 8Z"/><path d="M12 8h4.2a2.4 2.4 0 1 0 0-4.8C13.4 3.2 12 8 12 8Z"/>',
            'shield-check' => '<path d="M12 22s8-3.4 8-10V5.5l-8-3-8 3V12c0 6.6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
            'check'        => '<path d="m5 13 4 4L19 7"/>',
            'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m8 12.5 2.5 2.5L16 9.5"/>',
            'upload-cloud' => '<path d="M12 14v7"/><path d="m8.5 17.5 3.5-3.5 3.5 3.5"/><path d="M20 17.6A4.5 4.5 0 0 0 17.5 9h-1.3A7 7 0 1 0 4.5 16.3"/>',
            'calendar'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 2.5v5M16 2.5v5M3 10h18"/>',
            'globe'        => '<circle cx="12" cy="12" r="10"/><path d="M2.5 12h19"/><path d="M12 2.5a15 15 0 0 1 0 19 15 15 0 0 1 0-19Z"/>',
            'building'     => '<rect x="4" y="2.5" width="16" height="19" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/><path d="M10 21.5v-3h4v3"/>',
            'bell'         => '<path d="M18 8.5a6 6 0 1 0-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5Z"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/>',
            'file-check'   => '<path d="M14 2.5H7a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.5Z"/><path d="M14 2.5v5h5"/><path d="m9.5 14.5 2 2 3.5-3.5"/>',
            'megaphone'    => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M16 9a4 4 0 0 1 0 6"/><path d="M19 6.5a8 8 0 0 1 0 11"/>',
            'clock'        => '<circle cx="12" cy="12" r="9.5"/><path d="M12 7v5.2l3.2 2"/>',
            'bar-chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'clipboard'    => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M9 11h6M9 15h4"/>',
            'leaf'         => '<path d="M4 20A13 13 0 0 1 20 4a13 13 0 0 1-16 16Z"/><path d="M4 20 14 10"/>',
            // Reuse used to borrow 'leaf', which cleanup already owns - the two
            // sat next to each other in the sidebar wearing the same glyph.
            // Traced from Lucide's own `recycle`. The hand-drawn approximation it
            // replaces had arrowheads that did not sit on the ends of the arcs,
            // so at 18px it read as three unrelated strokes.
            'recycle'      => '<path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/>'
                            . '<path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/>'
                            . '<path d="m14 16-3 3 3 3"/>'
                            . '<path d="M8.293 13.596 7.196 9.5 3.1 10.598"/>'
                            . '<path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843"/>'
                            . '<path d="m13.378 9.633 4.096 1.098 1.097-4.096"/>',
            // Disasters used to borrow 'bug', which the dengue map already owns.
            'alert-triangle' => '<path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'more'         => '<circle cx="5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="19" cy="12" r="1.4"/>',
            'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',

            /* admin area */
            'grid'           => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
            'ban'            => '<circle cx="12" cy="12" r="9.5"/><path d="m5.3 5.3 13.4 13.4"/>',
            'message-circle' => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 9.5 9.5 0 0 1-3.4-.6L3 21l1.6-4.6A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5Z"/>',
            'bug'            => '<rect x="8" y="6" width="8" height="13" rx="4"/><path d="M8 12H3M21 12h-5M8.5 7 6 4.5M15.5 7 18 4.5M8.5 17 6 19.5M15.5 17 18 19.5"/>',
            'user-cog'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 9.5-5.8"/><circle cx="18" cy="17" r="2.5"/><path d="M18 13.5v1M18 19.5v1M21 15.2l-.9.5M15.9 18.3l-.9.5M21 18.8l-.9-.5M15.9 15.7l-.9-.5"/>',
            'file-text'      => '<path d="M14 2.5H7a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.5Z"/><path d="M14 2.5v5h5"/><path d="M9 12.5h6M9 16h6M9 9h2"/>',
            'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2 2 2 0 1 1-4 0 1.7 1.7 0 0 0-2.9-1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15a2 2 0 1 1 0-4 1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4.1a2 2 0 1 1 4 0 1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 11a2 2 0 1 1 0 4 1.7 1.7 0 0 0-1.6 0Z"/>',
            'search'         => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.9-3.9"/>',
            'x-circle'       => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
            'eye'            => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'download'       => '<path d="M12 3v12"/><path d="m7.5 11.5 4.5 4.5 4.5-4.5"/><path d="M4 20.5h16"/>',
            'arrow-left'     => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
            'inbox'          => '<path d="M21 12h-5l-1.5 3h-5L8 12H3"/><path d="M5.5 4.5h13l2.5 7.5v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6Z"/>',
        ];

        $body = $paths[$name] ?? '';

        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" '
             . 'stroke="currentColor" stroke-width="1.7" stroke-linecap="round" '
             . 'stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
    }
}
