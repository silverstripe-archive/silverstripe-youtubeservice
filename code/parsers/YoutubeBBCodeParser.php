<?php
/**
 * Adds the ability to do one of the following in content areas that are parsed by BBCode:
 * [youtubevideo]http://www.youtube.com/watch?v=EQ2vLdFTaT0[/youtubevideo] OR
 * [youtubevideo]EQ2vLdFTaT0[/youtubevideo] OR 
 * [youtubevideo=http://www.youtube.com/watch?v=EQ2vLdFTaT0] OR 
 * [youtubevideo=EQ2vLdFTaT0].
 * 
 * Of the four formats, the last two are preferred, because the first two are just regex'd
 * into the last two formats anyway.
 * 
 * CAUTION: Currently only works on BlogEntry.php pages within the blog module.
 */
class YoutubeBBCodeParser extends SSHTMLBBCodeParserFilter {
	/**
	 * The youtubevideo tag
	 */
	var $_definedTags = array(
		'youtubevideo' => array(
			'htmlopen' => 'object',
			'htmlclose' => 'object',
			'wrapperopen' => 'p',
			'wrapperclose' => 'p',
			'allowed' => 'none',
			'attributes' => array(
				'youtubevideo', // Allowed attributes for the tag (e.g. [youtubevideo=the_url])
			),
			
			'callableClass' => 'YoutubeBBCodeParser', // Name of the filter array key to call the attrMethod and innerMethod values on
			
			'attrMethod' => 'getAttributes', // returns attributes of the tag ({@link YoutubeBBCodeParser::getAttributes()})
			'innerMethod' => 'getParams', // returns extra content to be put inside the tag ({@link YoutubeBBCodeParser::getParams()})
			'disable_sprintf' => array(
				'data' => true
			),
		)
	);
	
	/**
	 * The response from the YoutubeService class. Cached because two different methods are called and we only want to query the API once.
	 * 
	 * @var array The response from Youtube
	 */
	protected $response;
	
	/**
	 * Gets the parameters (<param> tags) used to render this video object
	 * 
	 * @param array $attributes The array of valid attributes that was inside the [youtubevideo] tag after parsing
	 * @return string Lots of <param> tags in a string
	 */
	function getParams($attributes) {
		if(!isset($this->response) && isset($attributes['youtubevideo'])) {
			// Get data from Youtube
			$this->getDataFromService($attributes['youtubevideo']);
		}
		
		if(!is_array($this->response)) return array(); // In case of error
		
		$array = array(
			"movie" => $this->response["PlayerURL"],
			"quality" => "best"
		);
		
		$string = "";
		foreach($array as $key => $val) {
			$string .= "<param name=\"" . Convert::raw2att($key) . "\" value=\"" . Convert::raw2att($val) . "\" />";
		}

		return $string;
	}
	
	/**
	 * Gets the object attributes (e.g. width="200") used to render this video object
	 * 
	 * @param array $attributes The array of valid attributes that was inside the [youtubevideo] tag after parsing
	 * @return array An array of attributes to add to the <object> tag
	 */
	function getAttributes($attributes) {
		if(!isset($this->response) && isset($attributes['youtubevideo'])) {
			// Get data from Youtube
			$this->getDataFromService($attributes['youtubevideo']);
		}
		
		if(!is_array($this->response)) return array(); // In case of error
		
		return array(
			"type" => 'type="application/x-shockwave-flash"',
			"data" => 'data="' . $this->response['PlayerURL'] . '"',
			"width" => 'width="' . $this->response["PlayerWidth"] . '"',
			"height" => 'height="' . $this->response["PlayerHeight"] . '"',
			"allowfullscreen" => 'allowfullscreen="true"',
			"id" => 'id="showplayer"'
		);
	}
	
	/**
	 * Turns this format:
	 * [youtubevideo]http://www.youtube.com/watch?v=EQ2vLdFTaT0[/youtubevideo] OR
	 * [youtubevideo]EQ2vLdFTaT0[/youtubevideo]
	 * 
	 * Into this format:
	 * [youtubevideo=http://www.youtube.com/watch?v=EQ2vLdFTaT0] OR
	 * [youtubevideo=EQ2vLdFTaT0]
	 */
	function _preparse()
    {
        $options = SSHTMLBBCodeParser::getStaticProperty('SSHTMLBBCodeParser','_options');
        $o  = $options['open'];
        $c  = $options['close'];
        $oe = $options['open_esc'];
        $ce = $options['close_esc'];
        $this->_preparsed = preg_replace(
			"!".$oe."youtubevideo(\s?.*)".$ce."(.*)".$oe."/youtubevideo".$ce."!Ui",
			$o."youtubevideo=\"\$2\"\$1".$c.$o."/youtubevideo".$c,
			$this->_text);
    }
    
    /**
     * Gets data about this video from youtube. Needs to get the file ID, and then query details about that ID
     * 
     * @param string $video Either the URL to a video (e.g. http://www.youtube.com/watch?v=EQ2vLdFTaT0) or the video ID (EQ2vLdFTaT0). 
     */
    function getDataFromService($videoStr) {
    	// Parse the ID number out of the url only if we have to
    	if(preg_match('/watch\?v\=/', $videoStr)) {
    		preg_match('/watch\?v\=([^&]*)/', $videoStr, $matches);
    		if(!isset($matches[1])) return "ERROR";
    		$videoStr = $matches[1];
    	}
    	
    	// Now that we have a number, look it up with youtube
    	$youtubeService = new YoutubeService();
    	$response = $youtubeService->getVideoInfo($videoStr); // Returns all pertinent information about one video, identified by the video ID
    	
    	if(isset($response)) $this->response = $response;
    	else $this->response = "ERROR";
    }
}
?>
