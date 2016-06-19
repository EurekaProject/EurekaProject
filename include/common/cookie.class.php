<?php

	// mysql // mysqli class
	require_once( "db.class.php" );

	// get user config
	session_start();
	if ( !isset( $user ) )
	{
		require_once "user.class.php";
		$user = new stduser( null , null );
	}
	session_write_close();

	// id user
	if ( !$user || !$user->data[ 'id' ] )
	{
		$id = $_SERVER["REMOTE_ADDR"];
	}
	else
	{
		$id = $user->data[ 'id' ];
	}

	class cookie
	{

		private $connection;
		private $id;

		public function __construct( $host = '127.0.0.1' , $user = 'root' , $pass = 'root' , $bdd = 'eureka_cookie' )
		{
			global $id;
			$this->id = $id;

			// connexion sql
			$this->connection = new db( $bdd , $host , $user , $pass );

			// pas de connexion, tentative de création de la bdd
			if ( $this->connection->status == -1 )
			{
				// creation de la table
				if ( !$this->connection->create_db( $bdd ) )
				{
					die( 'create database ' . $bdd . ' failed in cookie.php' );
				}
			}

			// check table
			$check_table = $this->connection->query( 'SHOW TABLES LIKE "cookie"' );
			if ( $check_table !== false && $check_table->num_rows() == 0 )
			{
				// création de la table
				$createTable = $this->connection->query( '
					CREATE TABLE IF NOT EXISTS `cookie` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `id_user` varchar(15) NOT NULL,
					  `cookie_key` varchar(255) NOT NULL,
					  `cookie_value` varchar(255) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;' );

				if ( !$createTable )
				{
					die( 'create table cookie failed in cookie.php' );
				}
			}

		}

		public function read( $key )
		{
			if ( $this->connection->status == -1 )
			{
				die;
			}

			$result = $this->connection->query( 'SELECT cookie_value FROM cookie WHERE cookie_key="' . $this->connection->escape_string( $key ) . '" AND id_user="' . $this->id . '"' );

			if ( $result !== false && $result->num_rows() > 0 )
			{
				return $result->fetch_array()[ 'cookie_value' ];
			}

			return "__null__";
		}

		public function write( $key , $value )
		{
			if ( $this->connection->status == -1 )
			{
				die;
			}

			$cookie_key = $this->connection->escape_string( $key );
			$cookie_value = $this->connection->escape_string( $value );

			// key already exist, upadte them
			if ( $this->read( $key ) !== '__null__' )
			{
				$result = $this->connection->query( 'UPDATE cookie SET cookie_value = "' . $cookie_value . '" WHERE cookie_key = "' . $cookie_key . '" AND id_user="' . $this->id . '"' );
			}
			// or create them
			else
			{
				$result = $this->connection->query( 'INSERT INTO cookie ( id_user , cookie_key , cookie_value ) VALUES ( "' . $this->id . '" , "' . $cookie_key . '" , "' . $cookie_value . '" ) ' );
			}


			if ( !$result )
			{
				die( 'fail to insert || update new value' );
			}

		}

		public function delete( $key )
		{
			if ( $this->connection->status == -1 )
			{
				die;
			}

			$this->connection->query( 'DELETE FROM cookie WHERE cookie_key = "' . $this->connection->escape_string( $key ) . '" AND id_user="' . $this->id . '" ' );

		}

		public function deleteAll()
		{
			if ( $this->connection->status == -1 )
			{
				die;
			}

			$this->connection->query( 'DELETE FROM cookie WHERE id_user="' . $this->id . '" ' );
		}

	}
