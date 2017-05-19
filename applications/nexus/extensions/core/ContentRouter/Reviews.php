<?php
/**
 * @brief		Content Router extension: Reviews
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		05 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Reviews
 */
class _Reviews
{	
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
		if ( ( $member === NULL or $member->canAccessModule( \IPS\Application\Module::get( 'nexus', 'store', 'front' ) ) ) and \IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages', array( 'p_store=1 AND p_reviewable=1' ) )->first() )
		{
			$this->classes[] = 'IPS\nexus\Package\Review';
		}
	}
	
	/**
	 * @brief	Item Classes for embed only
	 */
	public $embeddableContent = array( 'IPS\nexus\Package\Item' );
}