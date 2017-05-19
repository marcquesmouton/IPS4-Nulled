<?php
/**
 * @brief		Moderation
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		23 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderation
 */
class _moderation extends \IPS\Dispatcher\Controller
{
	/**
	 * Flag Member As Spammer
	 *
	 * @return	void
	 */
	protected function flagAsSpammer()
	{
		\IPS\Session::i()->csrfCheck();
		
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( $member->member_id and $member->member_id != \IPS\Member::loggedIn()->member_id and \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') and !$member->modPermission() and !$member->isAdmin() )
		{
			if ( \IPS\Request::i()->s )
			{
				$member->flagAsSpammer();
				\IPS\Session::i()->modLog( 'modlog__spammer_flagged', array( $member->name => FALSE ) );
			}
			else
			{
				$member->unflagAsSpammer();
				\IPS\Session::i()->modLog( 'modlog__spammer_unflagged', array( $member->name => FALSE ) );
			}
		}

		\IPS\Output::i()->redirect( ( \IPS\Request::i()->referrer ) ? new \IPS\Http\Url( urldecode( \IPS\Request::i()->referrer ), TRUE ) : $member->url(), ( \IPS\Request::i()->s ) ? 'account_flagged' : 'account_unflagged');
	}
}