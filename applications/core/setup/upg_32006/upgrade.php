<?php
/**
 * @brief		Upgrade steps
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		4 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_32006;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrade steps
 */
class _Upgrade
{
	/**
	 * Step 1
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\Db::i()->dropColumn( 'rss_import', 'rss_import_charset' );

		\IPS\Db::i()->addColumn( 'sessions', array(
			'name'			=> 'session_msg_id',
			'type'			=> 'int',
			'length'		=> 10,
			'allow_null'	=> false,
			'default'		=> 0
		) );

		\IPS\Db::i()->createTable( array(
			'name'		=> 'core_inline_messages',
			'columns'	=> array(
				array(
					'name'			=> 'inline_msg_id',
					'type'			=> 'int',
					'length'		=> 10,
					'allow_null'	=> false,
					'auto_increment'	=> true
				),
				array(
					'name'			=> 'inline_msg_date',
					'type'			=> 'int',
					'length'		=> 10,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'inline_msg_content',
					'type'			=> 'text',
					'allow_null'	=> true,
					'default'		=> null
				),
			),
			'indexes'	=> array(
				array(
					'type'		=> 'primary',
					'columns'	=> array( 'inline_msg_id' )
				),
				array(
					'type'		=> 'key',
					'name'		=> 'inline_msg_date',
					'columns'	=> array( 'inline_msg_date' )
				),
			)
		)	);

		/* Finish */
		return TRUE;
	}
}