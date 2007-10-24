<?php

/**
 * Used to connect to YouTube API via REST interface.
 */
class YoutubeService extends RestfulService {
	private static $api_key;
	
	function __construct(){
		$this->baseURL = 'http://www.youtube.com/api2_rest';
		$this->checkErrors = true;
	}
	
	/*
	This will return API specific error messages.
	*/
	function errorCatch($response){
		$err_msg = $this->getValue($response, "error", "description");
	 if($err_msg)
		//user_error("YouTube Service Error : $err_msg", E_USER_ERROR);
	 	throw new Exception("YouTube Service Error : $err_msg");
	 else
	 	return $response;
	}
	/*
	Sets the Developer ID for YouTube. Method name remains same as setAPIKey but it implies the dev_id
	*/
	static function setAPIKey($key){
		self::$api_key = $key;
	}
	
	function getAPIKey(){
		return self::$api_key;
	}
	
	function getVideosByTag($tag=NULL, $per_page=20, $page=1){
		$params = array(
			'method' => 'youtube.videos.list_by_tag',
			'tag' => $tag,
			'per_page' => $per_page,
			'page' => $page,
			'dev_id' => $this->getAPIKey()
			);
		
		$this->setQueryString($params);
		$conn = $this->connect();
		
		$results = $this->getValues($conn, 'video_list', 'video');
		Debug::show($results);
		return $results;
	}
	
	function getVideosByUser($user=NULL, $per_page=20, $page=1){
		$params = array(
			'method' => 'youtube.videos.list_by_user',
			'user' => $user,
			'per_page' => $per_page,
			'page' => $page,
			'dev_id' => $this->getAPIKey()
			);
		
		$this->setQueryString($params);
		$conn = $this->connect();
		
		$results = $this->getValues($conn, 'video_list', 'video');
		Debug::show($results);
		return $results;
	}
	
	function getVideosByPlaylist($playlist=NULL, $per_page=20, $page=1){
		$params = array(
			'method' => 'youtube.videos.list_by_playlist',
			'id' => $playlist,
			'per_page' => $per_page,
			'page' => $page,
			'dev_id' => $this->getAPIKey()
			);
		
		$this->setQueryString($params);
		$conn = $this->connect();
		
		$results = $this->getValues($conn, 'video_list', 'video');
		Debug::show($results);
		return $results;
	}
	
	
	}
?>