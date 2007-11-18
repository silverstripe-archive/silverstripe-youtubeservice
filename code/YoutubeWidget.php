<?php

class YoutubeWidget extends Widget {
	static $db = array(
		"Method" => "Int",
		"User" => "Varchar",
		"Query" => "Varchar",
		"CategoryTag" => "Varchar",
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
	static $description = "Shows thumbnails of Youtube videos.";
	
	function Videos() {
		
		$youtube = new YoutubeService();
		
		try {
			switch ($this->Method){
			case 1:
				$videos = $youtube->getVideosByQuery($this->Query);
				break;
			case 2:
				$videos = $youtube->getVideosByCategoryTag($this->CategoryTag);
				break;
			case 3:
				$videos = $youtube->getVideosUploadedByUser($this->User);
				break;
			case 4:
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
				"Link" => "http://www.youtube.com/watch?v=" . $videoId,
				"Image" => "http://img.youtube.com/vi/" .$videoId. "/2.jpg",
				"Author" => $video->author_name
			)));
			
		}
		
		return $output;
	}

	function getCMSFields() {
	
	Requirements::javascript( 'youtubeservice/javascript/YoutubeWidget_CMS.js' );
	
		return new FieldSet(
			new DropdownField("Method", "Select ", array(
				'1' => 'Videos containing phrase',
				'2' => 'Videos by Category or Tag',
				'3' => 'Videos uploaded by',
				'4' => 'Favorite videos of'	) ),
			new TextField("User", "User"),
			new TextField("Query", "Search for"),
			new TextField("CategoryTag", "Category or Tag"),
    		new TextField("Playlist", "Playlist ID"), 
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