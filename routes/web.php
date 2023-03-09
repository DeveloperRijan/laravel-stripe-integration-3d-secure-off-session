<?php

use App\Helpers\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $stripe_publishable_key = config("payment.STRIPE_PUBLISHABLE_KEY");
    return view('welcome', compact("stripe_publishable_key"));
});

Route::group(["prefix"=>"payments", "as"=>"payments."], function(){
    //step 1
    Route::post("setup-intents", function(Request $request){
        $customerName = $request->get("customer_name");
        $customerEmail = $request->get("customer_email");
        $customerPhone = $request->get("customer_phone");

        $stripe = new \Stripe\StripeClient(config("payment.STRIPE_SECRET_KEY")); //secret key

        //step 01
        //Create Customer
        $customer = $stripe->customers->create([
            'name'=>$customerName,
            "phone"=>$customerPhone,
            'description' =>"Test Customer",
            'email' => $customerEmail
        ]);

        if(!isset($customer["id"]) || $customer["id"] == ''){
            return response()->json([
                "success"=>false,
                "msg"=>"Creating customer has been failed",
                "customer"=>$customer
            ], 500);
        }

         //step 02
        //Setup Intent
        $setupIntent = $stripe->setupIntents->create(
            [
                'customer' => $customer["id"],
                'payment_method_types' => ['card'],
            ]
        );

        if(!isset($setupIntent['client_secret'])){
            return response()->json([
                "success"=>false,
                "msg"=>"Setup Intent has been failed!",
                "customer"=>$customer
            ], 500);
        }


        //success all

        return response()->json([
            "success"=>true,
            "client_secret"=>$setupIntent->client_secret,
            "setupIntent"=>$setupIntent,
            "customer"=>$customer
        ]);
    })->name("setup.intents");


    //step 2
    Route::get("/setup-intents/verify", function(Request $request){
        $setup_intent = $request->get("setup_intent");
        $setup_intent_client_secret = $request->get("setup_intent_client_secret");
        $redirect_status = $request->get("redirect_status");

        //retrive intent
        $stripe = new \Stripe\StripeClient(config("payment.STRIPE_SECRET_KEY")); //secret key
        $intent = $stripe->setupIntents->retrieve($setup_intent);

        //doc
            // Inspect the SetupIntent `status` to indicate the status of the payment
            // to your customer.
            //
            // Some payment methods will [immediately succeed or fail][0] upon
            // confirmation, while others will first enter a `processing` state.
            //
            // [0]: https://stripe.com/docs/payments/payment-methods#payment-notification
            // switch (setupIntent.status) {
            //     case 'succeeded': {
            //         message.innerText = 'Success! Your payment method has been saved.';
            //         break;
            //     }

            //     case 'processing': {
            //         message.innerText = "Processing payment details. We'll update you when processing is complete.";
            //         break;
            //     }

            //     case 'requires_payment_method': {
            //         message.innerText = 'Failed to process payment details. Please try another payment method.';

            //         // Redirect your user back to your payment page to attempt collecting
            //         // payment again

            //     break;
            // }
        //doc end

        //=================================================================
        // You can now create payment intent + subscription or whatever using above payment intent
        // Note you have to save payment_method (payment method id) returned in response of payment intent
        // that payment method id will be use to determine which payment method will be use to charge your customer.
        // you can hit test-execute-payment-off-session route to test things
        // before hit you have to set env as per response of setup intent 


        // return [
        //     "setup_intent"=>$setup_intent,//id,
        //     "setup_intent_client_secret"=>$setup_intent_client_secret,
        //     "redirect_status"=>$redirect_status,
        //     "intent"=>$intent
        // ];

        if(isset($intent['id']) && $intent['status'] === "succeeded"){
            //update customer payment default method...
            $intent['payment_method']; //verified payment method id

        }
        //eit id amer database -- childrens tabl 

        return [
            "msg"=>"Verifying intent has been failed",
            "intent"=>$intent
        ];

    })->name("setup.intents.verify");
});

Route::get("get", function(){
    return round(1.33333333333);
});

Route::get("/subsciption-update", function(){
    $stripe = new \Stripe\StripeClient(config("payment.STRIPE_SECRET_KEY")); //secret key

    

    $subscription = $stripe->subscriptions->retrieve('sub_id');

    //remove subscription item
    // foreach( $subscription->items->data as $item){
    //     if($item->id === "item_id_here"){
    //         $stripe->subscriptionItems->delete(
    //             $item->id,
    //             []
    //         );
    //     }
    // }
    // return "dne";

    //update subscriptio item
    // foreach( $subscription->items->data as $item){
    //     if($item->id === "item_id_here"){
    //         $stripe->subscriptionItems->update(
    //             [
    //                 "quantity"=>1
    //             ],
    //             ['metadata' => ['order_id' => '6735']]
    //         );
    //     }
    // }
    

    // return "DONE";
    


    //return $subscription->items->data[0]->id;
    $stripe->subscriptions->update(
    $subscription->id,
    [
        //'cancel_at_period_end' => false,
        'proration_behavior' => "create_prorations",// 'create_prorations',
        'proration_date' => Carbon::now()->addDays(1)->timestamp,//
        'items' => [
            [
                'id' => $subscription->items->data[0]->id,
                "price"=>"price_id_here",
                'quantity' => 3,
            ],
        ],
    ]
    );
    return $subscription;
});

