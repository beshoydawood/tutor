<?php
/**
 * Template for displaying single course
 *
 * @since v.1.0.0
 *
 * @author Themeum
 * @url https://themeum.com
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

$topics = tutor_utils()->get_topics();
$course_id = get_the_ID();

?>


<?php do_action('tutor_course/single/before/topics'); ?>

<?php if($topics->have_posts()) { ?>
    <div class="tutor-single-course-segment  tutor-course-topics-wrap">
        <div class="tutor-course-topics-header">
            <div class="tutor-course-topics-header-left">
                <h4 class="tutor-segment-title"><?php _e('Topics for this course', 'tutor'); ?></h4>
            </div>
            <div class="tutor-course-topics-header-right">
				<?php
				$tutor_lesson_count = tutor_utils()->get_lesson()->post_count;
				$tutor_course_duration = get_tutor_course_duration_context($course_id);

				if($tutor_lesson_count) {
					echo "<span> $tutor_lesson_count";
					_e(' Lessons', 'tutor');
					echo "</span>";
				}
				if($tutor_course_duration){
					echo "<span>$tutor_course_duration</span>";
				}
				?>
            </div>
        </div>
        <div class="tutor-course-topics-contents">
			<?php

			$index = 0;

			if ($topics->have_posts()){
				while ($topics->have_posts()){ $topics->the_post();
					$index++;
					?>

                    <div class="tutor-course-topic <?php if($index == 1) echo "tutor-active"; ?>">
                        <div class="tutor-course-title">
                            <h4> <i class="tutor-icon-plus"></i> <?php the_title(); ?></h4>
                        </div>


                        <div class="tutor-course-lessons">

							<?php
							$lessons = tutor_utils()->get_lessons_by_topic(get_the_ID());
							if ($lessons->have_posts()){
								while ($lessons->have_posts()){ $lessons->the_post();

									$video = tutor_utils()->get_video_info();

									$play_time = false;
									if ($video){
										$play_time = $video->playtime;
									}

									$lesson_icon = $play_time ? 'tutor-icon-youtube' : 'tutor-icon-document-alt';
									?>

                                    <div class="tutor-course-lesson">
                                        <h5>
											<?php

											$lesson_title = "<i class='$lesson_icon'></i>";
											$lesson_title .= get_the_title();

											echo apply_filters('tutor_course/contents/lesson/title', $lesson_title, get_the_ID());
											?>
                                        </h5>
                                    </div>

									<?php
								}
								$lessons->reset_postdata();
							}
							?>
                        </div>
                    </div>
					<?php
				}
				$topics->reset_postdata();
			}
			?>
        </div>
    </div>
<?php } ?>


<?php do_action('tutor_course/single/after/topics'); ?>