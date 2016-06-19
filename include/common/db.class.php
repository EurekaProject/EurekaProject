<?php

	//$root = $_SERVER['DOCUMENT_ROOT'];
	//require_once ($root . "/config.php");
	
abstract class dbresultabs
{
	abstract function error();
	abstract function num_rows();
	abstract function value($row=0,$col=-1);
	abstract function fetch_array();
};

	abstract class dbabs
	{
		protected $connection;
		public $status;
		private $dbname;
		public $debug = false;
		
		public function import( $filename , $dbname = false )
		{
			if ( $this->status == -1 )
				return false;

			if ( $dbname == false )
			{
				$dbname = $this->dbname;
			}
			else
			{
				$this->select_db( $dbname );
			}
			// Temporary variable, used to store current query
			$templine = '';
			// Read in entire file
			$lines = file( $filename );
			if (!$lines)
				error_log("file ".$filename." not found!!!");
			// Loop through each line
			foreach ( $lines as $line )
			{
				// Skip it if it's a comment
				if ( substr( trim( $line ) , 0 , 2 ) == '--' || $line == '' )
					continue;

				// Add this line to the current segment
				$templine .= trim(preg_replace('/\s\s+/','',$line));
				// If it has a semicolon at the end, it's the end of the query
				if ( substr( trim( $line ) , -1 , 1 ) == ';' ) {
					// Perform the query
					$mysql_result = $this->query( $templine );
					if ( $this->debug )
						error_log( $templine . ' ' . $mysql_result );
					if ( $mysql_result == false )
					{
						error_log("Import error on : ".$templine);
						return $mysql_result;
					}
					// Reset temp variable to empty
					$templine = '';
				}
			}

			return true;

		}

	}
	
	// Use MySQL if requested or if mysqli not loaded
	if ( !extension_loaded( "mysqli" ) )
	{
		echo 'use MySQL';
		require_once( "dbmysql.class.php" );
	}
	else
	{
		require_once( "dbmysqli.class.php" );
	}

?>
