<?php
/**
 * @brief		Gallery bandwidth statistics
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gallery bandwidth statistics
 */
class _bandwidth extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'bandwidth_manage' );
		parent::execute();
	}

	/**
	 * Gallery bandwidth statistics
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart = new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( "app=gallery&module=stats&controller=bandwidth" ), 'gallery_bandwidth', 'bdate', '', array( 'vAxis' => array( 'title' => '(kB)' ) ), 'LineChart', 'daily', array( 'start' => 0, 'end' => 0 ), array( 'member_id', 'image_id', 'bdate', 'bsize' ) );
		
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('bandwidth'), 'number', 'ROUND((SUM(bsize)/1024),2)', FALSE );
		$chart->groupBy = 'bdate';
		
		$chart->tableParsers = array(
			'member_id'	=> function( $val )
			{
				try
				{
					$member = \IPS\Member::load( $val );
					return "<a href='" . \IPS\Http\Url::internal( "app=gallery&module=stats&controller=member&do=images&id={$member->member_id}" ) . "'>{$member->name}</a>";
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'image_id'	=> function( $val )
			{
				try
				{
					$image = \IPS\gallery\Image::load( $val );
					return "<a href='{$image->url()}' target='_blank'>{$image->caption}</a>";
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_image');
				}
			},
			'bdate'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			},
			'bsize'	=> function( $val )
			{
				return \IPS\Output\Plugin\Filesize::humanReadableFilesize( $val );
			}
		);
		
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('bandwidth_stats');
		\IPS\Output::i()->output = (string) $chart;
	}
}