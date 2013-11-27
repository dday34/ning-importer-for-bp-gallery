<?php

/**
 * Description of RTMediaNingImporter
 *
 * @author damien
 */
class NingImporterBPGalleryManager{

    function __construct() {
        
    }

    function upload_ning_media($ningMedia, $media_type, &$fileSkipped, &$realNumberOfMediaUpload){
        // Retrieve the right gallery to import the media
        if($media_type == "photos"){
            $gallery_exist = bp_gallery_exists("imported_photos", "user", $ningMedia->owner_id);
            if($gallery_exist){
                $id = $gallery_exist;
            }
            else{
                $id = gallery_create_gallery(
                array(  
                    "title"=> "Imported photos",
                    "creator_id"=>$ningMedia->owner_id,
                    "description"=> "All the photos you have uploaded in the older version of the community website.",
                    "owner_object_id"=>$ningMedia->owner_id,
                    "slug"=> "imported_photos",
                    "status"=>"public",
                    "gallery_type"=>"photo",
                    "owner_object_type"=>"user"
                  )
                 );
            }
        }
        else if($media_type == "videos"){
            $gallery_exist = bp_gallery_exists("imported_videos", "user", $ningMedia->owner_id);

            if($gallery_exist){
                $id = $gallery_exist;
            }
            else{
                $id = gallery_create_gallery(
                array(  
                    "title"=> "Imported videos",
                    "creator_id"=>$ningMedia->owner_id,
                    "description"=> "All the videos you have uploaded in the older version of the community website.",
                    "owner_object_id"=>$ningMedia->owner_id,
                    "slug"=> "imported_videos",
                    "status"=>"public",
                    "gallery_type"=>"video",
                    "owner_object_type"=>"user"
                  )
                 );
            }
        }


        if($id){
            $gallery=new BP_Gallery_Gallery($id);

            // Add the media file to the gallery
            if($gallery){
                if($ningMedia->type == 'file'){
                    
                    $this->gallery_action_upload_media("user", $gallery, $ningMedia, substr($media_type, 0, 5), $fileSkipped, $realNumberOfMediaUpload);
                }
                else if($ningMedia->type == 'link'){
                    $this->gallery_action_add_link("user", $gallery, $ningMedia, substr($media_type, 0, 5), $fileSkipped, $realNumberOfMediaUpload);
                }
            }
        }
        else{
            echo "Impossible to create a gallery";
        }  
    }

    function gallery_action_upload_media($owner_type="user", $gallery, $ningMedia, $media_type, &$fileSkipped, &$realNumberOfMediaUpload){
        global $bp;
        //upload file
        if($media_type == "photo"){
            $media_urls = $this->copy_and_resize_image($ningMedia, $gallery, $fileSkipped, $realNumberOfMediaUpload);
        }
        else if($media_type == "video"){

            $media_urls = $this->copy_video($ningMedia, $gallery, $fileSkipped, $realNumberOfMediaUpload);
        }

        if($media_urls){
            /* if there are no upload errors , let us proceed*/
            $title=$ningMedia->title;
            $description=$ningMedia->description;
            $status=$gallery->status;//inherit from parent,gallery must have an status


            $id=gallery_add_media(array(
                "title"=>stripslashes($title),
                "description"=>stripslashes($description),
                "gallery_id"=>$gallery->id,
                "user_id"=>$ningMedia->owner_id,
                "is_remote"=>false,
                "type"=>$gallery->gallery_type,
                "local_thumb_path"=>$media_urls[0],
                "local_mid_path"=>$media_urls[1],
                "local_orig_path"=>$media_urls[2],
                "slug"=>gallery_check_media_slug(sanitize_title($title),$gallery->id),
                "status"=>$status,
                "enable_wire"=>1)
            );

            if(!$id){
                $fileSkipped[] = array(
                    'title' => stripslashes($title),
                    'mediapath' => $media_urls[2],
                    'owner_id' => $ningMedia->owner_id,
                    'description' => 'media adding failed'
                );
            }
        }
    }

    function copy_and_resize_image($ningMedia, $gallery, &$fileSkipped, &$realNumberOfMediaUpload){
        global $blog_id;

        $media_urls = array();

        // Copy file to the right upload folder
        $media_url =  WP_CONTENT_DIR."/ning-files/".$ningMedia->url;
        $upload_folder = WP_CONTENT_DIR."/uploads/sites/".$blog_id."/gallery/user/".$ningMedia->owner_id."/".$gallery->id."/";

        if (!file_exists($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }
        $filename = explode('/', $ningMedia->url);
        //var_dump($filename);
        
        if(count($filename)==2){
            $dest_url =  $upload_folder.$filename[1];
            //var_dump($filename[1]);
            $reduce_filename = explode('-', $filename[1])[1];
        }
        else if(count($filename)==3){
            $dest_url =  $upload_folder.$filename[2];
            //var_dump($filename[2]);
            $reduce_filename = explode('-', $filename[2])[1];
        }
        
        //$dest_url =  $upload_folder.substr($ningMedia->url, 7);
        //var_dump($reduce_filename);
        $search = $upload_folder."*-".$reduce_filename;  
        if(glob($search)){
            //var_dump("PASS ".$search);
            return false;
        }
        
        //var_dump($media_url.'<br><br>'.$dest_url);

        $copy = copy($media_url, $dest_url);


        // Resize image
        if($copy){
            $realNumberOfMediaUpload++;
            $resizing_path = $upload_folder;
            $settings=gallery_get_media_size_settings("photo");//return an array

            //use image_resize instead of the wp_create_thumbnail as it allows creation of non squared images too
            foreach($settings as $size_type=>$dimensions){
                $size = getimagesize($media_url);
                if($size[0]>$settings[$size_type]['width'] || $size[1]>$settings[$size_type]['height']){
                    $resized_image =bp_gallery_image_resize(
                        $dest_url, 
                        $settings[$size_type]['width'], 
                        $settings[$size_type]['height'], 
                        false, 
                        $settings[$size_type]['width']."x".$settings[$size_type]['height'], 
                        $upload_folder, 
                        90
                    );
                }

                if(!$resized_image){
                    $resized_image = $dest_url;
                }
                
                $resized_image = str_replace( '//', '/', $resized_image );
                $image_path=str_replace(array(ABSPATH),'',$resized_image);
                $media_urls[] = $image_path;
            }
        }
        else{
            $fileSkipped[] = array(
                'title' => $ningMedia->title,
                'mediapath' => $media_url,
                'owner_id' => $ningMedia->owner_id,
                'description' => 'copy of the file failed'
            );
        }

        return $media_urls;
    }

    function copy_video($ningMedia, $gallery, &$fileSkipped, &$realNumberOfMediaUpload){
        global $blog_id;

        $media_urls = array();

        // Copy file to the right upload folder
        $media_url =  WP_CONTENT_DIR."/ning-files/".$ningMedia->url;
        $upload_folder = WP_CONTENT_DIR."/uploads/sites/".$blog_id."/gallery/user/".$ningMedia->owner_id."/".$gallery->id."/";
        if (!file_exists($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }
        $dest_url =  $upload_folder.substr($ningMedia->url, 7);
        
//        if(file_exists($dest_url)){
//            return false;
//        }
        
        $reduce_filename = explode('-', substr($ningMedia->url, 7))[1];
        //var_dump($reduce_filename);
        $search = $upload_folder."*-".$reduce_filename;  
        if(glob($search)){
            //var_dump("PASS ".$search);
            return false;
        }

        $copy = copy($media_url, $dest_url);

        // Resize image
        if($copy){
            $realNumberOfMediaUpload++;
            $dest_url = str_replace( '//', '/', $dest_url );
        
            $dest_url=str_replace(array(ABSPATH),'',$dest_url);
            $media_urls[] = $dest_url;
            $media_urls[] = $dest_url;
            $media_urls[] = $dest_url;
        }
        else{
            $fileSkipped[] = array(
                'title' => $ningMedia->title,
                'mediapath' => $media_url,
                'owner_id' => $ningMedia->owner_id,
                'description' => 'copy of the file failed'
            );
        }

        return $media_urls;
    }

    function gallery_action_add_link($owner_type="user", $gallery, $ningMedia, $media_type, &$fileSkipped, &$realNumberOfMediaUpload){
        global $bp;

        if($media_type == "video"){
            $title=$ningMedia->title;
            $description=$ningMedia->description;
            $status=$gallery->status;//inherit from parent,gallery must have an status
            
            global $wpdb;
            $id = get_current_blog_id();
            $blog_prefix = $wpdb->get_blog_prefix($id);
            $reduce_filename = explode('-', substr($ningMedia->url, 7))[1];
            $query = "SELECT * FROM ".$blog_prefix."bp_gallery_media where gallery_id='".$gallery->id."' and remote_url= '".$ningMedia->url."';";
            
            $url_exist = $wpdb->get_results($query);
            
            if(!$url_exist){
                $id=gallery_add_media(array(
                    "title"=>stripslashes($title),
                    "description"=>stripslashes($description),
                    "gallery_id"=>$gallery->id,
                    "user_id"=>$ningMedia->owner_id,
                    "is_remote"=>true,
                    "type"=>$gallery->gallery_type,
                    "remote_url"=>$ningMedia->url,
                    "slug"=>gallery_check_media_slug(sanitize_title($title),$gallery->id),
                    "status"=>$status,
                    "enable_wire"=>1)
                );

                if(!$id){
                    $fileSkipped[] = array(
                        'title' => stripslashes($title),
                        'mediapath' => $media_urls[2],
                        'owner_id' => $ningMedia->owner_id,
                        'description' => 'media adding failed'
                    );
                }else{
                    $realNumberOfMediaUpload++;
                }
            }
            else{
                return false;
            }
            
        }
    }


}

?>