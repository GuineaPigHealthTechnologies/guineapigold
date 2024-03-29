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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'rJ3jdjBUhuxVXJg06ddRplIIylt6D6kQDE0XXNXy69FVU7HvSy4tBXmUV49yM3yHeiHdxsPeo8aG5+MoMla2Eg==');
define('SECURE_AUTH_KEY',  'VqgI41QHFHG+WWC5R/O9nv6PUxX5U9203Kq8H5qNBhgaY1DZl/jHEwu1kwbgbrWo/8a37NxIUSXh6ZnqotSEMw==');
define('LOGGED_IN_KEY',    'ZK5D4sx6dYrLSqRBwJjXaBkLALITZRlVjOp43T8zvh2Pd5aLACB1ePh9FCdXfX+H4p/iO1pFaSU+sMmFT9PHHQ==');
define('NONCE_KEY',        '2tDdHGHlP0xShTbPde65UMgv2Q0dC0t8LGaxcLJdRj78vBwFy5MUorvRS0QtO1LUP7xqB0u2iBoXkgKI3LFvZA==');
define('AUTH_SALT',        'guVyFD0Qx/7hiaFC2n+U43uXlfFV45lQOdrD2jAbc/U5XEVO7CItwrEuOjCdzB1tY6sgy4q8+PCeBQ/6+kUPqQ==');
define('SECURE_AUTH_SALT', 'c7dWjVWXdnH99g3epPntJ3izu3Q1NvlO+If9rZG4BXRmnS5JUPY60oWdCgoNDBqh1dw60mrCYVZpj5NoXY5G3g==');
define('LOGGED_IN_SALT',   'kVzXxeTM0ThryGyH8mHSGWeTKFbxqZebNXciaWkwUymWRKKHEPMlk5j02AsmP7E+Z45zUxqfzozsShHyowFbBw==');
define('NONCE_SALT',       'j0VZ1BWGBf+kaxfYjQmO7vBy4qHl2MSSIT5oTzp0TJ5KhqAZxLANguu2BIRV/Dt0eULcnnf4WKlR9eZa4gNM2A==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_actna8l0qc_';





/* Inserted by Local by Flywheel. See: http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Inserted by Local by Flywheel. Fixes $is_nginx global for rewrites. */
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) !== false ) {
	$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.10.1';
}
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
