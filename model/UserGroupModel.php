<?php
class UserGroupModel extends BaseModel {
	
	protected $_table = 'user_group';
	
	public function getById($id) {
		try {
			$result = $this->find ( "id='{$id}'" );
			return $result;
		} catch ( Exception $e ) {
			echo $e;
		}
	}
	
	public function getsGroupByUID($uid) {
		try {
			$result = $this->find ( "user_id='{$uid}'" );
			return $result;
		} catch ( Exception $e ) {
			echo $e;
		}
	}
}