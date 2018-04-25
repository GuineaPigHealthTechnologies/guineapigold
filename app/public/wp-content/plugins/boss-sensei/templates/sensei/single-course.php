<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php get_sensei_header();  ?>

<?php 
global $woothemes_sensei, $post, $current_user, $woocommerce;

if ( ! is_singular('course') ) return;

// Get User Meta
wp_get_current_user();

?>
	<?php
	/**
	 * woocommerce_before_single_product hook
	 *
	 * @hooked woocommerce_show_messages - 10
	 */
	if ( Sensei_WC::is_woocommerce_active() ) {
		do_action( 'woocommerce_before_single_product' );
	} // End If Statement
	?>

        	<article <?php post_class( array( 'course', 'post' ) ); ?>>
                <!--Modification-->
                <section class="course-header">
                    <div class="table top">
                        <?php $img = get_the_post_thumbnail( $post->ID, 'course-single-thumb', array( 'class' => 'woo-image thumbnail alignleft') );

                        if ( '' == $img ) {

                            // Display Image Placeholder if none
                            if ( Sensei()->settings->get( 'placeholder_images_enable' ) ) {

                                $img = apply_filters( 'sensei_course_placeholder_image_url', '<img src="http://placehold.it/100x100&text=Course" class="woo-image thumbnail alignleft" />' );

                            } // End If Statement

                        } // End If Statement
                        ?>
                        <div class="table-cell <?php echo (esc_html($img))?'image':''; ?>">
                            <?php echo $img; ?>
                        </div>
                        <div class="table-cell content">
                            <span><?php _e('Course', 'boss-sensei')?></span>
                            <header><h1><?php the_title();?></h1></header>
                            <?php if ( !empty($post->post_excerpt) ) {
                                        $excerpt = $post->post_excerpt;
                                   } else {
                                       $excerpt = boss_sensei_custom_excerpt( $post->post_content );
                                   }
                                    echo '<p class="course-excerpt">' . $excerpt . '</p>';
                            ?>
                        </div>
                    </div>
                    <div class="table bottom">
                        <div class="table-cell categories">
                           <?php                                 
                            // Get Course Categories
                            echo get_the_term_list( $post->ID, 'course-category', '<span>', '</span><span>', '</span>' );
                            ?>
                        </div>
                        <div class="table-cell progress">
                        <?php

                        $html = '';
                        // Get Course Lessons
                        $lessons_completed = 0;
                        $course_lessons = $woothemes_sensei->post_types->course->course_lessons( $post->ID );
                        $total_lessons = count( $course_lessons );
                        // Check if the user is taking the course

                        if ( isset( $current_user->ID ) && 0 != intval ( $current_user->ID ) ) {
                            $is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_status' ) );
                        }
                        
                        if ( 0 < $total_lessons ) {
                            foreach ( $course_lessons as $lesson_item ){
                                $single_lesson_complete = false;
                                $post_classes = array( 'course', 'post' );
                                $user_lesson_status = false;
                                if ( is_user_logged_in() ) {
                                    // Check if Lesson is complete
                                    $user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_item->ID, $current_user->ID );
                                    $single_lesson_complete = WooThemes_Sensei_Utils::user_completed_lesson( $user_lesson_status );
                                    if ( $single_lesson_complete ) {
                                        $lessons_completed++;
                                        $post_classes[] = 'lesson-completed';
                                    } // End If Statement
                                } // End If Statement
                            }

                            if ( is_user_logged_in() && $is_user_taking_course ) {

                                $html = '<span class="course-completion-rate">' . sprintf( __( 'Currently completed %1$s of %2$s in total', 'boss-sensei' ), '######', $total_lessons ) . '</span>';
                                $html .= '<span class="percent">@@@@@%</span>';
                                $html .= '<div class="meter+++++"><span style="width: @@@@@%"></span></div>';

                                // Add dynamic data to the output
                                $html = str_replace( '######', $lessons_completed, $html );
                                $progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );
                                /* if ( 0 == $progress_percentage ) { $progress_percentage = 5; } */
                                $html = str_replace( '@@@@@', $progress_percentage, $html );
                                if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }
                                $html = str_replace( '+++++', $class, $html );

                                echo $html;
                            } // End If Statement
                        }
                        ?>
                        </div>
                    </div>
                    
                </section>
                
                <div id="course-video">
                    <a href="#" id="hide-video" class="button"><i class="fa fa-close"></i></a>
                    <?php
                    /**
                    * sensei_course_meta_video hook
                    *
                    * @hooked sensei_course_meta_video - 10 (outputs the video for course)
                    */
                    $course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );
                    if ( 'http' == substr( $course_video_embed, 0, 4) ) {
                        // V2 - make width and height a setting for video embed
                        $course_video_embed = wp_oembed_get( esc_url( $course_video_embed )/*, array( 'width' => 100 , 'height' => 100)*/ );
                    } // End If Statement
                    if ( '' != $course_video_embed ) {
                    ?><div class="course-video"><?php echo html_entity_decode($course_video_embed); ?></div><?php
                    } // End If Statement
                    ?> 
                </div>
                
                <section id="course-details">
                    <span class="course-statistic">
                        <?php
                        printf( _n( '%s Lesson', '%s Lessons', $total_lessons, 'boss-sensei' ), $total_lessons ); 
                        if(function_exists('Sensei_Course_Participants')) {
                            printf('<span>%s</span>', __(' / ', 'boss-sensei'));
                            do_action('boss_edu_participants');
                        } 
                        ?>
                    </span>
                    <div class="course-buttons">
                       <?php
                        if($course_video_embed) {
                        ?>
                        <a href="#" id="show-video" class="button"><i class="fa fa-play"></i><?php apply_filters( 'boss_edu_show_video_text', _e( 'Watch Introduction', 'boss-sensei' ) ) ?></a>
                        <?php } ?>
                        <?php // do_action( 'sensei_course_single_meta' ); This is deprecated since 1.9.0 ?>
                        <?php echo Sensei_Course::the_course_enrolment_actions(); ?>
                    </div>

                </section>

                <?php 
                $group_attached = get_post_meta( $post->ID, 'bp_course_group', true );
                $group_data = ( bp_is_active('groups') ) ? groups_get_group( array( 'group_id' => $group_attached ) ) : '';

                if ( !empty($group_attached) && $group_attached != '-1' && ! empty( $group_data->id ) ) {

                    $group_slug = trailingslashit(home_url()).bp_get_root_slug('groups').'/'.$group_data->slug;

                    $group_query = array(
                        'count_total' => '', // Prevents total count
                        'populate_extras' => false,
                        'type' => 'alphabetical',
                        'group_id' => absint( $group_attached ),
                        'group_role' => array( 'admin', 'member', 'mod' )
                    );
                    $group_users = new BP_Group_Member_Query( $group_query );
                    $forum_id = groups_get_groupmeta( $group_attached, 'forum_id' );
                    $members = groups_get_group_members( array( 'group_id' => $group_attached ) );
                    $mods = groups_get_group_mods($group_attached);
                    $admins = groups_get_group_admins($group_attached);
                    $admins_and_mods = [];
                    $member_ids = [];
                    foreach($mods as $mod) {
                        $admins_and_mods[] = $mod->user_id;
                    }
                    foreach($admins as $admin) {
                        $admins_and_mods[] = $admin->user_id;
                    }

                    foreach($members['members'] as $member) {
                        $member_ids[] = $member->user_id;
                    }

                    $user_id = get_current_user_id();

                    $access_array = array_merge($member_ids, $admins_and_mods);
                    $is_admin_mod = in_array($user_id, $admins_and_mods);
                    $is_member = in_array($user_id, $access_array);

                    //Send invite slug
                    if ( defined( 'BP_INVITE_ANYONE_SLUG' ) ) {
                        $send_invite_slug = BP_INVITE_ANYONE_SLUG;
                    } else {
                        $send_invite_slug = 'send-invites';
                    }

                    ?>
                    <div id="buddypress">
                        <div id="item-nav" class="course-group-nav">
                            <div role="navigation" id="object-nav" class="item-list-tabs no-ajax">
                                <ul>
                                    <?php if( 'hidden' !== $group_data->status || $is_member): ?>
                                    <li id="home-groups-li"><a href="<?php echo $group_slug; ?>" id="home"><?php _e('Home','boss-sensei'); ?></a></li>
                                    <?php endif; ?>

                                    <?php if($is_member): ?>

                                        <?php if(function_exists('bbpress') && !empty( $group_data->enable_forum )):?>
                                        <li id="nav-forum-groups-li"><a href="<?php echo $group_slug.'/forum/'; ?>" id="nav-forum"><?php _e('Forum','boss-sensei'); ?></a></li>
                                        <?php endif; ?>
                                        <li class="current selected" id="nav-experiences-groups-li"><a href="" id="nav-experiences"><?php _e('Course','boss-sensei'); ?></a></li>
                                        <li id="members-groups-li"><a href="<?php echo $group_slug.'/members/'; ?>" id="members"><?php _e('Members','boss-sensei'); ?><span><?php echo $group_users->total_users; ?></span></a></li>
                                        <?php	if ( bp_is_active( 'friends' ) && bp_groups_user_can_send_invites( $group_attached ) ): ?>
                                        <li id="invite-groups-li"><a href="<?php echo $group_slug.'/'. $send_invite_slug .'/'; ?>" id="invite"><?php _e('Send Invites','boss-sensei'); ?></a></li>
                                        <?php endif; ?>
                                        <?php if($is_admin_mod): ?>
                                        <li id="admin-groups-li"><a href="<?php echo $group_slug.'/admin/edit-details/'; ?>" id="admin"><?php _e('Manage','boss-sensei'); ?></a></li>
                                        <?php endif; ?>

                                    <?php elseif ( 'hidden' !== $group_data->status && is_user_logged_in() ): ?>
                                        <li id="request-membership-groups-li"><a id="request-membership" href="<?php echo $group_slug.'/request-membership/'; ?>">Request Membership</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                } ?>

                <?php
                //Single course content part template
                boss_sensei_single_course_content_part( $post->ID );
                ?>

            </article><!-- .post -->

	        <?php //do_action('sensei_pagination'); ?>

<?php get_sensei_footer(); ?>