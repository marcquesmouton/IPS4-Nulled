<?php
/**
 * @brief		Designers Mode Extension
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		28 Nov 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\DesignersMode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Designers Mode Extension
 */
class _Cms
{
	/**
	 * Anything need building?
	 *
	 * @return array
	 */
	public function toBuild()
	{
		/* Yeah.. not gonna even bother trying to match up timestamps and such like and so on etc and etcetera is that spelled right? */
		return TRUE;
	}
	
	/**
	 * Designer's mode on
	 *
	 * @return array
	 */
	public function on( $data=NULL )
	{
		\IPS\cms\Theme\Advanced\Theme::export();
		\IPS\cms\Media::exportDesignersModeMedia();
		return TRUE;
	}
	
	/**
	 * Designer's mode off
	 *
	 * @return array
	 */
	public function off( $data=NULL )
	{
		\IPS\cms\Theme\Advanced\Theme::import();
		\IPS\cms\Media::importDesignersModeMedia();
		
		return TRUE;
	}
}