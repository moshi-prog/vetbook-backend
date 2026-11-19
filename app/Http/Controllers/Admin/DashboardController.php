<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branch = $user->branch; // null = Admin (Account Management), non-null = Staff branch

        if ($branch === null) {
            $adminAccounts = User::where('role', 'admin')->orderBy('id')->get();
            $staffCount = $adminAccounts->whereNotNull('branch')->count();
            $totalPetOwners = User::where('role', 'pet_owner')->count();
            $newOwnersThisMonth = User::where('role', 'pet_owner')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
            $recentPetOwners = User::where('role', 'pet_owner')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
            $activeBranchesCount = 2;

            return view('admin.dashboard.index', compact(
                'branch',
                'adminAccounts',
                'staffCount',
                'totalPetOwners',
                'newOwnersThisMonth',
                'recentPetOwners',
                'activeBranchesCount'
            ));
        }

        try {
            \App\Services\AppointmentReminderService::dispatchDueReminders();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard reminder dispatch failed: ' . $e->getMessage());
        }

        // Base queries with optional branch filter
        $appointmentsQuery = Appointment::query();
        $petsQuery = Pet::query();
        $usersQuery = User::where('role', 'pet_owner');

        if ($branch) {
            $appointmentsQuery->where('branch', $branch);
            // Note: pets and users don't have a branch column yet, so we 
            // filter pets via their owner's appointments OR show all pets 
            // belonging to users who have made an appointment at this branch
            // For now: show all pets (branch filtering for pets will come later)
        }

        // Consolidate status counts into 1 single query instead of 4 sequential roundtrips
        $apptCounts = (clone $appointmentsQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalAppointments = (int) $apptCounts->sum();
        $pendingAppointments = (int) $apptCounts->get('pending', 0);
        $approvedAppointments = (int) $apptCounts->get('approved', 0);
        $completedAppointments = (int) $apptCounts->get('completed', 0);
        $totalPets = $petsQuery->count();
        $totalPetOwners = $usersQuery->count();

        // Recent appointments (with pet and owner)
        $recentAppointments = (clone $appointmentsQuery)
            ->with(['pet:id,user_id,name,species,breed,age,age_months,gender,weight,health_status', 'user:id,name,email,phone_number,role'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Appointments by branch (only meaningful for super admin)
        $appointmentsByBranch = Appointment::select('branch')
            ->selectRaw('count(*) as total')
            ->groupBy('branch')
            ->get();

        return view('admin.dashboard.index', compact(
            'totalAppointments',
            'pendingAppointments',
            'approvedAppointments',
            'completedAppointments',
            'totalPets',
            'totalPetOwners',
            'recentAppointments',
            'appointmentsByBranch',
            'branch'
        ));
    }
}
