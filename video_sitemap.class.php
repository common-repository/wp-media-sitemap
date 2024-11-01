<?php

/**
 * All the function relative to an video sitemap
 * @author thomas
 *
 */
class Video_Sitemap{
	
	/**
	 * Number max of link in a sitemap file. Google say 50k
	 * @var integer
	 */
	const MAX_LINK = 45000;
	
	/**
	 * Size max for a sitemap file. Google say 10Mb
	 * @var integer
	 */
	const MAX_SIZE = 9000000;
	
	/**
	 * absolute path to wrtie the file
	 * @var unknown_type
	 */
	private $pathRoot;
	
	/**
	 * Name of the file
	 * @var string
	 */
	private $videoNameFile;
		
	function __construct($path, $videoName ){
		$this->pathRoot = $path;
		$this->videoNameFile = $videoName;
	}

	/**
	 * Write content in the sitemap file
	 * @param string $content
	 * @param integer $numFile Number of the file
	 * @return FALSE on error, number of caracter written on sucess
	 */
	private function write($content,$numFile){
		$fileName = $this->pathRoot.$this->videoNameFile.$numFile.'.xml';
		return @file_put_contents($fileName, $content);
	}
	
	
	/**
	 * Main function
	 * @return boolean TRUE on sucess, FALSE on error
	 */
	public function generate(){

		/**
		 * All the image of the blog
		 * @var array
		 */
		$videos = $this->get_video();
			
		/**
		 * WP options, mainly stats about generation
		 * @var array
		 */
		$options = array();
		
		/**
		 * Number of images
		 * @var integer
		 */
		$nbOfVideos = 0;
		
		/**
		 * number of character to write to the sitemap file
		 * @var integer
		 */
		$length = 0;
		
		/**
		 * Number of image site map file
		 * @var integer
		 */
		$numFile = 0;
		
		/**
		 * id of the post where the image is link. Usefull to the loc tag
		 * @var integer
		 */
		$idParent = -1;
		
		$output = '';
		
		foreach($videos as $vid){
			//new file
			if( $nbOfVideos % self::MAX_LINK == 0 || ob_get_length() > self::MAX_SIZE ){
				//not the fist iteration -> close file
				if($nbOfVideos){
					$output .= "\t<url>\n</urlset>";
					if( ! $this->write($output, $numFile ) )
						return false;
					$output = '';
					$numFile++;
				}
				//create file
				$output .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
				$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">'."\n";
			}
			
			//new post
			if( $idParent != $vid->postID ){
				//close previous <url>
				if($nbOfVideos){
					$output .= "</url>\n";
				}
				$pageUrl = get_permalink($vid->postID);
				if(empty( $pageUrl) )
					$pageUrl = trailingslashit(get_option('siteurl') );
				$output .= "<url>\n\t<loc>".$pageUrl."</loc>\n";
				$idParent = $vid->postID;
			}
			//display image data
			$output .= "\t<video:video>\n\t\t<video:content_loc>". $vid->url ."</video:content_loc>\n";
			$output .= "\t\t<video:thumbnail_loc>http://s.wordpress.org/style/images/wp3-logo.png</video:thumbnail_loc>\n";
			$output .= "\t\t<video:publication_date>" . $vid->publicationDate . "</video:publication_date>\n";
			if( isset($vid->description) ){
				$output .= "\t\t<video:description>".urlencode( substr( $vid->description,0,2047) )."</video:description>\n";
			}
			if( isset($vid->title) ){
				$output .= "\t\t<video:title>".urlencode( substr($vid->title,0,99) )."</video:title>\n";
			}
			$output .= "\t</video:video>\n";
			$nbOfVideos++;
		}
		if(count( $videos))
			$output .= "\n</url>\n</urlset>";
		if( ! $this->write( $output , $numFile ) )
						return false;

		$options['nbVideo'] = $nbOfVideos;
		$options['nbVideoFile'] = $numFile;				
		wp_media_sitemap_saveOption(OPTN_VIDEO,$options);				
		
		return $numFile;
	}
	
	/**
	 * Return an array of object with image information inside
	 * @author Thomas Genin
	 * @return array
	 */
	private function get_video(){
		global $wpdb;
		$query = "
			SELECT P.post_title as title, P.post_date as publicationDate, P.post_content as description, P.guid as url, P.post_parent as postID
			FROM ".$wpdb->prefix."posts P
			WHERE post_type = 'attachment' AND post_mime_type LIKE 'video%'
			ORDER BY post_parent DESC";
		return $wpdb->get_results($query, OBJECT);
	}
}