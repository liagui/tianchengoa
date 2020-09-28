<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use Illuminate\Support\Facades\Redis;
use App\Tools\CurrentAdmin;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller {

    


     /*
     * @param  description   获取用户列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *     school_id    学校id  （非必填项）
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public function getAdminUserList(){

        $result = Adminuser::getAdminUserList(self::$accept_data);

        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }

    /*
     * @param  description  更改用户状态（启用、禁用）
     * @param  参数说明       body包含以下参数[
     *     id           用户id
     * ]
     * @param author    lys
     * @param ctime     2020-09-03
     */

    public function upUserForbidStatus(){
        $data =  self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) || empty($data['id']) || is_int($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'账号id为空或缺少或类型不合法']);
        }
        if(in_array($data['id'], [1])){
           return response()->json(['code'=>201,'msg'=>'admin账户禁止禁用']);
        }
        $userInfo = Adminuser::getUserOne(['id'=>$data['id']]);
        if($userInfo['code'] !=200){
            return response()->json(['code'=>$userInfo['code'],'msg'=>$userInfo['msg']]); 
        }   
        if($userInfo['data']['is_forbid'] == 1)  $updateArr['is_forbid'] = 0;  else  $updateArr['is_forbid'] = 1; 
        // $updateArr['update_time']= date('Y-m-d H:i:s');

        $result = Adminuser::where(['id'=>$data['id']])->update($updateArr);
        
        if($result){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/upUserForbidStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'Success']);    
        }else{
            return response()->json(['code'=>204,'msg'=>'网络超时，请重试']);    
        }
    }
    /*
     * @param  description  更改用户状态（删除）
     * @param  参数说明       body包含以下参数[
     *     id           用户id
     * ]
     * @param author    lys
     * @param ctime     2020-04-29   7.11
     */
    public function upUserDelStatus(){
        $data =  self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) || empty($data['id']) || is_int($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'账号id为空或缺少或类型不合法']);
        }
        $role_id = isset(AdminLog::getAdminInfo()->admin_user->role_id) ? AdminLog::getAdminInfo()->admin_user->role_id : 0;
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        // //7.11  begin
        // $zongxiaoAdminArr = Adminuser::where(['id'=>$data['id']])->first(); 
        // $zongxiaoRoleArr = Roleauth::where('id',$zongxiaoAdminArr['role_id'])->first();
        // if($zongxiaoRoleArr['is_super'] == 1 && $zongxiaoSchoolArr['super_id'] == $zongxiaoAdminArr['id']){
        //     return response()->json(['code'=>203,'msg'=>'超级管理员信息，不能删除']);
        // }       
         //7.11  end
         if(in_array($data['id'], [1])){
           return response()->json(['code'=>201,'msg'=>'admin账户禁止删除']);
        }
        $userInfo = Adminuser::findOrFail($data['id']);
        $userInfo->is_del = 0;
        if($userInfo->save()){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>  !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id']  ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/upUserDelStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'Success']);    
        }else{
            return response()->json(['code'=>204,'msg'=>'网络超时，请重试']);    
        }
    }
    /*
     * @param  description  更改是否使用状态
     * @param  参数说明       body包含以下参数[
     *     id           用户id
     * ]
     * @param author    lys
     * @param ctime     2020-04-29   7.11
     */
    public function upUseStatus(){
        $data =  self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) || empty($data['id']) || is_int($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'账号id为空或缺少或类型不合法']);
        }
        if( !isset($data['is_use']) || empty($data['is_use']) || is_int($data['is_use']) ){
            return response()->json(['code'=>201,'msg'=>'是否使用为空或缺少或类型不合法']);
        }   
        $updateArr['audit_course_desc'] =  !isset($data['audit_course_desc']) || empty($data['audit_course_desc'])?'':$data['audit_course_desc']; 
        $role_id = isset(AdminLog::getAdminInfo()->admin_user->role_id) ? AdminLog::getAdminInfo()->admin_user->role_id : 0;
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $updateArr['is_use'] = $data['is_use'];
        $updateArr['updated_at'] = date('Y-m-d H:i:s');
        if(Adminuser::where(['id'=>$data['id']])->update($updateArr)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>  !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id']  ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/upUserDelStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'Success']);    
        }else{
            return response()->json(['code'=>204,'msg'=>'网络超时，请重试']);    
        }
    }
     /*
     * @param  description   获取添加账号信息
     * @param  id            当前登录用户id
     * @param author    lys
     * @param ctime     2020-09-04
    */
    public function getInsertAdminUser(){
            $data =  self::$accept_data;
            $where['search'] = !isset($data['search']) && empty($data['search']) ?'':$data['search']; 
            $schoolData = School::where('school_name','like',"%".$where['search']."%")->where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
            $rolAuthArr = \App\Models\Roleauth::getRoleAuthAlls(['is_del'=>0],['id','role_name']);
            $arr = [
                'school'=>$schoolData,
                'role_auth'=>$rolAuthArr
            ];
            return response()->json(['code' => 200 , 'msg' => '获取信息成功' , 'data' => $arr]);
    }
    /*
     * @param  description   添加后台账号
     * @param  参数说明       body包含以下参数[
     *     school_id       所属学校id
     *     username         账号
     *     realname        姓名
     *     mobile          手机号
     *     sex             性别
     *     password        密码
     *     pwd             确认密码
     *     role_id         角色id
     *     teacher_id      关联讲师id串
     * ]
     * @param author    lys
     * @param ctime     2020-04-29   5.12修改账号唯一性验证
     */

    public function doInsertAdminUser(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                    // 'school_id' => 'required',
                    'username' => 'required',
                    // 'realname' => 'required',
                    'password'=>'required',
                    'pwd'=>'required',
                    'role_id' => 'required|integer',
                ],
                Adminuser::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if(strlen($data['password']) <8){
            return response()->json(['code'=>207,'msg'=>'密码长度不能小于8位']);
        }
        if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $data['password'])) {
            return response()->json(['code'=>207,'msg'=>'密码格式不正确，请重新输入']);
        }
        if($data['password'] != $data['pwd']){
            return response()->json(['code'=>206,'msg'=>'登录密码不一致']);
        }
        // if(!isset($data['school_id']) || empty($data['school_id'])){ //屠屠的思维
        //     $schoolIds = School::where(['is_open'=>0,'is_del'=>0])->select('id')->get()->toArray();
        //     $schoolIds = empty($schoolIds)  ?'':array_column($schoolIds, 'id');
        //     $data['school_id'] = $schoolIds == ''?'':implode(',',$schoolIds);
        // }else{
        //     $data['school_id'] = $data['school_id'];
        // }  
        if(!isset($data['school_id']) || empty($data['school_id'])){
            $data['school_id'] = 0;
        }
        if(isset($data['pwd'])){
            unset($data['pwd']);
        }
        $count = Adminuser::where('username',$data['username'])->count();
        if($count>0){
            return response()->json(['code'=>205,'msg'=>'用户名已存在']);
        }
       
        if(isset($data['/admin/adminuser/doInsertAdminUser'])){
            unset($data['/admin/adminuser/doInsertAdminUser']);
        }
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['create_id'] = !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id'];
        $data['create_time'] =  date('Y-m-d H:i:s');
        $result = Adminuser::insertAdminUser($data);
        if($result>0){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/doInsertAdminUser' , 
                'operate_method' =>  'insert' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return   response()->json(['code'=>200,'msg'=>'添加成功']);
        }else{
            return  response()->json(['code'=>203,'msg'=>'网络超时，请重试']);
        }
    }
    /*
     * @param  description   获取角色列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *  
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public function getAuthList(){
         $result =  Adminuser::getAuthList(self::$accept_data);
         return response()->json($result);
    }
    /*
     * @param  description   获取账号信息（编辑）
     * @param  参数说明       body包含以下参数[
     *      id => 账号id
     * ]
     * @param author    lys
     * @param ctime     2020-05-04
     */

    public function getAdminUserUpdate(){
        $data = self::$accept_data;
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $where['search'] = !isset($data['search']) && empty($data['search']) ?'':$data['search']; 
        if( !isset($data['id']) || empty($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'用户表示缺少或为空']);
        }    
        $adminUserArr = Adminuser::getUserOne(['id'=>$data['id']],['id','username','role_id','school_id']);
        if($adminUserArr['code'] != 200){
            return response()->json(['code'=>204,'msg'=>'用户不存在']);
        }
        $schoolData = School::where('school_name','like',"%".$where['search']."%")->where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
        $roleAuthArr = Roleauth::getRoleAuthAlls(['is_del'=>0],['id','role_name']); //角色信息
        $arr = [
            'school'=> $schoolData,
            'admin_user'=> $adminUserArr['data'],
            'role_auth' => $roleAuthArr, //角色
        ];
        return response()->json(['code'=>200,'msg'=>'获取信息成功','data'=>$arr]);

    }
    /*
     * @param  description   账号信息（编辑）
     * @param  参数说明       body包含以下参数[
     *      id => 账号id 
            school_id => 学校id  
            username => 账号名称
            realname => 真实姓名
            mobile => 联系方式
            sex => 性别
            password => 登录密码 
            pwd => 确认密码
            role_id => 角色id
            teacher_id => 老师id组
     * ]
     * @param author    lys
     * @param ctime     2020-05-04
     */

    public function doAdminUserUpdate(){
        $data = self::$accept_data;
        $role_id = isset(AdminLog::getAdminInfo()->admin_user->role_id) ? AdminLog::getAdminInfo()->admin_user->role_id : 0;
        $school_status = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : -1;
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $validator = Validator::make($data,
                [
                    
                    'username' => 'required',
                    // 'realname' => 'required',
                    // 'password'=>'required',
                    // 'pwd'=>'required',
                    'role_id' => 'required|integer',
                ],
                Adminuser::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if( !isset($data['id']) || empty($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'用户表示缺少或为空']);
        } 
        if(in_array($data['id'], [1])){
           return response()->json(['code'=>201,'msg'=>'admin账户禁止编辑']);
        }
        // if(!isset($data['school_id']) || empty($data['school_id'])){ //屠屠的思维
        //     $schoolIds = School::where(['is_open'=>0,'is_del'=>0])->select('id')->get()->toArray();
        //     $schoolIds = empty($schoolIds)  ?'':array_column($schoolIds, 'id');
        //     $data['school_id'] = $schoolIds == ''?'':implode(',',$schoolIds);
        // }else{
        //     $data['school_id'] = $data['school_id'];
        // } 
        if(!isset($data['school_id']) || empty($data['school_id'])){
             $data['school_id'] =0;
        }   
         //7.11  end  
        if(isset($data['password']) && isset($data['pwd'])){
         
            if(strlen($data['password']) <8){
                return response()->json(['code'=>207,'msg'=>'密码长度不能小于8位']);
            }
            if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $data['password'])) {
                return response()->json(['code'=>207,'msg'=>'密码格式不正确，请重新输入']);
            }
            if(!empty($data['password'])|| !empty($data['pwd']) ){
               if($data['password'] != $data['pwd'] ){
                    return ['code'=>206,'msg'=>'两个密码不一致'];
                }else{
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
            }
            unset($data['pwd']);
        }
     
        
        if(isset($data['/admin/adminuser/doAdminUserUpdate'])){
            unset($data['/admin/adminuser/doAdminUserUpdate']);
        }
        $where['username']   = $data['username'];
        $count = Adminuser::where($where)->where('id','!=',$data['id'])->count();
        if($count >=1 ){
             return response()->json(['code'=>205,'msg'=>'用户名已存在']);
        }  
      
        $admin_id  = !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id'];
      
            DB::beginTransaction();
            // if(Adminuser::where(['school_id'=>$data['school_id'],'is_del'=>1])->count() == 1){  //判断该账号是不是分校超管 5.14
            //     if(Roleauth::where(['school_id'=>$data['school_id'],'is_del'=>1])->count() <1){
            //         $roleAuthArr = Roleauth::where(['id'=>$data['role_id']])->select('auth_id')->first()->toArray();
            //         $roleAuthArr['role_name'] = '超级管理员';
            //         $roleAuthArr['auth_desc'] = '拥有所有权限';
            //         $roleAuthArr['is_super'] = 1;
            //         $roleAuthArr['school_id'] = $data['school_id'];
            //         $roleAuthArr['admin_id']  = $admin_id;
            //         $role_id = Roleauth::insertGetId($roleAuthArr);
            //         if($role_id<=0){
            //              return   response()->json(['code'=>500,'msg'=>'角色创建失败']);
            //         }
            //         $data['role_id'] = $role_id;
            //     }
            // }
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = Adminuser::where('id','=',$data['id'])->update($data);
            if($result){
             //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id ,
                    'module_name'    =>  'Adminuser' ,
                    'route_url'      =>  'admin/adminuser/doAdminUserUpdate' , 
                    'operate_method' =>  'update' ,
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return   response()->json(['code'=>200,'msg'=>'更改成功']);
            }else{
                 DB::rollBack();
                return   response()->json(['code'=>203,'msg'=>'网络超时，请重试']);    
            }
            
            
        
    }  
    /*
     * @param  description   登录账号权限（菜单栏）
     * @param  参数说明       body包含以下参数[
     *      id => 角色id
     * ]
     * @param author    lys
     * @param ctime     2020-05-05
     */

    public function getAdminUserLoginAuth($admin_role_id){
        if(empty($admin_role_id) || !intval($admin_role_id)){
            return ['code'=>201,'msg'=>'参数值为空或参数类型错误'];
        }
        $adminRole =  Roleauth::getRoleOne(['id'=>$admin_role_id,'is_del'=>0],['id','role_name','auth_id','map_auth_id']);

        if($adminRole['code'] != 200){
            return ['code'=>$adminRole['code'],'msg'=>$adminRole['msg']];
        }
        $adminRuths = Authrules::getAdminAuthAll($adminRole['data']['map_auth_id']);
         
        if($adminRuths['code'] != 200){
            return ['code'=>$adminRuths['code'],'msg'=>$adminRuths['msg']];
        }
        return ['code'=>200,'msg'=>'success','data'=>$adminRuths['data']];
    }
    
    /*
     * @param  description   后台用户修改密码
     * @param  参数说明       body包含以下参数[
     *     oldpassword       旧密码
     *     newpassword       新密码
     *     repassword        确认密码
     * ]
     * @param author    dzj
     * @param ctime     2020-07-11
     */
    public function doAdminUserUpdatePwd(){
        $data =  self::$accept_data;
        //判断传过来的数组数据是否为空
        if(!$data || !is_array($data)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断旧密码是否为空
        if(!isset($data['oldpassword']) || empty($data['oldpassword'])){
            return ['code' => 201 , 'msg' => '旧密码为空'];
        }
        
        //判断新密码是否为空
        if(!isset($data['newpassword']) || empty($data['newpassword'])){
            return ['code' => 201 , 'msg' => '新密码为空'];
        }
        
        //判断确认密码是否为空
        if(!isset($data['repassword']) || empty($data['repassword'])){
            return ['code' => 201 , 'msg' => '确认密码为空'];
        }
        
        //判断两次输入的密码是否相等
        if($data['newpassword'] != $data['repassword']){
            return ['code' => 202 , 'msg' => '两次密码输入不一致'];
        }
        
        //获取后端的用户id
        $admin_id  = !isset(CurrentAdmin::user()['id'])?0:CurrentAdmin::user()['id'];
        
        //根据用户的id获取用户详情
        $admin_info = Adminuser::where('id' , $admin_id)->first();
        
        //判断输入的旧密码是否正确
        if(password_verify($data['oldpassword']  , $admin_info['password']) === false){
            return response()->json(['code' => 203 , 'msg' => '旧密码错误']);
        }
        
        //开启事务
        DB::beginTransaction();
        
        //更改后台用户的密码
        $rs = Adminuser::where('id' , $admin_id)->update(['password' => password_hash($data['newpassword'], PASSWORD_DEFAULT) , 'updated_at' => date('Y-m-d H:i:s')]);
        if($rs && !empty($rs)){
            //事务提交
            DB::commit();
            return response()->json(['code' => 200 , 'msg' => '更改成功']);
        } else {
            //事务回滚
            DB::rollBack();
            return response()->json(['code' => 203 , 'msg' => '更改失败']);
        }
    }
}
