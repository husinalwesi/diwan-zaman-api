<?php
// http://diwan-zaman.com/api/api.php?type=api&action=getProductsListPager

/**
 * @author Hussein Alwesi
 * @copyright 2017
 */

	class api extends mainController
	{
		/**
			* check authentication for API call "the value of the token id" and check the
			* validation of the method request
		*/
		public function __construct()
		{
			// $this->checkAuth();
			$this->callMethod($this);
		}
		
		
		public function getUNByIDAPI(){
			$id = $this->getSecureParams("id");
						$data = $this->queryResponse("select * from unit where id=$id")[0];
			$this->dataArray = $data;
			$this->getResponse(200);				
		}				

		public function getProductsListAPI(){
			$this->dataArray = $this->getProducts('','');
			$this->getResponse(200);	
		}
		
		public function getProductsListPagerAPI(){
			$page = $this->getSecureParams("page");			
			if(!$page) $page = 1;
			$page = $page - 1;
			$offset = $page * 10;
			
			$data = array('data'=>'','total'=>'','totalPages'=>'');
			$data["data"] = $this->queryResponse("select * from product limit 10 offset $offset");			
			foreach ($data["data"] as $key => $value) {
				$data["data"][$key] = $this->getProductDetails($data["data"][$key]);
			}
			
			$total = $this->queryResponse("select count(id) as 'total' from product")[0]['total'];
			$data["total"] = intval($total);						
			$data["totalPages"] = round($total / 10);						
			$this->dataArray = $data;
			$this->getResponse(200);							
		}
		
		
				public function getOrderDetailsAPI(){
			$id = $this->getSecureParams("id");
			$data = $this->queryResponse("select * from orders where id=$id")[0];			
			//$data["productsJSON"] = json_decode($data["products"]);		
			$this->dataArray = $data;
			$this->getResponse(200);	
		}
		
		
		
		
		public function getSharedDataAPI(){
			$id = $this->getSecureParams("id");
			$data = $this->queryResponse("select * from sharedData where id=1")[0];			
			$this->dataArray = $data;
			$this->getResponse(200);	
		}		
		
				public function getOrderListDataAPI(){
			$data = $this->queryResponse("select * from orders");			
			$this->dataArray = $data;
			$this->getResponse(200);	
		}
		
		public function isThereNewOrdersAPI(){
			$data = $this->queryResponse("select count(id) as 'total' from orders where isNotified='0'");		
			if($data[0]["total"] > 0) {
				
				$params = array('isNotified'=>'1');	
				if(!$this->queryUpdate('orders',$params,"")) $this->getResponse(503);
				
				$this->getResponse(200,"You have ".$data[0]["total"]." new orders");	
			}
			
			
			$this->getResponse(503);				
		}
		
		

		public function getHomeDataAPI(){
			$categories = $this->getCategoriesData("","");
			foreach ($categories as $key => $value) {
				$categories[$key]['data'] = $this->getProducts('category',$categories[$key]["id"]);
			}
			// 
			$object = new stdClass();
			$object->id = 'special';		 
			$object->title_en = 'Special items';		 
			$object->title_ar = 'أصناف مميره';		 			
			$object->data = array();
			$object->data = $this->getProducts('star',"1");
			// 
			array_unshift($categories, $object);
			$this->dataArray = $categories;
			$this->getResponse(200);	
		}

		public function getCategoriesAPI(){
			$this->dataArray = $this->getCategoriesData("","");
			$this->getResponse(200);	
		}

		public function getCategoriesData($field, $val){
			$data = array();
			$sql = "select * from category order by order_sort asc";
			if ($field) $sql = "select * from category where $field=$val order by order_sort asc";			
			$data = $this->queryResponse($sql);			
			return $data;
		}

		public function getProducts($field, $val){
			$data = array();
			$sql = "select * from product";
			if ($field) $sql = "select * from product where $field=$val";
			$data = $this->queryResponse($sql);			
			foreach ($data as $key => $value) {
				$data[$key] = $this->getProductDetails($data[$key]);
			}
			return $data;
		}

		public function getProductByIDAPI(){
			$id = $this->getSecureParams("id");
			$data = $this->queryResponse("select * from product where id=$id")[0];			
			$data = $this->getProductDetails($data);
			$this->dataArray = $data;
			$this->getResponse(200);	
		}

		public function getUnitByID($id){
			return $this->queryResponse("select * from unit where id='".$id."'")[0];
		}
		
	

		public function getProductDetails($product){
			$temp = array();
			$data = array();
			if($product["units"]) {
				if (strpos($product["units"], '-') !== false) {
					// multiple
					$data = explode ("-", $product["units"]);
				}else{
					array_push($data,$product["units"]);
					// only one
				}				
			}
			foreach ($data as $key => $value) {
				$unit = explode(",", $data[$key]);
				$unitDetails = $this->getUnitByID($unit[0]);
				$unitDetails["price"] = $unit[1];
				array_push($temp,$unitDetails);
			}			
			$product["unitsDetails"] = $temp;
			// 
			$product["categoryDetails"] = $this->getCategoriesData("id",$product["category"])[0];
			return $product;
    	}

		public function getSiteContentAPI(){
			$this->dataArray = $this->queryResponse("select * from site_content");
			$this->getResponse(200);	
		}

		public function deleteProductAPI(){
			$id = $this->getSecureParams("id");
			$this->deleteDbRow("product","id",$id);
			$this->getResponse(200,"row deleted successfully.");
	    }


		public function createProductAPI(){
			$jsonParam = $this->getJsonParam();
			//$img = "";
			$img = $jsonParam['image'];
			//if(count($jsonParam['image']) > 0) $img = $jsonParam['image'];
			//else $img = "";
						//
			$params = array(
				'title_en'=>$jsonParam['title_en'],
				'title_ar'=>$jsonParam['title_ar'],
				'desc1_en'=>$jsonParam['desc1_en'],
				'desc1_ar'=>$jsonParam['desc1_ar'],
				'desc2_en'=>$jsonParam['desc2_en'],
				'desc2_ar'=>$jsonParam['desc2_ar'],
				'price'=>$jsonParam['price'],
				'image'=>$img,
				//'image'=>'',	
				'category'=>$jsonParam['category'],
				'units'=>$jsonParam['units'],
				'star'=>$jsonParam['star']
			);								

			$this->queryInsert('product',$params);
			//if(!$this->queryInsert('product',$params)) $this->getResponse(503);
			$this->getResponse(200);			
    	}
		
				public function uploadFileAPI(){
			$params = array('file'=>$this->getSecureParams('file'));
			$params['file'] = $this->uploadMultipartImagesThumbnails(1);
            $this->dataArray = $params["file"];
			$this->getResponse(200);
    	}

		public function editProductAPI(){
            $id = $this->getSecureParams("id");
			$jsonParam = $this->getJsonParam();
			//$img = "";
			$img = $jsonParam['image'];
			//if(count($jsonParam['image']) > 0) $img = $jsonParam['image'];
			//else $img = "";
						//
			$params = array(
				'title_en'=>$jsonParam['title_en'],
				'title_ar'=>$jsonParam['title_ar'],
				'desc1_en'=>$jsonParam['desc1_en'],
				'desc1_ar'=>$jsonParam['desc1_ar'],
				'desc2_en'=>$jsonParam['desc2_en'],
				'desc2_ar'=>$jsonParam['desc2_ar'],
				'price'=>$jsonParam['price'],
				'image'=>$img,
				//'image'=>'',	
				'category'=>$jsonParam['category'],
				'units'=>$jsonParam['units'],
				'star'=>$jsonParam['star']
			);			

			if(!$this->queryUpdate('product',$params,"where id='$id'")) $this->getResponse(503);
			$this->getResponse(200);
	    }
		
		
		public function reordercategoryAPI(){
			$jsonParam = $this->getJsonParam();
			foreach ($jsonParam as $key => $value){
				$id = $value["id"];
				$params = array(
					'order_sort'=>$value["index"],
				);							
				if(!$this->queryUpdate('category',$params,"where id='$id'")) $this->getResponse(503);				
			}
			$this->getResponse(200);
	    }
		
		public function editSharedDataAPI(){
			//$id = $this->getSecureParams("id");
			
			$jsonParam = $this->getJsonParam();
			//$phone = $jsonParam["customer_phone"];
			
			$params = array('banner1'=>$jsonParam["banner1"],'banner2'=>$jsonParam["banner2"],
			'startTime'=>$jsonParam["startTime"],'closeTime'=>$jsonParam["closeTime"]);

			//foreach ($params as $key => $value){
			//	$params[$key] = $this->getSecureParams($key);
				//if(!$params[$key]) unset($params[$key]);
			//}

			//$params['img'] = $this->uploadMultipartImagesThumbnails(1);
			//if(!$params['img']) unset($params['img']);

			if(!$this->queryUpdate('sharedData',$params,"where id=1")) $this->getResponse(503);
			$this->getResponse(200);
	    }
		
		public function editsite_contentAPI(){
			
			$jsonParam = $this->getJsonParam();
			//$phone = $jsonParam["customer_phone"];
			

			
$params = array('value_en'=>$jsonParam["privacy_en"],'value_ar'=>$jsonParam["privacy_ar"]);

$params2 = array('value_en'=>$jsonParam["terms_en"],'value_ar'=>$jsonParam["terms_ar"]);

			if(!$this->queryUpdate('site_content',$params,"where id=1")) $this->getResponse(503);
			if(!$this->queryUpdate('site_content',$params2,"where id=2")) $this->getResponse(503);			
			$this->getResponse(200);
	    }
		


		public function createCategoryAPI(){
			$params = array('title_en'=>'','title_ar'=>'');
		
			foreach ($params as $key => $value){
				$params[$key] = $this->getSecureParams($key);
			}
			
			if(!$this->queryInsert('category',$params)) $this->getResponse(503);
			$this->getResponse(200);
		}
		
		public function editCategoryAPI(){
			$id = $this->getSecureParams("id");			
			$params = array('title_en'=>'','title_ar'=>'');
		
			foreach ($params as $key => $value){
				$params[$key] = $this->getSecureParams($key);
			}
			
			if(!$this->queryUpdate('category',$params,"where id='$id'")) $this->getResponse(503);			
			$this->getResponse(200);
		}
		
		public function editUnitAPI(){
			$id = $this->getSecureParams("id");			
			$params = array('title_en'=>'','title_ar'=>'');
		
			foreach ($params as $key => $value){
				$params[$key] = $this->getSecureParams($key);
			}
			
			if(!$this->queryUpdate('unit',$params,"where id='$id'")) $this->getResponse(503);			
			$this->getResponse(200);
		}		
		
				public function editOrderCodeAPI(){
			$id = $this->getSecureParams("id");
			$params = array('code'=>'');

			foreach ($params as $key => $value){
				$params[$key] = $this->getSecureParams($key);
				if(!$params[$key]) unset($params[$key]);
			}

			if(!$this->queryUpdate('orders',$params,"where id='$id'")) $this->getResponse(503);
			$this->getResponse(200);
	    }
		
		
		public function confirmPhoneAPI(){
			$id = $this->getSecureParams("id");
			$params = array('isPohneConfirmed'=>'1');

			if(!$this->queryUpdate('orders',$params,"where id='$id'")) $this->getResponse(503);
			$this->getResponse(200);
	    }
		
		

		public function deleteCategoryAPI(){
			$id = $this->getSecureParams("id");
			
			$data = $this->getProducts('category',$id);
			if(count($data) > 0){
				$this->getResponse(205,"there are items linked to this category");				
			}
			
			$this->deleteDbRow("category","id",$id);
			$this->getResponse(200,"row deleted successfully.");
		}

		public function getCategoryByIDAPI(){
			$id = $this->getSecureParams("id");
			$this->dataArray = $this->getCategoriesData("id",$id)[0];
			$this->getResponse(200);				
		}
		


				public function getUnitListAPI(){
			$this->dataArray = $this->queryResponse("select * from unit");
			$this->getResponse(200);	
		}
		
		
		public function checkOrderCodeAPI(){
			$id = $this->getSecureParams("id");
			$code = $this->getSecureParams("code");
			$data = $this->queryResponse("select * from orders where id=$id and confirmDigits=$code")[0];			
			//$data["productsJSON"] = json_decode($data["products"]);		
			$this->dataArray = $data;
			$this->getResponse(200);	
		}
		
		

		public function createUnitAPI(){
			$params = array('title_en'=>'','title_ar'=>'');
		
			foreach ($params as $key => $value){
				$params[$key] = $this->getSecureParams($key);
			}
			
			if(!$this->queryInsert('unit',$params)) $this->getResponse(503);
			$this->getResponse(200);
		}

		public function deleteUnitAPI(){
			$id = $this->getSecureParams("id");
			$this->deleteDbRow("unit","id",$id);
			$this->getResponse(200,"row deleted successfully.");
		}

		// public function createOrderAPI(){					 	  		
		// 	$params = array('customer_phone'=>'','customer_address'=>'',
		// 	'customer_fullName'=>'','deliveryMethod'=>'','created_at'=>'','status'=>'',
		// 	'admin_responsible'=>'','change_status_at'=>'','products'=>'');

		// 	foreach ($params as $key => $value){
		// 		$params[$key] = $this->getSecureParams($key);
		// 	}
			
		// 	$params["created_at"] = time();
		// 	$params["status"] = "pending";			
		// 	if(!$this->queryInsert('orders',$params)) $this->getResponse(503);
		// 	$this->getResponse(200);
    	// }
		
		
		public function createOrderAPI(){					 	  		
			$jsonParam = $this->getJsonParam();
			$phone = json_decode($jsonParam["customer_phone"]);
			//$phone->number
			//$this->getResponse(200,$phone->number);		
			if(!$data = $this->queryInsert('orders',$jsonParam)) $this->getResponse(503);
			$this->dataArray = $data;
			$this->getResponse(200);
    	}	

		public function updateOrderStatusAPI(){
			$id = $this->getSecureParams("id");
			$params = array(
				'status'=>$this->getSecureParams("status"),
				'admin_responsible'=>$this->getSecureParams("admin_responsible"),
				'change_status_at'=>$this->getSecureParams("change_status_at")
			);

			if(!$this->queryUpdate('orders',$params,"where id='$id'")) $this->getResponse(503);
			$this->getResponse(200);
	    }
		
				public function signInAPI(){
			$username = $this->getSecureParams("username");
			$password = $this->getSecureParams("password");
			if(!$username || !$password) $this->getResponse(201,"Missed Data.");
			// $password = md5($password);
			$data = array();
			$data = $this->queryResponse("select * from login where username='$username' and password='$password'");
			if(!$data) $this->getResponse(503,"invalid credentials.");
			$this->dataArray = $data[0];
			$this->getResponse(200,"User successfully logged in.");
		}
		
		public function getDashboardDetailsAPI(){
			$data = array(
				'total_orders_counter'=> $this->queryResponse("select count(id) as counter from orders")[0]['counter'],
				'pending_orders'=>$this->queryResponse("select count(id) as counter from orders where status='pending'")[0]['counter'],
				'approved_orders'=>$this->queryResponse("select count(id) as counter from orders where status='approved'")[0]['counter'],
				'rejected_orders'=>$this->queryResponse("select count(id) as counter from orders where status='rejected'")[0]['counter'],
				'categories_counter'=>$this->queryResponse("select count(id) as counter from category")[0]['counter'],
				'products_counter'=>$this->queryResponse("select count(id) as counter from product")[0]['counter'],
				'special_items_counter'=>$this->queryResponse("select count(id) as counter from product where star='1'")[0]['counter'],
				'units_counter'=>$this->queryResponse("select count(id) as counter from unit")[0]['counter'],				
				'pickup_delivery_method_counter'=>$this->queryResponse("select count(id) as counter from orders where deliveryMethod='direct'")[0]['counter'],
				'delivery_delivery_method_counter'=>$this->queryResponse("select count(id) as counter from orders where deliveryMethod='delivery'")[0]['counter'],
			);
			$this->dataArray = $data;
			$this->getResponse(200);
	    }		


// 		public function getCountryCodesAPI(){
// 			$table_name = "country_code";
// 			$handleIsDeleted = $this->handleIsDeleted();
// 			$handlePagination = $this->handlePagination();
// 			$handleQuery = $this->handleQuery();
// 			// 
// 			$data = array();
// 			$data["data"] = $this->queryResponse("select $handleQuery from $table_name where $handleIsDeleted $handlePagination");
// 			$data["config"] = $this->getTotal($table_name,'id');
// 			$this->dataArray = $data;
// 			$this->getResponse(200);
// 		}

// 		public function getAdminsAPI(){
// 			$table_name = "admin_users";
// 			$status = $this->getSecureParams("status");
// 			$handlePagination = $this->handlePagination();
// 			$handleQuery = $this->handleQuery();
// 			// 
// 			$where = "";
// 			if($status == "active") $where = "status='1' and is_deleted='0'";
// 			else if($status == "inactive") $where = "status='0' and is_deleted='0'";
// 			else if($status == "deleted") $where = "is_deleted='1'";
// 			// 
// 			$data = array();
// 			$data["data"] = $this->queryResponse("select $handleQuery from $table_name where $where $handlePagination");
// 			// 
// 			foreach ($data["data"] as $key => $value) {
// 				$data["data"][$key]["created_date"] = $this->timeStampToDate($data["data"][$key]["created_date"],"dateTime");
// 				$user_id = $data["data"][$key]["id"];
// 				$last_login = $this->queryResponse("select ip,created_date from users_log where user_id='$user_id'");
// 				if($last_login){
// 					$last_login[0]["created_date"] = $this->timeStampToDate($last_login[0]["created_date"],"dateTime");
// 					$data["data"][$key]["last_login"] = $last_login[0];
// 				}else{
// 					$data["data"][$key]["last_login"] = (object) array();
// 				}
// 			}
// 			// 
// 			$data["config"] = $this->getTotalWhere($table_name,'id',$where);
// 			$this->dataArray = $data;
// 			$this->getResponse(200);
// 		}

// 		public function signInAPI(){
// 			$params = $this->getJsonParam();
// 			$email = $params->email;
// 			$password = $params->password;
// 			if(!$email || !$password) $this->getResponse(201,"Missed Data.");
// 			//
// 			$password = md5($password);
// 			$data = array();
// 			// 
// 			$data = $this->queryResponse("select id,full_name,username,email,img,status from admin_users where email='$email' and password='$password' and is_deleted='0'");
// 			if(!$data) $this->getResponse(503,"invalid credentials.");
// 			$userDetails = $data[0];
// 			// 
// 			if($userDetails["status"] == 1) unset($userDetails["status"]);
// 			else $this->getResponse(503,"User Inactive.");
// 			// 
// 			$this->dataArray = $userDetails;
// 			$this->setIPLog($userDetails["id"]);
// 			$this->getResponse(200,"User successfully logged in.");
// 		}

// 		// D:\Atomkit Projects\farhet-omry\event-cms\public\media\avatars\blank.png

// 		public function deleteQueryAPI(){
// 			$table = $this->getSecureParams("table");
// 			$id = $this->getSecureParams("id");
// 			$field = $this->getSecureParams("field");
// 			$this->deleteDbRow($table,$field,$id);
// 			$this->getResponse(200,"row deleted successfully.");
// 	    }

// 		public function restoreQueryAPI(){
// 			$table = $this->getSecureParams("table");
// 			$id = $this->getSecureParams("id");
// 			$field = $this->getSecureParams("field");
// 			$this->restoreDbRow($table,$field,$id);
// 			$this->getResponse(200,"row restored successfully.");
// 	    }

// 		public function changeStatusAPI(){
// 			$table = $this->getSecureParams("table");
// 			$id = $this->getSecureParams("id");
// 			$field = $this->getSecureParams("field");
// 			$status = $this->getSecureParams("status");
// 			$this->changeStatus($table,$field,$id,$status);
// 			$this->getResponse(200,"row status changed successfully.");
// 	    }


// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////
// 		////////////////////////////////////////				

// 		// public function changeImageAPI(){
// 		// 	$data = $this->queryResponse("select * from country_code");
// 		// 	for ($i=0; $i < count($data); $i++) { 
// 		// 		$id = $data[$i]["id"];
// 		// 		$flag = $data[$i]["flag"];
// 		// 		$code = strtolower($data[$i]["code"]);
// 		// 		$path = "/flags/$code.png";

// 		// 		$params = array('flag'=>$path);
// 		// 		if(!$this->queryUpdate('country_code',$params,"where id='$id'")) $this->getResponse(503);
				
// 		// 	}
// 		// 	$this->getResponse(200);
// 		// }

// 		// 
// 		// 
// 		// 
// 				// 
// 		// 
// 		// 
// 				// 
// 		// 
// 		// 
// 				// 
// 		// 
// 		// 
// 				// 
// 		// 
// 		// 
// 		public function insertCountriesAPI(){
// 			$jsonParam = $this->getJsonParam();
// 			// 
// 			$params = array(
// 				'id'=>'',
// 				'name'=> $jsonParam->name,
// 				'code'=> $jsonParam->code,
// 				'capital'=> $jsonParam->capital,
// 				'region'=> $jsonParam->region,
// 				'currency_code'=> $jsonParam->currency_code,
// 				'currency_name'=> $jsonParam->currency_name,
// 				'currency_symbol'=> $jsonParam->currency_symbol,
// 				'language_code'=> $jsonParam->language_code,
// 				'language_name'=> $jsonParam->language_name,
// 				'flag'=> $jsonParam->flag
// 			);
// 			if(!$this->queryInsert('country_code',$params)) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function getZmmReportAPI(){
// 			// $month_year_from = "1593561600";
// 			// $month_year_to = "1596153600";
// 			$data = array();
// 		  $client_list = $this->queryResponse("select user_id,fullname,username from users where user_type='client' && is_deleted='0'");
// 			foreach ($client_list as $key => $value) {
// 				$data[$key]["client_name"] = $client_list[$key]["username"]." (".$client_list[$key]["fullname"].")";
// 				$client_id = $client_list[$key]["user_id"];
// 				//

// 				$get_contract_id = $this->queryResponse("select id from contract where client_id='$client_id' && is_deleted='0'")[0]["id"];
// 				// $get_contract_id = $data[$key]["get_contract_id"];
// 				$contract_timeline = $this->queryResponse("select sum(amount) as 'total_amount', sum(payment_amount) as 'total_payment_amount' from contract_timeline where contract_id='$get_contract_id' && is_deleted='0'")[0];
// 				// $data[$key]["contract_timeline"] = $this->queryResponse("select amount,payment_amount from contract_timeline where contract_id='$get_contract_id' && is_deleted='0'")[0];
// 				$contract_timeline = $contract_timeline["total_amount"] - ($contract_timeline["total_payment_amount"] * 0.74);
// 				// $data[$key]["contract_timeline"] = $contract_timeline["total_amount"] - ($contract_timeline["total_payment_amount"] * 0.74);


// 				// $data[$key]["contract_timeline"] = $this->queryResponse("select * from contract_timeline where contract_id='$get_contract_id' && is_deleted='0'")[0];
// 				// $data[$key]["contract_timeline"] = $this->queryResponse("select * from contract_timeline where start_date >= $month_year_from && end_date <= $month_year_to && contract_id='$get_contract_id' && is_deleted='0'")[0];
// 				// $data[$key]["contract_timeline"] = $this->queryResponse("select * from contract_timeline where start_date >= $month_year_from && end_date <= $month_year_to && contract_id='$get_contract_id' && is_deleted='0'")[0];
// 				//
// 				//
// 			  $total_payment = $this->queryResponse("select sum(amount) as 'total_amount' from payment_managment where client_id='$client_id' && is_deleted='0'")[0]["total_amount"];
// 			  // $data[$key]["total_payment"] = $this->queryResponse("select sum(amount) as 'total_amount' from payment_managment where client_id='$client_id' && is_deleted='0'")[0]["total_amount"];
// 			  // $data["total_payment"] = $this->queryResponse("select count(amount) as 'total_amount' from payment_managment where date >= $month_year_from && date <= $month_year_to && client_id='$client_id' && is_deleted='0'")[0]["total_amount"];
// 			  // // $total_payment = $this->queryResponse("select amount from payment_managment where date >= $month_year_from && date <= $month_year_to && client_id='$client_id' && is_deleted='0'");
// 				// //
// 			  $total_online_finance = $this->queryResponse("select sum(amount) as 'total_amount' from online_finance_managment where client_id='$client_id' && is_deleted='0'")[0]["total_amount"];
// 			  // $total_online_finance = $this->queryResponse("select count(amount) as 'total_amount' from online_finance_managment where created_date >= $month_year_from && created_date <= $month_year_to && client_id='$client_id' && is_deleted='0'")[0]["total_amount"];
// 				$total_online_finance = ($total_online_finance * 0.74);
// 				// $data[$key]["total_online_finance"] = ($total_online_finance * 0.74);
// 			  // $total_payment = $this->queryResponse("select amount from payment_managment where date >= $month_year_from && date <= $month_year_to && client_id='$client_id' && is_deleted='0'");
// 				//

// 				if(!$total_online_finance) $total_online_finance = 0;
// 				if(!$contract_timeline) $contract_timeline = 0;
// 				if(!$total_payment) $total_payment = 0;
// 				$data[$key]["rseed"] = $contract_timeline + $total_online_finance - $total_payment;
// 				if(!$data[$key]["rseed"]) unset($data[$key]);
// 			}

// 			$this->dataArray = $data;
// 			$this->getResponse(200);
// 			//

// 		}

// 		public function client_statisticsAPI(){
// 			$contract_id = $this->getSecureParams("contract_id");
// 			$client_id = $this->getSecureParams("client_id");
// 			$month = $this->getSecureParams("month");
// 			$year = $this->getSecureParams("year");

// 			// contract_amount
//       // contract_finance_amount

// 			//

// 			// payment_rest
//       // contract_amount
//       // contract_finance_amount
//       // contract_finance_amount_until_now
//       // no_of_online_finance
// 		}

// 		public function changePasswordAPI(){
// 			$user_id = $this->getSecureParams("user_id");
// 			// new_password
// 			$params = array('password'=>'','modified_date'=>'');

// 				$params["modified_date"] = time();
// 				$params['password'] = md5($this->getSecureParams("new_password"));
// 				// $this->getResponse(200,json_encode($params));

// 			if(!$this->queryUpdate('users',$params,"where user_id='$user_id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function editUserAPI(){
// 			$user_id = $this->getSecureParams("id");
// 			// if(!$user_id) $this->getResponse(503);
// 			$params = array('mobile_num'=>'','mobile_num_country_code'=>'',
// 			'mobile_num_iso_code'=>'','username'=>'','password'=>'','img'=>'',
// 			'fullname'=>'','email'=>'',
// 			'first_name'=>'','last_name'=>'','modified_date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				if(!$params[$key]) unset($params[$key]);
// 			}

// 				// $params['img'] = $this->uploadMultipartImagesThumbnails(1);
// 				// if(!$params['img']) unset($params['img']);


// 			if($params['password']) $params['password'] = md5($params['password']);
// 			else unset($params['password']);
// 			$params['fullname'] = $params['first_name'] .' '.$params['last_name'];
// 			$params['modified_date'] = time();
// 			// $this->getResponse(200,json_encode($params));
// 			if(!$this->queryUpdate('users',$params,"where user_id='$user_id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function addUserAPI(){
// 			// $params = array('user_id'=>'','mobile_num'=>'','mobile_num_country_code'=>'',
// 			$params = array('mobile_num'=>'','mobile_num_country_code'=>'',
// 			'mobile_num_iso_code'=>'','username'=>'','password'=>'','img'=>'',
// 			'created_date'=>'','last_login'=>'','last_login_ip'=>'','status'=>'',
// 			'fullname'=>'','is_deleted'=>'','email'=>'','user_type'=>'',
// 			'first_name'=>'','last_name'=>'','modified_date'=>'');
// 		// double check here to dosnot allow rigistering with same email..
// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 			}
// 			//////////////////////////////////
// 			$email = $params['email'];
// 			$username = $params['username'];
// 			$sql = "select * from users where email='$email' or username='$username'";
// 		  $data = $this->queryResponse($sql);
// 			if($data) $this->getResponse(503,"email address already in use");
// 			//////////////////////////////////
// 			// $params['img'] = $this->uploadMultipartImagesThumbnails(1);
// 			$params['password'] = md5($params['password']);
// 			$params['created_date'] = time();
// 			$params['fullname'] = $params['first_name'] .' '.$params['last_name'];
// 			if(!$this->queryInsert('users',$params)) $this->getResponse(503);
// 			// $this->registerMailTemplate($params['user_type'],$params['email']);
// 			$this->getResponse(200);
//     }

// 		public function getUserAPI(){
// 			$id = $this->getSecureParams("id");
// 			$user_type = $this->getSecureParams("user_type");
// 			$where = "where is_deleted='0'";
// 			if($id) $where = "where user_id='$id' and is_deleted='0'";
// 			if($user_type) $where = "where user_type='$user_type' and is_deleted='0'";
// 			$sql = "select * from users $where";
// 		  $data = $this->queryResponse($sql);
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function getListOfClientsAPI(){
// 			$employee_id = $this->getSecureParams("employee_id");
// 			$admin_flag = $this->getSecureParams("admin_flag");
// 			$where = "where employee_id='$employee_id' and is_deleted='0'";
// 			if($admin_flag){
// 				$where = "where is_deleted='0'";
// 			}
// 			$sql = "select client_id from contract $where";
// 			// $this->getResponse(200,$sql);
// 		  $data = $this->queryResponse($sql);
// 			if(!$data) {
// 				$this->dataArray = $data;
// 				$this->getResponse(200);
// 			}
// 			$arr_client_ids = array();
// 			foreach ($data as $key => $value) {
// 				$arr_client_ids[] = $data[$key]['client_id'];
// 			}
// 			//
// 			$arr_client_ids = implode(",",$arr_client_ids);
// 			$sql2 = "select user_id,username from users where user_id IN ($arr_client_ids) and is_deleted='0'";
// 		  $data2 = $this->queryResponse($sql2);

// 		  $this->dataArray = $data2;
// 			$this->getResponse(200);
//     }

// 		public function addBackupLogAPI(){
// 				$params = array('id'=>'','created_date'=>'','admin_id'=>'');
// 				foreach ($params as $key => $value) {
// 					$params[$key] = $this->getSecureParams($key);
// 				}
// 				$params['created_date'] = time();
// 		    if(!$this->queryInsert('backup_log',$params)) $this->getResponse(503);

// 				$this->getResponse(200);
// 		}
// 		//
// 		public function generateBackupAPI(){
//   		$this->EXPORT_DATABASE(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// 			$this->getResponse(200,"Back-up Generated Successfully");
// 		}

// 		public function getBackupLogAPI(){
// 			$sql = "select * from backup_log";
// 		  $data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 				$data[$key]['admin_details'] = $this->getUserDetails($data[$key]['admin_id']);
// 			}
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function deleteUsersAPI(){
// 			$user_id = $this->getSecureParams("id");
// 			$this->deleteDbRow("users","user_id",$user_id);
// 			$this->getResponse(200);
//     }

// 		public function loginApi()
// 		{
// 			$username = $this->getSecureParams("username");
// 			$password = $this->getSecureParams("password");
// 			if(!$username || !$password) $this->getResponse(503,'parameters needed');
// 	    $password = md5($password);
// 	    $sql = "select * from users where email='$username' or username='$username' and password='$password'";
// 	    $rows = $this->queryResponse($sql);
// 			if(!$rows) $this->getResponse(204,"Invalid Credentials");
// 			if(!$rows[0]['img']) $rows[0]['img'] = PROFILE_DEFAULT;
// 			//
// 			$this->dataArray = $rows;
// 	    $this->getResponse(200);
// 		}
// 		// contract start
// 		// contract start
// 		// contract start
// 		// contract start
// 		public function addContractApi()
// 		{
// 			$params = array('id'=>'','client_id'=>'','employee_id'=>'','amount'=>'',
// 			'payment_amount'=>'','created_date'=>'','modified_date'=>'','user_id'=>'',
// 			'is_deleted'=>'','first_payment'=>'');

// 			$contract_timeline = array('id'=>'','start_month'=>'0','start_year'=>'0','end_month'=>'0',
// 			'end_year'=>'0','created_date'=>'','user_id'=>'','is_deleted'=>'','contract_id'=>'',
// 			'modified_date'=>'','start_date'=>'','end_date'=>'','amount'=>'','payment_amount'=>'');
// 			//
// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 			}

// 			foreach ($contract_timeline as $key => $value){
// 				$contract_timeline[$key] = $this->getSecureParams($key);
// 			}
// 			//////////////////////////////////
// 			$params['created_date'] = time();
// 			$contract_timeline['created_date'] = time();
// 			$contract_timeline['start_date'] = strtotime($contract_timeline['start_date']);
// 			$contract_timeline['end_date'] = strtotime($contract_timeline['end_date']);
// 			if(!$contract_id = $this->queryInsert('contract',$params)) $this->getResponse(503);
// 			//
// 			$contract_timeline["contract_id"] = $contract_id;
// 			if(!$this->queryInsert('contract_timeline',$contract_timeline)) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function ignore_contract_endApi()
// 		{
// 			$id = $this->getSecureParams("id");
// 			$params = array("is_ignored"=>1);
// 			//
// 			if(!$this->queryUpdate('contract',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function renew_contractApi()
// 		{
// 			$id = $this->getSecureParams("id");
// 			//
// 		  $data = $this->queryResponse("select * from contract where id='$id'")[0];
// 			//
// 			$start_date = 0;
// 			$end_date = 0;
// 			$last_end_date = $this->queryResponse("select max(end_date) as 'last_end_date' from contract_timeline where contract_id='$id'")[0]["last_end_date"];
// 			$last_end_date = (int) $last_end_date;
// 			$start_date = $last_end_date + 86400;//plus one day
// 			$end_date = $start_date + (86400 * 30);
// 			// 2592000 month or 30 day exactly..
// 			// start_date
// 			// $data
// 			//
// 			$params = array('id'=>'','start_month'=>'','start_year'=>'','end_month'=>'',
// 			'end_year'=>'','created_date'=>time(),'user_id'=>$data["user_id"],'is_deleted'=>'0','contract_id'=>$id,
// 			'modified_date'=>'','start_date'=>$start_date,'end_date'=>$end_date,'amount'=>$data["amount"],'payment_amount'=>$data["payment_amount"]);
// 			//
// 			if(!$this->queryInsert('contract_timeline',$params)) $this->getResponse(503);
// 			//
// 			$this->getResponse(200);
// 		}

// 		public function addContractTimelineApi()
// 		{
// 			$params = array('id'=>'','start_month'=>'','start_year'=>'','end_month'=>'',
// 			'end_year'=>'','created_date'=>'','user_id'=>'','is_deleted'=>'','contract_id'=>'',
// 			'modified_date'=>'','start_date'=>'','end_date'=>'','amount'=>'','payment_amount'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 			}
// 			//////////////////////////////////
// 			$params['created_date'] = time();
// 			$params['start_date'] = strtotime($params['start_date']);
// 			$params['end_date'] = strtotime($params['end_date']);
// 			if(!$this->queryInsert('contract_timeline',$params)) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function editContractAPI(){
// 			$id = $this->getSecureParams("id");
// 			$params = array('client_id'=>'','employee_id'=>'','amount'=>'',
// 			'payment_amount'=>'','modified_date'=>'','user_id'=>'','is_ignored'=>'0','first_payment'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				if(!$params[$key]) unset($params[$key]);
// 			}

// 			$params['modified_date'] = time();
// 			// $params['start_month'] = strtotime($params['start_date']);
// 			// $params['start_year'] = strtotime($params['end_date']);
// 			if(!$this->queryUpdate('contract',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function editContractTimelineAPI(){
// 			$id = $this->getSecureParams("id");
// 			$params = array('start_month'=>'','start_year'=>'','end_month'=>'',
// 			'end_year'=>'','is_deleted'=>'','modified_date'=>'','start_date'=>'',
// 			'end_date'=>'','amount'=>'','payment_amount'=>'');


// 			// $params = array('client_id'=>'','start_month'=>'','start_year'=>'',
// 			// 'end_month'=>'','end_year'=>'','employee_id'=>'','amount'=>'',
// 			// 'payment_amount'=>'','modified_date'=>'','user_id'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				if(!$params[$key]) unset($params[$key]);
// 			}
// 			//////////////////////////////////
// 			$params['modified_date'] = time();
// 			$params['start_date'] = strtotime($params['start_date']);
// 			$params['end_date'] = strtotime($params['end_date']);
// 			if(!$this->queryUpdate('contract_timeline',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function getContractAPI(){
// 			$searchMode = $this->getSecureParams("searchMode");
// 			$id = $this->getSecureParams("id");
// 			$where = "where is_deleted='0'";
// 			if($id) $where = "where id='$id' and is_deleted='0'";
// 			if($searchMode){
// 				$date_to = strtotime($this->getSecureParams("date_to"));
// 				$date_from = strtotime($this->getSecureParams("date_from"));
// 				$user_id = $this->getSecureParams("user_id");
// 				$client_id = $this->getSecureParams("client_id");
// 				//
// 				if($date_to) $date_to_sql = "end_date <= '$date_to'";
// 				else $date_to_sql = "1 = 1"; //always pass.
// 				//
// 				if($date_from) $date_from_sql = "start_date >= '$date_from'";
// 				else $date_from_sql = "1 = 1";
// 				//
// 				if($user_id) $user_id_sql = "employee_id='$user_id'";
// 				else $user_id_sql = "1 = 1";
// 				//
// 				if($client_id) $client_id_sql = "client_id='$client_id'";
// 				else $client_id_sql = "1 = 1";
// 				//
// 				//
// 				//
// 				$where = "where $date_to_sql && $date_from_sql && $user_id_sql && $client_id_sql && is_deleted='0'";
// 				// user_id ----->  employee_id
// 				// date_from ----> start_date
// 				// date_to  -----> end_date
// 				// status
// 			}
// 			$sql = "select * from contract $where";
// 			// $this->getResponse(200,$sql);
// 		  $data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 				$data[$key]['client_details'] = $this->getUserDetails($data[$key]['client_id']);
// 				$data[$key]['employee_details'] = $this->getUserDetails($data[$key]['employee_id']);
// 			}
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function getContractEndAPI(){
// 			$sql = "select contract_id,max(end_date) as 'end_date' from contract_timeline where is_deleted='0' group by contract_id";
// 			$data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 				// $start_date = strtotime($data[$key]["start_month"]."/01/".$data[$key]["start_year"]);
// 					// $end_date = strtotime($data[$key]["start_month"]."/31/".$data[$key]["start_year"]);
// 					$end_date = $data[$key]['end_date'];
// 					// $end_date = strtotime($data[$key]["start_month"]."/31/".$data[$key]["start_year"]);
// 					$contract_id = $data[$key]['contract_id'];
// 					$data[$key]["contract_details"] = $this->queryResponse("select * from contract where id='$contract_id' and is_ignored='0'")[0];
// 					$data[$key]['contract_details']["client_details"] = $this->getUserDetails($data[$key]["contract_details"]["client_id"]);
// 					if(!$data[$key]["contract_details"]["client_details"]){
// 						unset($data[$key]);
// 						continue;
// 					}
// 					$data[$key]['contract_details']["days_rest"] = round(($end_date - time()) / 86400);
// 				if($data[$key]['contract_details']["days_rest"] > 7){
// 					unset($data[$key]);
// 				}
// 			}
// 			$this->dataArray = $data;
// 			$this->getResponse(200);
// 		}

// 		public function getContractTimelineAPI(){
// 			$searchMode = $this->getSecureParams("searchMode");
// 			$id = $this->getSecureParams("id");
// 			$where = "where is_deleted='0'";
// 			if($id) $where = "where id='$id' and is_deleted='0'";
// 			if($searchMode){
// 				$contract_id = $this->getSecureParams("contract_id");
// 				$start_month = $this->getSecureParams("month");
// 				$start_year = $this->getSecureParams("year");
// 				// $date_from = strtotime($this->getSecureParams("date_from"));
// 				$date_from = strtotime($this->getSecureParams("start_date"));
// 				$date_to = strtotime($this->getSecureParams("end_date"));
// 				// $date_to = strtotime($this->getSecureParams("date_to"));
// 				// $date_from = strtotime($this->getSecureParams("date_from"));
// 				$user_id = $this->getSecureParams("user_id");
// 				//
// 				// if($start_month) $start_month_sql = "start_month = '$start_month'";
// 				// else $start_month_sql = "1 = 1"; //always pass.
// 				// //
// 				// if($start_year) $start_year_sql = "start_year = '$start_year'";
// 				// else $start_year_sql = "1 = 1"; //always pass.
// 				//
// 				if($date_to){
// 					// $date_to-=86400;
// 					$date_to_sql = "created_date <= '$date_to'";
// 				}else $date_to_sql = "1 = 1"; //always pass.
// 				//
// 				if($date_from){
// 					// $date_from-=86400;
// 					$date_from_sql = "created_date >= '$date_from'";
// 				}else $date_from_sql = "1 = 1";
// 				//
// 				if($user_id) $user_id_sql = "employee_id='$user_id'";
// 				else $user_id_sql = "1 = 1";
// 				//
// 				if($contract_id) $contract_id_sql = "contract_id='$contract_id'";
// 				else $contract_id_sql = "1 = 1";
// 				//
// 				//
// 				//
// 				$where = "where $date_to_sql && $date_from_sql && $user_id_sql && $contract_id_sql && is_deleted='0'";
// 				// $where = "where $date_to_sql && $date_from_sql && $user_id_sql && is_deleted='0'";
// 				// user_id ----->  employee_id
// 				// date_from ----> start_date
// 				// date_to  -----> end_date
// 				// status
// 			}
// 			$sql = "select * from contract_timeline $where";
// 			// $this->getResponse(200,$sql);
// 		  $data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 				$contract_id = $data[$key]['contract_id'];
// 				$data[$key]['contract_details'] = $this->queryResponse("select * from contract where id='$contract_id'")[0];
// 				//
// 				// $data[$key]['client_details'] = $this->getUserDetails($data[$key]['client_id']);
// 				$data[$key]['employee_details'] = $this->getUserDetails($data[$key]['user_id']);
// 			}
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function deletecontract_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$this->deleteDbRow("contract","id",$id);
// 			$this->getResponse(200);
//     }

// 		public function deleteContractTimelineAPI(){
// 			$id = $this->getSecureParams("id");
// 			$this->deleteDbRow("contract_timeline","id",$id);
// 			$this->getResponse(200);
//     }

// 		// contract end
// 		// contract end
// 		// contract end
// 		// contract end
// 		public function addonline_finance_managmentApi()
// 		{
// 			$params = array('id'=>'','client_id'=>'','title_finance'=>'',
// 			'amount'=>'','user_id'=>'','status'=>'','created_date'=>'',
// 			'modified_date'=>'','is_deleted'=>'','finance_date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 			}
// 			//////////////////////////////////
// 			$params['created_date'] = time();
// 			if(!$this->queryInsert('online_finance_managment',$params)) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function editonline_finance_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$params = array('client_id'=>'','title_finance'=>'','amount'=>'',
// 			'user_id'=>'','status'=>'','modified_date'=>'','is_deleted'=>'','finance_date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				if(!$params[$key]) unset($params[$key]);
// 			}

// 			$params['modified_date'] = time();
// 			if(!$this->queryUpdate('online_finance_managment',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function update_status_online_finance_managmentAPI(){
// 			// $this->getResponse(200);
// 			$id = $this->getSecureParams("id");
// 			$params = array('status'=>'','modified_date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				// if(!$params[$key]) unset($params[$key]);
// 			}

// 			$params['modified_date'] = time();
// 			if(!$this->queryUpdate('online_finance_managment',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function getonline_finance_managmentAPI(){
// 			$searchMode = $this->getSecureParams("searchMode");
// 			$pending_finance = $this->getSecureParams("pending_finance");
// 			$topTen = $this->getSecureParams("topTen");
// 			$id = $this->getSecureParams("id");
// 			$where = "where is_deleted='0'";
// 			if($id) $where = "where id='$id' and is_deleted='0'";
// 			if($searchMode){
// 				// client_id
// 				// date_from
// 				// date_to
// 				// status
// 				$list_client = $this->getSecureParams("list_client");
// 			// $this->getResponse(200,$list_client);
// 				// $this->getResponse(200,$list_client);

// 			  $date_to = strtotime($this->getSecureParams("date_to"));
// 			  $date_from = strtotime($this->getSecureParams("date_from"));
// 			  $client_id = $this->getSecureParams("client_id");
// 			  $status = $this->getSecureParams("status");
// 				if($status == 3) $status = "";//remove ..
// 			  //
// 			  if($date_to) $date_to_sql = "created_date <= '$date_to'";
// 			  else $date_to_sql = "1 = 1"; //always pass.
// 			  //
// 			  if($date_from) $date_from_sql = "created_date >= '$date_from'";
// 			  else $date_from_sql = "1 = 1";
// 			  //
// 			  if($client_id) $client_id_sql = "client_id='$client_id'";
// 			  else $client_id_sql = "1 = 1";
// 			  //
// 				if($status) $status_sql = "status IN ($status)";
// 				else $status_sql = "status IN (0,1,2)";

// 				if($status == "0") $status_sql = "status IN (0)";

// 				if($list_client) $list_client_sql = "&& client_id IN ($list_client)";
// 				else $list_client_sql = "";
// 			  //
// 			  //
// 			  $where = "where $date_to_sql && $date_from_sql && $client_id_sql && $status_sql $list_client_sql && is_deleted='0'";
// 			  // user_id ----->  employee_id
// 			  // date_from ----> start_date
// 			  // date_to  -----> end_date
// 			  // status
// 			}
// 			if($pending_finance){
// 				$where = "where is_deleted='0' && status='0'";
// 			}
			
			
// 			$limit = "";
// 			if($topTen) $limit = " limit 10 ";
			
// 			$sql = "select * from online_finance_managment $where order by created_date desc $limit";
// //     		  $this->dataArray = $sql;
// // 			$this->getResponse(200);

			
// 			$data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 				// $this->getResponse(200,$this->getUserDetails("0"));
// 			  $data[$key]['client_details'] = $this->getUserDetails($data[$key]['client_id']);
// 			  $data[$key]['employee_details'] = $this->getUserDetails($data[$key]['user_id']);
// 			}
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function pending_online_finance_managment_numAPI(){
// 			$sql = "select count(id) as 'pending_online_finance_managment_num' from online_finance_managment where is_deleted='0' and status='0'";
// 		  $data = $this->queryResponse($sql)[0];
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function deleteonline_finance_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$this->deleteDbRow("online_finance_managment","id",$id);
// 			$this->getResponse(200);
//     }
// 		//
// 		//
// 		//
// 		//
// 		public function addpayment_managmentApi()
// 		{
// 			$params = array('id'=>'','client_id'=>'','title_finance'=>'',
// 			'amount'=>'','user_id'=>'','created_date'=>'','modified_date'=>'',
// 			'is_deleted'=>'','month'=>'','year'=>'','date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 			}
// 			//////////////////////////////////
// 			$params['created_date'] = time();
// 			$params['date'] = strtotime($params['date']);
// 			if(!$this->queryInsert('payment_managment',$params)) $this->getResponse(503);
// 			$this->getResponse(200);
// 		}

// 		public function editpayment_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$params = array('client_id'=>'','title_finance'=>'','amount'=>'',
// 			'user_id'=>'','modified_date'=>'','is_deleted'=>'','month'=>'','year'=>'',
// 			'date'=>'');

// 			foreach ($params as $key => $value){
// 				$params[$key] = $this->getSecureParams($key);
// 				if(!$params[$key]) unset($params[$key]);
// 			}

// 			$params['date'] = strtotime($params['date']);
// 			$params['modified_date'] = time();
// 			if(!$this->queryUpdate('payment_managment',$params,"where id='$id'")) $this->getResponse(503);
// 			$this->getResponse(200);
//     }

// 		public function getpayment_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$searchMode = $this->getSecureParams("searchMode");
// 			$where = "where is_deleted='0'";
// 			if($id) $where = "where id='$id' and is_deleted='0'";
// 			if($searchMode){
// 				// client_id
// 				// date_from
// 				// date_to
// 			  $date_to = strtotime($this->getSecureParams("date_to"));
// 			  $date_from = strtotime($this->getSecureParams("date_from"));
// 			  $client_id = $this->getSecureParams("client_id");
// 			  $year = $this->getSecureParams("year");
// 			  $month = $this->getSecureParams("month");
// 			  //
// 			  if($date_to) $date_to_sql = "date <= '$date_to'";
// 			  // if($date_to) $date_to_sql = "created_date <= '$date_to'";
// 			  else $date_to_sql = "1 = 1"; //always pass.
// 			  //
// 			  if($date_from) $date_from_sql = "date >= '$date_from'";
// 			  // if($date_from) $date_from_sql = "created_date >= '$date_from'";
// 			  else $date_from_sql = "1 = 1";
// 			  //
// 			  if($client_id) $client_id_sql = "client_id='$client_id'";
// 			  else $client_id_sql = "1 = 1";
// 			  //
// 			  if($year) $year_sql = "year='$year'";
// 			  else $year_sql = "1 = 1";
// 			  //
// 			  if($month) $month_sql = "month='$month'";
// 			  else $month_sql = "1 = 1";
// 			  //
// 			  //
// 			  $where = "where $date_to_sql && $date_from_sql && $client_id_sql && $month_sql && $year_sql && is_deleted='0'";
// 			  // user_id ----->  employee_id
// 			  // date_from ----> start_date
// 			  // date_to  -----> end_date
// 			}
// 			$sql = "select * from payment_managment $where";
// 			// $this->getResponse(200,$sql);
// 		  $data = $this->queryResponse($sql);
// 			foreach ($data as $key => $value) {
// 			  $data[$key]['client_details'] = $this->getUserDetails($data[$key]['client_id']);
// 			  // $data[$key]['employee_details'] = $this->getUserDetails($data[$key]['employee_id']);
// 			}
// 		  $this->dataArray = $data;
// 			$this->getResponse(200);
//     }

// 		public function deletepayment_managmentAPI(){
// 			$id = $this->getSecureParams("id");
// 			$this->deleteDbRow("payment_managment","id",$id);
// 			$this->getResponse(200);
//     }
// 		/* function */ /* function */ /* function */ /* function */ /* function */
// 		/* function */ /* function */ /* function */ /* function */ /* function */
// 		/* function */ /* function */ /* function */ /* function */ /* function */
// 		public function getUserDetails($id){
// 			// if(!$id) $this->getResponse(503);
// 			if(!$id) return false;
// 			$sql = "select * from users where user_id='$id'";
// 		  $data = $this->queryResponse($sql);
// 			// if(!$data[0]['img']) $data[0]['img'] = PROFILE_DEFAULT;
// 			// if(!$data[0]['b_img']) $data[0]['b_img'] = PROFILE_DEFAULT;
// 			if($data[0]['user_type'] == "client"){
// 				$user_id = $data[0]['user_id'];
// 				$data[0]['contract'] = $this->queryResponse("select * from contract where client_id='$user_id'")[0];
// 				$data[0]['contract']['online_finance_on_this_month'] = $this->getUserTotalOnlineFinanceOnThisMonth($user_id);
// 				$data[0]['contract']['rest_for_this_month'] = $data[0]['contract']['payment_amount'] - $data[0]['contract']['online_finance_on_this_month'];
// 			}
// 		  return $data[0];
//     }


// 		public function getUserTotalOnlineFinanceOnThisMonth($user_id){
// 			// all online payment should be status -> 1.
// 			$current_month = date("m");
// 			$month_start_date = $current_month."/"."01/".date("yy");
// 			$month_end_date = $current_month."/"."31/".date("yy");
// 			$month_start_date_timestamp = strtotime($month_start_date);
// 			$month_end_date_timestamp = strtotime($month_end_date);

// 			$sql = "select sum(amount) as 'online_finance_on_this_month' from online_finance_managment where client_id='$user_id' and is_deleted='0' and status='1' and created_date >= $month_start_date_timestamp and created_date <= $month_end_date_timestamp";
// 		  $data = $this->queryResponse($sql)[0]['online_finance_on_this_month'];
// 			if(!$data) $data = 0;//if there is no online finance for this month until now.

// 			// return $sql;
// 			return $data;
// 		}

	}
?>
