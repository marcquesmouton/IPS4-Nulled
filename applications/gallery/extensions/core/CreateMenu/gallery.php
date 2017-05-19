<?php
/**
 * @brief		Create Menu Extension : gallery
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension: gallery
 */
class _gallery
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{		
		if ( \IPS\gallery\Category::canOnAny( 'add' ) )
		{
			return array(
				'gallery_image' => array(
					'link' 		=> \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&_new=1", 'front', 'gallery_submit' ),
					'extraData'	=> array( 'data-ipsDialog-size' => "fullscreen", 'data-ipsDialog' => true )
				)
			);
		}

		return array();
	}
}