<?php
/**
 * Template for displaying course instructors/ instructor
 *
 * @since v.1.0.0
 *
 * @author Themeum
 * @url https://themeum.com
 *
 * @package TutorLMS/Templates
 * @version 1.4.3
 */



do_action( 'tutor_course/single/enrolled/before/instructors' );

$instructors = tutor_utils()->get_instructors_by_course();

if($instructors && count($instructors)) {
	?>
	<div class="tutor-mt-60">
		<h3 class="tutor-fs-5 tutor-fw-bold tutor-color-black tutor-mb-24">
			<?php _e( 'About the ' . ( count( (array) $instructors ) > 1 ? 'instructors' : 'instructor' ), 'tutor'); ?>
		</h3>
		<?php foreach($instructors as $instructor): ?>
			<div class="tutor-instructor-info-card tutor-mb-16 tutor-break-word">
				<div class="tutor-instructor-info-card-body tutor-d-sm-flex tutor-align-items-start tutor-px-32 tutor-py-24">
					<div class="tutor-ins-avatar tutor-flex-shrink-0 tutor-mr-sm-16">
						<img src="<?php echo get_avatar_url($instructor->ID); ?>" alt="instructor avatar" />
					</div>
					<div class="tutor-ins-rest">
						<div class="tutor-ins-title  tutor-fs-6 tutor-fw-medium  tutor-color-black">
							<a href="<?php echo tutor_utils()->profile_url($instructor->ID, true); ?>"><?php echo $instructor->display_name; ?></a>
						</div>
						<?php if ( ! empty($instructor->tutor_profile_job_title)): ?>
							<div class="tutor-ins-designation tutor-fs-7 tutor-color-muted tutor-mt-4">
								<?php echo $instructor->tutor_profile_job_title; ?>
							</div>
						<?php endif; ?>
						<div class="tutor-ins-summary tutor-fs-6 tutor-color-black-60 tutor-mt-20">
							<?php echo htmlspecialchars($instructor->tutor_profile_bio); ?>
						</div>
					</div>
				</div>
				<div class="tutor-instructor-info-card-footer tutor-d-sm-flex tutor-align-items-center tutor-justify-between tutor-px-32 tutor-py-16">
					<?php 
						$instructor_rating = tutor_utils()->get_instructor_ratings($instructor->ID);
						tutor_utils()->star_rating_generator_v2($instructor_rating->rating_avg, $instructor_rating->rating_count, true); 
					?>
					<div class="tutor-ins-meta tutor-d-flex">
						<div class="tutor-ins-meta-item tutor-color-design-dark tutor-d-flex tutor-align-items-center">
							<span class="tutor-icon-user-filled tutor-mr-8"></span>
							<span class="tutor-fs-6 tutor-fw-bold tutor-color-black tutor-mr-4">
								<?php echo tutor_utils()->get_total_students_by_instructor($instructor->ID); ?>
							</span>
							<span class="tutor-fs-7 tutor-color-black-60">
								<?php _e('Students', 'tutor'); ?>
							</span>
						</div>
						<div class="tutor-ins-meta-item tutor-color-design-dark tutor-d-flex tutor-align-items-center ">
							<span class="tutor-icon-mortarboard-line tutor-mr-8"></span>
							<span class="tutor-fs-6 tutor-fw-bold tutor-color-black tutor-mr-4">
								<?php echo tutor_utils()->get_course_count_by_instructor($instructor->ID); ?>
							</span>
							<span class="tutor-fs-7 tutor-color-black-60">
								<?php _e('Courses', 'tutor'); ?>
							</span>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

do_action( 'tutor_course/single/enrolled/after/instructors' );
