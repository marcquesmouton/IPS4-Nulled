<?php
/**
 * @brief		Dashboard extension: Registrations
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		23 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Registrations
 */
class _Registrations
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		/* Init Chart */
		$chart = new \IPS\Helpers\Chart;
		
		/* Specify headers */
		$chart->addHeader( \IPS\Member::loggedIn()->language()->addToStack('date'), 'string' ); // Since we're displaying a column chart, it makes more sense to use a string so they plotted as discreet columns rather than in the middle of dates
		$chart->addHeader( \IPS\Member::loggedIn()->language()->addToStack('members'), 'number' );
		
		$data = array();
		$date = \IPS\DateTime::create()->sub( new \DateInterval( 'P7D' ) );

		while ( $date->getTimestamp() < time() )
		{
			$data[ $date->format( 'j n Y' ) ] = 0;
			$date->add( new \DateInterval( 'P1D' ) );
		}
		
		/* Add Rows */
		foreach ( \IPS\Db::i()->select( "COUNT(*) AS count, DATE_FORMAT( FROM_UNIXTIME( joined ), '%e %c %Y' ) as joined_date", 'core_members', array( 'joined>? AND name<>? AND email<>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P7D' ) )->getTimestamp(), '', ''  ), 'joined_date DESC', NULL, 'joined_date' ) as $row )
		{
			$data[$row['joined_date']] = $row['count'];
		}
		
		/* Add to graph */
		foreach ( $data as $time => $d )
		{
			$datetime = new \IPS\DateTime;
			$exploded = explode( ' ', $time );
			$datetime->setDate( $exploded[2], $exploded[1], $exploded[0] );
			
			$chart->addRow( array( $datetime->format( 'j M Y' ), $d ) );
		}
		
		/* Work out the ticks */
		$ticks = array();
		$increment = ceil( max($data) / 5 );
		
		for ($i = 1; $i <= 5; $i++)
		{
			$v = $increment * $i;
			$ticks[] = array( 'v' => $v, 'f' => (string) $v );
		}
		
		/* Output */
		return \IPS\Theme::i()->getTemplate( 'dashboard' )->registrations( $chart->render( 'ColumnChart', array(
 			'legend' 			=> array( 'position' => 'none' ),
 			'backgroundColor' 	=> '#fafafa',
 			'vAxis'				=> array( 'ticks' => $ticks ),
 		) ) );
	}
}