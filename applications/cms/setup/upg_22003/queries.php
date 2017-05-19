<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
+---------------------------------------------------------------------------
*/

/* IP.Content upgrade */

$SQL[] = "ALTER TABLE ccs_pages CHANGE page_cache_ttl page_cache_ttl VARCHAR( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_page_wizard CHANGE wizard_cache_ttl wizard_cache_ttl VARCHAR( 10 ) NOT NULL DEFAULT '0';";