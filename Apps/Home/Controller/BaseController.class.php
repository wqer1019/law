<?php
namespace Home\Controller;
use Think\Controller;


class BaseController extends Controller {

	/**
	 *  构造方法
	 */
	public function __construct() {
		parent::__construct();
		$this->loginCount();
		//获取导航参数
		$this->getNav();
		//获取网站信息
		$this->getWebInfo();
		//获取页脚参数
		$this->getFooter();
	}

    /**
     * 通过分类名字获取class_id
     * @return bool
     */
	public function getClassIdByName($name= ''){
        $classify = D('Class');
        $class = deep_in_array($name, $classify->getParents());
        if($class){
            return $class['class_id'];
        }else{
            return false;
        }
    }

	/**
	 * 获取网站信息
	 */
	public function getWebInfo() {
		$Setting = M ('Setting');
		$webInfoArr = $Setting->select();
		$webInfo = array();
		//处理网站信息数组
		foreach ($webInfoArr as $key=>$val) {
			$webInfo[$val['item']] = $val['value'];
		}
		$this->assign('webInfo',$webInfo);
	}

	/**
	 * 获取页面导航栏
	 */
	public function getNav() {
		$Class = D ('Class');

		$nav = $Class->getNav();
        /*foreach ($navList as $nav=>$val)
        {
            $nav[$val['class_id']] = $navList[$nav];
            if($val['father_id'] !=0)
            {
                $navList[$val['father_id']]['child'][] = $val;
                unset($val);
            }
        }*/
		$this->assign('navList',$nav);
	}

    public function get_array($id=0){
        $sql = "select id,title from class where pid= $id";
        $result = mysql_query($sql);//查询子类
        $arr = array();
        if($result && mysql_affected_rows()){//如果有子类
            while($rows=mysql_fetch_assoc($result)){ //循环记录集
                $rows['list'] = get_array($rows['id']); //调用函数，传入参数，继续查询下级
                $arr[] = $rows; //组合数组
            }
            return $arr;
        }
    }
    /**
	 * 获取页面页脚内容
	 */
	public function getFooter() {
		$Flink = M ('Flink');

		//获取院系设置
        //todo 这里的4暂时写死
		$condition['type_id'] = 4;
		$faculty = $Flink->where($condition)->select();
		unset($condition);

		//获取热门链接
		$condition['type_id'] = 2;
		$hotUrl = $Flink->where($condition)->select();
		unset($condition);

        //获取带图片的友情链接
        $condition['type_id'] = 1;
        $hasImgUrl = $Flink->where($condition)->limit(4)->select();
        unset($condition);

		$this->assign('faculty',$faculty);
		$this->assign('hasImgUrl',$hasImgUrl);
		$this->assign('hotUrl',$hotUrl);
	}

	/**
	 *  统计网站流量函数
	 */
	public function loginCount() {

		$ip = get_client_ip();
		$time = time();

		//判断当前用户是否任然存在于session中
		if (is_null(session(md5($ip)))) {

			$Visit = M ('Visit');
			$condition['ip'] = $ip;
			$v = $Visit->where($condition)->order('time desc')->find();
			if (is_null($v)) {
				$data['ip'] = $ip;
				$data['time'] = $time;
				$data['view'] = 1;
				$data['y'] = date('y',$time);
				$data['m'] = date('m',$time);
				$data['d'] = date('d',$time);
				$Visit->add($data);
				unset($data);
			} else {
				$vid = $v['vid'];
				$lastTime = $v['time'];
				$y = date('y',$lastTime);
				$m = date('m',$lastTime);
				$d = date('d',$lastTime);
				$y_ = date('y',$time);
				$m_ = date('m',$time);
				$d_ = date('d',$time);

				if ($y == $y_ && $m == $m_ && $d == $d_) {
					//用户当天访问量加1
					$Visit->where('vid = '.$vid)->setInc('view');
				} else {
					$data['ip'] = $ip;
					$data['time'] = $time;
					$data['view'] = 1;
					$data['y'] = $y_;
					$data['m'] = $m_;
					$data['d'] = $d_;
					$Visit->add($data);
				}
			}
			//给session赋值
			session(md5($ip),'hnjrxxw');
		} 
		return;
	}
	
	/**
	 * 系统文件上传函数
	 * @param unknown_type $type 文件上传类型
	 * @param unknown_type $thumb 是否生成缩略图
	 * @param unknown_type $path 返回路径还是上传文件对象
	 * @return String | Object
	 */
	function upload($type,$thumb = false,$path=true){
		// 上传文件类型
		$ext_arr = array(
				'file' => array('jpg', 'jpeg', 'png','doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2','pdf')
		);
	
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     3145728 ;// 设置附件上传大小
		$upload->autoSub = true;//使用子目录保存上传文件
		$upload->subType = 'date';//使用日期模式创建子目录
		$upload->dateFormat = 'Ymd';//设置子目录日期格式
		$upload->allowExts  = $ext_arr[$type];// 设置附件上传类型
		$upload->rootPath = "./Public/upload/";
		$upload->savePath =  $type."/";// 设置附件上传目录
		$upload->saveRule = 'uniqid';
		$upload->thumb = $thumb;//生成缩略图
		$upload->thumbMaxWidth = '200';//缩略图最大宽度
		$upload->thumbMaxHeight = '200';//缩略图最大高度
		$upload->thumbRemoveOrigin = false;
	
		// 上传文件
		$info   =   $upload->upload();
		//p($info === false);
		if($info === false) {
			echo "error";
			// 上传错误提示错误信息
			$this->error($upload->getError());
		}else{
			// 上传成功 获取上传文件信息
			if ($path){
				foreach($info as $file){
					return $file['savepath'].$file['savename'];
				}
			} else {
				return $info;
			}
		}
	}

}