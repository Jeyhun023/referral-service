<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\CancelReferralRequest;
use App\Http\Requests\Referral\ListReferralsRequest;
use App\Http\Requests\Referral\StoreReferralRequest;
use App\Http\Resources\ReferralResource;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function store(StoreReferralRequest $request): JsonResponse
    {
        $referral = $this->referralService->create($request->validated());

        return (new ReferralResource($referral))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id): ReferralResource
    {
        $referral = $this->referralService->find($id);

        return new ReferralResource($referral);
    }

    public function index(ListReferralsRequest $request): AnonymousResourceCollection
    {
        $referrals = $this->referralService->list($request->validated());

        return ReferralResource::collection($referrals);
    }

    public function cancel(CancelReferralRequest $request, string $id): ReferralResource
    {
        $referral = $this->referralService->find($id);
        $referral = $this->referralService->cancel($referral, $request->validated('reason'));

        return new ReferralResource($referral);
    }
}
