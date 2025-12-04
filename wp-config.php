<?php
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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',          'az`LTJ)@jg/L|I}BC}!cEp|5TAWBD[Dxf:d>0~aWG C$x|nTobKw;P3W&<kF%^-I' );
define( 'SECURE_AUTH_KEY',   'B2I]]vT,N^gUE=>0[^]NK$`F!dseYG1z#Tjnh0O$]W rt5HI?x!<Jc: p,8AxDLF' );
define( 'LOGGED_IN_KEY',     ' t 3Iu_|.5fGrpd6_GON6q>-xJ:||d 2ROwBN`o//N@!ye|#jR2B?Y@/L:QL< xa' );
define( 'NONCE_KEY',         'k(7rf&YJ@6#_Ef!QylDVw3F6_U*>@ zdeLwxRN5C /A%r-r3Q`6HP>!q|+tRy/46' );
define( 'AUTH_SALT',         'RyT./WgyT<`SD=@<3D{Nx&6jtxTq2,4ledz2nlmc1D68KayWNddxxqNFR3cSjF&c' );
define( 'SECURE_AUTH_SALT',  '5zr*>,IrS6X)rbJ30Ys}tVoW!Eq`@RN[E6gn:7@@,oy]N&`HX$1x!S.2+.+xcV#s' );
define( 'LOGGED_IN_SALT',    ' Pkfn mCo7@pG0#+N/.S5[p,;PCp(U;{ASX^rSaTW)Q.gb$tZapA8+W+ybwb*6g>' );
define( 'NONCE_SALT',        'v>D}&vcvX1>XSHH:7YeHr5}x~r]0D03QTaw=):<8;6yy5zL.ules6ZmFx8~B@|~J' );
define( 'WP_CACHE_KEY_SALT', '- a6I3>*1D7=yZRL1CaJqPUzAgdJ%qH8LbntUi]_m#qL^Er+SA)6Fd-4rJ/|xvkQ' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


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
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
