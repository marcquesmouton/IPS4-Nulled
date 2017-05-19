<?php
/**
 * @brief		downloadStats Widget
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	downloads
 * @since		09 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * downloadStats Widget
 */
class _downloadStats extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'downloadStats';
	
	/**
	 * @brief	App
	 */
	public $app = 'downloads';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

 	/**
	 * Init the widget
	 *
	 * @return	void
	 */
 	public function init()
 	{
 		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'widgets.css', 'downloads', 'front' ) );

 		parent::init();
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$stats = array();
		$stats['totalFiles'] = \IPS\Db::i()->select( 'COUNT(*)', 'downloads_files' )->first();
		$stats['totalAuthors'] = \IPS\Db::i()->select( 'COUNT(DISTINCT file_submitter)', 'downloads_files' )->first();
		$stats['totalComments'] = \IPS\Db::i()->select( 'COUNT(*)', 'downloads_comments' )->first();
		$stats['totalReviews'] = \IPS\Db::i()->select( 'COUNT(*)', 'downloads_reviews' )->first();
		
		$latestFile = NULL;
		foreach ( \IPS\downloads\File::getItemsWithPermission( array(), NULL, 1 ) as $latestFile )
		{
			break;
		}
		
		return $this->output( $stats, $latestFile );
	}
}