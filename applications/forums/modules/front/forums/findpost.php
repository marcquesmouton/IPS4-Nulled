<?php
/**
 * @brief		Legacy 3.x findpost
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		09 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Legacy 3.x findpost
 */
class _findpost extends \IPS\Dispatcher\Controller
{
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		try
		{
			\IPS\Output::i()->redirect( \IPS\forums\Topic\Post::loadAndCheckPerms( \IPS\Request::i()->pid )->url(), NULL, 301 );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F284/1', 404, '' );
		}
	}
}