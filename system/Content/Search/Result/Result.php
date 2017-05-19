<?php
/**
 * @brief		Search Result
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		15 Sep 2015
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result
 */
abstract class _Result
{
	/**
	 * @brief	Created Date
	 */
	public $createdDate;
	
	/**
	 * @brief	Last Updated Date
	 */
	public $lastUpdatedDate;
	
	/**
	 * Separator for activity streams - "past hour", "today", etc.
	 *
	 * @param	bool	$createdDate	If TRUE, uses $createdDate, otherwise uses $lastUpdatedDate
	 * @return	string
	 */
	public function streamSeparator( $createdDate=TRUE )
	{
		$date = $createdDate ? $this->createdDate : $this->lastUpdatedDate;
		
		$now = \IPS\DateTime::ts( time() );
		$yesterday = clone $now;
		$yesterday = $yesterday->sub( new \DateInterval('P1D') );
		$diff = $date->diff( $now );
		if ( $diff->h < 1 && !$diff->d && !$diff->m )
		{
			return 'past_hour';
		}
		elseif ( $date->format('Y-m-d') == $now->format('Y-m-d') )
		{
			return 'today';
		}
		elseif ( $date->format('Y-m-d') == $yesterday->format('Y-m-d') )
		{
			return 'yesterday';
		}
		elseif ( !$diff->y and !$diff->m and $diff->d < 7 )
		{
			return 'last_week';
		}
		else
		{
			return 'earlier';
		}
	}
}