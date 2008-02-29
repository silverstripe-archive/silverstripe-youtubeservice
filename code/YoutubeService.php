<?php

/**
 * Used to connect to YouTube API via REST interface.
 * 
 * @see http://code.google.com/apis/youtube/developers_guide_protocol.html
 * @todo Fix SimpleXML parsing for PHP <5.2.5
 */
 
class YoutubeService extends RestfulService {
	
	/**
	 * Youtube default video width.
	 *
	 * @var int
	 */
	protected static $player_width = 425;
	
	/**
	 * Youtube default video height.
	 *
	 * @var int
	 */
	protected static $player_height = 355;
	
	/**
	 * @var int
	 */
	private $videoCount;
	
	/**
	 * @var int
	 */
	private $pageCount;
	
	/**
	 * RESTful URI-resource to query video-feeds
	 * in Atom 1.0 format. Add search-parameters
	 * through POST with the execution.
	 * 
	 * @var string
	 */
	public static $api_base_url = "http://gdata.youtube.com/feeds/";
	
	/**
	 * RESTful URI-resource for a single video,
	 * returning an Atom 1.0 XML feed.
	 * Sprintf-params: video-id
	 *
	 * @var string
	 */
	public static $api_detail_url = "http://gdata.youtube.com/feeds/api/videos/%s";
	
	/**
 	* Creates a new YoutubeService object.
 	* @param expiry - Set the cache expiry time or TTL of the response
 	*/
	function __construct($expiry=NULL){
		parent::__construct(self::$api_base_url, $expiry);
		
		$this->checkErrors = true; //set this to call errorCatch function on response
	}
	
	/**
	 * This will raise API specific error messages (if any).
	 */
	function errorCatch($response){
		$err_msg = $response;
		if(strpos($err_msg, '<') === false) user_error("YouTubeService Error : $err_msg", E_USER_ERROR);

		return $response;
	}
	
	/**
	* Retrieves a Videos Feed - generic method
	* 
	* @param method - video function, actually the sub url of the feed eg:/playlists
	* @param params - params to pass
	* @param max_results - maximum results to return
	* @param start_index - start index of the video feed
	* @param orderby - Sorting method. The possible valus are relevance, updated, viewCount, rating
	* @return DataObjectSet
	*/
	function getVideosFeed($method=NULL, $params=array(), $max_results=NULL, $start_index=NULL, $orderby=NULL){
		$default_params = array(
			'max-results' => $max_results, 
			'start-index' => $start_index,
			'orderby' => $orderby
		);
			
		$params = array_merge($params, $default_params);
		
		$this->baseURL = self::$api_base_url . $method;
		$this->setQueryString($params);
		$conn = $this->connect();
		
		//have to make a custom XML object
		try {
			$xml =  new SimpleXMLElement($conn);
			
			$videos = $xml->entry;
			$results = new DataObjectSet();
			
			foreach($videos as $video){
				$data = $this->extractVideoInfo($video); // Get the data requested
				$results->push(new ArrayData($data));
			}
					
			//get total number of videos
			$this->videoCount = $this->searchValue($conn, 'openSearch:totalResults');
			$this->pageCount = (int)($this->videoCount/$max_results);
			
			return $results;
		} catch (Exception $e) {
			user_error("Error occurred in processing YouTube response");
			return false;
		}
		
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
	 * Get information about one video
	 * 
	 * @param string $videoID The ID of the video (e.g. EQ2vLdFTaT0)
	 */
	function getVideoInfo($videoID) {
		// make sure ID is valid
		/*
		if(!preg_match('/^[a-zA-Z0-9]+$/', $videoID)) {
			user_error('YoutubeService->getVideoInfo(): Invalid Youtube ID', E_USER_WARNING);
			return false;
		}
		*/
		
		$this->baseURL = sprintf(self::$api_detail_url, $videoID);
		$youtubeHandle = $this->connect();
		
		try {
			$video = new SimpleXMLElement($youtubeHandle, LIBXML_NOCDATA); // Convert the response to a SimpleXMLElement, stripping CDATA elements and returning 'pure' HTML
			if(!$video) return false;
			
			return $this->extractVideoInfo($video); // Get the data requested
		} catch (Exception $e) {
			// Don't error out if there was an error with youbute
			// user_error("Error occured processing www.youtube.com response", E_USER_WARNING);
			return null;
		}
	}
	
	/**
	* Handles pagination
	* 
	* @todo Refactor to use DataObjectSet pagnination and templates
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
	
/**
	 * Gets information from one <entry> tag from the feed
	 */
	function extractVideoInfo($video) {
		$data = array();
		
		$mediaentry = $video->children('media', true);
		$attrs = ($mediaentry[0]->children('media', true));
		
		$data['Author'] = Convert::raw2xml((string)$video->author->name);
		
		$data['Title'] = Convert::raw2xml((string)$video->title); // Title of the video
					
		$data['HTML'] = trim((string)$video->content);
		$descriptionObj = $mediaentry->xpath("media:description");
		$data['Description'] = Convert::raw2xml(trim((string)$descriptionObj[0])); // should not contain HTML markup
		
		$runtimeSecObj = $mediaentry->xpath('yt:duration/@seconds');
		$data['RuntimeSec'] = (int)$runtimeSecObj[0]; // Runtime in seconds
		$data['RuntimeMin'] = $this->convertSecsToMins($data['RuntimeSec']); // Runtime in minutes
		$data['Runtime'] = $data['RuntimeSec'] < 60 ? $data['RuntimeMin'] . " seconds" : $data['RuntimeMin'] . " minutes"; // Output either xx seconds or xx minutes
		$data['ShowRuntime'] = $data['RuntimeSec'] == 0 ? false : true; // Only show the runtime if it's longer than 0 seconds
		
		// get embeddable SWF (format code "5")
		// @see http://code.google.com/apis/youtube/reference.html#yt_format
		$urlObj = $mediaentry->xpath('media:content[@yt:format=5]');
		$data['PlayerURL'] = Convert::raw2xml((string)$urlObj[0]['url']);
		
		$data['PlayerWidth'] = self::$player_width;
		$data['PlayerHeight'] = self::$player_height;
		
		$thumbnailObjs = $mediaentry->xpath('media:thumbnail');
		$data['SmallThumbnail'] = new ArrayData(array(
			'URL' => Convert::raw2xml((string)$thumbnailObjs[0]['url']),
			'Width' => (int)$thumbnailObjs[0]['width'],
			'Height' => (int)$thumbnailObjs[0]['height'],
		));
		
		return $data;
	}
	
	/**
	 * Helper method to convert a number of seconds into the equivilent number of minutes:seconds
	 * 
	 * @param int $seconds The number of seconds
	 * @return string The number of seconds into minutes (e.g. input 300, output 5:00)
	 */
	function convertSecsToMins($seconds) {
		return date("i:s", $seconds);
	}
}

?>