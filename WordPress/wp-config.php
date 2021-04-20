<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'pulkit_wp992' );

/** MySQL database username */
define( 'DB_USER', 'pulkit_wp992' );

/** MySQL database password */
define( 'DB_PASSWORD', '1-uph)S729' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'zuzxuc2ueghghzgjgk3ifazhh35acqab52tpyircymbzljjuw75hooxph3hdsnng' );
define( 'SECURE_AUTH_KEY',  'keoj1vwyhtnjk1xsbq9p08r47kehojaqd8zk8cwdcmmkwobsjyz1dqtadnssikur' );
define( 'LOGGED_IN_KEY',    'oy13dqaurnws8swvbggdmqkpljnobuqmxd4vvxawkm5v6uvaxjalvhtieobiun5v' );
define( 'NONCE_KEY',        'muqnswqt8ir1fln9hk2j3inqtxsqufffdsdthnfvkxjf3sajyvcbegmwxfmao7xs' );
define( 'AUTH_SALT',        'oabdbf3ddayovko6eoccxuc1mthjfkz4zzxeijweyzohbukghjcwtkmkbwxb7z8u' );
define( 'SECURE_AUTH_SALT', 'nz2nczhsapiycxscbs5go2x4t7hk59jexlei42chya8gescxdim0lgiaqz95dwto' );
define( 'LOGGED_IN_SALT',   'qiwmjiwlthvwju5c7fatnciral7tsxiqcsxg8pu3u3fxwrtxux9lfkzxexepn3xm' );
define( 'NONCE_SALT',       '0h0frm8eysxmbewe3fyuwjjkhq8s9yyonwowxay8eh4lgdw8jjaob3cqunuus5os' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpgs_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
