<?php
/**
 * @brief		Member filter extension: member last visit date
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		20 June 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Member last visit date
 */
class _Lastvisit
{
	/** 
	 * Get Setting Field
	 *
	 * @param	mixed	$criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( $criteria )
	{
		return array(
					new \IPS\Helpers\Form\DateRange( 'bmf_members_last_visit', isset( $criteria['range'] ) ? $criteria['range'] : '', FALSE ),
					);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	mixed			False, or an array of data to use later when filtering the members
	 * @throws \LogicException
	 */
	public function save( $post )
	{
		return ( empty($post['bmf_members_last_visit']) ) ? FALSE : array( 'range' => $post['bmf_members_last_visit'] );
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param	mixed				$data	The array returned from the save() method
	 * @return	string|array|NULL	Where clause
	 */
	public function getQueryWhereClause( $data )
	{
		if( !empty($data['range']) AND !empty($data['range']['end']) )
		{
			$start	= ( $data['range']['start']['date'] ) ? strtotime( $data['range']['start']['date'] ) : 0;
			$end	= strtotime( $data['range']['end']['date'] );

			return "core_members.last_visit BETWEEN {$start} AND {$end}";
		}

		return NULL;
	}
}