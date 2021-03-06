<?php
/**
 * @brief		Installer: System Check
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		10 Dec 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Installer: System Check
 */
class _systemcheck extends \IPS\Dispatcher\Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage()
	{
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('healthcheck');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->healthcheck( \IPS\core\Setup\Install::systemRequirements() );
	}
}