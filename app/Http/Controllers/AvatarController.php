<?php

namespace App\Http\Controllers;

use App\Models\AvatarItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /**
     * Display avatar customization page
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Ensure user has default items unlocked
        $user->checkAndUnlockItems();

        // Filter equipment based on user's gender
        $allEquipment = AvatarItem::ordered()
            ->where(function($q) use ($user) {
                $q->where('gender', $user->gender)
                  ->orWhere('gender', 'neutral')
                  ->orWhereNull('gender');
            })
            ->get();

        $unlockedIds = $user->avatarItems()->pluck('avatar_item_id')->toArray();
        $equippedIds = $user->equippedItems()->pluck('avatar_item_id')->toArray();
        $nextLevelProgress = app(\App\Actions\CheckLevelUpAction::class)->getProgressToNextLevel($user);

        return view('avatars.index', compact('user', 'allEquipment', 'unlockedIds', 'equippedIds', 'nextLevelProgress'));
    }

    /**
     * Update user's gender
     */
    public function setGender(Request $request)
    {
        $request->validate([
            'gender' => 'required|in:male,female,neutral,hombre,mujer',
        ]);

        $genderValue = $request->gender;
        if ($genderValue === 'hombre') $genderValue = 'male';
        if ($genderValue === 'mujer') $genderValue = 'female';

        /** @var User $user */
        $user = Auth::user();
        $user->update(['gender' => $genderValue]);

        // Unequip items that don't match the new gender
        $incompatibleItems = $user->equippedItems()
            ->whereNotNull('gender')
            ->where('gender', '!=', $genderValue)
            ->where('gender', '!=', 'neutral')
            ->get();

        foreach ($incompatibleItems as $item) {
            $user->unequipItem($item);
        }

        return redirect()->back()->with('success', 'Género actualizado correctamente.');
    }

    /**
     * Update user's avatar equipment
     */
    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:avatar_items,id',
            'action' => 'required|in:equip,unequip',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $item = AvatarItem::findOrFail($request->item_id);

        if ($request->action === 'equip') {
            if (!$user->hasUnlockedItem($item)) {
                if (!$user->unlockItem($item)) {
                    return redirect()->back()->with('error', 'No cumples con los requisitos para desbloquear este objeto.');
                }
            }
            
            $user->equipItem($item);
            $message = 'Objeto equipado exitosamente.';
        } else {
            $user->unequipItem($item);
            $message = 'Objeto desequipado exitosamente.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Upload custom avatar image
     */
    public function upload(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $path = $request->file('avatar')->store('avatars', 'public');

        // Delete old custom avatar if exists
        if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $path]);

        return redirect()->back()->with('success', 'Avatar subido exitosamente.');
    }
}
