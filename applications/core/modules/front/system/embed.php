<?php
/**
 * @brief		Embed iframe display
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		15 Sep 2014
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
 * Embed iframe display
 */
class _embed extends \IPS\Content\Controller
{
	/**
	 * Embed iframe display
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		\IPS\Output::i()->sendOutput( \IPS\Text\Parser::embeddableMedia( \IPS\Request::i()->url, TRUE ), 200 );
	}
}