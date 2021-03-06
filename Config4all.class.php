<?php

/**
 * @desc Config4all - Configuration files reader/ loader
 * @author André Filipe <andre.r.flip@gmail.com>
 * 
 * @link https://github.com/ReiDuKuduro/Config4all
 * @link http://masnathan.users.phpclasses.org/package/8111-PHP-Load-configuration-files-in-multiple-formats.html
 * 
 * @version 0.0.1 - 22-06-2013 16:23:46
 *     - release into the wild
 * 
 */
class Config4all {
	
	private static $_me = null;

	/**
	 * Configs are stored here
	 * @var array
	 */
	private $_configs = array();
	
	public function __construct() 
    { 
    }
	
	/**
	 * Returns it self
	 * @return obj
	 */
	static public function getInstance()
	{
		if ( !self::$_me instanceof Config4all )
			self::$_me = new self();
		
		return self::$_me;
	}
	
	/**
	 * Loads the configs on the desired files
	 * @param args Files to load
	 * @throws Exception When the supposed file path is not a string
	 * @throws Exception If the file doesn't exist
	 */
	public function load()
	{
		$data =  func_get_args();
		$files = array();
		foreach ($data as $value )
		{
			if ( !is_string( $value ) )
				throw new Exception( "Invalid parameter passed on Config4all -> load function. All parameters must be string!" );
			
			$files = array_merge( $files, glob( $value ) );
		}
		
		foreach ( $files as $file ) {
			if ( !file_exists( $file ) )
				throw new Exception( sprintf( "The file \"%s\" doesn't exist!", $this -> _getExtension( $file ) ) );
			
			switch ( $this -> _getExtension( $file ) ) 
			{
				case 'php':
					$this -> _importPhp( $file );
					break;
				case 'xml':
					$this -> _importXml( $file );
					break;
				case 'ini':
					$this -> _importIni( $file );
					break;
				case 'json':
					$this -> _importJson( $file );
					break;
				case 'yml':
					$this -> _importYml( $file );
					break;
				default:
					throw new Exception("Config4all doesn't support the file format: " . $this -> _getExtension( $file ) );
					break;
			}
		}
		
		return self::getInstance();
	}
	
	/**
	 * Imports the data/ configs from a XML file
	 * @param string	$filePath
	 */
	private function _importPhp( $filePath )
	{
		$array = $this -> _getName( $filePath );
		$$array = array();
		
		require $filePath;
		
		$this -> _configs[ $array ] = $$array;
	}
	
	/**
	 * Imports the data/ configs from a XML file
	 * @param string	$filePath
	 */
	private function _importXml( $filePath )
	{
		if ( !function_exists( 'simplexml_load_file' ) )
			throw new Exception( "You need the XML extension for PHP to parse a XML file" );
				
		$this -> _configs[ $this -> _getName( $filePath ) ] = json_decode( json_encode( (array) simplexml_load_file( $filePath ) ), true );
	}
	
	/**
	 * Imports the data/ configs from a INI file
	 * @param string	$filePath
	 */
	private function _importIni( $filePath )
	{
		$this -> _configs = array_merge( $this -> _configs, parse_ini_file( $filePath, true ) );
	}
	
	/**
	 * Imports the data/ configs from a JSON file
	 * @param string	$filePath
	 * @throws Exception When the extension to load parse JSON is not loaded
	 * @throws Exception When there is some error at parsing the JSON file
	 */
	private function _importJson( $filePath )
	{
		if ( !function_exists( 'json_decode' ) )
			throw new Exception( "You need the JSON extension for PHP to parse a JSON file" );
		
		$array = json_decode( file_get_contents( $filePath ), true );
		
		switch ( json_last_error() ) {
			case JSON_ERROR_NONE:
				$this -> _configs = array_merge( $this -> _configs, $array );
				break;
	        case JSON_ERROR_DEPTH:
	            throw new Exception("Maximum stack depth exceeded");
	        break;
	        case JSON_ERROR_STATE_MISMATCH:
	            throw new Exception("Underflow or the modes mismatch");
	        break;
	        case JSON_ERROR_CTRL_CHAR:
	            throw new Exception("Unexpected control character found");
	        break;
	        case JSON_ERROR_SYNTAX:
	            throw new Exception("Syntax error, malformed JSON");
	        break;
	        case JSON_ERROR_UTF8:
	            throw new Exception("Malformed UTF-8 characters, possibly incorrectly encoded");
	        break;
	        default:
	            throw new Exception("Unknown error on JSON file");
	        break;
    	}
	}

	/**
	 * Imports the data/ configs from a Yml file
	 * @todo add yml parser
	 */
	private function _importYml( $filePath ){
		
	}
	
	/**
	 * Gets the extension of the file
	 * @param string	$filePath
	 * @return string
	 */
	private function _getExtension( $filePath )
	{
		return pathinfo( $filePath, PATHINFO_EXTENSION );
	}
	
	/**
	 * Gets the filename of the file
	 * @param string	$filePath
	 * @return string
	 */
	private function _getName( $filePath )
	{
		return pathinfo( $filePath, PATHINFO_FILENAME );
	}
	
	/**
	 * Empty all the loaded configs
	 */
	public function clear()
	{
		$this -> _configs = array();
	}
	
	/**
	 * Returns all configs if no param is passed, returns a specific config if it's name is defined
	 * @param args Used to find the desired position on the configs array
	 * @return array|string|null
	 */
	public function get()
	{
		$data =  func_get_args();
		if ( empty( $data ) )
			return $this -> _configs;
		
		$tmp_config = $this -> _configs;
		foreach ( $data as $config_position )
		{
			if ( isset( $tmp_config[ $config_position ] ) )
				$tmp_config = $tmp_config[ $config_position ];
			else
				return null;
		}
		
		return $tmp_config;
	}
	
	/**
	 * Sets a new config
	 * @param args Used to set the desired position on the configs array and it's value, the last argument will be the value to set
	 * @throws Exception If no parameter is passed
	 * @return obj
	 */
	public function set()
	{
		$data 	=  func_get_args();
		$value 	= null;
		
		if ( empty( $data ) )
			throw new Exception( "Missing argument!" );
		
		if ( count( $data ) > 1 )
			$value = array_pop( $data );
		
		$data 	= array_reverse( $data );
		$tmp 	= array();
		
		foreach ( $data as $new_position ) {
			if ( empty( $tmp ) )
				$tmp = array( $new_position => $value );
			else
				$tmp = array( $new_position => $tmp );
		}
		
		//Merging the arrays
		$this -> _configs = array_replace_recursive( $this -> _configs, $tmp );
		
		return self::$_me;
	}
}
