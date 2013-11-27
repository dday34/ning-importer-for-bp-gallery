<?php

/**
 * Description of RTMediaNingImporter
 *
 * @author damien
 */
class NingImporter{

    public $mediaType;

    // JSON objects
    public $members; // List of all the members and their data.
    public $medias;

    public $min; // At which photo the upload should start
    public $max; // At which photo the upload should stop (It stops just before uploading this photo)
    public $maxNumberOfUploads=1; // Number of medias you want to upload at once
    public $NumberOfMedias;
    public $response; // Response of the ajax request
    public $fileSkipped;
    public $realNumberOfMediaUpload;

    function __construct($mediaType) {
        global $wpdb;

        $this->mediaType = $mediaType;
        // Retrieve all the members
        $this->members =$this->prepare_json('members', true);
        
        $this->fileSkipped = array();
        $this->realNumberOfMediaUpload = 0;
    }

    function prepare_json( $type) {
        $local .= '-local';

        $json = WP_CONTENT_DIR . '/ning-files/ning-' . $type . $local . '.json';
        if ( !file_exists( $json ) ){
            $json = WP_CONTENT_DIR . '/ning-files/ning-' . $type .'.json';
            if(!file_exists( $json )){
                return false;
            }
        }           

        $data = file_get_contents( $json );
        //var_dump($this->members);
        $data = preg_replace( '|^\(|', '', $data );
        $data = preg_replace( '|\)$|', '', $data );
        $data = str_replace( '}{', '},{', $data );
        $data = str_replace( ']{', ',{', $data );
        $parsed = json_decode( $data );
        //var_dump($parsed);

        unset( $json );
        unset( $data );
        
        return $parsed;
    }

    /*
    *  Extract data from the json files and convert them in Ning media objects
    *
    */
   function jsonToNingMedia($startIndex){
        ini_set('memory_limit', '-1');
        global $wpdb;

        // Get the content of ning-photos-local.json
        if(!isset($this->medias)){
            $this->medias=$this->prepare_json($this->mediaType, true);

            $this->NumberOfMedias = count($this->medias);
            // Initialise the min and max to define how many photos we are going to upload
            $this->min = $startIndex;

            if($this->min + $this->maxNumberOfUploads < $this->NumberOfMedias){
                $this->max = $startIndex + $this->maxNumberOfUploads;
            }
            else{
                $this->max = $this->NumberOfMedias;
            }
        }
        else{
            // Initialise the min and max to define how many photos we are going to upload

            $this->min = $this->max;
            if($this->min === $this->NumberOfMedias){ // If there is no more photos to upload
                return false;
            }

            if($this->max + $this->maxNumberOfUploads < $this->NumberOfMedias){
                $this->max = $this->max+$this->maxNumberOfUploads;
            }
            else {
                $this->max = $this->NumberOfMedias;
            }
        }
        
        //var_dump($this->NumberOfMedias);

        // Initialise table to gather the data
        $mediasLinkedToMembers=array();

        if($this->medias){ // If the file ning-mediatype-local.json is found
            // For each media

            for($i=$this->min; $i<$this->max; $i++){
                
                // Read the contributor name
                $contributorName = $this->medias[$i]->contributorName;
                // Find the email of the member with this contributor name
                
                $j = 0;
                    while($this->members[$j]->contributorName !== $this->medias[$i]->contributorName){
                        $j++;
                    }
                    //var_dump('pass');
                    if($j!==count($this->members)){
                        $owner_id = $wpdb->get_results("SELECT id FROM wp_users WHERE user_email='".$this->members[$j]->email."';");
                        if(!is_null($owner_id) && isset($owner_id[0]->id)){ // If the member is found
                            $mediaPath = '';
                            $localPath = '';
                            $type = 'file';
                            if($this->mediaType==='photos'){
                                $localPath = $this->medias[$i]->photoUrl;
                                $mediaPath = WP_CONTENT_DIR. '/ning-files/'.$this->medias[$i]->photoUrl;
                                //var_dump($mediaPath);
                            }
                            else if($this->mediaType==='music'){
                                $localPath = $this->medias[$i]->audioUrl;
                                $mediaPath = WP_CONTENT_DIR. '/ning-files/'.$this->medias[$i]->audioUrl;
                            }
                            else if($this->mediaType==='videos' && isset($this->medias[$i]->videoAttachmentUrl)){
                                $localPath = $this->medias[$i]->videoAttachmentUrl;
                                //var_dump($this->medias[$i]->videoAttachmentUrl);
                                $mediaPath = WP_CONTENT_DIR. '/ning-files/'.$this->medias[$i]->videoAttachmentUrl;
                                // $cmd = '/usr/local/bin/ffmpeg -i '.WP_CONTENT_DIR. '/ning-files/'.$this->medias[$i]->videoAttachmentUrl.' -qscale 0 -ar 22050 '.$mediaPath;
                                // exec($cmd);
                            }
                            else if ($this->mediaType==='videos' && isset($this->medias[$i]->embedCode)){
                                $embedCode = $this->medias[$i]->embedCode;
                                //var_dump($this->medias[$i]->embedCode);
                                $doc = new DOMDocument();
                                $doc->loadHTML($embedCode);
                                $iframe = $doc->getElementsByTagName('iframe');
                                if($iframe->length==0){
                                    $object = $doc->getElementsByTagName('object');
                                    if($object->length!=0){
                                        $localPath = $object->item(0)->getAttribute('value');
                                        $localPath=str_replace('http://www.youtube.com/v/','',$localPath);
                                        $localPath='http://youtu.be/'.$localPath;
                                        $pos = stripos($localPath, '?');
                                        $localPath=substr($localPath, 0, $pos);
                                    }
                                    else{
                                        $embed = $doc->getElementsByTagName('embed');
                                        if($embed->length != 0){
                                            $localPath = $embed->item(0)->getAttribute('src');
                                        }
                                        else{
                                            continue;
                                        }
                                         
                                    }
                                    
                                }
                                else{
                                    $localPath = $iframe->item(0)->getAttribute('src');
                                    $localPath=str_replace('http://www.youtube.com/embed/','',$localPath);
                                    $localPath='http://youtu.be/'.$localPath;
                                    $pos = stripos($localPath, '?');
                                    $localPath=substr($localPath, 0, $pos);
                                    
                                }
                                
                                
                                $mediaPath = $localPath;
                                $type = 'link';
                            }
                            if(isset($this->medias[$i]->embedCode) || (file_exists($mediaPath) && (substr($mediaPath, -4)=='.gif' 
                            || substr($mediaPath, -4)=='.jpg' 
                            || substr($mediaPath, -4)=='.png' 
                            || substr($mediaPath, -4)=='.flv' 
                            || substr($mediaPath, -4)=='.mp4' 
                            || substr($mediaPath, -4)=='.mp3'
                            || substr($mediaPath, -4)=='.JPG'
                            || substr($mediaPath, -5)=='.jpeg'
                            || substr($mediaPath, -4)=='.PNG'))){ // If the media file exist
                                $mediaLinkedToMember = new NingMedia($this->medias[$i]->title, $localPath, $owner_id[0]->id, $this->medias[$i]->description, $type);

                                $mediasLinkedToMembers[] = $mediaLinkedToMember;
                            }
                            else{
                                $this->fileSkipped[] = array(
                                    'title' => $this->medias[$i]->title,
                                    'mediapath' => $mediaPath,
                                    'owner_id' => $owner_id[0]->id,
                                    'description' => 'File does not exist or wrong extension'
                                );
                                $this->max++;
                                if($this->max > $this->NumberOfMedias){
                                    break;
                                }
                            }
                        }
                    }
                
            }
        }
        return $mediasLinkedToMembers;
    }

    function ningMediaToBpGalleryDB($ningMedias){
        global $wpdb;
        $bpGalleryManager = new NingImporterBPGalleryManager(); 
        if($ningMedias && isset($ningMedias[0])){ // There is some media to upload
            for($i=0; $i<count($ningMedias); $i++){
                $bpGalleryManager->upload_ning_media($ningMedias[$i], $this->mediaType, $this->fileSkipped, $this->realNumberOfMediaUpload);
            }
        }
        global $blog_id;
        $_SESSION['bp_media_ning_importer_start_index_'.$blog_id] = $this->max;
    }

    function import_ning_medias() {
        //echo 'Go to import ning media <br>';
        global $blog_id;

        $ningMedias = $this->jsonToNingMedia($_SESSION['bp_media_ning_importer_start_index_'.$blog_id]);
        $this->ningMediaToBpGalleryDB($ningMedias);
        update_option('real_number_of_media_uploaded', get_option('real_number_of_media_uploaded')+$this->realNumberOfMediaUpload);
        
        $this->response = array (
            'totalNumberOfMedia' => $this->NumberOfMedias,
            'numberOfMediaUpload' => $this->max,
            'realNumberOfMediaUploaded' => get_option('real_number_of_media_uploaded'),
            'filesSkipped' => $this->fileSkipped
        );
        return $this->response;
    }

}

?>