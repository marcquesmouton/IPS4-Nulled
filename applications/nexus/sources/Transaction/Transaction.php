<?php
/**
 * @brief		Transaction Model
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Transaction Model
 */
class _Transaction extends \IPS\Patterns\ActiveRecord
{
	const STATUS_PAID			= 'okay'; // Transaction has been paid successfully
	const STATUS_PENDING		= 'pend'; // Payment not yet submitted (for example, has been redirected to external site)
	const STATUS_WAITING		= 'wait'; // Waiting for user (for example, a check is in the mail). Manual approval will be required
	const STATUS_HELD			= 'hold'; // Transaction is being held for approval
	const STATUS_REVIEW			= 'revw'; // Transaction, after being held for approval, has been flagged for review by staff
	const STATUS_REFUSED		= 'fail'; // Transaction was refused
	const STATUS_REFUNDED		= 'rfnd'; // Transaction has been refunded in fulll
	const STATUS_PART_REFUNDED	= 'prfd'; // Transaction has been partially refunded
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'nexus_transactions';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 't_';
	
	/**
	 * Load and check permissions
	 *
	 * @return	\IPS\Content\Item
	 * @throws	\OutOfRangeException
	 */
	public static function loadAndCheckPerms( $id )
	{
		$obj = static::load( $id );
				
		if ( $obj->member->member_id !== \IPS\Member::loggedIn()->member_id )
		{
			throw new \OutOfRangeException;
		}

		return $obj;
	}
	
	/**
	 * Get statuses
	 *
	 * @return	array
	 */
	public static function statuses()
	{
		$options = array();
		$reflection = new \ReflectionClass( get_called_class() );
		foreach ( $reflection->getConstants() as $k => $v )
		{
			if ( mb_substr( $k, 0, 7 ) === 'STATUS_' )
			{
				$options[ $v ] = "tstatus_{$v}";
			}
		}
		return $options;	
	}
	
	/**
	 * Get transaction table
	 *
	 * @param	array	$where	Where clause
	 * @param	string	$ref	Referer
	 * @return	\IPS\Helpers\Table\Db
	 */
	public static function table( $where = array(), \IPS\Http\Url $url, $ref = 't' )
	{
		$where[] = array( 't_status<>?', static::STATUS_PENDING );
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'nexus_transactions', $url, $where );
		$table->sortBy = $table->sortBy ?: 't_date';

		/* Format Columns */
		$table->include = array( 't_status', 't_id', 't_method', 't_member', 't_amount', 't_invoice', 't_date' );
		$table->parsers = array(
			't_status'	=> function( $val )
			{
				return \IPS\Theme::i()->getTemplate('transactions', 'nexus')->status( $val );
			},
			't_method'	=> function( $val )
			{
				if ( $val )
				{
					try
					{
						return \IPS\nexus\Gateway::load( $val )->_title;
					}
					catch ( \OutOfRangeException $e )
					{
						return '';
					}
				}
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack('account_credit');
				}
			},
			't_member'	=> function ( $val )
			{
				return \IPS\Theme::i()->getTemplate('global', 'nexus')->userLink( \IPS\Member::load( $val ) );
			},
			't_amount'	=> function( $val, $row )
			{
				return (string) new \IPS\nexus\Money( $val, $row['t_currency'] );
			},
			't_invoice'	=> function( $val )
			{
				try
				{
					return \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val ) );
				}
				catch ( \OutOfRangeException $e )
				{
					return '';
				}
			},
			't_date'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val );
			}
		);
				
		/* Buttons */
		$table->rowButtons = function( $row ) use ( $ref )
		{
			return array_merge( array(
				'view'	=> array(
					'icon'	=> 'search',
					'title'	=> 'transaction_view',
					'link'	=> \IPS\Http\Url::internal( "app=nexus&module=payments&controller=transactions&do=view&id={$row['t_id']}" )
				),
			), \IPS\nexus\Transaction::constructFromData( $row )->buttons( $ref ) );
		};
		
		return $table;	
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->status = static::STATUS_PENDING;
		$this->date = new \IPS\DateTime;
		$this->fraud_blocked = NULL;
		$this->extra = array();
	}
	
	/**
	 * Get member
	 *
	 * @return	\IPS\Member
	 */
	public function get_member()
	{
		return \IPS\nexus\Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	\IPS\Member
	 * @return	void
	 */
	public function set_member( \IPS\Member $member )
	{
		$this->_data['member'] = (int) $member->member_id;
	}
	
	/**
	 * Get invoice
	 *
	 * @return	\IPS\nexus\Invoice
	 */
	public function get_invoice()
	{
		return \IPS\nexus\Invoice::load( $this->_data['invoice'] );
	}
	
	/**
	 * Set invoice
	 *
	 * @param	\IPS\nexus\Invoice
	 * @return	void
	 */
	public function set_invoice( \IPS\nexus\Invoice $invoice )
	{
		$this->_data['invoice'] = $invoice->id;
	}
	
	/**
	 * Get payment gateway
	 *
	 * @return	\IPS\nexus\Gateway
	 */
	public function get_method()
	{
		if ( !isset( $this->_data['method'] ) or $this->_data['method'] === 0 )
		{
			return 0;
		}
		
		try
		{
			return \IPS\nexus\Gateway::load( $this->_data['method'] );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Set payment gateway
	 *
	 * @param	\IPS\nexus\Gateway
	 * @return	void
	 */
	public function set_method( \IPS\nexus\Gateway $gateway )
	{
		$this->_data['method'] = $gateway->id;
	}
	
	/**
	 * Get amount
	 *
	 * @return	\IPS\nexus\Money
	 */
	public function get_amount()
	{		
		$amount = new \IPS\nexus\Money( $this->_data['amount'], $this->_data['currency'] );
		return $amount->round(); // A transaction has to be a valid monetary amount, eg not $1.005
	}
	
	/**
	 * Set total
	 *
	 * @param	\IPS\nexus\Money	$amount	The total
	 * @return	void
	 */
	public function set_amount( \IPS\nexus\Money $amount )
	{
		$amount = $amount->round();  // A transaction has to be a valid monetary amount, eg not $1.005
		$this->_data['amount'] = $amount->amount;
		$this->_data['currency'] = $amount->currency;
	}
	
	/**
	 * Get date
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_date()
	{
		return \IPS\DateTime::ts( $this->_data['date'] );
	}
	
	/**
	 * Set date
	 *
	 * @param	\IPS\DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_date( \IPS\DateTime $date )
	{
		$this->_data['date'] = $date->getTimestamp();
	}
	
	/**
	 * Get extra information
	 *
	 * @return	mixed
	 */
	public function get_extra()
	{
		return json_decode( $this->_data['extra'], TRUE );
	}
	
	/**
	 * Set extra information
	 *
	 * @param	mixed	$extra	The data
	 * @return	void
	 */
	public function set_extra( $extra )
	{
		$this->_data['extra'] = json_encode( $extra );
	}
	
	/**
	 * Get MaxMind data
	 *
	 * @return	\IPS\nexus\Fraud\MaxMind\Response 
	 */
	public function get_fraud()
	{
		return $this->_data['fraud'] ? \IPS\nexus\Fraud\MaxMind\Response::buildFromJson( $this->_data['fraud'] ) : NULL;
	}
	
	/**
	 * Set MaxMind data
	 *
	 * @param	\IPS\nexus\Fraud\MaxMind\Response 	$maxMind	The data
	 * @return	void
	 */
	public function set_fraud( \IPS\nexus\Fraud\MaxMind\Response $maxMind )
	{
		$this->_data['fraud'] = (string) $maxMind;
	}
	
	/**
	 * Get triggered fraud rule
	 *
	 * @return	\IPS\nexus\Fraud\Rule|NULL
	 */
	public function get_fraud_blocked()
	{
		try
		{
			return $this->_data['fraud_blocked'] ? \IPS\nexus\Fraud\Rule::load( $this->_data['fraud_blocked'] ) : NULL;
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Set triggered fraud rule
	 *
	 * @param	\IPS\nexus\Fraud\Rule 	$rule The rule
	 * @return	void
	 */
	public function set_fraud_blocked( \IPS\nexus\Fraud\Rule $rule = NULL )
	{
		$this->_data['fraud_blocked'] = $rule ? $rule->id : 0;
	}
	
	/**
	 * Get partial refund amount
	 *
	 * @return	\IPS\nexus\Money
	 */
	public function get_partial_refund()
	{		
		return new \IPS\nexus\Money( $this->_data['partial_refund'], $this->_data['currency'] );
	}
	
	/**
	 * Set partial refund amount
	 *
	 * @param	\IPS\nexus\Money	$amount	The total
	 * @return	void
	 */
	public function set_partial_refund( \IPS\nexus\Money $amount )
	{
		$this->_data['partial_refund'] = $amount->amount;
	}
	
	/**
	 * Get date transaction must be captured by (is set after authorisation. once captured, should be NULL)
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_auth()
	{
		return $this->_data['auth'] ? \IPS\DateTime::ts( $this->_data['auth'] ) : NULL;
	}
	
	/**
	 * Set date transaction must be captured by (is set after authorisation. once captured, should be NULL)
	 *
	 * @param	\IPS\DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_auth( \IPS\DateTime $date = NULL )
	{
		$this->_data['auth'] = $date === NULL ? NULL : $date->getTimestamp();
	}
	
	/**
	 * Check fraud rules and capture
	 *
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @return	void
	 * @throws	\LogicException
	 */
	public function checkFraudRulesAndCapture( $maxMind=NULL )
	{
		/* Run MaxMind */
		if ( \IPS\Settings::i()->maxmind_key )
		{
			if ( $maxMind === NULL )
			{
				$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
				$maxMind->setTransaction( $this );
			}
			
			$this->fraud = $maxMind->request();
			
			/* If MaxMind fails, stop here */
			if ( $this->fraud->err and \IPS\Settings::i()->maxmind_error == 'hold' )
			{
				$this->status = static::STATUS_HELD;
				$extra = $this->extra;
				$extra['history'][] = array( 's' => static::STATUS_HELD );
				$this->extra = $extra;
				$this->save();
				
				$this->member->log( 'transaction', array(
					'type'			=> 'paid',
					'status'		=> $this->status,
					'id'			=> $this->id,
					'invoice_id'	=> $this->invoice->id,
					'invoice_title'	=> $this->invoice->title,
				) );
			
				return;
			}
		}
		
		/* Check Fraud Rules */
		foreach ( \IPS\nexus\Fraud\Rule::roots() as $rule )
		{
			if ( $rule->matches( $this ) )
			{
				$this->fraud_blocked = $rule;
			}
		}
		
		if ( $this->fraud_blocked and $this->fraud_blocked->action !== static::STATUS_PAID )
		{
			$this->status = $this->fraud_blocked->action;
			$extra = $this->extra;
			$extra['history'][] = array( 's' => $this->fraud_blocked->action );
			$this->extra = $extra;
			$this->save();
			
			$this->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> $this->status,
				'id'			=> $this->id,
				'invoice_id'	=> $this->invoice->id,
				'invoice_title'	=> $this->invoice->title,
			) );
			
			return;
		}
		
		$this->capture();
		
		$this->member->log( 'transaction', array(
			'type'			=> 'paid',
			'status'		=> static::STATUS_PAID,
			'id'			=> $this->id,
			'invoice_id'	=> $this->invoice->id,
			'invoice_title'	=> $this->invoice->title,
		) );
		
		$this->approve();
	}
	
	/**
	 * Capture
	 *
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture()
	{
		$this->method->capture( $this );
		$this->auth = NULL;
		$this->save();
	}
	
	/**
	 * Approve
	 *
	 * @param	\IPS\Member|NULL	$by	The staff member approving, or NULL if it's automatic
	 * @return	void
	 */
	public function approve( $by = NULL )
	{		
		/* Set the transaction as paid */
		$this->status = static::STATUS_PAID;
		$extra = $this->extra;
		if ( $by )
		{
			$extra['history'][] = array( 's' => static::STATUS_PAID, 'on' => time(), 'by' => $by->member_id );
		}
		else
		{
			$extra['history'][] = array( 's' => static::STATUS_PAID );
		}
		$this->extra = $extra;
		$this->save();
		
		/* Mark the invoice paid if necessary */
		if ( $this->invoice->amountToPay()->amount <= 0 )
		{	
			$this->invoice->markPaid();
		}
	}
	
	/**
	 * Refund
	 *
	 * @param	string		$refundMethod	"gateway", "credit", or "none"
	 * @param	float|NULL	$amount			Amount (NULL for full amount)
	 * @return	void
	 * @throws	\Exception
	 */
	public function refund( $refundMethod='gateway', $amount=NULL )
	{
		/* What's the amount? */
		if ( !$amount or $amount == $this->amount->amount )
		{
			$amount = NULL;
		}
										
		/* Refund */
		$refundReference = NULL;
		if ( $refundMethod === 'gateway' and method_exists( $this->method, 'refund' ) )
		{
			$refundReference = $this->method->refund( $this, $amount );
		}
		elseif ( $refundMethod === 'credit' )
		{
			$credits = $this->member->cm_credits;
			$credits[ $this->amount->currency ]->amount += ( $amount ?: $this->amount->amount );
			$this->member->cm_credits = $credits;
			$this->member->save();
		}
		
		/* Update transaction */
		$extra = $this->extra;
		if ( $refundMethod === 'none' )
		{
			$this->status = static::STATUS_REFUSED;
			$extra['history'][] = array( 's' => static::STATUS_REFUSED, 'by' => \IPS\Member::loggedIn()->member_id, 'on' => time() );
			
			$this->member->log( 'transaction', array(
				'type'		=> 'status',
				'status'	=> static::STATUS_REFUSED,
				'id'		=> $this->id
			) );
		}
		else
		{
			if ( $amount === NULL )
			{
				$this->status = static::STATUS_REFUNDED;
				$extra['history'][] = array( 's' => static::STATUS_REFUNDED, 'by' => \IPS\Member::loggedIn()->member_id, 'on' => time(), 'to' => $refundMethod, 'ref' => $refundReference );
				
				$this->member->log( 'transaction', array(
					'type'		=> 'status',
					'status'	=> static::STATUS_REFUNDED,
					'id'		=> $this->id,
					'refund'	=> $refundMethod
				) );
			}
			else
			{
				$this->partial_refund->amount += $amount;
				$extra['history'][] = array( 's' => static::STATUS_PART_REFUNDED, 'by' => \IPS\Member::loggedIn()->member_id, 'on' => time(), 'to' => $refundMethod, 'amount' => $amount, 'ref' => $refundReference );
				if ( $amount >= $this->amount->amount )
				{
					$this->status = static::STATUS_REFUNDED;
					$extra['history'][] = array( 's' => static::STATUS_REFUNDED );
				}
				else
				{
					$this->status = static::STATUS_PART_REFUNDED;
				}
				
				$this->member->log( 'transaction', array(
					'type'		=> 'status',
					'status'	=> static::STATUS_PART_REFUNDED,
					'id'		=> $this->id,
					'refund'	=> $refundMethod,
					'amount'	=> $amount,
					'currency'	=> $this->currency
				) );
			}
		}
		$this->extra = $extra;
		$this->save();
	}
	
	/**
	 * Send Notification
	 *
	 * @return	void
	 */
	public function sendNotification()
	{		
		switch ( $this->status )
		{	
			case static::STATUS_PAID:
				$key = 'transactionApproved';
				$emailKey = 'payment_received';
				break;
							
			case static::STATUS_WAITING:
				$key = 'transactionWaiting';
				$emailKey = 'payment_waiting';
				break;
				
			case static::STATUS_HELD:
				$key = 'transactionHeld';
				$emailKey = 'payment_held';
				break;
				
			case static::STATUS_REFUSED:
				$key = 'transactionFailed';
				$emailKey = 'payment_failed';
				break;
				
			case static::STATUS_REFUNDED:
			case static::STATUS_PART_REFUNDED:
				$key = 'transactionRefunded';
				$emailKey = 'payment_refunded';
				break;

			default:
				throw new \RuntimeException;
				break;
		}
				
		\IPS\Email::buildFromTemplate( 'nexus', $key, array( $this, $this->invoice, $this->invoice->summary() ) )
			->send(
				$this->invoice->member,
				array_map(
					function( $contact )
					{
						return $contact->alt_id->email;
					},
					iterator_to_array( $this->invoice->member->alternativeContacts( array( 'billing=1' ) ) )
				),
				( ( in_array( $emailKey, explode( ',', \IPS\Settings::i()->nexus_notify_copy_types ) ) AND \IPS\Settings::i()->nexus_notify_copy_email ) ? explode( ',', \IPS\Settings::i()->nexus_notify_copy_email ) : array() )
			);
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			$this->_url = \IPS\Http\Url::internal( "app=nexus&module=payments&controller=checkout&do=transaction&id={$this->invoice->id}&t={$this->id}", 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https );
		}

		return $this->_url;
	}

	/**
	 * ACP URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function acpUrl()
	{
		return \IPS\Http\Url::internal( "app=nexus&module=payments&controller=transactions&do=view&id={$this->id}", 'admin' );
	}
	
	/**
	 * ACP Buttons
	 *
	 * @param	string	$ref	Referer
	 * @return	array
	 */
	public function buttons( $ref='v' )
	{
		$url = $this->acpUrl()->setQueryString( 'r', $ref );
		$return = array();
		
		/* Approve button */
		if ( in_array( $this->status, array( static::STATUS_PENDING, static::STATUS_WAITING, static::STATUS_HELD, static::STATUS_REVIEW ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_edit' ) )
		{
			$return['approve'] = array(
				'title'		=> $this->auth ? 'transaction_capture' : 'transaction_approve',
				'icon'		=> 'check',
				'link'		=> $url->setQueryString( array( 'do' => 'approve' ) ),
				'data'		=> array( 'confirm' => '' )
			);
		}
		
		/* Review button */
		if ( in_array( $this->status, array( static::STATUS_PENDING, static::STATUS_WAITING, static::STATUS_HELD ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_edit' ) )
		{
			$return['review'] = array(
				'title'		=> 'transaction_flag_review',
				'icon'		=> 'flag',
				'link'		=> $url->setQueryString( array( 'do' => 'review' ) ),
			);
		}
				
		/* Void button */
		if ( ( $this->status === static::STATUS_PENDING or ( $this->auth and in_array( $this->status, array( static::STATUS_HELD, static::STATUS_REVIEW ) ) ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_edit' ) )
		{
			$return['void'] = array(
				'title'		=> 'transaction_void',
				'icon'		=> 'times',
				'link'		=> $url->setQueryString( array( 'do' => 'void' ) ),
				'data'		=> array( 'confirm' => '' )
			);
		}
		
		/* Cancal button for manual */
		elseif ( $this->status === static::STATUS_WAITING and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_edit' ) )
		{
			$return['void'] = array(
				'title'		=> 'cancel',
				'icon'		=> 'times',
				'link'		=> $url->setQueryString( array( 'do' => 'void', 'override' => 1 ) ),
				'data'		=> array( 'confirm' => '' )
			);
		}
		
		/* Refund button */
		elseif ( in_array( $this->status, array( static::STATUS_PAID, static::STATUS_HELD, static::STATUS_REVIEW, static::STATUS_PART_REFUNDED ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_refund' ) )
		{
			$return['refund'] = array(
				'title'		=> 'transaction_refund',
				'icon'		=> 'reply',
				'link'		=> $url->setQueryString( array( 'do' => 'refund' ) ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack( 'transaction_refund_title', FALSE, array( 'sprintf' => array( $this->amount ) ) ) )
			);
		}
		
		/* Delete button */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_delete' ) )
		{
			$return['delete'] = array(
				'title'		=> 'delete',
				'icon'		=> 'times-circle',
				'link'		=> $url->setQueryString( 'do', 'delete' ),
				'data'		=> array( 'confirm' => '', 'confirmSubMessage' => \IPS\Member::loggedIn()->language()->addToStack('trans_delete_warning') )
			);
		}
		
		return $return;
	}
	
	/**
	 * History
	 *
	 * @return	array
	 */
	public function history()
	{
		$return = array();
		$extra = $this->extra;
		
		if ( isset( $extra['history'] ) )
		{
			return $extra['history'];
		}
		else
		{
			if ( !in_array( $this->status, array( static::STATUS_PENDING, static::STATUS_WAITING ) ) )
			{
				$return[] = array(
					's'		=> $this->status,
					'by'	=> isset( $extra['status_by'] ) ? $extra['status_by'] : NULL,
					'on'	=> isset( $extra['status_on'] ) ? $extra['status_on'] : NULL,
				);
			}
		}
		
		return $return;
	}
}