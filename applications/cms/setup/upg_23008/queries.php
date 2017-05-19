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

$SQL[] = "UPDATE ccs_database_fields SET field_is_numeric = '1' WHERE field_key='article_date';";

