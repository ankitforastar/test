<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;

use App\Models\Cart;
use App\Models\Item;
use App\Models\Restaurant;
use App\Models\ItemAddOn;
use App\Models\CartItemAddon;
use App\Models\Setting;
use App\Models\Promotion;

class CartRepo 
{ 

	static function getAll($userID=null)
	{
		return Cart::with('items','items.files','cart_item_addon','cart_item_addon.add_ons')->where('user_id',$userID)->get();
	}

	static function getDeliveryType($userID=null)
	{
		return Cart::where('user_id',$userID)->first();
	}

	static function getCartCount($userID=null)
	{
		return Cart::where('user_id',$userID)->count();
	}


	static function getTotalCost($userID=null)
	{
		return Cart::where('user_id',$userID)->sum('total_items_cost_without_discount');
	}

	static function getOrderCalculation($input)
	{


		$userID=$input['user_id'];
		$restaurant = Cart::with('restaurant')->where('user_id',$userID)->first();
		$data['subtotal']  = Cart::where('user_id',$userID)->sum('total_items_cost_without_discount');

		if(!empty($restaurant)){
		// Calculate Promotion
			if(isset($input['promo_code'])){
				$checkPromo = Promotion::where('promo_code',$input['promo_code'])
				->where('valid_till','>=',date('Y-m-d'))->first();

				if(!empty($checkPromo) && ($checkPromo->code_for==0 || ($checkPromo->code_for==$restaurant->restaurant_id)) && ($checkPromo->min_order < $data['subtotal'])){
					$data['promo_code'] =  $checkPromo->promo_code;
					if($checkPromo->is_percentage_flat=="percentage"){
						$promo_discount = $checkPromo->discount;
						$promo_mins = ($promo_discount/100) * $data['subtotal'];
						$data['promo_discounted_amount'] =  number_format($promo_mins,2,'.',''); 
					}else{
						$promo_mins =  $checkPromo->discount; 
						$data['promo_discounted_amount'] = number_format($promo_mins,2,'.',''); 
					}
				}else{
					$data['promo_code'] = null;
					$data['promo_discounted_amount'] = 0;
				}
			}else{
				$data['promo_code'] = null;
				$data['promo_discounted_amount'] = 0;
			}

			$data['delivery_time'] = $restaurant->restaurant->deliver_time;
			$data['delivery_charge'] = $restaurant->restaurant->delivery_charge;

		// Discount
			$data['restaurant_discount_percentage'] = $restaurant->restaurant->discount;

			$discount = Cart::where('user_id',$userID)->sum('discounted_amount');
			$data['restaurant_discounted_amount']  = $discount;

			/** subtotal-promocode - discount **/
			$costAfterDiscount = $data['subtotal'] -  $discount - $data['promo_discounted_amount'];
			$data['total_after_discount_promocode']  =  number_format($costAfterDiscount,2,'.','');
			$global = Setting::all()
			->keyBy('key')  
			->transform(function ($setting) {
				return $setting->value;  
			});

			// tax calculate on 
			if(isset($global['tax'])){
				$tax = $global['tax'];	
			}else{
				$tax = 5;	
			}

			$data['tax'] = (int) $tax ;
			$tax_plus = ($tax/100) * ($costAfterDiscount);
			$data['tax_amount'] = number_format($tax_plus, 2, '.', '');

			// service charge	   
			if(isset($global['service_charge'])){
				$service_charge = $global['service_charge'];	
			}else{
				$service_charge = 5;
			}

			$data['service_percentage'] = (int) $service_charge ;
			$service_charge_plus = ($service_charge/100) * $costAfterDiscount;
			$data['service_charge'] =number_format($service_charge_plus, 2, '.', '');

			$finalCost = $costAfterDiscount + $data['tax_amount'] + $data['service_charge'] + $data['delivery_charge'];
			$data['total_price_to_pay'] = number_format($finalCost, 2, '.', '');


			return $data;
		}
	}

	// 28-6
	static function create($request){
		// Get Item Detail 
		$item = Item::where('id',$request['item_id'])->first();	
		// Get Restaurant Discount
		$data =  Restaurant::findOrFail($item->restaurant_id);
		if(!empty($request['addon_id']) && $request['addon_id']!=NULL){
			$addOnIDs = array_filter(explode(',', $request['addon_id']));
			$addonCost =ItemAddOn::whereIn('id',$addOnIDs)->sum('price');
		}else{
			$addonCost = 0 ;
		}

		$item_price = $item->item_price + $addonCost;// Item + Addons cost
		$discount =  $data->discount;		
		// Calculate data with discount
		if($data->is_discount_active==1){			
			$discounted_amount = ($discount/100)*$item_price;
			$price = $item_price-$discounted_amount;
			$cost =  number_format($price,2, '.', '');
		}else{
			$cost =number_format($item_price,2, '.', '');
			$discounted_amount = 0;
		}
		$request['actual_item_cost'] = $item_price;
		$request['addon_cost_total'] = $addonCost;
		$request['final_item_cost'] = $cost;		
		$request['total_items_cost_without_discount'] = $item_price * $request['qty'];
		$request['discounted_amount'] = $discounted_amount * $request['qty'];
		$request['total_items_cost'] = $cost * $request['qty'];
		return Cart::create($request);	
	}
// 28-6
	static function updateExisting($request,$id)
	{


		$item =Cart::findOrFail($id);

		$totalQty = $item->qty + $request['qty'];
		$request['qty'] = $totalQty;
		if($totalQty <= 0){
			return Cart::destroy($id);
		}

		$data =  Restaurant::findOrFail($item->restaurant_id);
		$discount =  $data->discount;	

		$item_price = $item->actual_item_cost;
		if($data->is_discount_active==1){			
			$discounted_amount = ($discount/100)*$item_price;
			$price = $item_price-$discounted_amount;
			$cost = $price;
		}else{
			$cost = $item->actual_item_cost;
			$discounted_amount = 0;
		}

		$request['total_items_cost_without_discount'] =  $item->actual_item_cost * $request['qty'];
		
		$request['discounted_amount'] = $discounted_amount * $totalQty;

		$request['total_items_cost'] =  $item->final_item_cost * $totalQty;

		Cart::findOrFail($id)->update($request);	
		return Cart::findOrFail($id);	 
	}



	static function checkItemeExist($request)
	{

		return Cart::where('item_id',$request['item_id'])
		->where('user_id',$request['user_id'])->with('cart_item_addon')->first();
	}

	static function getById($id=null)

	{
		return Cart::with('items','restaurant')->findOrFail($id);
	}

	static function update($request,$id)
	{

		$item = Self::getById($id);
		
		if($request['qty'] <= 0){
			return Cart::destroy($id);
		}

		// Calculate data with discount
		$data =  Restaurant::findOrFail($item->restaurant_id);
		$discount =  $data->discount;	

		$item_price = $item->actual_item_cost;
		if($data->is_discount_active==1){			
			$discounted_amount = ($discount/100)*$item_price;
			$price = $item_price-$discounted_amount;
			$cost =  $price;
		}else{
			$cost = $item->actual_item_cost;
			$discounted_amount = 0;
		}
		
		$request['total_items_cost_without_discount'] =  $item->actual_item_cost * $request['qty'];
		$request['discounted_amount'] = $discounted_amount * $request['qty'];
		$request['total_items_cost'] =  $item->final_item_cost * $request['qty'];
		
		return Cart::findOrFail($id)->update($request);		
	}

	static function delete($id=null)
	{
		return Cart::destroy($id);
	}

	static function createAddon($request)
	{

		$data = CartItemAddon::where('addon_id',$request['addon_id'])
		->where('user_id',$request['user_id'])->first();
		if(empty($data)){
			return CartItemAddon::create($request);	
		}
		
	}

	static function getAddOnById($id=null)
	{
		return ItemAddOn::where('id',$id)->first();
	}
	static function deleteAddon($id=null)
	{
		return CartItemAddon::destroy($id);
	}

	//Delete by UserID
	static function deleteMultiple($id=NULL)
	{
		Cart::where('user_id',$id)->delete();
		return CartItemAddon::where('user_id',$id)->delete();
	}
	

	/**New Algo for create item addon cart STEP 1**/
	static function createCartWithAddOn($input){
		$cart = Self::create($input);
		$input['cart_id'] = $cart->id;

		if(!empty($input['addon_id']) && $input['addon_id']!=NULL){
			$addOns = array_filter(explode(',', $input['addon_id']));
			
			foreach($addOns as $addon_id){

				$addon_price = Self::getAddOnById($addon_id)->price;
				$insertAddOn = [
					'cart_id'=>$cart->id,
					'addon_id'=>$addon_id,
					'cost'=>$addon_price,
					'user_id'=>$input['user_id']
				];
				CartItemAddon::create($insertAddOn);
			}
		}
	}

	/**New Algo for create item addon cart STEP 2**/
	static function checkCartItemAddOnExist($input){
		$checkValue = 1;
		$checkaDatas =  Cart::where('user_id',$input['user_id'])
		->where('item_id',$input['item_id'])  
		->get();

		if(!empty($checkaDatas)){
			foreach($checkaDatas as $getCart){

				$addOneOld=CartItemAddon::where('cart_id',$getCart->id)
				->where('user_id',$input['user_id'])
				->pluck('addon_id')->toArray();
				if(collect($addOneOld)->isEmpty() && $addOneOld==Null){
					$checkValue = 2;
				}
			}
		}
		return $checkValue;
	}

}

