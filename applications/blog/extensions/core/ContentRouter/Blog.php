<?php
/**
 * @brief		Content Router extension: Blog
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	
 * @since		04 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Entries
 */
class _Blog
{	
	/**
	 * @brief	Owned Node Classes
	 */
	public $ownedNodes = array( 'IPS\blog\Blog' );
	
	/**
	 * @brief	Content Item Classes
	 */
	public $classes = array();
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member|NULL	$member		If checking access, the member to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( \IPS\Member $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( \IPS\Application\Module::get( 'blog', 'blogs', 'front' ) ) )
		{
			$this->classes[] = 'IPS\blog\Entry';
		}
	}
}