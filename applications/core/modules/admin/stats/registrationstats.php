<?php
/**
 * @brief		Registration Stats
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		3 June 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Registration Stats
 */
class _registrationstats extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'registrations_manage' );
		
		$chart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=core&module=stats&controller=registrationstats' ), 'core_members', 'joined', '', array( 'isStacked' => FALSE ) );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('stats_new_registrations'), 'number', 'COUNT(*)', FALSE );
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_registrations_title');
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );
	
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_registrationstats');
		\IPS\Output::i()->output = (string) $chart;
	}
}