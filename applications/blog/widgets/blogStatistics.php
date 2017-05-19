<?php
/**
 * @brief		blogStatistics Widget
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	blog
 * @since		14 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blogStatistics Widget
 */
class _blogStatistics extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'blogStatistics';
	
	/**
	 * @brief	App
	 */
	public $app = 'blog';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$stats = array();
		
		$stats['total_blogs']	= \IPS\Db::i()->select( "COUNT(*)", 'blog_blogs' )->first();
		$stats['total_entries']	= \IPS\Db::i()->select( "COUNT(*)", 'blog_entries' )->first();
		
		return $this->output( $stats );
	}
}