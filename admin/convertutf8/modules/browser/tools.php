<?php
/**
 * @brief		Web conversion process
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\modules\browser;
use \IPSUtf8\Output\Browser\Template;

/**
 * Web Conversion process
 */
class tools extends \IPSUtf8\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function manage( $msg=null )
	{
		$isUtf8 = (boolean) ( \IPSUtf8\Convert::i()->database_charset == 'utf8' OR \IPSUtf8\Session::i()->current_charset == 'utf-8' );
		\IPSUtf8\Output\Browser::i()->output = Template::tools( $isUtf8, $msg );
	}
	
	/**
	 * Check and repair collation
	 *
	 * @return	void
	 */
	public function collation()
	{
		\IPSUtf8\Convert::i()->fixCollation();
		
		$this->manage( "Collation checked and fixed where appropriate" );
	}
}