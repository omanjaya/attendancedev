@extends('layouts.authenticated-unified')

@section('title', 'Employee Management - Test')

@section('page-content')
<div class="container">
    <h1>Employee Management Test</h1>
    
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees ?? [] as $employee)
                <tr>
                    <td>{{ $employee['name'] ?? 'Test Employee' }}</td>
                    <td>
                        <button onclick="viewEmployee({{ $employee['id'] ?? 1 }})">View</button>
                        <button onclick="editEmployee({{ $employee['id'] ?? 1 }})">Edit</button>
                        <button onclick="confirmDelete({{ $employee['id'] ?? 1 }})">Delete</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No employees found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    function viewEmployee(employeeId) {
        console.log('View employee:', employeeId);
        window.location.href = '/employees/' + employeeId;
    }

    function editEmployee(employeeId) {
        console.log('Edit employee:', employeeId);
        window.location.href = '/employees/' + employeeId + '/edit';
    }

    function confirmDelete(employeeId) {
        console.log('Delete employee:', employeeId);
        if (confirm('Are you sure?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/employees/' + employeeId;
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            
            form.appendChild(methodInput);
            form.appendChild(tokenInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush