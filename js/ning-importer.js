jQuery(document).ready( function() {
	function fireNingImportRequest(data) {
	    return jQuery.ajax({
			'dataType': "json",
			'type': 'POST',
			'url': NingImporter.ajaxurl,
			'data': data,
			'success': function(data){
				console.log('SUCCESS');
                        },
                        'error': function(jqXHR, textStatus, errorThrown) {
				console.log(jqXHR.responseText, textStatus, errorThrown);
			}
		});
	}

	function display_skipped_files(skippedFiles){
		var content = '';
		for(var i=0; i<skippedFiles.length; i++){
			content += skippedFiles[i]['title'] + ' -- ' + skippedFiles[i]['mediapath'] + ' -- ' + skippedFiles[i]['owner_id'] + ' -- ' + skippedFiles[i]['description'] +'<br>';
		}

		if(skippedFiles.length == 0){
			content = 'None';
		}

		return content;
	}

	jQuery('#ning-start-photos-import').on('click', function(e){
	    e.preventDefault();

	    var data = {
	        'action':'ning_importer_for_bp_gallery',
	        'mediaType': 'photos'
	    }

	    return jQuery.ajax({
			dataType: "json",
			type: 'POST',
			url: NingImporter.ajaxurl,
			data: data,
			success: function(data){
				console.log('SUCCESS');
				jQuery('#numberOfPhotosUploaded').html(data["numberOfMediaUpload"]);
				jQuery('#totalNumberOfPhotos').html(data["totalNumberOfMedia"]);
				jQuery('#file_skipped_from_ning').html(display_skipped_files(data["filesSkipped"]));
                                //setTimeout(jQuery('#ning-start-photos-import').trigger('click'),3000);
                                jQuery('#ning-start-photos-import').trigger('click');
		    },
		    error: function(jqXHR, textStatus, errorThrown) {
			  console.log(jqXHR.responseText, textStatus, errorThrown);
			}
		});
	});

	jQuery('#ning-start-videos-import').on('click', function(e){
	    e.preventDefault();

	    var data = {
	        'action':'ning_importer_for_bp_gallery',
	        'mediaType': 'videos'
	    }

	    return jQuery.ajax({
			dataType: "json",
			type: 'POST',
			url: NingImporter.ajaxurl,
			data: data,
			success: function(data){
				console.log('SUCCESS');
				jQuery('#numberOfVideosUploaded').html(data["numberOfMediaUpload"]);
				jQuery('#totalNumberOfVideos').html(data["totalNumberOfMedia"]);
				jQuery('#file_skipped_from_ning').html(display_skipped_files(data["filesSkipped"]));
                                setTimeout(jQuery('#ning-start-videos-import').trigger('click'),3000);
		    },
		    error: function(jqXHR, textStatus, errorThrown) {
			  console.log(jqXHR.responseText, textStatus, errorThrown);
			}
		});
	});



	jQuery('#ning-clean-import').on('click', function(e){
	    e.preventDefault();

	    var data = {
	        'action':'ning_importer_for_bp_gallery_clean'
	    }

	    return jQuery.ajax({
			dataType: "json",
			type: 'POST',
			url: NingImporter.ajaxurl,
			data: data,
			success: function(data){
				console.log('SUCCESS');
				jQuery('#numberOfVideosUploaded').html(0);
				jQuery('#totalNumberOfVideos').html(0);
				jQuery('#numberOfPhotosUploaded').html(0);
				jQuery('#totalNumberOfPhotos').html(0);
				jQuery('#file_skipped_from_ning').html('None');
		    },
		    error: function(jqXHR, textStatus, errorThrown) {
			  console.log(jqXHR.responseText, textStatus, errorThrown);
			}
		});
	});
});

