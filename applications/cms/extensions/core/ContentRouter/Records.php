<?php
/**
 * @brief		Content Router extension: Records
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		17 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Records
 */
class _Records
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
		try
		{
			foreach ( \IPS\Db::i()->select( 'database_id', 'cms_databases', 'database_page_id>0' ) as $id )
			{
				if ( !$member or call_user_func( array( 'IPS\cms\Databases', 'load' ), $id )->can( 'view', $member ) )
				{
					$this->classes[] = 'IPS\cms\Records' . $id;
				}
			}
		}
		catch ( \Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error
	}
}