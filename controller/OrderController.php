<?php
/**
 * 订单相关
 * @author Administrator
 *
 */
class OrderController extends BaseController {
	
	public function indexAction() {
		$this->display ();
	}
	
	/**
	 * 购物车
	 */
	public function cartAction() {
		$gid = intval ( base64_decode ( $this->param ( 'g' ) ) );
		$cid = intval ( base64_decode ( $this->param ( 'c' ) ) );
		$category = CategoryData::getById ( $cid );
		if ($category ['group_id'] != $gid) {
			exit ( 'wrong params' );
		}
		$cart = array ();
		$cart = $this->getCart ( $cid );
		$group = GroupData::getById ( $category ['group_id'] );
		$currUser = $currUserGroup = array ();
		$isLogin = $this->isLogin ();
		if ($isLogin) {
			$currUser = $this->getCurrentUser ();
			$userGroups = UserGroupData::getsGroupByUID ( $currUser ['id'] );
			foreach ( $userGroups as $userGroup ) {
				if ($group ['id'] == $userGroup ['group_id']) {
					$currUserGroup = $userGroup;
					break;
				}
			}
		}
		$this->assign ( 'group', $group );
		$this->assign ( 'currUser', $currUser );
		$this->assign ( 'currUserGroup', $currUserGroup );
		$this->assign ( 'category', $category );
		$this->assign ( 'products', $cart ['products'] );
		$this->assign ( 'totalPrice', $cart ['totalPrice'] );
		$this->display ();
	}
	
	/**
	 * 物品加入购物车
	 */
	public function acAction() {
		if (ComTool::isAjax ()) {
			$productId = intval ( $this->post ( 'proid', 0 ) );
			if (! $productId) {
				ComTool::ajax ( 100001, '服务器忙，请刷新重试' );
			}
			$product = GoodsData::getById ( $productId );
			if (! $product) {
				ComTool::ajax ( 100001, '服务器忙，请刷新重试' );
			}
			if (! isset ( $_SESSION ['cart'] )) {
				$_SESSION ['cart'] = array ();
			}
			$curCategory = $product ['category_id'];
			if (! isset ( $_SESSION ['cart'] [$curCategory] )) {
				$_SESSION ['cart'] [$curCategory] = array ();
			}
			if (isset ( $_SESSION ['cart'] [$curCategory] [$productId] ) && $_SESSION ['cart'] [$curCategory] [$productId]) {
				$productQuantity = intval ( $_SESSION ['cart'] [$curCategory] [$productId] ['quantity'] ) + 1;
				$_SESSION ['cart'] [$curCategory] [$productId] = array (
					'id' => $product ['id'], 
					'name' => $product ['name'], 
					'price' => $product ['price'], 
					'price_num' => $product ['price_num'], 
					'price_unit' => $product ['price_unit'], 
					'quantity' => $productQuantity 
				);
			} else {
				$_SESSION ['cart'] [$curCategory] [$productId] = array (
					'id' => $product ['id'], 
					'name' => $product ['name'], 
					'price' => $product ['price'], 
					'price_num' => $product ['price_num'], 
					'price_unit' => $product ['price_unit'], 
					'quantity' => 1 
				);
			}
			//计算总价
			ComTool::ajax ( 100000, 'ok' );
		}
	}
	
	/**
	 * update cart更新购物车物品数量+-
	 */
	public function ucAction() {
		if (ComTool::isAjax ()) {
			$type = $this->post ( 'type', 'inc' );
			$productId = intval ( $this->post ( 'proid', 0 ) );
			if (! $productId) {
				ComTool::ajax ( 100001, '服务器忙，请刷新重试' );
			}
			$product = GoodsData::getById ( $productId );
			if (! $product) {
				ComTool::ajax ( 100001, '服务器忙，请刷新重试' );
			}
			$curCategory = $product ['category_id'];
			$productInCart = $_SESSION ['cart'] [$curCategory] [$productId];
			$productQuantity = intval ( $productInCart ['quantity'] );
			switch ($type) {
				case 'inc' : //increment
					$productInCart ['quantity'] = $productQuantity + 1;
					$_SESSION ['cart'] [$curCategory] [$productId] = $productInCart;
					break;
				case 'dec' : //decrement
					$productInCart ['quantity'] = $productQuantity - 1;
					if ($productInCart ['quantity'] <= 0) {
						$_SESSION ['cart'] [$curCategory] [$productId] = array ();
						unset ( $_SESSION ['cart'] [$curCategory] [$productId] );
					} else {
						$_SESSION ['cart'] [$curCategory] [$productId] = $productInCart;
					}
					break;
				case 'rm' : //delete
					$_SESSION ['cart'] [$curCategory] [$productId] = array ();
					unset ( $_SESSION ['cart'] [$curCategory] [$productId] );
					break;
			}
			ComTool::ajax ( 100000, 'ok' );
		}
	}
	
	/**
	 * 提交订单
	 */
	public function goAction() {
		if (ComTool::isAjax ()) {
			if (! $this->isLogin ()) {
				ComTool::ajax ( Cola::getConfig ( '_error.mustlogin' ), '请先登录，即将跳转至登录页面' );
			}
			$mobile = trim ( $this->post ( 'mobile' ) );
			ComTool::checkEmpty ( $mobile, '请填写常用手机号' );
			if (! ComTool::isMobile ( $mobile )) {
				ComTool::ajax ( 100001, '请填写正确的手机号' );
			}
			$receiver = $this->post ( 'receiver', '' );
			ComTool::checkMaxLen ( $receiver, 16, "收货人姓名最多16位" );
			$addrDesc = $this->post ( 'addr_desc', '' );
			ComTool::checkMaxLen ( $addrDesc, 32, "详细位置最多32位" );
			$curCategory = $this->post ( 'cate', 0 );
			$curCategory = intval ( base64_decode ( $curCategory ) );
			if (! isset ( $_SESSION ['cart'] [$curCategory] )) {
				ComTool::ajax ( 100001, '购物车为空' );
			}
			$cart = $this->getCart ( $curCategory );
			if (! $cart) {
				ComTool::ajax ( 100001, '购物车为空' );
			}
			$groupName = $this->post ( 'group', '' );
            if (! $groupName) {
                $category = CategoryData::getById ( $curCategory );
                $group = GroupData::getById ( $category ['group_id'] );
                $groupName = $group ['name'];
            } else {
                $groupName = base64_decode ( $groupName );
            }
            $currUser = $this->getCurrentUser ();
            $data = array ();
            $orderId = ComTool::getOrderId ();
            $data ['id'] = $orderId;
            $data ['user_id'] = $currUser ['id'];
            $data ['user_name'] = $receiver;
            $data ['user_tel'] = $mobile;
            $data ['user_addr'] = "{$groupName} {$addrDesc}";
            $data ['create_time'] = $data ['update_time'] = time ();
            $data ['total_cost'] = $cart ['totalPrice'];
            $data ['status'] = '1';
            $res = OrderData::add ( $data );
            if ($res === false) {
                ComTool::ajax ( 100001, '服务器忙，请重试' );
            }
            $sql = "insert into order_detail(order_id,good_id,good_name,amount,`price`,`status`) values";
            foreach ( $cart ['products'] as $product ) {
                $sql .= "('{$orderId}','{$product['id']}','{$product['name']}','{$product['quantity']}','{$product['price']}','1'),";
            }
            $sql = trim ( $sql, ',' );
            $res = OrderData::sql ( $sql );
            if ($res === false) {
                ComTool::ajax ( 100001, '服务器忙，请重试' );
            }
            ComTool::ajax ( 100000, 'ok' );
		}
	}
    
    /**
     * 订单详情
     */
    public function detailAction() {
        $oid = $this->param ( 'o', 0 );
        $details = array ();
        $totalPrice = 0;
        if ($oid) {
            $sql = "SELECT * FROM `order` where id='{$oid}' and `status`='1' limit 1";
            $order = OrderData::sql ( $sql );
            if ($order) {
                $sql = "SELECT * FROM `order_detail` where order_id='{$oid}'";
                $details = OrderData::sql ( $sql );
                if ($details) {
                    foreach ( $details as $detail ) {
                        $totalPrice += intval ( $detail ['price'] * $detail ['amount'] );
                    }
                }
            }
        }
        $this->assign ( 'totalPrice', $totalPrice );
        $this->assign ( 'order', $order [0] );
        $this->assign ( 'details', $details );
        $this->display ();
    }
    
    /**
     * 删除订单
     */
    public function delAction() {
        if (ComTool::isAjax ()) {
            if (! $this->isLogin ()) {
                ComTool::ajax ( Cola::getConfig ( '_error.mustlogin' ), '请先登录，即将跳转至登录页面' );
            }
            $currUser = $this->getCurrentUser ();
            $orderId = $this->post ( 'oid', '' );
            if (! $orderId) {
                ComTool::ajax ( 100001, '未知订单' );
            }
            $updateTime = time ();
            $sql = "update `order` set `status`=4,update_time='{$updateTime}' where id='{$orderId}' and user_id='{$currUser['id']}'";
            $res = OrderData::sql ( $sql );
            if ($res === false) {
                ComTool::ajax ( 100001, '服务器忙，请重试' );
            }
            //暂时不删除订单详情(order_detail表)
            ComTool::ajax ( 100000, 'ok' );
        }
    }
	
	/**
	 * 取消订单
	 */
	public function cancelAction() {
		//提交10分钟内可取消订单
	}
}