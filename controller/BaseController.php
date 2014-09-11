<?php
class BaseController extends Cola_Controller {
    
    public $tplExt = '.html';
    
    protected $mustLogin = 0;
    
    protected $token = '';
    
    protected $urlroot = '';
    
    public function __construct() {
        /* $this->token = ComTool::buildToken ();
        $this->assign ( 'token', $this->token ); */
        $urlroot = ComTool::urlRoot ();
        $this->urlroot = $urlroot;
        $mygroups = $_SESSION ['groups'];
        $this->assign ( 'mygroups', $mygroups );
        $this->assign ( 'urlroot', $urlroot );
        $this->assign ( 'wwwroot', WWW_ROOT );
        $this->assign ( 'isLogin', $this->isLogin () );
    }
    
    /**
     * 判断是否登录
     * @return boolean
     */
    protected function isLogin() {
        return isset ( $_SESSION ['islogin'] ) && $_SESSION ['islogin'] ? true : false;
    }
    
    /**
     * 获取当前登录用户
     * @return Ambigous <multitype:, unknown>
     */
    protected function getCurrentUser() {
        return isset ( $_SESSION ['user'] ) && $_SESSION ['user'] ? $_SESSION ['user'] : array ();
    }
    
    /**
     * 重新获取当前登录用户信息
     */
    protected function refreshCurrentUser() {
        $currUser = $this->getCurrentUser ();
        $currUser = UserData::getById ( $currUser ['id'] );
        $_SESSION ['user'] = $currUser;
        return $currUser;
    }
    
    /**
     * 获取某分类的购物车
     * @param unknown_type $categoryId
     * @return multitype:multitype: number unknown 
     */
    protected function getCart($categoryId) {
        $cart = array ();
        $cart ['products'] = array ();
        $cart ['totalPrice'] = 0;
        if (isset ( $_SESSION ['cart'] [$categoryId] )) {
            $products = $_SESSION ['cart'] [$categoryId];
            $totalPrice = 0;
            foreach ( $products as &$v ) {
                $v ['thisTotalPrice'] = intval ( $v ['price'] ) * intval ( $v ['quantity'] );
                $totalPrice += $v ['thisTotalPrice'];
            }
            $cart ['products'] = $products;
            $cart ['totalPrice'] = $totalPrice;
        }
        return $cart;
    }
    
    /**
     * 必须登录检查，若未登录跳转至登录页
     */
    protected function mustLoginCheck() {
        if ($this->mustLogin) {
            if (! $this->isLogin ()) {
                if (ComTool::isAjax ()) {
                
                } else {
                    $pathinfo = trim ( $_SERVER ['PATH_INFO'], '/\\' );
                    $returnUrl = urlencode ( ComTool::urlRoot () . $pathinfo );
                    Cola_Response::redirect ( ComTool::url ( "acc/login?returnUrl={$returnUrl}" ) );
                }
            }
        }
    }
}