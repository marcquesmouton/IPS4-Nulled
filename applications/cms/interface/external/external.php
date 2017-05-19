<?php
/**
 * @brief		Pages External Block Gateway
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		30 Jun 2015
 * @version		SVN_VERSION_NUMBER
 *
 */

require_once str_replace( 'applications/cms/interface/external/external.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\Front::i();

$id = \IPS\Request::i()->blockid;
$k = \IPS\Request::i()->widgetid;
$block = \IPS\cms\Blocks\Block::display( $id );

\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_external.js', 'cms', 'front' ) );
\IPS\Output::i()->globalControllers[] = 'cms.front.external.communication';
\IPS\Output::i()->sendOutput( \IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $block ) ), 200, 'text/html' );