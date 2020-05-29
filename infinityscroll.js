
jQuery(function($){
    // Google analytics code setup
    var _gaq = _gaq || [];
    
    //if there is not GA track code:
    // // _gaq.push(['_setAccount', 'UA-XXXXX-X']);
    // $("body").load(function() {
    //     setTimeout(function() {
    //         var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    //         ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    //         var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);      
    //     }, 1);
    // });
	
	//check if a page is loading to prevent next one to be loaded at the same time
	var loadNextPage = true;
 
	$(window).scroll(function(){
	    var newUrl = '';
	    //set up data for the ajax request
		var post_data = {
			"action": "loadmore",
			"query": infinityscroll_variables.posts,
			"page" : infinityscroll_variables.paged
		};
		//calculates when to fire inserting of a new article - when the last one is in viewport 
		if(  Math.round( jQuery("footer").offset().top ) < Math.round( jQuery(window).height() + jQuery(window).scrollTop() )  && loadNextPage === true){
			//send the ajax request to the php code (plugin) to get next article code
			$.ajax({
				url : infinityscroll_variables.ajaxurl,
				data: post_data,
				type: "POST",
				beforeSend: function( xhr ){
					loadNextPage = false; 
				},
				//get the data from the ajax request
				success:function( result ){
					if( result ) {
						$("article").after( result );
						loadNextPage = true;
						infinityscroll_variables.paged ++;
						//set a new uri in the browser's address bar as anchor to the newly added post
						newUrl = '/#post-' + $(".postID").last().attr('id')
                        window.history.replaceState(null, null, newUrl);
                        //If Google analytics - push new url as a pageview
                        if(_gaq){
                            setTimeout(function() {
                                _gaq.push(['_trackPageview', newUrl]);
                            }, 1);
                        }
					}
				}
			});
		}
	});
	
});
