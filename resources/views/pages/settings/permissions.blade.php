@extends('layouts.authenticated-unified')

@section('title', 'Permission Management')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Manajemen Izin"
        subtitle="Sistem - Kelola peran dan izin"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pengaturan'],
            ['label' => 'Manajemen Izin']
        ]">
        <x-slot name="actions">
            <x-ui.button onclick="openCreateRoleModal()">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Peran Baru
            </x-ui.button>
        </x-slot>

        <div class="grid grid-cols-1 gap-6">
            <!-- Roles Table -->
            <x-ui.card title="Peran Sistem">
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="rolesTable" class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nama Peran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Izin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Permissions Matrix -->
            <x-ui.card title="Matriks Izin">
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider" style="width: 200px;">Kategori Izin</th>
                                    @foreach($roles as $role)
                                        <th class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ ucfirst($role->name) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-background divide-y divide-border">
                                @foreach($permissions as $category => $categoryPermissions)
                                    <tr>
                                        <td colspan="{{ count($roles) + 1 }}" class="px-6 py-3 bg-muted/50">
                                            <span class="font-medium text-foreground">Izin {{ ucfirst($category) }}</span>
                                        </td>
                                    </tr>
                                    @foreach($categoryPermissions as $permission)
                                        <tr class="hover:bg-muted/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ str_replace('_', ' ', ucfirst($permission->name)) }}</td>
                                            @foreach($roles as $role)
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" 
                                                               class="h-4 w-4 text-primary focus:ring-primary border-input rounded permission-toggle transition-colors" 
                                                               data-role-id="{{ $role->id }}"
                                                               data-permission="{{ $permission->name }}"
                                                               {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                                               {{ $role->name === 'superadmin' ? 'disabled' : '' }}>
                                                    </label>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </x-layouts.base-page>
</div>

<!-- Create Role Modal -->
<x-ui.modal id="createRoleModal" title="Create New Role">
    <form id="createRoleForm">
        <div class="space-y-6">
            <div>
                <x-ui.label for="role_name">Role Name</x-ui.label>
                <x-ui.input 
                    type="text" 
                    id="role_name"
                    name="name" 
                    placeholder="Enter role name" 
                    required />
            </div>
            
            <div>
                <x-ui.label>Select Permissions</x-ui.label>
                <div class="divide-y divide-border rounded-lg border">
                    @foreach($permissions as $category => $categoryPermissions)
                        <div class="p-4">
                            <div class="font-medium text-foreground mb-3">{{ ucfirst($category) }} Permissions</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($categoryPermissions as $permission)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" 
                                               class="h-4 w-4 text-primary focus:ring-primary border-input rounded transition-colors" 
                                               name="permissions[]" 
                                               value="{{ $permission->name }}">
                                        <span class="text-sm text-foreground select-none">
                                            {{ str_replace('_', ' ', ucfirst($permission->name)) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <x-ui.button type="button" variant="outline" onclick="closeCreateRoleModal()">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit">
                Create Role
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

@endsection

@push('scripts')
<script>
// Modal functions
function openCreateRoleModal() {
    openModal('createRoleModal');
}

function closeCreateRoleModal() {
    closeModal('createRoleModal');
}

$(document).ready(function() {
    // Initialize DataTable
    const table = $('#rolesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("system.permissions.roles.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'permissions_count', name: 'permissions_count', orderable: false },
            { data: 'users_count', name: 'users_count', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });
    
    // Permission toggle handler
    $('.permission-toggle').on('change', function() {
        const checkbox = $(this);
        const roleId = checkbox.data('role-id');
        const permission = checkbox.data('permission');
        const isChecked = checkbox.is(':checked');
        
        // Get all permissions for this role
        const rolePermissions = [];
        $(`.permission-toggle[data-role-id="${roleId}"]`).each(function() {
            if ($(this).is(':checked')) {
                rolePermissions.push($(this).data('permission'));
            }
        });
        
        // Update permissions
        $.ajax({
            url: `/system/permissions/roles/${roleId}/permissions`,
            method: 'POST',
            data: {
                permissions: rolePermissions,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success(response.message);
                table.ajax.reload();
            },
            error: function(xhr) {
                checkbox.prop('checked', !isChecked);
                toastr.error(xhr.responseJSON?.message || 'An error occurred');
            }
        });
    });
    
    // Create role form handler
    $('#createRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route("system.permissions.roles.create") }}',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(response) {
                closeCreateRoleModal();
                toastr.success(response.message);
                location.reload(); // Reload to update permissions matrix
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred');
            }
        });
    });
    
    // Delete role handler
    $(document).on('click', '.delete-role', function() {
        const roleId = $(this).data('role-id');
        
        if (confirm('Are you sure you want to delete this role?')) {
            $.ajax({
                url: `/system/permissions/roles/${roleId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            });
        }
    });
});
</script>
@endpush