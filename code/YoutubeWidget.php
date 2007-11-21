<?php

class YoutubeWidget extends Widget {
	static $db = array(
		"Method" => "Int",
		"User" => "Varchar",
		"MaxResults" => "Int",
		"StartIndex" => "Int"
	);
	
	static $defaults = array(
		"Method" => 1,
		"MaxResults" => 5,
		"StartIndex" => 1
	);
	
	static $title = "YouTube Videos";
	static $cmsTitle = "YouTube Videos";
	static $description = "Shows thumbnails of your Youtube videos.";
	
	function Videos() {
		
		$youtube = new YoutubeService();
		
		try {
			switch ($this->Method){
			case 1:
				$videos = $youtube->getVideosUploadedByUser($this->User);
				break;
			case 2:
				$videos = $youtube->getFavoriteVideosByUser($this->User);
				break;
			}
		} catch(Exception $e) {
			return false;
		}
		
		$output = new DataObjectSet();
		foreach($videos as $video) {
			$videoId = array_pop(explode("/", $video->id));	
			
			$output->push(new ArrayData(array(
				"Title" => $video->title,
				"Link" => $video->player_url,
				"Image" => $video->thumbnail_url,
				"Duration" => round((float)$video->content_duration/60, 2)
			)));
			
		}
		
		return $output;
	}

	function getCMSFields() {
	
		return new FieldSet(
			new TextField("User", "Youtube username"),
			new DropdownField("Method", "Select ", array(
				'1' => 'Videos uploaded by',
				'2' => 'Favorite videos of'	) ), 
			new NumericField("MaxResults", "Videos to Show", 5),
			new DropdownField("Sortby", "Sort by ", array(
				'relevance' => 'Relevance',
				'updated' => 'Upload date',
				'viewCount' => 'View count',
				'rating' => 'Rating'))
			);
	}
}

?>