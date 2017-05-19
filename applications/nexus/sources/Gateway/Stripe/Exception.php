<?php
/**
 * @brief		Stripe Exception
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		10 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Exception
 */
class _Exception extends \DomainException
{
	/**
	 * @brief	Details
	 */
	public $details;
	
	/**
	 * Constructor
	 *
	 * @param	array	$error	Error details
	 * @return	void
	 */
	public function __construct( array $response )
	{
		$this->details = $response;
		if ( $response['type'] == 'card_error' )
		{
			parent::__construct( $response['message'] );
		}
		else
		{
			parent::__construct( \IPS\Member::loggedIn()->language()->get( 'gateway_err' ) );
		}
	}
}