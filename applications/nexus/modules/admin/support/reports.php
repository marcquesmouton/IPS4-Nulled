<?php
/**
 * @brief		Support Reports
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		23 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Reports
 */
class _reports extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'reports_manage' );
		parent::execute();
	}

	/**
	 * Dashboard
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Tabs */
		$tabs = array(
			'overview' 			=> 'overview',
			'replies'	 		=> 'replies',
			'feedback_ratings' 	=> 'feedback_ratings',
			'latest_feedback'	=> 'latest_feedback',
		);
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'staff_productivity';
		$activeTabContents = '';
		
		/* Staff Productivity */
		if ( $activeTab === 'overview' )
		{
			/* Build table */
			$staffProductivityTable = new \IPS\Helpers\Table\Db(
				'nexus_support_replies',
				\IPS\Http\Url::internal( 'app=nexus&module=support&controller=reports&act=productivity_table' ),
				array(
					array( 'reply_type=?', \IPS\nexus\Support\Reply::REPLY_STAFF ),
					\IPS\Db::i()->in( 'reply_member', array_keys( \IPS\nexus\Support\Request::staff() ) )
				),
				'reply_member'
			);
			$staffProductivityTable->langPrefix = 'staff_';
			$staffProductivityTable->selects = array( 'COUNT(*) AS reply_count' );
			$staffProductivityTable->include = array( 'name', 'reply_count' );
			/* As we only need these columns, we use this to avoid group by errors */
			$staffProductivityTable->onlySelected = array( 'name', 'reply_member', 'rating_count', 'rating_average' );
			$staffProductivityTable->widths = array( 'name' => 75, 'reply_count' => 25 );
			$staffProductivityTable->parsers['reply_count'] = function( $val ) { return intval( $val ); };

			/* We need to join the members table for the name */
			$staffProductivityTable->joins[] = array(
				'select'	=> 'name',
				'from'		=> 'core_members',
				'where'		=> 'member_id=reply_member'
			);

			if ( \IPS\Settings::i()->nexus_support_satisfaction )
			{
				$staffProductivityTable->joins[] = array(
					'select'	=> 'rating_count, rating_average',
					'from'		=> \IPS\Db::i()->select( 'rating_reply, COUNT(*) AS rating_count, AVG(rating_rating) AS rating_average', 'nexus_support_ratings', NULL, NULL, NULL, 'rating_reply' ),
					'where'		=> 'rating_reply=reply_id'
				);
				
				$staffProductivityTable->include[] = 'rating_average';
				$staffProductivityTable->widths = array( 'name' => 50, 'reply_count' => 25, 'rating_average' => 25 );
				$staffProductivityTable->parsers['rating_average'] = function( $val, $row ) {
					return \IPS\Theme::i()->getTemplate('supportreports')->averageRatingCell( $val, $row['rating_count'] );
				};
			}
			$staffProductivityTable->sortBy = $staffProductivityTable->sortBy ?: 'reply_count';
			$staffProductivityTable->quickSearch = 'name';
					
			/* Specify the filters and search options */
			$staffProductivityTable->filters = array(
				'last_24_hours'	=> array( 'reply_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) )->getTimestamp() ),
				'last_7_days'	=> array( 'reply_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P7D' ) )->getTimestamp() ),
				'last_30_days'	=> array( 'reply_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P30D' ) )->getTimestamp() ),
			);
	
			/* Buttons for each member */
			$staffProductivityTable->rowButtons = function( $row )
			{
				return array(
					'report'	=> array(
						'icon'		=> 'search',
						'title'		=> 'view_report',
						'link'		=> \IPS\Http\Url::internal('app=nexus&module=support&controller=reports&do=staff')->setQueryString( 'id', $row['reply_member'] ),
					),
				);	
			};
			
			$activeTabContents = (string) $staffProductivityTable;
		}
		
		/* Replies */
		elseif ( $activeTab === 'replies' )
		{
			$staffRepliesChart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=nexus&module=support&controller=reports&tab=replies' ), 'nexus_support_replies', 'reply_date', '',
				array(
					'vAxis'		=> array( 'title' => \IPS\Member::loggedIn()->language()->addToStack('replies_made') ),
				),
				'LineChart', 'daily', array( 'start' => \IPS\DateTime::create()->sub( new \DateInterval( 'P1M' ) ), 'end' => 0 )
			);
			$staffRepliesChart->where[] = array( 'reply_type=?', \IPS\nexus\Support\Reply::REPLY_STAFF );
			$staffRepliesChart->groupBy	= 'reply_member';
			foreach( \IPS\nexus\Support\Request::staff() as $id => $name )
			{
				$staffRepliesChart->addSeries( $name, 'number', 'COUNT(*)', TRUE, $id );
			}
			
			$activeTabContents = (string) $staffRepliesChart;
		}
		
		/* Replies */
		elseif ( $activeTab === 'feedback_ratings' )
		{
			$staffRatingsChart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=nexus&module=support&controller=reports&tab=feedback_ratings' ), 'nexus_support_ratings', 'rating_date', '',
				array(
					'vAxis'		=> array( 'title' => \IPS\Member::loggedIn()->language()->addToStack('average_rating'), 'viewWindow' => array( 'min' => 0, 'max' => 5 ) ),
				),
				'ColumnChart', 'monthly'
			);
			$staffRatingsChart->plotZeros = FALSE;
			$staffRatingsChart->groupBy	= 'rating_staff';
			foreach( \IPS\nexus\Support\Request::staff() as $id => $name )
			{
				$staffRatingsChart->addSeries( $name, 'number', 'AVG(rating_rating)', TRUE, $id );
			}
			
			$activeTabContents = (string) $staffRatingsChart;
		}
		
		/* Latest Feedback */
		elseif ( $activeTab === 'latest_feedback' )
		{
			$table = new \IPS\Helpers\Table\Db( 'nexus_support_ratings', \IPS\Http\Url::internal('app=nexus&module=support&controller=reports&tab=latest_feedback') );
			$table->joins = array(
				array(
					'from'		=> 'nexus_support_replies',
					'where'		=> 'reply_id=rating_reply'
				),
				array(
					'from'		=> 'nexus_support_requests',
					'where'		=> 'r_id=reply_request'
				)
			);
			$table->sortBy = 'rating_date';
			$table->sortDirection = 'desc';
			$table->parsers = array(
				'reply_post'	=> function( $val )
				{
					return $val;
				}
			);
			
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate('support'), 'latestFeedback' );
			
			$activeTabContents = (string) $table;
		}
										
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('performance_reports');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=nexus&module=support&controller=reports" ) );
		}
	}
	
	/**
	 * Staff Report
	 *
	 * @return	void
	 */
	protected function staff()
	{
		/* Load */
		$id = \IPS\Request::i()->id;
		if ( !array_key_exists( $id, \IPS\nexus\Support\Request::staff() ) )
		{
			\IPS\Output::i()->error( 'node_error', '2X207/1', 404, '' );
		}
		$staff = \IPS\Member::load( $id );
		
		/* Tabs */
		$tabs = array(
			'productivity' 		=> 'productivity',
			'latest_replies'	=> 'latest_replies',
			'feedback_ratings'	=> 'feedback_ratings',
			'latest_feedback'	=> 'latest_feedback'
		);
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'productivity';
		$activeTabContents = '';
		
		/* Daily Productivity */
		if ( $activeTab === 'productivity' )
		{			
			/* Date Range */
			$where = array( array( 'reply_type=?', \IPS\nexus\Support\Reply::REPLY_STAFF ) );
			$timeframe = isset( \IPS\Request::i()->timeframe ) ? \IPS\Request::i()->timeframe : 'last_30_days';
			switch ( $timeframe )
			{
				case 'last_24_hours':
					$daysInTimeframe = 1;
					break;
				case 'last_7_days':
					$daysInTimeframe = 7;
					break;
				case 'last_30_days':
					$daysInTimeframe = 30;
					break;
			}
			$where[] = array( 'reply_date>?', \IPS\DateTime::ts( time() )->sub( new \DateInterval( "P{$daysInTimeframe}D" ) )->getTimestamp() );
						
			/* Init */
			$numberOfStaffMembers = \IPS\Db::i()->select( 'COUNT(DISTINCT reply_member)', 'nexus_support_replies', $where )->first();
			$now = \IPS\DateTime::ts( time() );
			$myOffset = $now->getTimezone()->getOffset( $now ) / 3600;
			
			/* Group */
			$span = isset( \IPS\Request::i()->span ) ? \IPS\Request::i()->span : 'day';
			$group = $span === 'day' ? '%H' : '%w';
			
			/* Type */
			$type = isset( \IPS\Request::i()->type ) ? \IPS\Request::i()->type : 'total';
			
			/* Get average replies by hour for this staff member */
			$thisStaffMember = array();
			foreach( \IPS\Db::i()->select( "COUNT(*) AS count, reply_date AS unixtime, DATE_FORMAT( FROM_UNIXTIME( reply_date ), '{$group}' ) AS hour", 'nexus_support_replies', array_merge( $where, array( array( 'reply_member=?', $staff->member_id ) ) ), NULL, NULL, 'hour' ) as $row )
			{				
				$_group = $span === 'day' ? \IPS\DateTime::ts( $row['unixtime'] )->format('G') : \IPS\DateTime::ts( $row['unixtime'] )->format('w');
				$thisStaffMember[ $_group ] = round( ( $type === 'average' ) ? ( $row['count'] / $daysInTimeframe ) : $row['count'], 1 );
			}
			
			/* Get average replies by hour for all staff members */
			if ( $numberOfStaffMembers > 1 )
			{
				foreach( \IPS\Db::i()->select( "COUNT(*) AS count, reply_date AS unixtime, DATE_FORMAT( FROM_UNIXTIME( reply_date ), '{$group}' ) AS hour", 'nexus_support_replies', $where, NULL, NULL, 'hour' ) as $row )
				{
					$_group = $span === 'day' ? \IPS\DateTime::ts( $row['unixtime'] )->format('G') : \IPS\DateTime::ts( $row['unixtime'] )->format('w');
					$allStaffMembers[ $_group ] = round( ( ( $type === 'average' ) ? ( $row['count'] / $daysInTimeframe ) : $row['count'] ) / $numberOfStaffMembers, 1 );
				}
			}
										
			/* Build Chart */
			$chart = new \IPS\Helpers\Chart;
			$chart->addHeader( \IPS\Member::loggedIn()->language()->addToStack('hour'), 'string' );
			$chart->addHeader( $staff->name, 'number' );
			if ( $numberOfStaffMembers > 1 )
			{
				$chart->addHeader( \IPS\Member::loggedIn()->language()->addToStack('all_staff_average'), 'number' );
			}
			if ( $span === 'day' )
			{
				foreach ( range( 0 - $myOffset, 23 - $myOffset ) as $hour )
				{
					$timestamp = mktime( 0, 0, 0 ) + ( $hour * 3600 );
													
					if ( $numberOfStaffMembers > 1 )
					{				
						$chart->addRow( array(
							\IPS\DateTime::ts( $timestamp )->localeTime( FALSE, FALSE ),
							isset( $thisStaffMember[ $hour + $myOffset ] ) ? $thisStaffMember[ $hour + $myOffset ] : 0,
							isset( $allStaffMembers[ $hour + $myOffset ] ) ? $allStaffMembers[ $hour + $myOffset ] : 0
						) );
					}
					else
					{
						$chart->addRow( array(
							\IPS\DateTime::ts( $timestamp )->localeTime( FALSE, FALSE ),
							isset( $thisStaffMember[ $hour + $myOffset ] ) ? $thisStaffMember[ $hour + $myOffset ] : 0
						) );
					}
				}
			}
			else
			{
				foreach ( range( 0, 6 ) as $day )
				{
					if ( $numberOfStaffMembers > 1 )
					{				
						$chart->addRow( array(
							\IPS\Member::loggedIn()->language()->addToStack("weekday_{$day}"),
							isset( $thisStaffMember[ $day ] ) ? $thisStaffMember[ $day ] : 0,
							isset( $allStaffMembers[ $day ] ) ? $allStaffMembers[ $day ] : 0
						) );
					}
					else
					{
						$chart->addRow( array(
							\IPS\Member::loggedIn()->language()->addToStack("weekday_{$day}"),
							isset( $thisStaffMember[ $day ] ) ? $thisStaffMember[ $day ] : 0
						) );
					}
				}
			}
			
			/* Display */
			$activeTabContents = \IPS\Theme::i()->getTemplate('supportreports')->timeChart( $chart->render( 'ColumnChart' ), \IPS\Http\Url::internal( "app=nexus&module=support&controller=reports&do=staff&id={$staff->member_id}&tab=productivity" )->setQueryString( array(
				'span'		=> $span,
				'timeframe'	=> $timeframe,
				'type'		=> $type
			) ) );
		}
		
		/* Latest Replies */
		elseif ( $activeTab === 'latest_replies' )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support.css', 'nexus', 'admin' ) );
			$table = new \IPS\Helpers\Table\Content( 'IPS\nexus\Support\Reply', \IPS\Http\Url::internal( "app=nexus&module=support&controller=reports&do=staff&id={$staff->member_id}&tab=latest_replies" ), array( array( 'reply_type=? AND reply_member=?', \IPS\nexus\Support\Reply::REPLY_STAFF, $staff->member_id ) ) );
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'tables', 'core', 'front' ), 'table' );
			$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'supportreports' ), 'supportReplyRows' );
			$table->sortBy = 'reply_date';
			$activeTabContents = (string) $table;
		}
		
		/* Feedback */
		elseif ( $activeTab === 'feedback_ratings' )
		{
			$staffRatingsChart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( "app=nexus&module=support&controller=reports&do=staff&id={$staff->member_id}&tab=feedback_ratings" ), 'nexus_support_ratings', 'rating_date', '',
				array(
					'vAxis'		=> array( 'title' => \IPS\Member::loggedIn()->language()->addToStack('average_rating'), 'viewWindow' => array( 'min' => 0, 'max' => 5 ) ),
					'legend'	=> 'none'
				),
				'LineChart', 'monthly'
			);
			$staffRatingsChart->plotZeros = FALSE;
			$staffRatingsChart->addSeries( $staff->name, 'number', 'AVG(rating_rating)', FALSE );
			
			$activeTabContents = (string) $staffRatingsChart;
		}
		
		/* Latest Feedback */
		elseif ( $activeTab === 'latest_feedback' )
		{
			$table = new \IPS\Helpers\Table\Db( 'nexus_support_ratings', \IPS\Http\Url::internal("app=nexus&module=support&controller=reports&do=staff&id={$staff->member_id}&tab=latest_feedback"), array( 'rating_staff=?', $staff->member_id ) );
			$table->joins = array(
				array(
					'from'		=> 'nexus_support_replies',
					'where'		=> 'reply_id=rating_reply'
				),
				array(
					'from'		=> 'nexus_support_requests',
					'where'		=> 'r_id=reply_request'
				)
			);
			$table->sortBy = 'rating_date';
			$table->sortDirection = 'desc';
			$table->parsers = array(
				'reply_post'	=> function( $val )
				{
					return $val;
				}
			);
			
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate('support'), 'latestFeedback' );
			
			$activeTabContents = (string) $table;
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
		}
		else
		{
			\IPS\Output::i()->title = $staff->name;
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=nexus&module=support&controller=reports&do=staff&id={$staff->member_id}" ) );
		}
	}
}