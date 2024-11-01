<?php
/**
 * General class to generate the sitemap
 * @author thomas
 *
 */
class Sitemap{
		
	/**
 	* Absolute path  to the website root directory
 	* @var string
	*/
	protected $pathRoot;
	
	/**
	 * Name of sitemap file for image sitemap
	 * @var unknown_type
	 */
	protected $imgNameFile = IMG_SITEMAP;
	
	/**
	 * Name of sitemap file for video sitemap
	 * @var unknown_type
	 */
	protected $vidNameFile = VID_SITEMAP;
	
	
	
	function __construct($path){
		$this->pathRoot = $path;
	}
	
	static public function get_imgNameFile(){
		return $this->imgNameFile;
	}
	
	static public function get_vidNameFile(){
		return $this->vidNameFile;
	}
	
	/**
	 * Generate the XML Sitemap
	 * @return bool
	 */
	public function generate(){
		
//		$this->test();
		
		//generate images
		$resImg = $this->generate_image();
		if( $resImg === false  ){
			return false;
		}
		
		$resVid = $this->generate_video();
		if( $resVid === false  ){
			return false;
		}
		
		//generate index
		$res = $this->generate_index($resImg, $resVid);
		if( $res === false  ){

			return false;
		}
		
		return true;
	}
	
	/**
	 * Generate the xml sitemap with on;y the image
	 * @return bool
	 */
	private function generate_image(){
		require_once 'image_sitemap.class.php';
		$img = new Image_Sitemap( $this->pathRoot, $this->imgNameFile );
		return $img->generate();
	}
	
	
	/**
	 * Generate the xml sitemap with on;y the video
	 * @return bool
	 */
	private function generate_video(){
		require_once 'video_sitemap.class.php';
		$vid = new Video_Sitemap( $this->pathRoot, $this->vidNameFile );
		return $vid->generate();
	}	
	
	/**
	 * Generate the index Sitemap which contain all the link to other sitemap files
	 * @param $numFile Number of sitemap files we have generated
	 * @return bool TRUE on succes, FALSE if an error occur
	 */
	private function generate_index($numFileImg, $numFileVid){
		
		//$numFile++;
		$output = "<?xml version='1.0' encoding='UTF-8'?>\n\t<sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
		$siteURL = wpms_get_site_url();
	
		//Links to image sitemap
		for($i=0;$i<$numFileImg;$i++){
			$output .= "\t<sitemap>\n\t\t<loc>". $siteURL .$this->imgNameFile.$i .".xml</loc>\n\t\t<lastmod>". date('c') ."</lastmod>\n\t</sitemap>\n";
			
		}
		for($i=0;$i<$numFileVid;$i++){
			$output .= "\t<sitemap>\n\t\t<loc>". $siteURL .$this->vidNameFile.$i .".xml</loc>\n\t\t<lastmod>". date('c') ."</lastmod>\n\t</sitemap>\n";
			
		}
		$output .= '</sitemapindex>';
		
		if( false === file_put_contents($this->pathRoot.'media_sitemap.xml', $output) ){
			return false;
		}
		
		return true;					
	}
}//end of class