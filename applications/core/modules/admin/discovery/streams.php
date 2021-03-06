<?php
/**
 * @brief		streams
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		01 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\discovery;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * streams
 */
class _streams extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\core\Stream';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'streams_manage' );
		parent::execute();
	}
	
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		\IPS\Output::i()->sidebar['actions'] = array(
			'rebuildIndex'	=> array(
				'title'		=> 'all_activity_stream_settings',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=discovery&controller=streams&do=allActivitySettings' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('all_activity_stream_settings') )
			),
			'rebuildDefault'	=> array(
				'title'		=> 'restore_default_streams',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=discovery&controller=streams&do=restoreDefaultStreams' )
			),
		);
		
		return parent::manage();
	}
	
	/**
	 * Restores default streams
	 *
	 * @return	void
	 */
	protected function restoreDefaultStreams()
	{
		$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/schema.json" ), TRUE );
		
		foreach ( $schema['core_streams']['inserts'] as $insertData )
		{
			try
			{
				\IPS\Db::i()->replace( 'core_streams', $insertData );
			}
			catch( \IPS\Db\Exception $e )
			{}
		}
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=discovery&controller=streams' ), 'restore_default_streams_restored' );
	}
	
	/**
	 * All Activity Stream Settings
	 *
	 * @return	void
	 */
	protected function allActivitySettings()
	{
		$types = array( 'register', 'follow_member', 'follow_content', 'photo' );
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			if ( \IPS\Settings::i()->reputation_point_types == 'like' )
			{
				$types[] = 'like';
			}
			elseif ( \IPS\Settings::i()->reputation_point_types == 'both' )
			{
				$types[] = 'like';
				$types[] = 'rep_neg';
			}
			elseif ( \IPS\Settings::i()->reputation_point_types == 'positive' )
			{
				$types[] = 'like';
			}
			elseif ( \IPS\Settings::i()->reputation_point_types == 'negative' )
			{
				$types[] = 'rep_neg';
			}
		}
		
		$options = array();
		$currentValuesStream = array();
		foreach ( $types as $k )
		{
			$key = "all_activity_{$k}";
			if ( \IPS\Settings::i()->$key )
			{
				$currentValuesStream[] = $k;
			}
			$options[ $k ] = ( $k == 'like' and \IPS\Settings::i()->reputation_point_types != 'like' ) ? 'all_activity_rep_pos' : $key;
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'all_activity_extra_stream', $currentValuesStream, FALSE, array( 'options' => $options ) ) );
		
		if ( $values = $form->values() )
		{
			$toSave = array();
			foreach ( $types as $k )
			{
				$toSave[ "all_activity_{$k}" ] = intval( in_array( $k, $values['all_activity_extra_stream'] ) );
			}
			$form->saveAsSettings( $toSave );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=discovery&controller=streams' ), 'saved' );
		}
		
		\IPS\Output::i()->output = $form;
	}
}