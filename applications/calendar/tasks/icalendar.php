<?php
/**
 * @brief		icalendar Task
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	calendar
 * @since		18 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * icalendar Task
 */
class _icalendar extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		try
		{
			$next	= \IPS\Db::i()->select( '*', 'calendar_import_feeds', NULL, 'feed_last_run ASC', 1 )->first();

			if( $next['feed_id'] )
			{
				/* Refresh the feed */
				try
				{
					$count	= \IPS\calendar\Icalendar::load( $next['feed_id'] )->refresh();
				}
				catch( \Exception $e )
				{
					throw new \IPS\Task\Exception( $this, \IPS\Member::loggedIn()->language()->addToStack( 'task_' . $e->getMessage(), FALSE, array( 'sprintf' => array( $next['feed_title'] ) ) ) );
				}

				/* Task log */
				return \IPS\Member::loggedIn()->language()->addToStack( 'acplog__icalfeed_refreshed', FALSE, array( 'sprintf' => array( $count['imported'], $count['skipped'] ) ) );
			}
		}
		catch( \UnderflowException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}