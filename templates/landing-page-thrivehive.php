<?php
/**
 * Template Name: Landing Page (ThriveHive)
 * 
 * A template for making a ThriveHive-powered landing page with SEO benefits and the best practices for a well-performing landing page.
 *
*/

add_filter('genesis_pre_get_option_site_layout', '__genesis_return_full_width_content');

remove_theme_support( 'genesis-menus' );
remove_action( 'genesis_loop', 'genesis_do_loop' );

// custom loop for agents

add_action( 'genesis_loop', 'landing_page_loop');

function landing_page_loop() {

    //* Use old loop hook structure if not supporting HTML5
    if ( ! genesis_html5() ) {
        landing_page_legacy_loop();
        return;
    }

    if ( have_posts() ) : while ( have_posts() ) : the_post();
            do_action( 'genesis_before_entry' );
            printf( '<article %s>', genesis_attr( 'entry' ) );
                do_action( 'genesis_entry_header' );
                do_action( 'genesis_before_entry_content' );
                printf( '<div %s>', genesis_attr( 'entry-content' ) );
                    echo '<div class="left-column-container"><div class="left-inner">';
                        do_action( 'genesis_entry_content' );
                    echo '</div></div>';
                    echo '<div class="right-column-container">';
                        renderFeaturedImage();
                        renderForm();
                    echo '</div>';
                echo '</div>'; //* end .entry-content
                do_action( 'genesis_after_entry_content' );
                do_action( 'genesis_entry_footer' );
            echo '</article>';
            do_action( 'genesis_after_entry' );
        endwhile; //* end of one post
        do_action( 'genesis_after_endwhile' );
    else : //* if no posts exist
        do_action( 'genesis_loop_else' );
    endif; //* end loop
}

function landing_page_legacy_loop() {
    global $loop_counter;
    $loop_counter = 0;
    if ( have_posts() ) : while ( have_posts() ) : the_post();
        do_action( 'genesis_before_post' );
        printf( '<div class="%s">', join( ' ', get_post_class() ) );
            do_action( 'genesis_before_post_title' );
            do_action( 'genesis_post_title' );
            do_action( 'genesis_after_post_title' );
            do_action( 'genesis_before_post_content' );
            echo '<div class="entry-content">';
            echo '<div class="left-column-container"><div class="left-inner">';
                do_action( 'genesis_post_content' );
            echo '</div></div>'; //* end .left
            echo '<div class="right-column-container">';
                renderFeaturedImage();
                renderForm();
            echo '</div>'; //* end .right
            echo '</div>'; //* end .entry-content
            do_action( 'genesis_after_post_content' );
        echo '</div>'; //* end .entry
        do_action( 'genesis_after_post' );
        $loop_counter++;
    endwhile; //* end of one post
        do_action( 'genesis_after_endwhile' );
    else : //* if no posts exist
        do_action( 'genesis_loop_else' );
    endif; //* end loop
}

function renderFeaturedImage() {
    if (has_post_thumbnail( $post->ID )) : ?>
    <div id="img-holder">
        <a id="img-container" href="#contact-form">
            <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); ?>
            <img id="landing-page-featured-image" src="<?php echo $image[0]; ?>" alt="Get a Free Marketing Consultation" />
        </a>
    </div>
    <?php endif;
}

function renderForm() {
    $landing_form_id = get_option('th_landingform_id');
    $tracker_id = get_option('th_tracking_code');
    $env = get_option('th_environment');
    if($env == false){
        $env = "my.thrivehive.com";
    }
   $redirect = get_post_custom_values('th_data');
    /*

        In order to provide a custom redirect for users who submit this form, create a custom field on the page called "url"

        On the post editing page (make sure "custom fields" enabled under "screen options"

    */
    $redirect_url = !empty($redirect[0]) == true ? json_decode( $redirect[0], true )['redirectUrl'] : '/'; //default to homepage
    if( $landing_form_id !== false || $landing_form_id == 0 && $tracker_id !== false){
        ?>
        <style>
            #img-holder {
                background-color: #F3F3F4;
                padding: 15px;
                text-align: center;
                border: 1px solid #E4E5E6;
                -webkit-border-top-left-radius: 5px;
                -webkit-border-top-right-radius: 5px;
                -moz-border-radius-topleft: 5px;
                -moz-border-radius-topright: 5px;
                border-top-left-radius: 5px;
                border-top-right-radius: 5px;
                float: right;
                width: 328px;
            }

            #img-container {
                max-width: 306px;
                box-shadow: 0 0 10px #CCC;
                -moz-box-shadow: 0 0 10px #CCC;
                -webkit-box-shadow: 0 0 10px #CCC;
                cursor: pointer;
                display: block;
                background-color: #fff;
                padding: 5px;
                text-align: center;
            }

            #landing-page-featured-image, #content #landing-page-featured-image {
                max-width: 306px;
                display: inline;
                border: none;
                box-shadow: none;
                -moz-box-shadow: none;
                -webkit-box-shadow: none;
            }

            .left-column-container {
                width: 60%;
            }

            .left-inner {
            	padding-right: 30px;
            }

            .right-column-container {
                width: 40%;
                margin-bottom: 30px;
            }

			.right-column-container, .right-column-container *, .right-column-container *:before, .right-column-container *:after {
			    -moz-box-sizing: border-box; 
			    -webkit-box-sizing: 
			    border-box; box-sizing: border-box;
			}

            .left-column-container, .right-column-container {
                float: left;
            }

            form.hiveform {
                color: #444444;
                background: #EEE;
                text-align: left;
                font-size: 1em;
                font-family: inherit;
                width: 328px;
                float: right;
                padding: 25px 20px;
                -webkit-border-bottom-left-radius: 5px;
                -webkit-border-bottom-right-radius: 5px;
                -moz-border-radius-bottomleft: 5px;
                -moz-border-radius-bottomright: 5px;
                border-bottom-left-radius: 5px;
                border-bottom-right-radius: 5px;                
            }

            form.hiveform label {
                display:inline-block;
                float: left;
                clear: both;
                margin-bottom: 5px;
                width: 135px;
         	}

            form.hiveform span.required-star {
                color: red;
            }

            form.hiveform input[type=text], form.hiveform textarea {
                display: inline-block;
                float: right;
                width: 150px;
                margin-bottom: 10px;
                padding: 5px;
            }

            form.hiveform textarea {
                resize: vertical;
         	}

            form.hiveform input.hivesubmit {
                color: white;
                background-color: #28738F;
                font-size: 1em;
                font-weight: bold;
                cursor: pointer;
                padding: 9px 12px 7px;
                border-radius: 5px;
                -moz-border-radius: 5px
                -webkit-border-radius: 5px;
                -o-border-radius: 5px;
                border: none;
                display: block;
                float: right;
                clear: both;
                font-family: sans-serif;
            }

            .error-block {     
                min-height: 1.6em; 
                clear: both; 
                color: #E10707; 
            } 

			@media only screen and (max-width: 1023px) {
				.left-column-container, .right-column-container {
					float: none;
					width: 100%;
				}
			}

            @media only screen and (max-width : 600px) { 
                form.hiveform input[type=text], form.hiveform textarea,  form.hiveform select {                 
                    clear: both; 
                    display: block; 
                    float: none;  
                    width: 220px; 
                } 
               form.hiveform input.hivesubmit {             
                    clear: both; 
                    float: none; 
                    margin-top: 10px;
                } 
            } 

			@media only screen and (max-width: 480px) {
				form.hiveform {
					width: 253px;
				}
			}
			
            </style>

            <a name="contact-form"></a>

            <form class="hiveform" action="//<?php echo $env; ?>/Webform/FormHandler" method="post" id="thrivehive-form278"> 
                  <label for="input5-form278">First Name<span class="required-star">*</span></label><input type="text" name="list.first_name" id="input5-form278" /> 
                <div id="input5-form278-errors" class="error-block"></div> 
                  <label for="input6-form278">Last Name</label><input type="text" name="list.last_name" id="input6-form278" /> 
                <div id="input6-form278-errors" class="error-block"></div> 
                  <label for="input7-form278">Phone</label><input type="text" name="list.phone" id="input7-form278" /> 
                <div id="input7-form278-errors" class="error-block"></div> 
                  <label for="input8-form278">Email<span class="required-star">*</span></label><input type="text" name="list.email" id="input8-form278" /> 
                <div id="input8-form278-errors" class="error-block"></div> 
                  <label for="input14-form278">Comments</label><textarea type="text" name="list.comments" id="input14-form278"></textarea> 
                  
                 <div id="input14-form278-errors" class="error-block"></div> 
                
                <input type="hidden" name="meta.redirectUrl" id="meta_redirectUrl" value="<?php echo $redirect_url; ?>" /> 
                <input type="submit" value="Submit" class="hivesubmit"/> 
            </form> 
             
            <script type="text/javascript"> 
                var domreadyScriptUrl = (("https:" == document.location.protocol) ? "https://" : "http://") + "<?php echo $env?>/content/js/domready.js"; 
                document.write(unescape("%3Cscript src%3D%27" + domreadyScriptUrl + "%27 type%3D'text/javascript'%3E%3C/script%3E")); 
                var validateScriptUrl = (("https:" == document.location.protocol) ? "https://" : "http://") + "<?php echo $env?>/content/js/validate.min.js"; 
                document.write(unescape("%3Cscript src%3D%27" + validateScriptUrl + "%27 type%3D'text/javascript'%3E%3C/script%3E")); 
            </script> 
            <script type="text/javascript"> 
                DomReady.ready(function () { 
                    $util.SetFormHiddenID("CA-uid","thrivehive-form278"); 
                    $util.SetFormSessionID("CA-sess","thrivehive-form278"); 
                    $util.AddHiddenFieldInForm("meta.form-id","thrivehive-form278","<?php echo $landing_form_id; ?>"); 
                    $util.AddHiddenFieldInForm("meta.trackerid","thrivehive-form278","<?php echo $tracker_id; ?>"); 
                
                        new FormValidator("thrivehive-form278", [{ 
                            name: "input5-form278", 
                            display: "First Name", 
                            rules: "required" 
                        }], function (errors) { 
                            var errorString = ""; 
                            if (errors.length > 0) { 
                                for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                                    errorString += errors[i].message + "<br />"; 
                                } 
                            } 
                            document.getElementById("input5-form278-errors").innerHTML = errorString; 
                        }) 
                 
                        new FormValidator("thrivehive-form278", [{ 
                            name: "input7-form278", 
                            display: "Phone", 
                            rules: "valid_phone_us" 
                        }], function (errors) { 
                            var errorString = ""; 
                            if (errors.length > 0) { 
                                for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                                    errorString += errors[i].message + "<br />"; 
                                } 
                            } 
                            document.getElementById("input7-form278-errors").innerHTML = errorString; 
                        }) 
                 
                        new FormValidator("thrivehive-form278", [{ 
                            name: "input8-form278", 
                            display: "Email", 
                            rules: "required|valid_email" 
                        }], function (errors) { 
                            var errorString = ""; 
                            if (errors.length > 0) { 
                                for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                                    errorString += errors[i].message + "<br />"; 
                                } 
                            } 
                            document.getElementById("input8-form278-errors").innerHTML = errorString; 
                        }) 
                 
                }); 
            </script>

        <?php

    } else {
        echo '<p class="error" style="color: red;">Landing page ID or Account ID is not set. Could not render landing page form. Create a form in ThriveHive and get the form id from that form.</p>';
    };
}

?>

<? genesis(); ?>