<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Registration;
use App\Services\ReferralCodeGenerator;
use App\Services\ImageOptimizer;
use App\Services\RegistrationService;
use Illuminate\Http\Request;

class ParticipantRegistrationController extends Controller
{
    public function __construct(private RegistrationService $registrations, private ImageOptimizer $images) {}

    /** QR / referral link landing page. */
    public function create(Request $request)
    {
        $ref = ReferralCodeGenerator::normalizeInput($request->query('ref'));
        if ($ref === '') {
            return view('competition.enter-code');
        }

        try {
            [$leader, $competition, $round] = $this->registrations->resolveContext($ref);
        } catch (BusinessRuleException $e) {
            return response()->view('competition.register-unavailable', [
                'reason' => $e->reason,
                'message' => $e->getMessage(),
                'code' => $ref,
            ], $e->reason === 'invalid_code' ? 404 : 200);
        }

        return view('competition.register', compact('leader', 'competition', 'round', 'ref'));
    }

    public function store(StoreRegistrationRequest $request)
    {
        // Validated field set; the service re-checks leader, competition, round, timer and duplicates.
        $data = $request->safe()->only(['full_name', 'phone', 'email', 'city', 'team', 'notes']);

        // The payment screenshot is stored privately (never publicly reachable) and
        // removed again if the registration is refused for any reason.
        $proof = null;
        if ($request->hasFile('transfer_screenshot')) {
            try {
                $proof = $this->images->store($request->file('transfer_screenshot'), 'transfers', 1800, 480, 82, private: true)['path'];
            } catch (\RuntimeException) {
                return back()->withInput()->withErrors(['transfer_screenshot' => __('We could not read this image. Please upload a clear screenshot (JPG, PNG or WebP).')]);
            }
            $data['transfer_path'] = $proof;
        }

        try {
            $registration = $this->registrations->submit($request->validated('ref'), $data);
        } catch (BusinessRuleException $e) {
            $this->images->delete($proof, private: true);
            if (in_array($e->reason, ['duplicate'], true)) {
                return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
            }

            return back()->withInput()->with('error', $e->getMessage())->with('error_reason', $e->reason);
        } catch (\Throwable $e) {
            $this->images->delete($proof, private: true);
            throw $e;
        }

        return redirect()->route('registration.result', $registration->public_token)->with('just_submitted', true);
    }

    public function result(string $token)
    {
        $registration = Registration::with('leader:id,name,unique_code', 'round:id,number,name,status')->where('public_token', $token)->firstOrFail();

        return view('competition.result', ['registration' => $registration]);
    }
}
