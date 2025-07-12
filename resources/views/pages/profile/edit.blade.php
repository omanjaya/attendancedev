@extends('layouts.authenticated')

@section('title', 'Profile Settings')

@section('page-content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Page Header -->
        <x-layout.page-header
            title="Profile Settings"
            subtitle="Manage your account information and security settings" />

        <!-- Profile Information Card -->
        <x-ui.card title="Profile Information" subtitle="Update your account's profile information and email address.">
            <div class="max-w-xl">
                @include('pages.profile.update_profile_information_form')
            </div>
        </x-ui.card>

        <!-- Update Password Card -->
        <x-ui.card title="Update Password" subtitle="Ensure your account is using a long, random password to stay secure.">
            <div class="max-w-xl">
                @include('pages.profile.update_password_form')
            </div>
        </x-ui.card>

        <!-- Delete Account Card -->
        <x-ui.card title="Delete Account" subtitle="Once your account is deleted, all of its resources and data will be permanently deleted.">
            <div class="max-w-xl">
                @include('pages.profile.delete_user_form')
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
