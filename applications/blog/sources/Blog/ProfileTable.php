<?php
/**
 * @brief		Blog Profile Table Helper
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		18 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\Blog;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Profile Table Helper
 */
class _ProfileTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	Base URL
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url=NULL )
	{
		parent::__construct( $url );
	}

	/**
	 * Get rows
	 *
	 * @param	array	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( $advancedSearchValues )
	{
		$rows = parent::getRows( $advancedSearchValues );
		
		$return = array();
		foreach( $rows AS $row )
		{
			if ( $row->owner() instanceof \IPS\Member )
			{
				$return['owner'][ $row->id ]		= $row;
			}
			else
			{
				$return['contributor'][ $row->id ]	= $row;
			}
		}
		
		return $return;
	}
}