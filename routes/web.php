<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        $stripe = new \Stripe\StripeClient(config("payment.STRIPE_SECRET_KEY")); //secret key

        //step 01
        //Create Customer
        $customer = $stripe->customers->create([
            'name'=>"Developer Rijan",
            "phone"=>"+8801933388899",
            'description' =>"Test Customer",
            'email' => "developerrijan@gmail.com"
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


        return [
            "setup_intent"=>$setup_intent,//id,
            "setup_intent_client_secret"=>$setup_intent_client_secret,
            "redirect_status"=>$redirect_status,
            "intent"=>$intent
        ];

    })->name("setup.intents.verify");
});

Route::get("test-execute-payment-off-session", function(){
    $stripe = new \Stripe\StripeClient(config("payment.STRIPE_SECRET_KEY"));

    try {
        //do one time charge
        $paymentIntent = $stripe->paymentIntents->create([
            "amount"=>30 * 100,//convert to cents
            "currency"=>"gbp",
            "payment_method_types"=>["card"],//optional
            "description"=>"One Time Charge - Admission Fee",
            'customer' => config("payment.STRIPE_TEST_CUSTOMER_ID"),
            "payment_method"=>config("payment.STRIPE_TEST_PAYMENT_METHOD_ID"),
            'off_session' => true,
            'confirm' => true
        ]);

        if(!isset($paymentIntent['status'])){
            return "Creating payment intent failed";
        }
        //verify the intent

        //do subscription
        $subscription = $stripe->subscriptions->create([
            'customer' => config("payment.STRIPE_TEST_CUSTOMER_ID"),
            'items' => [
              ['price' => config("payment.STRIPE_SUBSCRIPTION_PRICE_ID")],
            ],
            "collection_method"=>"charge_automatically",
            "default_payment_method"=>config("payment.STRIPE_TEST_PAYMENT_METHOD_ID"),//STRIPE SUBSCRIPTION PRODUCT PRICE ID
            "currency"=>"gbp",
            "description"=>"Testing subscription server side",
            "payment_settings"=>[
                "save_default_payment_method"=>"on_subscription"
            ],
            'off_session' => true
            //'confirm' => true
            // "expand" => ["latest_invoice.payment_intent"],
            // "application_fee_percent"=>$subscriptionProcessingFee,
            // "transfer_data" => [
            //   "destination" => $connected_acc_id
            // ]
        ]);

        //verify the subscription id

        return [
            "succes"=>true,
            "paymentIntent"=>$paymentIntent,
            "subscription"=>$subscription
        ];

    } catch (\Stripe\Exception\CardException $e) {
        // Error code will be authentication_required if authentication is needed
        // echo 'Error code is:' . $e->getError()->code;
        $payment_intent_id = $e->getError()->payment_intent->id;
        $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
        return [
            "errorCode"=>$e->getError()->code,
            "payment_intent_id"=>$payment_intent_id,
            "payment_intent"=>$payment_intent
        ];
    } catch(Exception $e){
        return "An exception occured please try again later | ({$e->getMessage()})";
    }


    
});
