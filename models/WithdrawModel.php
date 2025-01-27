<?php
namespace Tutor\Models;

/**
 * Class WithdrawModel
 * @since 2.0.7
 */
class WithdrawModel {
    /**
     * All withdraw status
     */
    const STATUS_PENDING    = 'pending';
    const STATUS_APPROVED   = 'approved';
    const STATUS_REJECTED   = 'rejected';

    /**
     * Get withdraw summary info for an user
     *
     * @param  int $instructor_id
     * @return array|object|null|void
     *
     * @since 2.0.7
     */
    public static function get_withdraw_summary( $instructor_id ) {
        global $wpdb;

        $maturity_days = tutor_utils()->get_option( 'minimum_days_for_balance_to_be_available' );
        $completed = array(
            'wc-completed',
            'completed',
            'complete',
        );
        $complete_status = "'" . implode( "','", $completed ) . "'";

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT ID, display_name, 
                    total_income,total_withdraw, 
                    (total_income-total_withdraw) current_balance, 
                    total_matured,
                    greatest(0, (total_income - total_withdraw) - total_matured ) available_for_withdraw 
                
                FROM (
                        SELECT ID,display_name, 
                    COALESCE((select SUM(instructor_amount) from {$wpdb->prefix}tutor_earnings where order_status IN({$complete_status}) group by user_id having user_id=u.ID ),0) total_income,
                    
                        COALESCE((
                        select sum(amount) total_withdraw from {$wpdb->prefix}tutor_withdraws 
                        where status !=%s
                        group by user_id
                        having user_id=u.ID
                    ),0) total_withdraw,
                
                    COALESCE((
                        SELECT SUM(instructor_amount) from(
                            SELECT user_id, instructor_amount, created_at, DATEDIFF(NOW(),created_at) AS days_old FROM {$wpdb->prefix}tutor_earnings
                        ) a
                        WHERE days_old <= %d
                        GROUP BY user_id
                        HAVING user_id = u.ID
                    ),0) total_matured
                    
                FROM {$wpdb->prefix}users u WHERE u.ID=%d
                
                ) a",
                self::STATUS_REJECTED,
                $maturity_days,
                $instructor_id
            )
        );

        return $data;
    }

    /**
     * Get withdrawal history
     *
     * @param int   $user_id | optional.
     * @param array $filter | ex:
     * array('status' => '','date' => '', 'order' => '', 'start' => 10, 'per_page' => 10,'search' => '')
     *
     * @return object
     */
    public static function get_withdrawals_history( $user_id = 0, $filter = array(), $start=0, $limit=20 ) {
        global $wpdb;

        $filter = (array) $filter;
        extract( $filter );

        $query_by_status_sql = '';
        $query_by_user_sql   = '';

        if ( ! empty( $status ) ) {
            $status = (array) $status;
            $status = "'" . implode( "','", $status ) . "'";

            $query_by_status_sql = " AND status IN({$status}) ";
        }

        if ( $user_id ) {
            $query_by_user_sql = " AND user_id = {$user_id} ";
        }

        // Order query @since v2.0.0
        $order_query = '';
        if ( isset( $order ) && '' !== $order ) {
            $order_query = "ORDER BY  	created_at {$order}";
        } else {
            $order_query = 'ORDER BY  	created_at DESC';
        }

        // Date query @since v.2.0.0
        $date_query = '';
        if ( isset( $date ) && '' !== $date ) {
            $date_query = "AND DATE(created_at) = CAST( '$date' AS DATE )";
        }

        // Search query @since v.2.0.0
        $search_term_raw = empty($search) ? '' : $search;
        $search_query = '%%';
        if ( !empty( $search_term_raw ) ) {
            $search_query = '%' . $wpdb->esc_like( $search_term_raw ) . '%';
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(withdraw_id)
			FROM 	{$wpdb->prefix}tutor_withdraws  withdraw_tbl
					INNER JOIN {$wpdb->users} user_tbl
						ON withdraw_tbl.user_id = user_tbl.ID
			WHERE 	1 = 1
					{$query_by_user_sql}
					{$query_by_status_sql}
					{$date_query}
					AND (user_tbl.display_name LIKE %s OR user_tbl.user_login LIKE %s OR user_tbl.user_nicename LIKE %s OR user_tbl.user_email = %s)
			",
                $search_query,
                $search_query,
                $search_query,
                $search_term_raw
            )
        );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 	withdraw_tbl.*,
					user_tbl.display_name AS user_name,
					user_tbl.user_email
				FROM {$wpdb->prefix}tutor_withdraws withdraw_tbl
					INNER JOIN {$wpdb->users} user_tbl
							ON withdraw_tbl.user_id = user_tbl.ID
				WHERE 1 = 1
					{$query_by_user_sql}
					{$query_by_status_sql}
					{$date_query}

					AND (user_tbl.display_name LIKE %s OR user_tbl.user_login LIKE %s OR user_tbl.user_nicename LIKE %s OR user_tbl.user_email = %s)
				{$order_query}
				LIMIT %d, %d
			",
                $search_query,
                $search_query,
                $search_query,
                $search_term_raw,
                $start,
                $limit
            )
        );

        $withdraw_history = array(
            'count'   => $count ? $count : 0,
            'results' => is_array($results) ? $results : array(),
        );

        return (object) $withdraw_history;
    }

    /**
     * Get withdraw method for a specific
     *
     * @param int $user_id
     *
     * @return bool|mixed
     */
    public static function get_user_withdraw_method( $user_id = 0 ) {
        $user_id = tutor_utils()->get_user_id( $user_id );
        $account = get_user_meta( $user_id, '_tutor_withdraw_method_data', true );

        if ( $account ) {
            return maybe_unserialize( $account );
        }

        return false;
    }
}