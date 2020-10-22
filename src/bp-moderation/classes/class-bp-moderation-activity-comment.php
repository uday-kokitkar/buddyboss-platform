<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @since   BuddyBoss 1.5.4
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity Comment.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Activity_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity';

	/**
	 * BP_Moderation_Activity_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'activity' ) ) {
			return;
		}

		$this->item_type = self::$moderation_type;

//		add_filter( 'bp_activity_comments_get_join_sql', array( $this, 'update_join_sql' ), 10 );
//		add_filter( 'bp_activity_comments_get_where_conditions', array( $this, 'update_where_sql' ), 10 );

	}

	/**
	 * Prepare activity Comment Join SQL query to filter blocked Activity Comment
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Activity Comment Join sql.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql ) {
		$join_sql .= $this->exclude_joint_query( 'a.id' );

		return $join_sql;
	}

	/**
	 * Prepare activity Comment Where SQL query to filter blocked Activity Comment
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $where_conditions Activity Comment Where sql.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions ) {
		$where                   = array();
		$where['activity_comment_where'] = $this->exclude_where_query();

		/**
		 * Exclude Blocked Member activity Comment
		 */
		$members_where = $this->exclude_member_activity_comment_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Exclude Blocked activity's activity Comment
		 */
		$activity_where = $this->exclude_activity_activity_comment_query();
		if ( $activity_where ) {
			$where['activity_where'] = $activity_where;
		}

		/**
		 * Filters the activity comment Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of activity comment moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_activity_comment_get_where_conditions', $where );

		$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related activity comment
	 *
	 * @return string|bool
	 */
	private function exclude_member_activity_comment_query() {
		$sql              = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = '( a.user_id NOT IN ( ' . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get SQL for Exclude Blocked Activity related activity comment
	 *
	 * @return string|bool
	 */
	private function exclude_activity_activity_comment_query() {
		$sql              = false;
		$hidden_activity_ids = BP_Moderation_Activity::get_sitewide_hidden_ids();
		$hidden_activity_comment_ids = self::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_activity_ids ) ) {
			$sql = '( a.item_id NOT IN ( ' . implode( ',', $hidden_activity_ids ) . ' ) AND a.secondary_item_id NOT IN ( ' . implode( ',', $hidden_activity_comment_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get blocked Activity Comments ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		//Todo : Merge comment of hidden activity & Inner comment ids
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}
}
