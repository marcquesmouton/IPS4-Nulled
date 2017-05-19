<?php
/**
 * @brief		4.1.0 Beta 10 Upgrade Code
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		22 Oct 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\setup\upg_101009;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.0 Beta 10 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Step 1
	 * Fix the first post flagged for topics
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$perCycle	= 500;
		$did		= 0;
		$limit		= intval( \IPS\Request::i()->extra );
		
		/* Try to prevent timeouts to the extent possible */
		$cutOff			= \IPS\core\Setup\Upgrade::determineCutoff();

		foreach( \IPS\Db::i()->select( '*', 'forums_topics', array( 'topic_firstpost=?', 0 ), 'tid ASC', array( $limit, $perCycle ) ) as $topic )
		{
			if( $cutOff !== null AND time() >= $cutOff )
			{
				return ( $limit + $did );
			}

			$did++;

			try
			{
				$firstPost = \IPS\Db::i()->select( 'pid', 'forums_posts', array( 'topic_id=?', $topic['tid'] ), 'post_date ASC', 1 )->first();
			}
			catch( \UnderflowException $e )
			{
				continue;
			}

			\IPS\Db::i()->update( 'forums_topics', array( 'topic_firstpost' => $firstPost ), 'tid=' . $topic['tid'] );
		}
		
		if( $did )
		{
			return ( $limit + $did );
		}
		else
		{
			unset( $_SESSION['_step1Count'] );

			$toRun = \IPS\core\Setup\Upgrade::runManualQueries( array( 
				array(
					'table' => 'forums_posts',
					'query' => "UPDATE " . \IPS\Db::i()->prefix . "forums_posts SET new_topic=1 WHERE pid IN ( SELECT topic_firstpost FROM " . \IPS\Db::i()->prefix . "forums_topics )"
				)
			) );

			if ( count( $toRun ) )
			{
				$mr = \IPS\core\Setup\Upgrade::adjustMultipleRedirect( array( 1 => 'forums', 'extra' => array( '_upgradeStep' => 2, '_upgradeData' => 0 ) ) );

				/* Queries to run manually */
				return array( 'html' => \IPS\Theme::i()->getTemplate( 'forms' )->queries( $toRun, \IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => $mr ) ) ) );
			}
			
			return TRUE;
		}
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		$limit = isset( \IPS\Request::i()->extra ) ? \IPS\Request::i()->extra : 0;

		if( !isset( $_SESSION['_step1Count'] ) )
		{
			$_SESSION['_step1Count'] = \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics' )->first();
		}

		$message = "Fixing flagged first posts (Upgraded so far: " . ( ( $limit > $_SESSION['_step1Count'] ) ? $_SESSION['_step1Count'] : $limit ) . ' out of ' . $_SESSION['_step1Count'] . ')';
		
		return $message;
	}
}