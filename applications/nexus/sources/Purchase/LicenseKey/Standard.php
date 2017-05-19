<?php
/**
 * @brief		License Key Model - Standard
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		30 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Purchase\LicenseKey;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * License Key Model - Standard
 */
class _Standard extends \IPS\nexus\Purchase\LicenseKey
{	
	/**
	 * @brief	Number of blocks
	 */
	protected static $blocks = 5;
	
	/**
	 * @brief	Number of characters in a block
	 */
	protected static $characters = 4;
	
	/**
	 * @brief	Lowest allowed ASCII number
	 */
	protected static $low = 48; // 0
	
	/**
	 * @brief	Highest allowed ASCII number
	 */
	protected static $high = 90; // Z
	
	/**
	 * @brief	Disallowed ASCII numbers
	 */
	protected static $disallowed = array( 58, 59, 60, 61, 62, 63, 64 ); // Various non A-Z / 0-9 characters
	
	/**
	 * @brief	Seperator between blocks
	 */
	protected static $seperator	= '-';

	/**
	 * Generates a License Key
	 *
	 * @return	string
	 */
	public function generate()
	{
		$key = array();
		foreach ( range( 1, self::$blocks ) as $i )
		{
			$_k = '';
			foreach ( range( 1, self::$characters ) as $j )
			{
				do
				{
					$chr = rand( self::$low, self::$high );
				}
				while ( in_array( $chr, self::$disallowed ) );
				$_k .= chr( $chr );
			}
			$key[] = $_k;
		}
		
		return implode( self::$seperator, $key );
	}
}