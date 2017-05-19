<?php
/**
 * @brief		Front Navigation Extension: Menu Separator
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Core
 * @since		22 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Menu Separator
 */
class _MenuSeparator
{
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		return TRUE;
	}
			
	/**
	 * Children
	 *
	 * @return	array
	 */
	public function children()
	{
		return NULL;
	}
}