<?php
namespace App\Helpers;

use Exception;
use Carbon\Carbon;

class Payment{
    public static function chargePayment($intent, $stripe)
    {
        try{
            //do subscription and otheres here.....
                //do one time charge
                $paymentIntent = $stripe->paymentIntents->create([
                    "amount"=>54 * 100,//convert to cents 10 + 27 + 13 == 50
                    "currency"=>"gbp",
                    "payment_method_types"=>["card"],//optional
                    "description"=>"One Time Charge - Admission Fee",
                    'customer' => $intent["customer"],
                    "payment_method"=>$intent["payment_method"],
                    'off_session' => true,
                    'confirm' => true
                ]);
    
                if(!isset($paymentIntent['status'])){
                    return [
                        "msg"=>"Creating payment intent failed (admision fee one time)",
                        "paymentIntent"=>$paymentIntent
                    ];
                }
    
                //do subscription here...
                //do subscription
                $subscription = $stripe->subscriptions->create([
                    'customer' => $intent["customer"],
                    'items' => [
                        ['price' => config("payment.STRIPE_SUBSCRIPTION_PRICE_ID")],
                    ],
                    "collection_method"=>"charge_automatically",
                    "default_payment_method"=>$intent["payment_method"],//STRIPE SUBSCRIPTION PRODUCT PRICE ID
                    "currency"=>"gbp",
                    "description"=>"Testing subscription server side",
                    "payment_settings"=>[
                        "save_default_payment_method"=>"on_subscription"
                    ],
                    "billing_cycle_anchor"=>Carbon::now()->addMonths(1)->timestamp,
                    'off_session' => true
                    //'confirm' => true
                    // "expand" => ["latest_invoice.payment_intent"],
                    // "application_fee_percent"=>$subscriptionProcessingFee,
                    // "transfer_data" => [
                    //   "destination" => $connected_acc_id
                    // ]
                ]);
    
                //do subscription
                $subscription = $stripe->subscriptions->create([
                    'customer' => $intent["customer"],
                    'items' => [
                        ['price' => config("payment.STRIPE_SUBSCRIPTION_PRICE_ID")],
                    ],
                    "collection_method"=>"charge_automatically",
                    "default_payment_method"=>$intent["payment_method"],//STRIPE SUBSCRIPTION PRODUCT PRICE ID
                    "currency"=>"gbp",
                    "description"=>"Twin subscription server side",
                    "payment_settings"=>[
                        "save_default_payment_method"=>"on_subscription"
                    ],
                    "billing_cycle_anchor"=>Carbon::now()->addMonths(1)->timestamp,
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
        }
        catch (\Stripe\Exception\CardException $e) {
            // Error code will be authentication_required if authentication is needed
            // echo 'Error code is:' . $e->getError()->code;
            $payment_intent_id = $e->getError()->payment_intent->id;
            //$payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $payment_intent = $stripe->paymentIntents->retrieve($payment_intent_id);
            return [
                "errorCode"=>$e->getError()->code,
                "payment_intent_id"=>$payment_intent_id,
                "payment_intent"=>$payment_intent
            ];
        } 
        catch(Exception $e){
            return [
                "msg"=>$e->getMessage(),
                "line"=>$e->getLine(),
                "file"=>$e->getFile()
            ];
        }
    }
}