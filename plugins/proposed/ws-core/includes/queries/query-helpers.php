<?php
/**
 * query-helpers.php
 *
 * Query Layer — Pure Utility Helpers
 *
 * PURPOSE
 * -------
 * Holds stateless utility functions used throughout the query layer.
 * Functions in this file must be pure utilities: no WP_Query, no
 * get_post_meta(), no get_field(). If a function reads WordPress data,
 * it belongs in query-shared.php instead.
 *
 * LOAD ORDER
 * ----------
 * Must be loaded before query-shared.php and query-jurisdiction.php.
 * Both files depend on functions defined here.
 *
 * FUNCTIONS
 * ---------
 *   ws_resolve_display_name()  Resolves a WP user ID to a display name string.
 *
 * @package    WhistleblowerShield
 * @since      3.6.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.6.0  Extracted from query-jurisdiction.php as part of query-layer split.
 *        ws_resolve_display_name() previously defined at top of that file.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Resolve Display Name
//
// Resolves a WordPress user ID to the user's display name.
// Returns an empty string if the user ID is falsy or the user does not exist.
// Used by all dataset functions so the render layer never calls get_userdata().
// ════════════════════════════════════════════════════════════════════════════

/**
 * Returns the display name for a given WP user ID.
 *
 * @param  int    $user_id  WordPress user ID.
 * @return string           Display name, or empty string if not resolvable.
 */
function ws_resolve_display_name( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) {
        return '';
    }
    $user = get_userdata( $user_id );
    return $user ? $user->display_name : '';
}
