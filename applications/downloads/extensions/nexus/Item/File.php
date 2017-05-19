<?php
/**
 * @brief		File
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		05 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File
 */
class _File extends \IPS\nexus\Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static $application = 'downloads';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'file';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'download';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'file';
	
	/**
	 * Image
	 *
	 * @return |IPS\File|NULL
	 */
	public function image()
	{
		try
		{
			return \IPS\downloads\File::load( $this->id )->primary_screenshot;
		}
		catch ( \Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Image
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return |IPS\File|NULL
	 */
	public static function purchaseImage( \IPS\nexus\Purchase $purchase )
	{
		try
		{			
			return \IPS\downloads\File::load( $purchase->item_id )->primary_screenshot;
		}
		catch ( \Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @return	array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public static function clientAreaPage( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$file = \IPS\downloads\File::load( $purchase->item_id );
			
			return array( 'packageInfo' => \IPS\Theme::i()->getTemplate( 'nexus', 'downloads' )->fileInfo( $file ), 'purchaseInfo' => \IPS\Theme::i()->getTemplate( 'nexus', 'downloads' )->filePurchaseInfo( $file ) );
		}
		catch ( \OutOfRangeException $e ) { }
		
		return NULL;
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @return	string
	 */
	public static function acpPage( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$file = \IPS\downloads\File::load( $purchase->item_id );
			return \IPS\Theme::i()->getTemplate( 'nexus', 'downloads' )->fileInfo( $file );
		}
		catch ( \OutOfRangeException $e ) { }
		
		return NULL;
	}
	
	/**
	 * URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function url()
	{
		try
		{
			return \IPS\downloads\File::load( $this->id )->url();
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * ACP URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function acpUrl()
	{
		return $this->url();
	}
}