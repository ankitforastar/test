<?php


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use App\Repositories\RestaurantRepo;
use App\Repositories\CartRepo;
use App\Repositories\ItemRepo;
use App\Repositories\ItemAddOnRepo;
use App\Models\CartItemAddon;
use App\Models\ItemAddOn;
use App\Models\Cart;


class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {

    } 

    public function index(Request $request){
     $input = $request->all();
     $userID = $input['user_id'];
     $data['cart_count'] = count(CartRepo::getAll($userID)); 
     $type = CartRepo::getDeliveryType($userID); 
     if(!empty($type)){
      $data['delivery_type'] = $type->delivery_type;
    }else{
      $data['delivery_type'] = null;
    }

    $data['total_items'] = CartRepo::getAll($userID);   
    if(!$data['total_items']->IsEmpty()){
      $data['restaurant_detail'] = RestaurantRepo::getById($data['total_items'][0]->restaurant_id);  
    }
    $data['order_calculation'] = CartRepo::getOrderCalculation($input); 


    return $this->sendResponse($data, trans('messages.data_get_success'));
  }




 //  public function  store(Request $request)
 //  {

 //    $validator = Validator::make($request->all(), [
 //      'restaurant_id' => 'required',
 //      'item_id' => 'required',
 //      'qty' => 'required',
 //      'user_id'=>'required'      
 //    ]);
 //    if($validator->fails()){
 //      return $this->sendError('Validation Error.', $validator->errors());       
 //    }
 //    $input = $request->all();
 //    $checkItem = CartRepo::checkItemeExist($input);

 //   // CartRepo::create($input);
 //    if(!empty($checkItem)){
 //      CartRepo::updateExisting($input,$checkItem->id);
 //    }else{
 //     CartRepo::create($input);
 //   } 

 //   $data['cart_count'] = (CartRepo::getCartCount($input['user_id'])); 
 //   return $this->sendResponse($data, trans('messages.cart_item_add'));

 // }

  public function show($id=null){

   $data = CartRepo::getById($id);
   return $this->sendResponse($data, trans('messages.data_get_success'));

 }

 public function update(Request $request,$id){


   $input = $request->all();
   CartRepo::update($input,$id);
   $data['cart_count'] = count(CartRepo::getAll($input['user_id'])); 
   return $this->sendResponse($data, trans('messages.cart_item_update'));

 }
 public function destroy(Request $request,$id=null){

  CartRepo::delete($id);
  $input = $request->all();
  $data['cart_count'] = count(CartRepo::getAll($input['use_id'])); 
  return $this->sendResponse($data, trans('messages.cart_item_delete'));

}

public function deleteMultiple(Request $request){
  $input = $request->all();
  if(!empty($input['user_id'])){
    $data = CartRepo::deleteMultiple($input['user_id']);
    return $this->messageResponse(trans('messages.cart_item_delete'));
  }
}


 // Item Addon Add to cart
public function getAddOnByItemID($id=null){
  $data =  ItemAddOnRepo::getAddOnByItemID($id);
  return $this->sendResponse($data, trans('messages.data_get_success'));
}


public function  createCartItemAddon(Request $request)
{

 $validator = Validator::make($request->all(), [
  'user_id'=>'required',
  'item_id' => 'required',
  'restaurant_id'=>'required',  
  'qty' => 'required'

]);


 if($validator->fails()){
  return $this->sendError('Validation Error.', $validator->errors());       
}
$input = $request->all();

$checkItem = CartRepo::checkItemeExist($input);

// if nothing found in cart with item it will create new cart
if(empty($checkItem)){

// create cart with item & addon
  CartRepo::createCartWithAddOn($input);

}else{
    /* We have item which already exist suppose have same item id 
     and diffrent addons than follow code
    *
    */

     if(!empty($input['addon_id']) && $input['addon_id']!=NULL){
       $add_ids = array_filter(explode(',', $input['addon_id']));
       $addOnsNew = array();
       foreach($add_ids as $addID){
        $addOnsNew[] = (int)  $addID;
      }

      $checkValue = 1;
      $checkaDatas =  Cart::where('user_id',$input['user_id'])
      ->where('item_id',$input['item_id'])  
      ->get();

      if(!empty($checkaDatas)){
        foreach($checkaDatas as $getCart){

          $addOneOld=CartItemAddon::where('cart_id',$getCart->id)
          ->where('user_id',$input['user_id'])
          ->pluck('addon_id')->toArray();
          $collection = collect($addOnsNew);
          $diff = $collection->diff($addOneOld);
          $checkExist =  $diff->all();
          if(collect($checkExist)->isEmpty() && (count($addOneOld)==count($addOnsNew))){
           $checkValue = 2;
           $getCartUpdateID = $getCart->id;
         }
       }
     }

     if($checkValue==2){
       CartRepo::updateExisting($input,$getCartUpdateID);
     }else{

      // create cart with item & addon
      CartRepo::createCartWithAddOn($input);

    }

  }else{
     // Check if addon exist or not if not exist than create new one else update

    $checkValue= CartRepo::checkCartItemAddOnExist($request);

    if($checkValue==1){
     CartRepo::create($input);
   }else{
    CartRepo::updateExisting($input,$checkItem->id);
  }
}
}

$data['cart_count'] = (CartRepo::getCartCount($input['user_id'])); 
$type = CartRepo::getDeliveryType($input['user_id']); 
if(!empty($type)){
  $data['delivery_type'] = $type->delivery_type;
}else{
  $data['delivery_type'] = null;
}
return $this->sendResponse($data, trans('messages.cart_item_add'));
}

public function deleteAddon($id=null){
 $data = CartRepo::deleteAddon($id);
 return $this->messageResponse(trans('messages.cart_item_delete'));
}



}
