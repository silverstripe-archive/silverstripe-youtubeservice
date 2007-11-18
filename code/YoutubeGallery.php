<?php
 
class YoutubeGallery extends Page {
 
   // define your database fields here - for example we have author
   static $db = array(
   		"Method" => "Int",
		"User" => "Varchar",
		"Query" => "Varchar",
		"CategoryTag" => "Varchar",
		"PerPage" => "Int",
		"Sortby" => "Varchar"
   );
   
   static $defaults = array(
		"Method" => 1,
		"PerPage" => 10,
		"Sortby" => 'relevance'
	);
   
  static $icon = "youtubeservice/images/youtube";
 
   // add custom fields for this youtube gallery page
   function getCMSFields($cms) {
   	  Requirements::javascript( 'youtubeservice/javascript/YoutubeGallery_CMS.js' );
   	  
      $fields = parent::getCMSFields($cms);
      $fields->addFieldToTab("Root.Content.Videos", new DropdownField("Method", "Select ", array(
				'1' => 'Videos containing phrase',
				'2' => 'Videos by Category or Tag',
				'3' => 'Videos uploaded by',
				'4' => 'Favorite videos of'	)));
      $fields->addFieldToTab("Root.Content.Videos", new TextField("User","Youtube Username"));
      $fields->addFieldToTab("Root.Content.Videos", new TextField("Query","Search for"));
      $fields->addFieldToTab("Root.Content.Videos", new TextField("CategoryTag", "Category or Tag"));
      $fields->addFieldToTab("Root.Content.Videos", new NumericField("MaxResults", "Per Page", 10));
      $fields->addFieldToTab("Root.Content.Videos", new DropdownField("Sortby", "Sort by (descending)", array(
				'relevance' => 'Relevance',
				'updated' => 'Most Recent',
				'viewCount' => 'Most Viewed',
				'rating' => 'Most Rated')));
      return $fields;
   }
   
   function YoutubeVideos(){
		$youtube = new YoutubeService();
		//Fix page setting
		$page = isset($_GET['page'])? $_GET['page']: 1;
		$start_index = (($page-1) * $this->PerPage) + 1 ;
		
		switch ($this->Method){
			case 1:
				$videos = $youtube->getVideosByQuery($this->Query, $this->PerPage, $start_index, $this->Sortby);
				break;
			case 2:
				$videos = $youtube->getVideosByCategoryTag($this->CategoryTag, $this->PerPage, $start_index, $this->Sortby);
				break;
			case 3:
				$videos = $youtube->getVideosUploadedByUser($this->User, $this->PerPage, $start_index, $this->Sortby);
				break;
			case 4:
				$videos = $youtube->getFavoriteVideosByUser($this->User, $this->PerPage, $start_index, $this->Sortby);
				break;
			}
			
		$outputHTML = "<div class='youtubevideo' style='float:left'>";
		foreach($videos as $video){			
			$outputHTML .=  '<a href="'.$video->player_url.'" title="'.htmlentities($video->title).'"><img src="'.$video->thumbnail_url.'" alt="'.htmlentities($video->title).'"/></a>';
		}
		$outputHTML .= "</div>";
	
	//pagination - needs to Fix	
	 if($videos){
		$outputHTML .= "<div class='pages'><div class='paginator'>";
		$outputHTML .= $youtube->getPages();
	$outputHTML .= "</div><span class='results'>(".$youtube->getTotalVideos()." Videos)</span></div>";
	}
	else {
	
	$outputHTML .= "<span>Sorry!  Gallery doesn't contain any images for this page.</span>";
	}
		
		return $outputHTML;
	}
}

class YoutubeGallery_Controller extends Page_Controller {
	function init() {
      if(Director::fileExists(project() . "/css/YoutubeGallery.css")) {
         Requirements::css(project() . "/css/YoutubeGallery.css");
      }else{
         Requirements::css("youtubeservice/css/YoutubeGallery.css");
      }
      
      parent::init();	
   }
   
   function Content(){
			return $this->Content.$this->YoutubeVideos();
   }

}


?>