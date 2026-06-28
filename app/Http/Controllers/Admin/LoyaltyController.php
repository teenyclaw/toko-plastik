<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function index(Request $request)
    {
        $outlet = current_outlet();
        $settings = $this->loyalty->settings($outlet);
        $search = $request->get('q');
        $members = $this->loyalty->membersForOutlet($outlet->id, $search);

        return view('admin.loyalty.index', compact('outlet', 'settings', 'members', 'search'));
    }

    public function updateSettings(Request $request)
    {
        $outlet = current_outlet();
        $settings = $this->loyalty->settings($outlet);

        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'earn_amount_basis' => 'required|integer|min:1',
            'earn_points' => 'required|integer|min:1',
            'redeem_rp_per_point' => 'required|integer|min:1',
            'min_redeem_points' => 'required|integer|min:1',
            'max_redeem_percent' => 'required|integer|min:1|max:100',
        ]);

        $settings->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'earn_amount_basis' => $data['earn_amount_basis'],
            'earn_points' => $data['earn_points'],
            'redeem_rp_per_point' => $data['redeem_rp_per_point'],
            'min_redeem_points' => $data['min_redeem_points'],
            'max_redeem_percent' => $data['max_redeem_percent'],
        ]);

        return back()->with('success', 'Pengaturan loyalty diperbarui.');
    }

    public function adjustMember(Request $request, Member $member)
    {
        abort_unless($member->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'points' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->loyalty->adjust($member, $data['points'], $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Saldo member diperbarui.');
    }
}
