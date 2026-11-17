<?php
/**
 * Uninstall cleanup.
 *
 * @package LCACC
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * Remove plugin-specific options.
 *
 * Add additional cleanup here only when the corresponding
 * persistent data has been implemented and documented.
 */

delete_option( 'lcacc_version' );
delete_option( 'lcacc_settings' );
