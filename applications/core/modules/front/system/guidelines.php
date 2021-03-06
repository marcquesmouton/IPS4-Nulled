<?php
/**
 * @brief		Guidelines
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		02 Sept 2013
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
 * Guidelines
 */
class _guidelines extends \IPS\Dispatcher\Controller
{
	/**
	 * Guidelines
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( \IPS\Settings::i()->gl_type == "none" )
		{
			\IPS\Output::i()->error( 'node_error', '', 404 ); /* @todo Error code */
		}

		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=system&controller=guidelines', NULL, 'guidelines' ), array(), 'loc_viewing_guidelines' );
		
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('guidelines') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('guidelines');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->guidelines( \IPS\Settings::i()->gl_guidelines );
	}
}