<?php 
class AdminController extends Controller{
	public function actionLogin(){
		$name = Yii::app()->request->getParam("Phone");
		$pwd  = Yii::app()->request->getParam("Password");
		if(isset($name) && isset($pwd) && $name != '' && $pwd != ''){
			session_start();
			self::adminlogin($name, $pwd);
			$this->redirect("/admin/money");
			exit();
		}
		$this->renderPartial('login');
	}
	
	public function actionIndex(){
		if(self::isLogin()){
			$this->renderPartial("/admin/money");
			exit();
		}
		$this->redirect('/admin/login');
	}
	public function actionRequest(){
		$type= Yii::app()->request->getParam("Type");
		
		if(isset($type) && $type != ''){
			if($type == '1'){
				$phone = Yii::app()->request->getParam("Phone");
				if (isset($phone)){
					if(strstr($phone,"@")){
						$usermodel = User::model()->find("mail = :mail",array(":mail"=>$phone));
					}else{
						$usermodel = User::model()->find("phone = :phone",array(":phone"=>$phone));
					}
					
					if($usermodel){
						$vipmodel  = Vip::model()->findAll("user_id = :userid",array(":userid"=>$usermodel["user_id"]));
						$arr["userid"] = $usermodel["user_id"];
						if($vipmodel){
							foreach ($vipmodel as $_v){
								$arr["list"][] = array(
										"id"=>$_v["vip_id"],
										"product"=>$_v["product_flag_id"],
										"isvip"  =>$_v["isvip"],
										"endtime"=>date("Y-m-d",$_v["end_time"])
								);
							}
							Tools::req_hander(1,$arr);
						}else{
							$userid = $arr["userid"];
							Tools::req_hander(2,$userid);
						}
						//Tools::req_hander(1,$arr);//?
					}			
				}
			}
		}
		Tools::req_hander(0);
	}
	//判断是否登录
	public static function isLogin(){
		if(!isset($_SESSION)){
			session_start();
		}
		if(isset($_SESSION["S_CAD_ADMINAPP_NAME"]) && !empty($_SESSION["S_CAD_ADMINAPP_NAME"])
				&& isset($_SESSION["S_CAD_ADMINAPP_PWD"]) && !empty($_SESSION["S_CAD_ADMINAPP_PWD"]) ){
			return true;
		}
		else if(isset($_COOKIE["C_CAD_ADMINAPP_NAME"]) && !empty($_COOKIE["C_CAD_ADMINAPP_NAME"]) &&
				isset($_COOKIE["C_CAD_ADMINAPP_PWD"]) && !empty($_COOKIE["C_CAD_ADMINAPP_PWD"]) ){
			$res=$this->userLogin($_COOKIE["C_CAD_ADMINAPP_NAME"],$_COOKIE["C_CAD_ADMINAPP_PWD"]);
			if($res){
				$_SESSION["S_CAD_ADMINAPP_NAME"]=$_COOKIE["C_CAD_ADMINAPP_NAME"];
				$_SESSION["S_CAD_ADMINAPP_PWD"]=$_COOKIE["C_CAD_ADMINAPP_PWD"];
				return true;
			}
		}
		return false;
	}
	//登录
	public static function adminlogin($name,$password){
		if($name == 'cadadmin' && $password == 'aec188cad'){
			//session_start();
			$_SESSION["S_CAD_ADMINAPP_NAME"]=$name;
			$_SESSION["S_CAD_ADMINAPP_PWD"]=md5($password);
				
			$expire = time() + 3*24*3600;
			setcookie("C_CAD_ADMINAPP_NAME", $name, $expire,"/");
			setcookie("S_CAD_ADMINAPP_PWD",md5($password),$expire,"/");
		}
	}
	public function actionMoney(){
		if(self::isLogin()){
			$this->renderPartial('money');
			exit();
		}
		$this->renderPartial('login');
	}
	//后台管理页面
	public static function actionCheck(){
		$type = Yii::app()->request->getParam("Type");
		$dt = Yii::app()->request->getParam("Time");
		$start = date("Y-m-d H:i:s",strtotime($dt));
		$end = date("Y-m-d H:i:s",strtotime("$start +1 day"));
		$money = 0;
		if (!isset($type)||($type == "") ||  !isset($dt)||($dt == "")){
			Tools::req_hander(0);
		}elseif($type == "vip"){
			$rs = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end)",array(":start"=>$start,":end"=>$end));
			foreach ($rs as $cont){
				$money+=$cont['money'];
			}
			$vipCharge = $money;
			Tools::req_hander(1,$vipCharge);
		}else{
			$rs = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>$type));
			foreach ($rs as $cont){
				$money+=$cont['money'];
			}
			$Charge = $money;
			Tools::req_hander(1,$Charge);
		}
	}
	public static function actionChpwd(){//修改密码
		$pwd = Yii::app()->request->getParam("Pwd");
		$userid = Yii::app()->request->getParam("Userid");
		if ($pwd == '' || $userid == ''){
			Tools::req_hander(0);
		}else{
			$rs = User::model()->updateAll(array("password"=>md5($pwd)),"user_id=:userid",array(":userid"=>$userid));
			//echo md5($pwd)."<br>";
			$tip = $pwd;
			Tools::req_hander(1,$tip);
		}
	}
	public static function actionChtime(){
		$time = Yii::app()->request->getParam("Time");
		$time = strtotime($time);
		$vipid = Yii::app()->request->getParam("Vipid");
		
		if ($time == '' || $vipid == ''){
			Tools::req_hander(0);
		}else{
			$vipmodel = Vip::model()->updateAll(array("end_time"=>$time),"vip_id=:vipid",array(":vipid"=>$vipid));
			if($vipmodel){
				$time = date("Y-m-d",$time);
				Tools::req_hander(1,$time);
			}else {
				Tools::req_hander(0);
			}
			
		}	
	}
	//数据记录默认开始界面
	public function actionShow(){
		if(self::isLogin()){
			$w = date("w",time());
			$d = date("Y-m-d",time());
			switch ($w){
					case "1" :  $d = date("Y-m-d",strtotime("$d - 0 day"));break;
					case "2" :  $d = date("Y-m-d",strtotime("$d - 1 day"));break;
					case "3" :  $d = date("Y-m-d",strtotime("$d - 2 day"));break;
					case "4" :  $d = date("Y-m-d",strtotime("$d - 3 day"));break;
					case "5" :  $d = date("Y-m-d",strtotime("$d - 4 day"));break;
					case "6" :  $d = date("Y-m-d",strtotime("$d - 5 day"));break;
					case "0" :  $d = date("Y-m-d",strtotime("$d - 6 day"));break;
					default: echo "Eeror!";break;
			}
			$type = "1w";
			$this->redirect(array('/admin/week','Type'=>$type,'Date'=>$d));
		}else{
			$this->renderPartial('login');
		}
	}
	
	//处理周数据
	public  function actionWeek(){
		$rtype = Yii::app()->request->getParam("Type");//获取查询类型，类型1w查询一周，类型2w查询两周，类型1m查询一个月，类型2m查询两个月，类型all查询所有月份
		$start = Yii::app()->request->getParam("Date");//获取查询开始日期
		if($rtype == "1w"){
			$end = date("Y-m-d",strtotime("$start + 7 day"));
			$type = "0";
		}elseif ($rtype == "2w"){
			$end = date("Y-m-d",strtotime($start));
			$start = date("Y-m-d",strtotime("$start - 14 day"));
			$type = "0";	
		}elseif ($rtype == "1"){
			$start = date("Y-m-d 00:00:00",time());
			$end = date("Y-m-d H:i:s",time());
			//----------------------@begin计算三个软件的总额---------------
			//获取cadsee看图当天的总额
			$cadsee = 0; $cadhome = 0;$homecost = 0;
			$cads = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"CADSEE"));
			if(!isset($cads)){
				$cadsee = 0;
			}else{
				foreach ($cads as $cont){
					$cadsee +=$cont['money'];
				}
			}
			
			//获取cadhome看图当天的总额
			$cadh = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"CADHOME"));
			if(!isset($cadh)){
				$cadhome = 0;
			}else{
				foreach ($cadh as $cont){
					$cadhome +=$cont['money'];
				}
			}
				
			//获取cad看图当天的总额
			$hc = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"HOMECOST"));
			if(!isset($hc)){
				$cadsee = 0;
			}else{
				foreach ($hc as $cont){
					$homecost +=$cont['money'];
				}
			}
			$dsum = $cadhome+$cadsee+$homecost;//查询当天的总额
			$rdata = array("CADSEE"=>$cadsee,"CADHOME"=>$cadhome,"HOMECOST"=>$homecost,"dsum"=>$dsum);
			if(count($rdata) == 0){
				Tools::req_hander(0);
			}else{
				Tools::req_hander(1,$rdata);
			}
			return ;
			//----------------------@end计算结束-------------------------
		}else{
			return array("warning"=>"查询出错！");
		}
		$arr = Sum::findWeek($type, $start, $end);
		$this->renderPartial("week",array("arr"=>$arr));
	}
	
	//处理月数据
	public function actionMonth(){
		$type = Yii::app()->request->getParam("Type");//获取查询模式
		$month = Yii::app()->request->getParam("Month");//查询待查询月份
		$month = date("Y")."-".$month."-"."01";
		if($type == "0"){//查询当前月份
			$startday = date("Y-m-01",time());
			$endday = date("Y-m-d",time());
			$mtype = "0";//月份标记
			$cadsee = 0;
			$cadhome = 0;
			$homecost = 0;
			$dsum = 0;
			//$arr = Sum::findMonth($mtype, $startday, $endday);
			$rs = Sum::model()->findAll("datetype='0' and (date>=:start and date<=:end ) order by date asc",array(":start"=>$startday,":end"=>$endday));
			foreach ($rs as $cont){
				$cadsee+=$cont['CADSEE'];
				$cadhome+=$cont['CADHOME'];
				$homecost+=$cont['HOMECOST'];
				$dsum+=$cont['dsum'];
			}
			
			$arr = array("date"=>$startday,"CADSEE"=>$cadsee,"CADHOME"=>$cadhome,"HOMECOST"=>$homecost,"msum"=>$dsum);
			$this->renderPartial("month",array("arr"=>array("0"=>$arr),"darr"=>$rs));
		}elseif($type == "3"){//查询近期三个月份
			$startday = date("Y-m-01",time());
			$endday = date("Y-m-d",time());
			$mtype = "0";//月份标记
			$cadsee = 0;
			$cadhome = 0;
			$homecost = 0;
			$dsum = 0;
			//$arr = Sum::findMonth($mtype, $startday, $endday);
			$rs = Sum::model()->findAll("datetype='0' and (date>=:start and date<=:end ) order by date asc",array(":start"=>$startday,":end"=>$endday));
			foreach ($rs as $cont){
				$cadsee+=$cont['CADSEE'];
				$cadhome+=$cont['CADHOME'];
				$homecost+=$cont['HOMECOST'];
				$dsum+=$cont['dsum'];
			}	
			$ct = array("date"=>$startday,"CADSEE"=>$cadsee,"CADHOME"=>$cadhome,"HOMECOST"=>$homecost,"msum"=>$dsum);//本月数据数组
			
			$start = date("Y-m-01",time());
			$startday = date("Y-m-01",strtotime("$start - 4 month"));
			$endday = date("Y-m-01",strtotime("$start -1 day"));
			$mtype = "1";
			$arr = Sum::findMonth($mtype, $startday, $endday);
			$this->renderPartial("month_",array("arr"=>$arr,"ct"=>$ct));
		}elseif($type == "1"){//查询单个月份
			$startday = $month;
			$endday = $month;
			$mtype = "1";
			$arr = Sum::findMonth($mtype, $startday, $endday);
			if(count($arr) == 0){
				Tools::req_hander(0);
			}else{
				$data = array("date"=>$arr['0']['date'],"CADSEE"=>$arr['0']['CADSEE'],"CADHOME"=>$arr['0']['CADHOME'],"HOMECOST"=>$arr['0']['HOMECOST'],"msum"=>$arr['0']['msum']);
				Tools::req_hander(1,$data);
			}
		}
	}
	//更新数据
	public function actionInsdata(){
		/* 	$ch = (time()-strtotime("2016-06-01"))/(3600*24);   *
		 *  $date = "2016-06-01";//全局更新数据，月份区间			*
		 *	for($i = 0; $i <= floor($ch); $i ++){               */
			//echo "<script> if(prompt('Key  Code:')!='123456'){alert('The key code is wrong!');if(confirm('Exit?')){location.href='https://www.baidu.com';}else{location.href='http://www.vipapps.com/admin/insdata';}}</script>";
			$date = Yii::app()->request->getParam("Date");
			if(!isset($date)){
				return 0;
			}
// 			$date = "2016-07-22";
			$flag = Sum::model()->find("date = :date",array(":date"=>$date));
			if($flag != ""){
				echo "<script>alert('exist~');history.back();</script>";return ;
			}
			$date = date("Y-m-d 00:00:00",strtotime($date));
			$cadsee = 0;//cad看图总和
			$cadhome = 0;//cad家装总和
			$homecost = 0;//预算总和
			$start = date("Y-m-d H:i:s",strtotime($date));
			$end = date("Y-m-d H:i:s",strtotime("$start + 1 day"));
			//获取cadsee看图当天的总额@可以封装
			$cads = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"CADSEE"));
			if(!isset($cads)){
				$cadsee = 0;
			}else{
				foreach ($cads as $cont){
					$cadsee +=$cont['money'];
				}
			}
			
			//获取cadhome看图当天的总额
			$cadh = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"CADHOME"));
			if(!isset($cadh)){
				$cadhome = 0;
			}else{
				foreach ($cadh as $cont){
					$cadhome +=$cont['money'];
				}
			}
			
			//获取cad看图当天的总额
			$hc = Charge::model()->findAll("statu = 1 and (insert_time>:start and insert_time<:end) and product_flag_id = :product_flag_id",array(":start"=>$start,":end"=>$end,":product_flag_id"=>"HOMECOST"));
			if(!isset($hc)){
				$cadsee = 0;
			}else{
				foreach ($hc as $cont){
					$homecost +=$cont['money'];
				}
			}		
			
		    $dsum = $cadhome+$cadsee+$homecost;//查询当天的总额
		    echo "-----------------".$date."-----------------"."<br/>".$cadhome."--".$cadsee."--".$homecost."--".$dsum."<br/>";
			$datetype = "0";//日期种类
			$model = new Sum();
			$model->datetype = $datetype;
			$model->date = $date;
			$model->CADSEE = floor($cadsee);
			$model->CADHOME = floor($cadhome);
			$model->HOMECOST = floor($homecost);
			$model->dsum = floor($dsum);
			$model->save();
			$freeline = $date;//寄存日期

			/*$date = $end;//循环累加，配合全局数据更新*/
			
			$d = date("d",strtotime($freeline));
			echo "<br/>-----------------today:".$d."-----------------<br>";
			if($d >=28){
				$num = date("t",strtotime($freeline));
				echo "<br/>-------------days".$num."-----------------";
				if($d == $num){									//如果当天等于当月的最后一天
					$startm = date("Y-m-01 00:00:00",strtotime($freeline));
					$endm = date("Y:m-d H:i:s",strtotime("$startm + 1 month"));
					echo $startm."		".$endm;
					$type = "CADSEE";
					$mnsum = Sum::sumFb($type, $startm, $endm); //cad迷你看图月总额
					$type = "CADHOME";
					$jzsum = Sum::sumFb($type, $startm, $endm);//cad迷你家装月总额
					$type = "HOMECOST";
					$yssum = Sum::sumFb($type, $startm, $endm);//家装预算月总额
					$sum = $mnsum + $jzsum + $yssum;
					echo "cad迷你看图总额：".$mnsum."cadhome迷你家装总额：".$jzsum."homcost".$yssum;
					//插入月总额数据
					$datetype = "1";
					$model2 = new Sum();
					$model2->datetype = $datetype;
					$model2->date = $startm;
					$model2->CADSEE = ceil($mnsum);
					$model2->CADHOME = ceil($jzsum);
					$model2->HOMECOST = ceil($yssum);
					$model2->msum = ceil($sum);
					if ($model2->save()){
						echo "MSuccess!";
					}else{
						echo "NSuccess!";
					}
				}
			}	
			/*echo "-----------------update success-----------------<br/>";//测试*/
	}
	//获取当月份的详情
	public function actionGetcon(){
		$month = Yii::app()->request->getParam("Month");
		$type = Yii::app()->request->getParam("Type");
		$firstday = "2016-".$month."-01";
		if($type == "3"){
			$lastday = date("Y-m-d H:i:s",time());
		}else{
			$lastday = date("Y-m-d H:i:s",strtotime("$firstday + 1 month - 1 day"));
		}
		$rs = Sum::model()->findAll("datetype='0' and (date>=:start and date<=:end ) order by date asc",array(":start"=>$firstday,":end"=>$lastday));
		if(count($rs)>0){
			Tools::req_hander(1,array("rs"=>$rs));
		}else{
			Tools::req_hander(0);
		}
	}
	//添加随机邀请码
	public function actionCode(){
		if(!self::isLogin()){
			$this->renderPartial('login');
			exit();
		}
		$this->renderPartial("code");
	}
	//批量产生邀请码
	public function actionAddcode(){
		$username = Yii::app()->request->getParam("Val");
		$phone = Yii::app()->request->getParam("Phone");
		$zfbao = Yii::app()->request->getParam("Zfbao");
		if($username == ''){
			Tools::req_hander(0);
		}
		do{
			$random_left = str_pad(mt_rand(1,9999),4,"0",STR_PAD_LEFT);
			$random_right = substr(microtime(),2,2);
			$random = $random_left.$random_right;
			$rs = Coupon::model()->findAll("code=:code",array(":code"=>$random));
		}while(count($rs) > 0);
		//保存数据
		$rad = new Coupon();
		$rad->user_id = $username;
		$rad->code = $random;
		$rad->phone = $phone;
		$rad->zfbao = $zfbao;
		$flag =$rad->save();
		//$rad->id=null;
		//$rad->setIsNewRecord(true);
		if($flag){
			Tools::req_hander(1,array("random"=>$random));
		}else{
			Tools::req_hander(2);
		}
	}
	//查询家装会员
	public function actionCheckhc(){
		if(!self::isLogin()){
			$this->renderPartial('login');
			exit();
		}
		$time_at = date("Y-m-d 00:00:00",$_SERVER['REQUEST_TIME']);//获取今天的初始时间
		//访问量
		$sql = "select distinct ip,product_id from va_download where product_id=:product_id";
		$count_see = Download::model()->findAllBySql($sql,array(":product_id"=>"cadsee"));
		$info['CADSEE'] = count($count_see);//cadsee
		
		$count_home = Download::model()->findAllBySql($sql,array(":product_id"=>"cadhome"));
		$info['CADHOME'] = count($count_home);
		
		$count_cost = Download::model()->findAllBySql($sql,array(":product_id"=>"homecost"));
		$info['HOMECOST'] = count($count_cost);
		
		$count_draw = Download::model()->findAllBySql($sql,array(":product_id"=>"caddraw"));
		$info['CADDRAW'] = count($count_draw);
		
		$count_mep = Download::model()->findAllBySql($sql,array(":product_id"=>"mep01"));
		$info['MEP01'] = count($count_mep);
		
		$count_web = Download::model()->findAllBySql($sql,array(":product_id"=>"web"));
		$info['WEB'] = count($count_web);
		//增量
		$sql_add = "select distinct ip,product_id from va_download where product_id=:product_id and time_at>:time_at";
		$count_see_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"cadsee",":time_at"=>$time_at));
		$info['SEE_ADD'] = count($count_see_add);
		
		$count_home_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"cadhome",":time_at"=>$time_at));
		$info['HOME_ADD'] = count($count_home_add);
		
		$count_draw_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"caddraw",":time_at"=>$time_at));
		$info['DRAW_ADD'] = count($count_draw_add);
		
		$count_homecost_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"homecost",":time_at"=>$time_at));
		$info['HOMECOST_ADD'] = count($count_homecost_add);
		
		$count_mep01_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"mep01",":time_at"=>$time_at));
		$info['MEP01_ADD'] = count($count_mep01_add);
		
		$web_add = Download::model()->findAllBySql($sql_add,array(":product_id"=>"web",":time_at"=>$time_at));
		$info['WEB_ADD'] = count($web_add);
		
		$this->renderPartial("checkhc",$info);
	}
	public function actionCheckrs(){
		$username = Yii::app()->request->getParam("Val");
		$type = Yii::app()->request->getParam("Type");
		if($username == ''){
			Tools::req_hander(0);//接收参数空
		}
		$user_rs = User::model()->find("phone=:username or mail=:username",array(":username"=>$username));
		if (count($user_rs) <= 0){
			Tools::req_hander(2);//没有账户信息
		}
		$vip_rs = Vip::model()->findAll("user_id=:user_id and product_flag_id=:type",array(":user_id"=>$user_rs['user_id'],":type"=>$type));
		if(sizeof($vip_rs) > 0){
			Tools::req_hander(1,$vip_rs);
		}else{
			Tools::req_hander(2);
		}
	}
	//查询优惠码对应账号和完成的订单信息
	public static function actionQueryinfo(){
		$invcode = Yii::app()->request->getParam("Code");
		$coders = Coupon::model()->find("code=:code",array(":code"=>$invcode));	
		if(count($coders) > 0){
			$codeinfo = array("userid"=>$coders['user_id'],"phone"=>$coders['phone']);
			$charge_rs = Charge::model()->findAll("handled=:userid and statu=1",array(":userid"=>$coders['id']));
			$count = count($charge_rs);
			if($count > 0){
				$storage = array();
				foreach ($charge_rs as $cont){
					$user_rs = User::model()->find("user_id=:userid",array(":userid"=>$cont['user_id']));
					if($user_rs['phone'] != ''){
						$username = $user_rs['phone'];
					}else{
						$username = $user_rs['mail'];
					}
					$rs= array("username"=>$username,"ordersn"=>$cont['ordersn'],"money"=>$cont['money'],"product_flag_id"=>$cont['product_flag_id'],"insert_time"=>$cont['insert_time']);
					array_push($storage, $rs);
				}
				Tools::req_hander(1,array("storage"=>$storage,"info"=>$codeinfo,"count"=>$count));
			}else{
				Tools::req_hander(2,array("name"=>$coders['user_id']));
			}
			
		}else{
			Tools::req_hander(0);
		}
	}
	//根据订单号查询相关信息
	public function actionTradeno(){
		$tradeno = Yii::app()->request->getParam("Tradeno");
		$rs = Charge::model()->find("tradeno=:tradeno",array(":tradeno"=>$tradeno));
		if(empty($rs)){
			Tools::req_hander(0);//订单号不存在
		}else{
			$user_rs = User::model()->find("user_id=:userid",array(":userid"=>$rs['user_id']));
			//print_r($user_rs);
			$arr = array("userid"=>$user_rs['user_id'],"phone"=>$user_rs['phone'],"mail"=>$user_rs['mail']);
			Tools::req_hander(1,$arr);
		}
	}
	//修改用户名
	public function actionChange(){
		$user = Yii::app()->request->getParam("Tradeuser");
		$userid = Yii::app()->request->getParam("Userid");
		if(strstr($user,"@")){
			$flag = User::model()->updateAll(array("mail"=>$user),"user_id=:userid",array(':userid'=>$userid));
			if($flag){
				Tools::req_hander(1);
			}else {
				Tools::req_hander(0);
			}
		}else{
			Tools::req_hander(2);
		}
	}
	public function actionTest(){
	  echo (time()+30*3600*24);
	}
	//合并相同项
/* 	public function actionTest(){
		$rs = Coupon::model()->findAll("id>:id",array(":id"=>"0"));
		$num = "159687";
		$arr = null;
		foreach ($rs as $cont){
			if ($cont['code'] === $num){
				continue;
			}else{
				Coupon::model()->(此处是删除)All("code=:code",array(":code"=>$arr['code']));
				$demo = new Coupon();
				$demo->code = $arr['code'];
				$demo->user_id = $arr['user_id'];
				$demo->zfbao = $arr['zfbao'];
				$demo->phone = $arr['phone'];
				$demo->save();
				$num=$cont['code'];
				echo "操作成功！","<br>";
			}
			$arr = $cont;
		}
	} */
	//查看所有优惠码的使用情况
	/* public function actionGet_code_data(){
		$coupon_rs = Coupon::model()->findAll("id>:id",array(":id"=>"0"));
		$alldata = array();
		$num = 0;
		foreach ($coupon_rs as $cont){
			$charge_rs = Charge::model()->findAll("statu=2 and handled=:handled",array(":handled"=>$cont['id']));
			$num = count($charge_rs);
			if ($num>0){
				array_push($alldata, array("code"=>$cont['code'],"num"=>$num,"name"=>$cont['user_id'],"phone"=>$cont['phone'],"zfbao"=>$cont['zfbao']));
				//$alldata[] = array("code"=>$cont['code'],"num"=>$num,"name"=>$cont['user_id'],"phone"=>$cont['phone'],"zfbao"=>$cont['zfbao']);
			}else{
				continue;
			}
		}

		$this->renderPartial("get_code_data",array("alldata"=>$alldata));
	} */
	//查看优惠码使用情况
	public function actionGet_code_data(){
		$db = yii::app()->db;
		$sql = "SELECT a.handled,COUNT(a.handled) as count,b.user_id,b.phone,b.code,b.zfbao FROM va_charge AS a LEFT JOIN va_coupon as b ON  a.handled = b.id WHERE a.statu = 1 and a.handled>0 GROUP BY a.handled";
		$rs = $db->createCommand($sql)->query();
		$this->renderPartial("get_code_data",array("alldata"=>$rs));
	}
	//显示优惠码具体详情
	public function actionReport()
	{
		$code = Yii::app()->request->getParam("code");
		$code_rs = Coupon::model()->find("code=:code",array(":code"=>$code));
		$sql = "select * from va_charge where handled=".$code_rs['id']." and statu=1 order by handled desc";
		$criteria= new CDbCriteria();
		$result = Yii::app()->db->createCommand($sql)->query();
		$pages= new CPagination($result->rowCount);
		$pages->pageSize=15;
		$pages->applyLimit($criteria);
		$result=Yii::app()->db->createCommand($sql." LIMIT :offset,:limit");
		$result->bindValue(':offset', $pages->currentPage*$pages->pageSize);
		$result->bindValue(':limit', $pages->pageSize);
		$posts=$result->query();
		$this->renderPartial('report',array(
				'posts'=>$posts,
				'pages'=>$pages,
		));
	}
	//后台添加会员
	public function actionAddvip(){
		$type = Yii::app()->request->getParam("Type");
		$year = Yii::app()->request->getParam("Year");
		$month = Yii::app()->request->getParam("Month");
		$user = Yii::app()->request->getParam("User");
		if(isset($type) && !empty($type) || isset($year) && !empty($year) || isset($month) && !empty($month) || isset($user) && !empty($user)){
			$time = $_SERVER['REQUEST_TIME'];
			switch ($year){//处理年数据
				case "0":
					$time += 0;
					break;
				case "1":
					$time += 1*365*24*3600;
					break;
				case "2":
					$time += 2*365*24*3600;
					break;
				case "3":
					$time += 3*365*24*3600;
					break;
				default:
					break;
			}
			if($month == "12" && $year == "0"){//处理月数据
				$time += 365*24*3600;
			}elseif ($month == "6"){
				$time += 183*24*3600;
			}else{
				$time += $month*30*24*3600;
			}
			$rs = Vip::model()->find("product_flag_id=:type and user_id=:user",array(":type"=>$type,":user"=>$user));
			if(empty($rs)){
				$ob = new Vip();
				$ob->user_id = $user;
				$ob->product_flag_id = $type;
				$ob->isvip = 0;
				$ob->end_time = $time;
				$ob->insert_time = time();
				if($ob->save()){
					Tools::req_hander(1);//添加成功
				}else{
					Tools::req_hander(0);//添加失败
				}
			}else{
				Tools::req_hander(2);//已经存在该产品的vip，请直接修改到期时间
			}
		}else{
			Tools::req_hander(3);//提交数据存在空值
		}
	}
	//查看下载量
	public function actionGetdownload(){
		
	}
	public function actionHome(){
		$this->renderPartial('home');
	}
	/* public function actionNgbudget(){
		//房型0的数据
		$fx0 = Yii::app()->request->getParam("Fx0");
		$mj0 = Yii::app()->request->getParam("Mj0");
		//房型1的数据
		$fx1 = Yii::app()->request->getParam("Fx1");
		$mj1 = Yii::app()->request->getParam("Mj1");
		//房型2的数据
		$fx2 = Yii::app()->request->getParam("Fx2");
		$mj2 = Yii::app()->request->getParam("Mj2");
		//房型3的数据
		$fx3 = Yii::app()->request->getParam("Fx3");
		$mj3 = Yii::app()->request->getParam("Mj3");
		//房型4的数据
		$fx4 = Yii::app()->request->getParam("Fx4");
		$mj4 = Yii::app()->request->getParam("Mj4");
		//具体数据处理过程
		$rtinfo = array();
		$mjsum = $mj0+$mj1+$mj2+$mj3+$mj4;
		if ($mj0 != "0"){
			$rs= Budget::Getinfo($fx0, $mj0);
			$rtinfo[$fx0] = $rs;
		}
		if ($mj1 != "0"){
			$rs= Budget::Getinfo($fx1, $mj1);
			$rtinfo[$fx1] = $rs;
		}
		if ($mj2 != "0"){
			$rs= Budget::Getinfo($fx2, $mj2);
			$rtinfo[$fx2] = $rs;
		}
		if ($mj3 != "0"){
			$rs= Budget::Getinfo($fx3, $mj3);
			$rtinfo[$fx3] = $rs;
		}
		if ($mj4 != "0"){
			$rs= Budget::Getinfo($fx4, $mj4);
			$rtinfo[$fx4] = $rs;
		}
		if ($mjsum != "0"){
			$rs= Budget::Getinfo("jicpz", $mjsum);
			$rtinfo['jichpz'] = $rs;
		}
		if (empty($rtinfo)){
			Tools::req_hander(0);
		}else{
			Tools::req_hander(1,$rtinfo);
		}
	} */
}

?>
