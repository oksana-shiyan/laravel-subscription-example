<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): Response
    {
        try {
            $stripe->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature', '')
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid payload', 400);
        }

        return response('Webhook handled', 200);
    }
}
