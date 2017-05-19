<?php
/**
 * @brief		No logging Class
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		12 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Log;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Disk log class
 */
class _None extends \IPS\Log
{

	/**
	 * Log
	 *
	 * @param		string			$message		The message
	 * @param		string			$suffix			Unique key for this log
	 * @return		void
	 */
	public function write( $message, $suffix=NULL )
	{
		return TRUE;
	}
}