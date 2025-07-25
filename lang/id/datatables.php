<?php

return [
    'processing' => 'Sedang memproses...',
    'search' => 'Cari:',
    'lengthMenu' => 'Tampilkan _MENU_ entri',
    'info' => 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
    'infoEmpty' => 'Menampilkan 0 sampai 0 dari 0 entri',
    'infoFiltered' => '(disaring dari _MAX_ entri keseluruhan)',
    'infoPostFix' => '',
    'loadingRecords' => 'Sedang memuat...',
    'zeroRecords' => 'Tidak ditemukan data yang sesuai',
    'emptyTable' => 'Tidak ada data yang tersedia pada tabel',
    'paginate' => [
        'first' => 'Pertama',
        'previous' => 'Sebelumnya',
        'next' => 'Selanjutnya',
        'last' => 'Terakhir',
    ],
    'aria' => [
        'sortAscending' => ': aktifkan untuk mengurutkan kolom ke atas',
        'sortDescending' => ': aktifkan untuk mengurutkan kolom ke bawah',
    ],
    'select' => [
        'rows' => [
            '_' => '%d baris dipilih',
            '0' => 'Klik baris untuk memilih',
            '1' => '1 baris dipilih',
        ],
    ],
    'buttons' => [
        'copy' => 'Salin',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'print' => 'Cetak',
        'colvis' => 'Visibilitas Kolom',
        'collection' => 'Koleksi',
        'upload' => 'Pilih file...',
    ],
    'searchBuilder' => [
        'add' => 'Tambah Kondisi',
        'button' => [
            '0' => 'Pembuat Pencarian',
            '_' => 'Pembuat Pencarian (%d)',
        ],
        'clearAll' => 'Bersihkan Semua',
        'condition' => 'Kondisi',
        'conditions' => [
            'date' => [
                'after' => 'Setelah',
                'before' => 'Sebelum',
                'between' => 'Antara',
                'empty' => 'Kosong',
                'equals' => 'Sama dengan',
                'not' => 'Tidak',
                'notBetween' => 'Tidak Antara',
                'notEmpty' => 'Tidak Kosong',
            ],
            'number' => [
                'between' => 'Antara',
                'empty' => 'Kosong',
                'equals' => 'Sama dengan',
                'gt' => 'Lebih besar dari',
                'gte' => 'Lebih besar atau sama dengan',
                'lt' => 'Lebih kecil dari',
                'lte' => 'Lebih kecil atau sama dengan',
                'not' => 'Tidak',
                'notBetween' => 'Tidak Antara',
                'notEmpty' => 'Tidak Kosong',
            ],
            'string' => [
                'contains' => 'Mengandung',
                'empty' => 'Kosong',
                'endsWith' => 'Berakhir dengan',
                'equals' => 'Sama dengan',
                'not' => 'Tidak',
                'notEmpty' => 'Tidak Kosong',
                'startsWith' => 'Dimulai dengan',
            ],
            'array' => [
                'without' => 'Tanpa',
                'notEmpty' => 'Tidak Kosong',
                'not' => 'Tidak',
                'contains' => 'Mengandung',
                'empty' => 'Kosong',
                'equals' => 'Sama dengan',
            ],
        ],
        'data' => 'Data',
        'deleteTitle' => 'Hapus aturan filtering',
        'leftTitle' => 'Kurangi indent kriteria',
        'logicAnd' => 'Dan',
        'logicOr' => 'Atau',
        'rightTitle' => 'Tambah indent kriteria',
        'title' => [
            '0' => 'Pembuat Pencarian',
            '_' => 'Pembuat Pencarian (%d)',
        ],
        'value' => 'Nilai',
    ],
    'searchPanes' => [
        'clearMessage' => 'Bersihkan Semua',
        'collapse' => [
            '0' => 'Panel Pencarian',
            '_' => 'Panel Pencarian (%d)',
        ],
        'count' => '{total}',
        'countFiltered' => '{shown} ({total})',
        'emptyPanes' => 'Tidak ada Panel Pencarian',
        'loadMessage' => 'Memuat Panel Pencarian...',
        'title' => 'Filter Aktif - %d',
        'showMessage' => 'Tampilkan Semua',
        'collapseMessage' => 'Tutup Semua',
    ],
    'autoFill' => [
        'cancel' => 'Batal',
        'fill' => 'Isi semua sel dengan <i>%d</i>',
        'fillHorizontal' => 'Isi sel secara horizontal',
        'fillVertical' => 'Isi sel secara vertikal',
    ],
    'datetime' => [
        'previous' => 'Sebelumnya',
        'next' => 'Selanjutnya',
        'hours' => 'Jam',
        'minutes' => 'Menit',
        'seconds' => 'Detik',
        'unknown' => '-',
        'amPm' => [
            'AM',
            'PM',
        ],
        'months' => [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ],
        'weekdays' => [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
        ],
    ],
    'editor' => [
        'close' => 'Tutup',
        'create' => [
            'button' => 'Baru',
            'title' => 'Buat entri baru',
            'submit' => 'Buat',
        ],
        'edit' => [
            'button' => 'Edit',
            'title' => 'Edit entri',
            'submit' => 'Perbarui',
        ],
        'remove' => [
            'button' => 'Hapus',
            'title' => 'Hapus',
            'submit' => 'Hapus',
            'confirm' => [
                '_' => 'Yakin ingin menghapus %d baris?',
                '1' => 'Yakin ingin menghapus 1 baris?',
            ],
        ],
        'error' => [
            'system' => 'Terjadi kesalahan sistem (<a target="_blank" href="%s" rel="noopener">Informasi lebih lanjut</a>).',
        ],
        'multi' => [
            'title' => 'Beberapa Nilai',
            'info' => 'Item yang dipilih mengandung nilai yang berbeda untuk input ini. Untuk mengedit dan mengatur semua item untuk input ini ke nilai yang sama, klik atau ketuk di sini, jika tidak mereka akan mempertahankan nilai individual mereka.',
            'restore' => 'Batalkan Perubahan',
            'noMulti' => 'Input ini dapat diedit secara individual, tetapi bukan sebagai bagian dari grup.',
        ],
    ],
    'stateRestore' => [
        'creationModal' => [
            'button' => 'Buat',
            'name' => 'Nama:',
            'order' => 'Pengurutan',
            'paging' => 'Paging',
            'search' => 'Pencarian',
            'select' => 'Pilih',
            'columns' => [
                'search' => 'Pencarian Kolom',
                'visible' => 'Visibilitas Kolom',
            ],
            'title' => 'Buat State Baru',
            'toggleLabel' => 'Termasuk:',
        ],
        'emptyError' => 'Nama tidak boleh kosong.',
        'removeConfirm' => 'Yakin ingin menghapus %s?',
        'removeError' => 'Gagal menghapus state.',
        'removeJoiner' => 'dan',
        'removeSubmit' => 'Hapus',
        'removeTitle' => 'Hapus State',
        'renameButton' => 'Ganti Nama',
        'renameLabel' => 'Nama baru untuk %s:',
        'duplicateError' => 'State dengan nama ini sudah ada.',
        'emptyStates' => 'Tidak ada state tersimpan',
        'removeState' => 'Hapus',
        'renameState' => 'Ganti Nama',
    ],
];
