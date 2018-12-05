<?php
namespace callApi\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use callApi\Common\ResultCommon;

/**
 * ControllerBase
 * This is the base controller for all controllers in the application
 *
 * @property \callApi\Auth\Auth auth
 */
class ControllerBase extends Controller
{
    /**
     * Execute before the router so we can determine if this is a private controller, and must be authenticated, or a
     * public controller that is open to all.
     *
     * @param Dispatcher $dispatcher
     * @return boolean
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerName();
        $api_token = $this->request->getHeader('api-token');
        if($api_token){
            $user_id = $this->auth->findFirstByToken($api_token);
            $resultCommon = new ResultCommon();
            //存在api_token
            if($user_id){
                $actionName = $dispatcher->getActionName();
                $sql = "select * from `permissions` where `profilesId` in (select id from `profiles` where id in (select `profilesId` from `users` where id = {$user_id})) and `resource` = '{$controllerName}' and `action` = '$actionName'";
                $profiles = $this->db->fetchOne($sql);
                if(empty($profiles)){
                    //不存在的api_token，直接报错
                    $result = $resultCommon->error('40002');
                    echo $result;
                    exit;
                }
                //开始判断该用户是否拥有此api的访问权限
            }else{
                //不存在的api_token，直接报错
                $result = $resultCommon->error('10000');
                echo $result;
                exit;
            }
        }else if ($this->acl->isPrivate($controllerName)) {
        // Only check permissions on private controllers

            // Get the current identity
            $identity = $this->auth->getIdentity();

            // If there is no identity available the user is redirected to index/index
            if (!is_array($identity)) {

                $this->flash->notice('You don\'t have access to this module: '.$controllerName);

                $dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'index'
                ]);
                return false;
            }

            // Check if the user have permission to the current option
            $actionName = $dispatcher->getActionName();
            if (!$this->acl->isAllowed($identity['profile'], $controllerName, $actionName)) {

                $this->flash->notice('You don\'t have access to this module: ' . $controllerName . ':' . $actionName);

                if ($this->acl->isAllowed($identity['profile'], $controllerName, 'index')) {
                    $dispatcher->forward([
                        'controller' => $controllerName,
                        'action' => 'index'
                    ]);
                } else {
                    $dispatcher->forward([
                        'controller' => 'user_control',
                        'action' => 'index'
                    ]);
                }

                return false;
            }
        }
    }
}
