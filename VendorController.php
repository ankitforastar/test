<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorRequest;
use App\Repositories\VendorRepo;
use App\Repositories\RestaurantRepo;
use App\Repositories\LocationRepo;
use App\Repositories\BrandRepo; 
use App\Repositories\OrderDeliveryRepo; 
use App\Repositories\OrderRepo;
use App\Repositories\RestaurantAccountSettleRepo;
use App\User;
use Validator;
use session;
use auth;
use Mail;
use Response;
class VendorController extends Controller
{ 

  public function __construct()
  { 
    $this->middleware('role:ADMIN');
    $this->middleware('auth');
  }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     $users = VendorRepo::getAllVendor();  
     return view('admin.vendor.list',compact('users'));
   } 

   public function vendorUsersNew()
   {

     $users = VendorRepo::getAllNewVendor();  
     return view('admin.vendor.new',compact('users'));
   }

   public function settleAccount(Request $request)
   {
     $input = $request->all();
     if(!empty($input)){

      $restaurants = OrderRepo::getAllOrders($input);     



    }
    return view('admin.vendor.settle_account',compact('restaurants'));
  }
  public function downloadSettleAccount(Request $request)
  {

   $input = $request->all();
   if(!empty($input)){
    $restaurants = OrderRepo::getAllOrders($input);     
    if($input['type']=='PDF'){
      $data =   view('admin.order.settle_account_download',compact('restaurants'));
      $pdf = \App::make('dompdf.wrapper');
      $pdf->loadHTML($data);    
      return $pdf->download('Settle-Account-Report-'.time().'.pdf');

    }

    if($input['type']=='CSV'){
      $headers = array(
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=Settle-Account-Report-".time().".csv",
        "Pragma" => "no-cache",
        "Cache-Control" => "no-cache, no-store, max-age=0, must-revalidate",
        "Expires" => "Sun, 02 Jan 1990 00:00:00 GMT"
      );      


      $columns = array('Restaurant Name', 'Location', 'Total Orders Cost', 
        'Total Service Cost', 'Total Due ');

      $callback = function() use ($restaurants, $columns)
      {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        foreach($restaurants as $restaurant) {
          fputcsv($file, array(
            $restaurant['restaurant_name'],
            $restaurant['location']['location_name'],
            $restaurant['order_total'], 
            $restaurant['service_charge'],
            $restaurant['total_due']
          ));
        }
        fclose($file);
      };

      return Response::stream($callback, 200, $headers);

    }


  }

}



public function restaurantOrderList($id)
{
 $data =  OrderRepo::getAllOrdersByRestaurantID($id,$_REQUEST);
 $orders = $data['orders'];
 $resaurant =  RestaurantRepo::getById($id);
 $settle_histories = OrderRepo::getSettleHistory($id);
 return view('admin.order.history_by_restaurant',compact('orders','resaurant','settle_histories'));
}


public function orderListDownload(Request $request)
{

 $input = $request->all();
 if(!empty($input)){
  $id = $input['restaurant_id'];
  $resaurant =  RestaurantRepo::getById($id);
  $data =  OrderRepo::getAllOrdersByRestaurantID($id,$input);
  $orders = $data['orders'];
  $order_total = $data['order_total'];
  $service_charge = $data['service_charge'];
  if(isset($input['pdf'])){
   $data =   view('admin.order.order_history_download',compact('orders','order_total','service_charge','resaurant'));
   $pdf = \App::make('dompdf.wrapper');
   $pdf->loadHTML($data);    
   return $pdf->download('Order-Report-'.time().'.pdf'); 
 }

 if(isset($input['csv'])){
  $headers = array(
    "Content-type" => "application/csv",
    "Content-Disposition" => "attachment; filename=Settle-Account-Report-".time().".csv",
    "Pragma" => "no-cache",
    "Cache-Control" => "no-cache, no-store, max-age=0, must-revalidate",
    "Expires" => "0"
  );      


  $columns = array('Order ID', 'Date Time', 'Total Cost (KHR)', 
    'Serivce Charge (KHR)', 'Status','Is Settled');


  $filename = time()."-Report.csv";
  $handle = fopen($filename, 'w+');
  fputcsv($handle, $columns);

  foreach($orders as $order) {
    fputcsv($handle, array(
      $order['id'],
      $order['created_at'],
      number_format($order['total_price_to_pay'],2), 
      $order['service_charge'],    
      $order['order_status']['status_name'],
      $order['settled_at']==NULL?"NO":"YES"
    ));
  }


  //fclose($filename);
  return Response::download($filename, $filename, $headers);

}


}


}



public function settleAccountUpdate(Request $request){
 $input = $request->all();
 if(!empty($input)){
   OrderRepo::settleAccount($input);
   $request->session()->flash('alert-success', 'Account Settle Updated');
   return back();
 }
}

public function settleAccountUpdateAll(Request $request){
 $input = $request->all();
 if(!empty($input)){
  $restaurantIDs = array_filter(explode(',',$input['restaurantIDs']));
  if(empty($restaurantIDs)){
    $request->session()->flash('alert-danger', 'Please select atlease one restaurant');
    return back();
  }

  OrderRepo::settleAccountAll($input);
  $request->session()->flash('alert-success', 'Account Settle Updated');
  return back();
}
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {         
     $locations = LocationRepo::getLocationsLists();   
     $brands = BrandRepo::getAll();   
     return view('admin.vendor.add',compact('locations','brands'));
   }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     $input = $request->all();
     $input = $request->all();
     $locationName = LocationRepo::getLocationById($request['location_id'])->location_name;

     $slug = str_slug($request['restaurant_name'].'-'.$locationName);

     $insert = VendorRepo::createVendor($input); 
     $restaurantInsert= [ 
      'user_id'=>$insert->id,
      'restaurant_name'=>$request['restaurant_name'],
      'slug'=>$slug, 
      'restaurant_description'=>$request['restaurant_description'],     
      'location_id'=>$request['location_id'],
      'is_out_let'=>$request['is_out_let'], 
      'is_order_online_active'=>$request['is_order_online_active'],
      'out_let_id'=>$request['out_let_id'],
      'is_active'=>$request['is_active'],
      'is_featured'=>$request['is_featured']
    ];
    $restaurantInfo =  RestaurantRepo::create($restaurantInsert);
    // Mail::send('email.register_vendor',
    //       ['data'=>$input], // dynamic data
    //       function($message) use ($input){      
    //         $message->to($input['email'], VENDOR_REGISTER)
    //         ->subject(VENDOR_REGISTER);      
    //       }); 

    $body = view('email.register_vendor',['data'=>$input]);
    $sendTo['email'] = $input['email'];
    $sendTo['name'] = $input['first_name']." ".$input['last_name'];
    $this->sendMail($sendTo,VENDOR_REGISTER,$body);    
    $request->session()->flash('alert-success', 'Vendor successfully added!');
    return redirect()->route('vendors.index');
  }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

     $locations = LocationRepo::getLocationsLists();   
     $brands = BrandRepo::getAll();   
     $user = VendorRepo::getVendorById($id);

     return view('admin.vendor.edit',compact('user','locations','brands'));
   }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $request->validate([
       'restaurant_name' => 'required',
       'first_name' => 'required',
       'last_name' => 'required',
       'email' => 'required|unique:users,email,'.$id,       
       'location_id' => 'required',
       'is_out_let' => 'required',
       'mobile_no' => 'required',
     ]);

      if (!$request->filled('password')) {
       $input  = $request->except('password');
     }  else{
       $input = $request->all();
     }

     VendorRepo::updateVendor($input,$id);   
     $locationName = LocationRepo::getLocationById($request['location_id'])->location_name;

     $slug = str_slug($request['restaurant_name'].'-'.$locationName);

     $restaurantUpdate= [ 
       'restaurant_name'=>$request['restaurant_name'],
      // 'slug'=>$slug,
       'restaurant_description'=>$request['restaurant_description'],     
       'location_id'=>$request['location_id'],
       'is_order_online_active'=>$request['is_order_online_active'],
       'is_out_let'=>$request['is_out_let'],
       'out_let_id'=>$request['out_let_id'] ,
       'is_active'=>$request['is_active'],
      // 'mobile_no'=>$request['mobile_no'],
       'is_featured'=>$request['is_featured']
     ];     
     if($request['is_out_let']==0){
      $restaurantUpdate['out_let_id']= 0;
    }
    
    RestaurantRepo::update($restaurantUpdate,$id);  

    $request->session()->flash('alert-success', 'Vendor successfully updated!');
    return redirect()->route('vendors.index');
  }

  public function restaurantApproveUpdate(Request $request){
    $input = $request->all();
    $id = $input['restaurant_id'];
    $userID = $input['user_id'];

    $dataGet =  VendorRepo::getVendorById($userID);
    $email = $dataGet->email;
    $name = $dataGet->first_name." ".$dataGet->last_name;

    if($input['aprpove']==1){
    //VendorRepo::updateVendor(['active'=>1],$userID); 

      $input['message'] = 'Congratulation ! Your account successfully created. Now you can login with your email and password. Please fill up all information and let admin know once you set up your profile contact with admin.';
    }else{
     $input['message'] = 'Your account is not approved after reviewing by admin. Please contact admin for more detail.' ;
   }
   $data =['is_active'=>$input['aprpove']];
   RestaurantRepo::updateRestaurant($data,$id); 
   // Mail::send('email.vendor_signup_status',
   //        ['data'=>$input], // dynamic data
   //        function($message) use ($email){      
   //          $message->to($email, '')
   //          ->subject(VENDOR_APPROVE);      
   //        }); 

   $body = view('email.vendor_signup_status',['data'=>$input]);
   $sendTo['email'] = $email;
   $sendTo['name'] = $name;
   $this->sendMail($sendTo,VENDOR_APPROVE,$body); 
   $request->session()->flash('alert-success', 'Vendor status successfully updated!');
   return redirect()->route('vendor_users_new.index'); 
 }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {      
      VendorRepo::deleteVendor($id);  
      session()->flash('alert-success', 'Vendor successfully deleted!');
      return redirect()->route('vendors.index');
    }

    public function deleteMultiple(Request $request)
    {

      $input = $request->all();
      $delete = array_filter(explode(',', $input['delete']));    
      if(empty( $delete)){
       session()->flash('alert-danger', 'Please select atleast one checkbox!');
       return redirect()->back();
     }
     VendorRepo::deleteMultiple($delete);  
     session()->flash('alert-success', 'Vendor successfully deleted!');
     return redirect()->back();
   }



   public function restaurantView($id=null){

    $restaurant = RestaurantRepo::detailRestaurant($id);
    return view('admin.vendor.detail',compact('restaurant'));


  }
  public function restaurantApprove($id= null){


   $user = VendorRepo::getVendorById($id);
   return view('admin.vendor.approve_view',compact('user'));
 }


 public function getOrder(){

   $orders = OrderRepo::getAllOrder();
   return view('admin.vendor.orderlist',compact('orders'));


 }



 public function orderView($id= null){
  $order = OrderRepo::getOrderDetail($id);

$deliveryUser=OrderDeliveryRepo::getDeliveryUserByOrder($id);
  return view('admin.vendor.order_view',compact('order','users','getUserId','deliveryUser'));
}


}
