<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		5 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

$options	= array(
	new \IPS\Helpers\Form\Radio( '40000_qa_forum', 0, TRUE, array( 'options' => array( 0 => '40000_qa_forum_0', 1 => '40000_qa_forum_1' ) ) )
);