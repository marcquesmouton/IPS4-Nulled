<?php
/**
 * @brief		Incoming Referrals
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		15 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Incoming Referrals
 */
class _referral extends \IPS\Dispatcher\Controller
{
	/**
	 * Handle Referral
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		\IPS\Request::i()->setCookie( 'referred_by', intval( \IPS\Request::i()->id ), \IPS\DateTime::create()->add( new \DateInterval( 'P1Y' ) ) );
		
		$target = new \IPS\Http\Url( \IPS\Request::i()->direct ? base64_decode( \IPS\Request::i()->direct ) : \IPS\Settings::i()->base_url );
		if ( $target->isInternal )
		{
			\IPS\Output::i()->redirect( $target );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Settings::i()->base_url );
		}
	}
}