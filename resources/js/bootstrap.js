import axios from 'axios';
import jQuery from 'jquery';

// Make jQuery available globally
window.$ = window.jQuery = jQuery;

// Import DataTables
import 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';

// Configure Axios for API requests
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set CSRF token for all requests
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}