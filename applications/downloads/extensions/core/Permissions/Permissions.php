<?php
/**
 * @brief		Permissions
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	
 * @since		16 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\extensions\core\Permissions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Permissions
 */
class _Permissions
{
	/**
	 * Get node classes
	 *
	 * @return	array
	 */
	public function getNodeClasses()
	{		
		return array(
			'IPS\downloads\Category' => function( $current, $group )
			{
				$rows = array();
				
				foreach( \IPS\downloads\Category::roots( NULL ) AS $root )
				{
					try
					{
						\IPS\downloads\Category::populatePermissionMatrix( $rows, $root, $group, $current );
					}
					catch( \BadMethodCallException $e ) {}
				}
				
				return $rows;
			}
		);
	}
}