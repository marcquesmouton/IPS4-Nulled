<?php
/**
 * @brief		recentImageReviews Widget
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
{subpackage}
 * @since		20 Aug 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * recentImageReviews Widget
 */
class _recentImageReviews extends \IPS\Content\WidgetComment
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'recentImageReviews';
	
	/**
	 * @brief	App
	 */
	public $app = 'gallery';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Class
	 */
	protected static $class = 'IPS\gallery\Image\Review';
}