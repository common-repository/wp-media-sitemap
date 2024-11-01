<?php

/**
 * All the function relative to an image sitemap
 * @author thomas
 *
 */
class Image_Sitemap{
	
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
	private $imgNameFile;
		
	function __construct($path, $imagename ){
		$this->pathRoot = $path;
		$this->imgNameFile = $imagename;
	}

	/**
	 * Write content in the sitemap file
	 * @param string $content
	 * @param integer $numFile Number of the file
	 * @return FALSE on error, number of caracter written on sucess
	 */
	private function write($content,$numFile){
		$fileName = $this->pathRoot.$this->imgNameFile.$numFile.'.xml';
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
		$images = $this->get_images();
			
		/**
		 * WP options, mainly stats about generation
		 * @var array
		 */
		$options = array();
		
		/**
		 * Number of images
		 * @var integer
		 */
		$nbOfImages = 0;
		
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
		
		foreach($images as $img){
			//new file
			if( $nbOfImages % self::MAX_LINK == 0 || ob_get_length() > self::MAX_SIZE ){
				//not the fist iteration -> close file
				if($nbOfImages){
					$output .= "\t<url>\n</urlset>";
					if( ! $this->write($output, $numFile ) )
						return false;
					$output = '';
					$numFile++;
				}
				
				//create file
				$output .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
				$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
			}
			
			//new post
			if( $idParent != $img->postID ){
				//close previous <url>
				if($nbOfImages){
					$output .= "</url>\n";
				}
				$pageUrl = get_permalink($img->postID);
				if(empty( $pageUrl) )
					$pageUrl = trailingslashit(get_option('siteurl') );
				$output .= "<url>\n\t<loc>".$pageUrl."</loc>\n";
				$idParent = $img->postID;
			}
			//display image data
			$output .= "\t<image:image>\n\t\t<image:loc>". $img->url ."</image:loc>\n";
			if( isset($img->caption) ){
				$output .= "\t\t<image:caption>".urlencode( $img->caption )."</image:caption>\n";
			}
			if( isset($img->title) ){
				$output .= "\t\t<image:title>".urlencode( $img->title )."</image:title>\n";
			}
			$output .= "\t</image:image>\n";
			$nbOfImages++;
		}
		$output .= "\n</url>\n</urlset>";
		if( ! $this->write( $output , $numFile ) )
						return false;

		$options['nbImg'] = $nbOfImages;
		$options['nbImgFile'] = $numFile;				
		wp_media_sitemap_saveOption(OPTN_IMAGE,$options);				
		
		return $numFile;
	}
	
	/**
	 * Return an array of object with image information inside
	 * @author Thomas Genin
	 * @return array
	 */
	private function get_images(){
		global $wpdb;
		$query = "
			SELECT P.post_title as title, P.post_excerpt as caption, P.guid as url, P.post_parent as postID
			FROM ".$wpdb->prefix."posts P
			WHERE post_type = 'attachment' AND post_mime_type LIKE 'image%'
			ORDER BY post_parent DESC";
		return $wpdb->get_results($query, OBJECT);
	}
}