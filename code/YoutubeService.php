<?php

/**
 * Used to connect to YouTube API via REST interface.
 */
 
class YoutubeService extends RestfulService {
	private $primaryURL;
	private $videoCount;
	private $pageCount;
	
	/**
 	* Creates a new YoutubeService object.
 	* @param expiry - Set the cache expiry time or TTL of the response
 	*/
	function __construct($expiry=NULL){
		$this->primaryURL = 'http://gdata.youtube.com/feeds/';
		parent::__construct($this->primaryURL, $expiry);
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
	
	/**
	* Retrives a Videos Feed - generic method
	* @param method - video function, actually the sub url of the feed eg:/playlists
	* @param params - params to pass
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getVideosFeed($method=NULL, $params=array(), $max_results=NULL, $start_index=NULL, $orderby=NULL){
		$default_params = array('max-results' => $max_results, 
									'start-index' => $start_index,
									'orderby' => $orderby);
			
		$params = array_merge($params, $default_params);
		
		$this->baseURL = $this->primaryURL.$method;
		$this->setQueryString($params);
		$conn = $this->connect();
		
		//have to make a custom XML object
		$xml =  new SimpleXMLElement($conn);
		
		$entries = $xml->entry;
		$results = new DataObjectSet();
		
		foreach($entries as $entry){
			//get into media section of each entry
			$data = array();
			$data["author"] = Convert::raw2xml($entry->author->name);
			$mediaentry = $entry->children('media', true);
			//print_r($mediaentry[0]);
			//go through the values in the media section
			foreach($mediaentry[0]->children('media', true) as $key => $value){		
				foreach($value->attributes() as $attr => $attr_value){
					$compkey = $key."_".$attr;
					if(array_key_exists($compkey, $data)){
						$compkey = $compkey."_1";
					}
					$data[$compkey] = Convert::raw2xml($attr_value);
				}
				
				if($value){
						$data["$key"] = Convert::raw2xml($value);
					}
			};
			$results->push(new ArrayData($data));
		}
				
		//get total number of videos
		$this->videoCount = $this->searchValue($conn, 'openSearch:totalResults');
		$this->pageCount = (int)($this->videoCount/$max_results);
		
		return $results;
	}
	
	/**
	* Get videos by category or Tag 
	* @param categoryTag - category name or tag separated by backslash '/', if it's a category name capitalize
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getVideosByCategoryTag($categoryTag, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos/-/$categoryTag";
		$params = array();
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	/**
	* Search for videos based on a phrase
	* @param quert - text to search
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getVideosByQuery($query=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos";
		$params = array(
			'vq' => $query
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	/**
	* Get videos uploaded by a particular user
	* @param user - user id of the user
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getVideosUploadedByUser($user=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos";
		$params = array(
			'author' => $user
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	/**
	* Get the favorite videos of an user
	* @param user - user id of the user
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getFavoriteVideosByUser($user=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "users/$user/favorites";
		$params = array(
			);
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	/**
	* Returns a playlist containing videos
	* @param playlistID - ID of the playlist to return
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	*/
	function getPlaylist($playlistID=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "playlists/$playlistID";
		$params = array(
			);
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
	/**
	* Handles pagination
	*/
	function Paginate(){
	$current_url = Director::currentURLSegment();

		$current_page = isset($_GET['page'])? (int)$_GET['page']: 1;;
		$last_page = $this->pageCount;
		//$this->TotalPosts = $this->postCount;
		
		
		if($current_page > 1){
			$qs = http_build_query(array('page' => $current_page - 1));
			$pagelist = "<a href='$current_url?$qs' class='prev'>&lt; Previous</a>";
		}
		
		if($current_page < 6)
			$start = 0;
		else
			$start = $current_page - 5;
		
		$end = $last_page < 10 ? $last_page : $start+10;
		
		$pagelist = "";
		for($i=$start; $i < $end ; $i++){
			$pagenum = $i + 1;
			if($pagenum != $current_page){
				$qs = http_build_query(array('page' => $pagenum));
				$page_item = "<a href='$current_url?$qs'>$pagenum</a>";
			}
			else 
				$page_item = "<span class='currentPage'>$pagenum</span>";
				
			$pagelist .= $page_item;
		}
		
		if ($current_page < $last_page){
			$qs = http_build_query(array('page' => $current_page + 1));
			$pagelist .= "<a href='$current_url?$qs' class='next'>Next &gt;</a>";
		}
			
		return $pagelist;
	}
	
	/**
	* Get page list 
	*/
	function getPages(){
		return $this->Paginate();
	}
	
	/**
	* Get total number of videos available for this query
	*/
	function getTotalVideos(){
		return $this->videoCount;
	}
	
	
	}
?>