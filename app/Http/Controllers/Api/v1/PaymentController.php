<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Request\Api\v1\CreateRequest;
use App\Http\Requests\Request\Api\v1\IndexRequest;
use App\Http\Resources\PaymentCollection;
use App\Http\Resources\PaymentSummaryResource;
use App\Jobs\Transaction\ProcessPaymentJob;
use App\Models\Transaction;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request)
    {
        $user = User::first();

        return new PaymentCollection(Transaction::getPaymentsByUser($user, $request->validated()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request)
    {
        $user = User::factory()->create();
        abort_if(empty($user), Response::HTTP_NOT_FOUND, __('User not found.'));

        ProcessPaymentJob::dispatch(
            $user->transactions()->create($request->validated())
        );

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function summary(): PaymentSummaryResource
    {
        return new PaymentSummaryResource(Transaction::getPaymentSummary());
    }
}
