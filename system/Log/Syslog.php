<?php
/**
 * @brief		Syslog Log Class
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		14 Nov 2013
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
class _Syslog extends \IPS\Log
{	
	/**
	 * Get log content
	 * 
	 * @param	string	$title	Title of log to fetch
	 * @return	string	Raw log contents
	 */
	public function getLog( $title )
	{
		return NULL;
	}
	
	/**
	 * Log
	 *
	 * @param		string			$message		The message
	 * @param		string			$suffix			Unique key for this log
	 * @return		void
	 */
	public function write( $message, $suffix=NULL )
	{	
		$date       = date( 'r' );
		$ip         = $this->getIpAddress();
		$url        = \IPS\Request::i()->url();
		
		syslog( $this->severity, $suffix . ': ' . $ip . ': ' . $url . ': ' . $date . ': ' . $message );
	}
}