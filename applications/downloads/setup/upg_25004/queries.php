<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
+---------------------------------------------------------------------------
*/

/* Downloads upgrade */

$SQL[] = "ALTER TABLE downloads_files CHANGE file_size file_size BIGINT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files_records CHANGE record_size record_size BIGINT NOT NULL DEFAULT '0';";
