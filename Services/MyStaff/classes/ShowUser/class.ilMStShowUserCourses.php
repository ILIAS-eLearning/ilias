<?php

/**
 * Class ilMStShowUserCourses
 *
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class ilMStShowUserCourses extends ilMStListCourses {

	/**
	 * @param array $arr_usr_ids
	 * @param array $options
	 *
	 * @return array|bool|int
	 */
	public static function getData(array $arr_usr_ids = array(), array $options = array()) {

		return parent::getData($arr_usr_ids, $options);
	}


	/**
	 * @param array  $arr_usr_ids
	 * @param array  $arr_filter
	 * @param string $tmp_table_user_matrix
	 *
	 * @return bool|string
	 */
	public static function createWhereStatement($arr_usr_ids, $arr_filter, $tmp_table_user_matrix) {
		/**
		 * @var $ilDB \ilDBInterface
		 */
		$ilDB = $GLOBALS['DIC']->database();

		if (!$arr_filter['usr_id']) {
			return false;
		}

		$where = parent::createWhereStatement($arr_usr_ids, $arr_filter, $tmp_table_user_matrix);
		$usr_filter = "usr_data.usr_id = " . $ilDB->quote($arr_filter['usr_id'], 'integer');

		if (empty($where)) {
			return ' WHERE ' . $usr_filter;
		} else {
			return $where . ' AND ' . $usr_filter;
		}
	}
}