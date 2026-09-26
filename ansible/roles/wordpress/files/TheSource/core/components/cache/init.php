<?php
/**
 * PAUSATF TheSource theme source: init.php.
 *
 * @package PAUSATF_TheSource
 */


if ( ! defined( 'et_core_cache_init' ) ):
function et_core_cache_init() {}
endif;


if ( ! defined( 'et_core_cache_dir' ) ):
function et_core_cache_dir() {
	return ET_Core_Cache_Directory::instance();
}
endif;
