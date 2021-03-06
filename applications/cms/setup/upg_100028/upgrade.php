<?php
/**
 * @brief		4.0.5 Upgrade Code
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		5 May 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\setup\upg_100028;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.5 Upgrade Code
 */
class _Upgrade
{
	
	/**
	 * Make sure all theme settings are applied to every theme.
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
    {
	    \IPS\core\Setup\Upgrade::repairFileUrls('cms');
	    
		foreach ( \IPS\Db::i()->select( 'database_id', 'cms_databases', 'database_page_id>0' ) as $id )
		{
			\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\cms\Records' . $id ), 3, array( 'class' ) );
			\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\cms\Records\Comment' . $id ), 3, array( 'class' ) );
			\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\cms\Records\Review' . $id ), 3, array( 'class' ) );
		}

        return TRUE;
    }

}