<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://js.stripe.com/v3/" defer></script>
  </head>
  <body>
    <div class="container text-center mt-3">
        <h1 class="text-center">Hello, Lets donate recurring or one time</h1>
        <button onclick="checkoutProcess(this)" class="btn btn-primary btn-sm">Checkout</button>
    </div>

    <div class="container d-flex justify-content-center mt-5">
        <form id="payment-form" style="max-width:600;display:none">
            <div class="mb-2">
                <label>Enter Payment Details</label>
            </div>

            <div id="payment-element">
                <!-- Elements will create form elements here -->
            </div>
            <button id="submit" class="btn btn-success btn-sm">Submit</button>
            <div id="error-message">
                <!-- Display error message to your customers here -->
            </div>
        </form>
    </div>

    {{-- //scripts --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script>
        //init
        //car elements
        const form = document.getElementById('payment-form');
        let stripe;
        let elements;

        //setup intents -- step 1
        function checkoutProcess(el){
            $.ajax({
                url:"{{route('payments.setup.intents')}}",
                dataType:"JSON",
                method:"POST",
                data:{
                    "_token":"{{csrf_token()}}"
                },
                beforeSend:function(){
                    $(el).attr("disabled", true)
                },
                success:function(res){
                    console.log(res)
                    setupPaymentDetailsCollector(res)
                },
                error:function(e){
                    $(el).removeAttr("disabled")
                    console.log(e)
                },
                complete:function(){
                    $(el).removeAttr("disabled")
                }
            })
        }


        //card elements rendering
        //step -- 2
        function setupPaymentDetailsCollector(res){
            stripe = Stripe("{{$stripe_publishable_key}}");
            
            const options = {
                clientSecret: res.client_secret,
                // Fully customizable with appearance API.
                //appearance: {/*...*/},
            };

            // Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in step 3
            elements = stripe.elements(options);

            // Create and mount the Payment Element
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');
            $(form).show()
        }


        //submit payment details
        //step -- 3
        $(form).on("submit", async function(e){
            e.preventDefault();

            const {error} = await stripe.confirmSetup({
                //`Elements` instance that was used to create the Payment Element
                elements,
                confirmParams: {
                    return_url: "{{route('payments.setup.intents.verify')}}",
                }
            });

            if (error) {
                // This point will only be reached if there is an immediate error when
                // confirming the payment. Show error to your customer (for example, payment
                // details incomplete)
                const messageContainer = document.querySelector('#error-message');
                messageContainer.textContent = error.message;
                console.log("confirmError", error)
            } else {
                // Your customer will be redirected to your `return_url`. For some payment
                // methods like iDEAL, your customer will be redirected to an intermediate
                // site first to authorize the payment, then redirected to the `return_url`.
                console.log("Success confirmed")
            }
        })
    </script>
  </body>
</html>