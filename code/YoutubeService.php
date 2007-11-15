<?php

/**
 * Used to connect to YouTube API via REST interface.
 */
class YoutubeService extends RestfulService {
	private $primaryURL;
	
	function __construct(){
		$this->primaryURL = 'http://gdata.youtube.com/feeds/';
		parent::__construct($this->primaryURL);
		$this->checkErrors = false;
	}
	
	/*
	This will return API specific error messages.
	FIX this to suit to GData feed
	*/
	function errorCatch($response){
		$err_msg = $this->getValue($response, "error", "description");
	 if($err_msg)
		//user_error("YouTube Service Error : $err_msg", E_USER_ERROR);
	 	throw new Exception("YouTube Service Error : $err_msg");
	 else
	 	return $response;
	}
	
	function getVideosFeed($method=NULL, $params=array(), $max_results=10, $start_index=1){
		$default_params = array('max-results' => $max_results, 
									'start-index' => $start_index);
			
		$params = array_merge($params, $default_params);
		
		$this->baseURL = $this->primaryURL.$method;
		$this->setQueryString($params);
		$conn = $this->connect();
		
		$results = $this->getValues($conn, 'entry');
		//Debug::show($results);
		return $results;
	}
	
	function getVideosByCategoryTag($categoryTag, $max_results=10, $start_index=1){
		$method = "videos/-/$categoryTag";
		$params = array();
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
	function getVideosByQuery($query=NULL, $max_results=10, $start_index=1){
		$method = "videos";
		$params = array(
			'vq' => $query
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
	function getVideosUploadedByUser($user=NULL, $max_results=10, $start_index=1){
		$method = "videos";
		$params = array(
			'author' => $user
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
	function getFavoriteVideosByUser($user=NULL, $max_results=10, $start_index=1){
		$method = "users/$user/favorites";
		$params = array(
			);
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
	
	}
?>