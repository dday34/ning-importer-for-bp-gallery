<?php
/* 
Plugin Name: Ning importer for Bp-gallery
Plugin URI: Your Plugin URI
Version: Current Plugin Version
Author: Damien Sendner
Description: 
*/

define( 'PLUGIN_DIR', dirname(__FILE__).'/' );
require_once "php/NingMedia.php";
require_once "php/NingImporter.php";
require_once "php/NingImporterBPGalleryManager.php";

if (!class_exists("NingImporterForBpGallery")) {
    class NingImporterForBpGallery {
        function __construct() { //constructor

        	if ( is_admin() ) {
				add_action('wp_ajax_ning_importer_for_bp_gallery', array(&$this, 'migrate_ning_media_files'));
				add_action('wp_ajax_ning_importer_for_bp_gallery_clean', array(&$this, 'clean_session'));
			}

        	add_action('init', array(&$this, 'init'));
        }

        function init(){
        	wp_enqueue_script( 'ning-importer', plugins_url() . '/ning-importer-for-bp-gallery/js/ning-importer.js', array( 'jquery' ) );
			wp_localize_script( 'ning-importer', 'NingImporter', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			) );

        	add_action('admin_menu', array(&$this, 'menu'));
        	$this->register_session();
        }

        
		function menu() {
		  add_options_page('Ning Importer for Bp-Gallery', 'Ning Importer for Bp-Gallery', 'manage_options', 'ning-importer-for-bp-gallery', array($this, 'ui'));
		}

		function ui() {
			if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			?>
	        <div class="wrap">
	            <h2>Ning to bp-Gallery Migration</h2>
	            <p>Put ning-music.json, ning-photos-local.json, ning-photos.json, ning-videos.json and your photos/videos/music folder in that folder /wp-content/ning-files”. </p>
	            <button id="ning-start-photos-import" type="button">Import photos</button>
	            <button id="ning-start-videos-import" type="button">Import videos</button>
	            <button id="ning-clean-import" type="button">Back to zero</button>
	            <div class="upload_media_counter_for_ning">
	            	<p>
	            		Photos: <span id="numberOfPhotosUploaded">0</span> out of <span id="totalNumberOfPhotos">0</span><br>
	            		Videos: <span id="numberOfVideosUploaded">0</span> out of <span id="totalNumberOfVideos">0</span><br>
	            	</p>
	            </div>
	            <div class="file_skipped_from_ning">
	            	<h3>File skipped by the uploader: </h3>
	            	<p id="file_skipped_from_ning">
	            		None
	            	</p>
	            </div>
	        </div>
	        <?php
		}

            function migrate_ning_media_files() {
	        ini_set('memory_limit', '-1');
	        // Get the type of media you want to retrieve
	        $mediaType = $_REQUEST['mediaType'];

	        // If the session is not open then open it
	        if( !session_id())
    			session_start();

    		
	        // Create the importer object that handles all the importation of media (Save in the session to keep track of where we are in the importation)
	        $bp_media_ning_importer = null;
	        global $blog_id;
	        if(isset($_SESSION['bp_media_ning_'.$mediaType.'_importer_'.$blog_id])) { // the importer has been already created
	            $bp_media_ning_importer = unserialize($_SESSION['bp_media_ning_'.$mediaType.'_importer_'.$blog_id]);
	            //echo 'Importer found <br>';
	        } else { // the importer does not exist
	            $bp_media_ning_importer = new NingImporter($mediaType);
	            $_SESSION['bp_media_ning_'.$mediaType.'_importer_'.$blog_id] = serialize($bp_media_ning_importer); 
	            $_SESSION['bp_media_ning_importer_start_index_'.$blog_id] = 0;
	            //echo 'Importer created <br>';
	        }

	        // Launch the import of media
	        echo json_encode($bp_media_ning_importer->import_ning_medias());
	        die();
	    }


	    function clean_session(){
	    	if(isset($_SESSION['bp_media_ning_photos_importer_'.$blog_id])){
	    		unset($_SESSION['bp_media_ning_photos_importer_'.$blog_id]);
	    	}
	    	if(isset($_SESSION['bp_media_ning_videos_importer_'.$blog_id])){
	    		unset($_SESSION['bp_media_ning_videos_importer_'.$blog_id]);
	    	}
	    }

	    function register_session(){
	        if( !session_id())
	            session_start();
    	}
    }
} //End Class NingImporterForBpGallery

if (class_exists("NingImporterForBpGallery")) {
	$dl_pluginSeries = new NingImporterForBpGallery();
}

//Actions and Filters	
if (isset($dl_pluginSeries)) {
	//Actions
	
	//Filters
}

?>
