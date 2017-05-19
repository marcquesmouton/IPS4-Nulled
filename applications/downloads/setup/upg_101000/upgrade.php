<?php
/**
 * @brief		4.1.0 Beta 1 Upgrade Code
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		23 Sep 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\setup\upg_101000;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.0 Beta 1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fix broken downloads reviews from 100044
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		foreach( \IPS\Db::i()->select( '*', 'downloads_reviews', 'review_date is null' ) as $review )
		{
			try
			{
				$member = \IPS\Member::load( $review['review_mid'] );

				\IPS\Db::i()->update( 'downloads_reviews', array( 'review_date' => time(), 'review_text' => '', 'review_author_name' => $member->name ), array( 'review_id=?', $review['review_id'] ) );
			}
			catch( \Exception $e ){}
		}


		return TRUE;
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Adjusting empty download reviews";
	}

	/**
	 * Reset the forum ID to post to if the forum ID does not exist
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
	{
		if ( \IPS\Application::appIsEnabled( 'forums' ) )
		{
			$prefix = \IPS\Db::i()->prefix;
	
			\IPS\Db::i()->update( 'downloads_categories', array( 'cforum_id' => 0 ), 'cforum_id NOT IN( SELECT id FROM ' . $prefix . 'forums_forums )' );
		}

		return TRUE;
	}
}