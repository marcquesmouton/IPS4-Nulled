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

$SQL[] = "ALTER TABLE ccs_blocks CHANGE block_cache_ttl block_cache_ttl VARCHAR(10) NOT NULL DEFAULT '0';";
