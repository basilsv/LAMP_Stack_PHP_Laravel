<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Processing;
use Stripe;

class CartsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.checkout');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!$request->get('product_id')){

            return['message'=> 'Items returned',
            'items' =>   $userItems = Cart::where('user_id',auth()->user()->id)->sum('quantity'),
        ];
        }
      
        $product = Product::where('id',$request->get('product_id'))->first();
        $productFoundInCart = Cart::where('product_id',$request->get('product_id'))->pluck('id');
       // dd($productFoundInCart->isEmpty());
       
       if($productFoundInCart->isEmpty()){

        $cart = Cart::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->sale_price,
            'user_id' => auth()->user()->id,
        ]);


       }
       else{
        $cart = Cart::where('product_id',$request->get('product_id'))->increment('quantity');

       }
       
        if($cart){
            return['message'=> 'Cart Updated',
            'items' =>   $userItems = Cart::where('user_id',auth()->user()->id)->sum('quantity'),
        ];
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource frosm storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
/**
 * 
 */
    public function getCartItemsForCheckout(){
        $cartItems = Cart::with('product')->where('user_id',auth()->user()->id)->get();
        $finalData = [];
        $amount = 0;
        if(isset($cartItems)){
        foreach($cartItems as $cartItem){
            if($cartItem->product){
                foreach($cartItem->product as $cartProduct){  
                    if($cartProduct->id == $cartItem->product_id){
                        $finalData[$cartItem->product_id]['id'] = $cartProduct->id;
                        $finalData[$cartItem->product_id]['name'] = $cartProduct->name;
                        $finalData[$cartItem->product_id]['quantity'] = $cartItem->quantity;
                        $finalData[$cartItem->product_id]['sale_price'] = $cartItem->price;
                        $finalData[$cartItem->product_id]['total'] = $cartItem->price * $cartItem->quantity;
                        $amount += $cartItem->price * $cartItem->quantity;
                        $finalData['totalAmount'] = $amount;
                    }
                  //  var_dump($cartProduct->name);  
                }
            }
          
         
        }
       // dd($finalData);
        }
       // dd($cartItems);
        return response()->json($finalData);
    }


    public function processPayment(Request $request){

        $firstName = $request->get('firstName');
        $lastName = $request->get('lastName');
        $address = $request->get('address');
        $city = $request->get('city');
        $state = $request->get('state');
        $ssip_code = $request->get('ssip_code');
        $email = $request->get('email');
        $phone = $request->get('phone');
        $country = $request->get('country');
        $cardType = $request->get('cardType');
        $expirationMonth = $request->get('expirationMonth');
        $expirationYear = $request->get('expirationYear');
        $cvv = $request->get('cvv');
        $cardNumber = $request->get('cardNumber');
        $amount = $request->get('amount');
        $orders = $request->get('order');
        $ordersArray=[];

        //getting Order Details
        foreach($orders as $order){
            if($order['id']){
                $ordersArray[$order['id']]['order_id'] = $order['id'];
                $ordersArray[$order['id']]['quantity'] = $order['quantity'];
            }
    

        }
      //  dd($order,json_encode($ordersArray));


       
        
       // dd($request->all());

       //Process Payment

       $stripe = Stripe::make(env('STRIPE_SECRET'));
       $token = $stripe->tokens()->create([
           
        'card'=> [
        'number' => $cardNumber,
        'exp_month'=> $expirationMonth,
        'exp_year'=> $expirationYear,
        'cvc'=> $cvv,
        ]]
    );

       //dd($token);
       if(!$token['id']){
           session()->flush('error','Stripe Token generation failed');
           return;
       }
       //Create a Customer stripe.

       $customer = $stripe->customers()->create([
           'name' => $firstName.' '.$lastName,
           'email' => $email,
           'phone' => $phone,
           'address' => [
               'line1' => $address,
               'postal_code' => $ssip_code,
               'city' => $city,
               'state' => $state,
               'country' => $country, 
           ],
           'shipping' => [
            'name' => $firstName.' '.$lastName,
            'address' => [
                'line1' => $address,
                'postal_code' => $ssip_code,
                'city' => $city,
                'state' => $state,
                'country' => $country, 
            ],

        ],
        'source' => $token['id'],

       ]);

       //Code to Charge the client in Stripe

       $charge = $stripe->charges()->create([
           'customer' => $customer['id'],
           'currency' => 'USD',
           'amount' => $amount,
           'description' => 'Payment for Order',
       ]);
     
        if($charge['status']=="succeeded"){
            
            //capture details from strip
            $customerIdStripe = $charge['id'];
            $amountRec = $charge['amount'];
            
            

           $processingDetails =  Processing::create([
                'client_name'=> $firstName.' '.$lastName,
                'client_address' => json_encode( [
                    'line1' => $address,
                    'postal_code' => $ssip_code,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country, 
                ]),
                'client_order_details' => json_encode($ordersArray),
                'amount'=> $amountRec,
                'currency' => $charge['currency'],
            ]);

            if($processingDetails){

            }     


        }
        else{
           // dd($charge);
        }

    
    }
}
