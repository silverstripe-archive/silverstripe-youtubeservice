<?php

/**
 * Used to connect to YouTube API via REST interface.
 */
 
 //Complete method documentation
class YoutubeService extends RestfulService {
	private $primaryURL;
	private $videoCount;
	private $pageCount;
	
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
	
	function getVideosByCategoryTag($categoryTag, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos/-/$categoryTag";
		$params = array();
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	function getVideosByQuery($query=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos";
		$params = array(
			'vq' => $query
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	function getVideosUploadedByUser($user=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "videos";
		$params = array(
			'author' => $user
			);
		
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	function getFavoriteVideosByUser($user=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "users/$user/favorites";
		$params = array(
			);
		return $this->getVideosFeed($method, $params, $max_results, $start_index, $orderby);
	}
	
	function getPlaylist($playlistID=NULL, $max_results=10, $start_index=1, $orderby='relevance'){
		$method = "playlists/$playlistID";
		$params = array(
			);
		return $this->getVideosFeed($method, $params, $max_results, $start_index);
	}
	
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
	
	function getPages(){
		return $this->Paginate();
	}
	
	function getTotalVideos(){
		return $this->videoCount;
	}
	
	
	}
?>