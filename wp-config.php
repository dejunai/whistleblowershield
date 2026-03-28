<?php
define( 'WP_CACHE', false ); // By Speed Optimizer by SiteGround

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbmohxkgixxqca' );

/** Database username */
define( 'DB_USER', 'u6svysul0g6o6' );

/** Database password */
define( 'DB_PASSWORD', 'NOT!7554wsdb' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '~o-^Tx_5A7A4S-JsRlc)5/TqBEy9_%Z<Pg!_7[g?Wm<xRlW[EhKe:Nywyo`q^G?9' );
define( 'SECURE_AUTH_KEY',   'E^BG%HY__?$(es68EpY~xeKH.?mlr-y}ff7xL|t]xdg%YM$4iKSS{m:JQT:r]ig$' );
define( 'LOGGED_IN_KEY',     '~3LS,[Pr,QN9l$)VyzaD,M|z:6SrwNT3cyJ :u$Q+:ygHq{[F`rSvy1O#:u5%&a7' );
define( 'NONCE_KEY',         '5Bz0w7|r3QfMErGKH:v^#P.ia>OSQ]a0<}>2Y$ru^n@/Rr*f2GN.m=-:i]/T)<nI' );
define( 'AUTH_SALT',         '~<h(7BF~!,@;~9<#0SJ/=im g!09MJj/17HXaYB0In!U$N0#&QHrfJHE};65Yq(D' );
define( 'SECURE_AUTH_SALT',  '7a9_%Q(MM#0F,JsOJ YOsRg6+l};Z|l@Z{y 1z?G3he1r>T!+8m,&oqXP`dZpwU{' );
define( 'LOGGED_IN_SALT',    '.CPR9S_`~S__tkONlgb3/93vdpae&M=]<[23;UKFB~B`l^/v3uN&l-p`e{q:)V^|' );
define( 'NONCE_SALT',        'zO77.iL/;h5ip-E[ |jTep0Klc8wAc].X#r_I2=/-H5$:Wqku`n7HKl(4b>8Gj5Y' );
define( 'WP_CACHE_KEY_SALT', '&/%?W[</6 Cgt.`v&EPB/CM&->rAV@(wnxu/t@!r{ e2c/:eLXfZ,UFXM`F8}b@8' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'thk_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );	define( 'WP_DEBUG_LOG', true );	define( 'WP_DEBUG_DISPLAY', false );	@ini_set( 'display_errors', 0 );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
