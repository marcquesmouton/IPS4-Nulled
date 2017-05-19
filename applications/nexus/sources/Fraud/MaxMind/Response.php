<?php
/**
 * @brief		MaxMind Response
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		07 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Fraud\MaxMind;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MaxMind Response
 */
class _Response
{
	/**
	 * @brief	Data
	 */
	protected $data = array();
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Response
	 * @return	void
	 */
	public function __construct( \IPS\Http\Response $data = NULL )
	{
		if ( $data )
		{
			foreach ( explode( ';', $data ) as $row )
			{
				$exploded = explode( '=', $row );
				$this->data[ $exploded[0] ] = $exploded[1];
			}
		}
	}
	
	/**
	 * Get data
	 *
	 * @param	string	$key	Key
	 * @return	mixed
	 */
	public function __get( $key )
	{
		if ( isset( $this->data[ $key ] ) )
		{
			return $this->data[ $key ];
		}
		return NULL;
	}
	
	/**
	 * Build from JSON
	 *
	 * @param	string	$json	JSON data
	 * @return	\IPS\nexus\Fraud\MaxMind\Response
	 */
	public static function buildFromJson( $json )
	{
		$obj = new static;
		$obj->data = json_decode( $json, TRUE );
		return $obj;
	}
	
	/**
	 * JSON encoded
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return json_encode( $this->data );
	}
	
	/**
	 * proxyScore as percentage
	 *
	 * @return	int
	 */
	public function proxyScorePercentage()
	{
		return ( 100 - 10 ) / 3 * $this->proxyScore + ( $this->proxyScore > 3 ? ( 10 * ( $this->proxyScore - 3 ) ) : 0 );
	}
}