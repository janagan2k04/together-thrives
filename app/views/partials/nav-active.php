<?php
/**
 * Path-segment-aware version of the `nav_active()` helper, used by the sidebars.
 *
 * `nav_active()` is a bare `str_starts_with`, so `/blood-bank/dashboard` counts
 * as being inside `/blood` and a blood bank saw two links lit at once. This
 * matches on a path boundary instead: the link is active for its own URL and
 * for anything genuinely below it.
 *
 * The helper lives in `app/core/Helpers.php`, which is on the do-not-modify
 * list, so the fix lives here. If the group later agrees to change the helper,
 * delete this file and the two `require_once` lines that pull it in.
 */
if (!function_exists('sidebar_active')) {

    function sidebar_active(string $href): string
    {
        $uri  = Core\Request::uri();
        $href = '/' . trim($href, '/');

        return ($uri === $href || str_starts_with($uri, $href . '/')) ? ' is-active' : '';
    }
}
