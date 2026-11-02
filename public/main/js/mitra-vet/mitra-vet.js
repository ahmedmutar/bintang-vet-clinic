$(document).ready(function () {
    let currentPageStok = 1;
    let currentPageOrder = 1;
    let selectedItemId = null;
    let selectedOrderId = null;
    let modalActionState = ""; // 'add-item', 'edit-item', 'add-stock', 'delete-item', 'delete-order'
    let orderItemsList = []; // items in current draft order
    let availableItemsDropdown = []; // items loaded for dropdown in order modal
    let availableCustomersList = []; // customers list for order modal dropdown

    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;
    const startYear = 2021; // Tahun awal operasional data

    // Inisialisasi dropdown filter tahun secara dinamis (fleksibel)
    function initYearFilters() {
        let chartYearOptions = '';
        let orderYearOptions = '<option value="">-- Semua Tahun --</option>';
        let strukYearOptions = '';

        // Loop dari tahun berjalan (+1 untuk antisipasi) mundur sampai tahun awal
        for (let y = currentYear + 1; y >= startYear; y--) {
            chartYearOptions += `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`;
            orderYearOptions += `<option value="${y}">${y}</option>`;
            strukYearOptions += `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`;
        }

        $('#filter-chart-year').html(chartYearOptions).val(currentYear);
        $('#filter-order-year').html(orderYearOptions); // default semua tahun agar riwayat selalu terlihat
        $('#struk-year').html(strukYearOptions).val(currentYear);
        $('#filter-chart-month').val(currentMonth);
    }
    initYearFilters();

    // Helper format Rupiah
    function formatRupiah(val) {
        if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
        return 'Rp ' + parseFloat(val).toLocaleString('id-ID');
    }

    // Validasi Akses Role:
    // Admin: Bisa Tambah Barang, Order Barang (mengurangi stok), & Hapus Order
    // Dokter: Bisa Tambah Barang & Lihat Order (tidak bisa buat/hapus order)
    // Resepsionis: Hanya Bisa Lihat Order (tidak bisa buat order/tambah barang)
    // Selain itu: Ditolak / Redirect
    const userRole = (typeof role !== 'undefined' ? role.toLowerCase() : '');

    if (userRole !== 'admin') {
        // Non-admin tidak bisa membuat orderan (mengurangi stok barang)
        $('#btnBuatOrder').hide();
    }

    if (userRole === 'resepsionis') {
        // Resepsionis tidak dapat menambah barang maupun menambah stok
        $('#btn-tambah-barang-wrap').hide();
        $('a[href="#tab-tambah-barang"]').parent().hide();
        $('#tab-stok .section-left-box-title').hide();

        // Otomatis aktifkan tab Orderan sebagai default untuk resepsionis
        $('a[href="#tab-orderan"]').tab('show');
    }

    // =========================================================================
    // 1. STOK BARANG (TAB 1)
    // =========================================================================

    function loadStokBarang() {
        const keyword = $('#search-stok').val();
        $('#list-stok-mitra-vet').html('<tr class="text-center"><td colspan="9">Memuat data stok...</td></tr>');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                page: currentPageStok,
                keyword: keyword
            },
            success: function (resp) {
                let rows = '';
                const data = resp.data || [];

                $('#total-jenis-barang').text(data.length > 0 ? (resp.total_paging > 1 ? `${data.length}+` : data.length) : '0');
                $('#total-nilai-aset').text(formatRupiah(resp.total_nilai_aset));
                $('#tfoot-total-aset').text(formatRupiah(resp.total_nilai_aset));
                if (resp.total_profit_bulan !== undefined) {
                    $('#total-profit-bulan').text(formatRupiah(resp.total_profit_bulan));
                }
                if (resp.total_profit_keseluruhan !== undefined) {
                    $('#total-profit-keseluruhan').text(formatRupiah(resp.total_profit_keseluruhan));
                }

                if (data.length === 0) {
                    rows = '<tr class="text-center"><td colspan="9">Belum ada data barang.</td></tr>';
                } else {
                    data.forEach(function (item, index) {
                        const no = (currentPageStok - 1) * 50 + (index + 1);
                        const nilaiAset = parseFloat(item.harga_satuan) * parseInt(item.stok);
                        const stokBadge = item.stok <= 5
                            ? `<span class="label label-danger">${item.stok}</span>`
                            : `<span class="label label-success">${item.stok}</span>`;

                        let actionBtns = '';

                        // Hanya Admin dan Dokter yang dapat Menambah Stok dan Mengedit Barang
                        if (userRole === 'admin' || userRole === 'dokter') {
                            actionBtns += `
                                <button class="btn btn-xs btn-success btn-quick-stock" data-id="${item.id}" data-nama="${item.nama_barang}" data-stok="${item.stok}" title="Tambah Stok">
                                    <i class="fa fa-plus"></i> Stok
                                </button>
                                <button class="btn btn-xs btn-warning btn-edit-item" data-id="${item.id}" data-nama="${item.nama_barang}" data-satuan="${item.satuan}" data-modal="${item.harga_modal}" data-jual="${item.harga_jual}" title="Edit Barang">
                                    <i class="fa fa-pencil"></i>
                                </button>
                            `;
                        }

                        // Hanya Admin yang dapat Menghapus Barang
                        if (userRole === 'admin') {
                            actionBtns += `
                                <button class="btn btn-xs btn-danger btn-delete-item" data-id="${item.id}" data-nama="${item.nama_barang}" title="Hapus Barang">
                                    <i class="fa fa-trash"></i>
                                </button>
                            `;
                        }

                        if (!actionBtns) {
                            actionBtns = '<span class="text-muted">-</span>';
                        }

                        rows += `
                            <tr>
                                <td>${no}</td>
                                <td><strong>${item.nama_barang}</strong></td>
                                <td>${item.satuan}</td>
                                <td>${stokBadge}</td>
                                <td>${formatRupiah(item.harga_modal)}</td>
                                <td>${formatRupiah(item.harga_jual)}</td>
                                <td><span class="text-success" style="font-weight:bold;">${formatRupiah(item.profit)}</span></td>
                                <td><strong>${formatRupiah(nilaiAset)}</strong></td>
                                <td>${actionBtns}</td>
                            </tr>
                        `;
                    });
                }

                $('#list-stok-mitra-vet').html(rows);
                renderPagination('#pagination-stok', resp.total_paging, currentPageStok, function (p) {
                    currentPageStok = p;
                    loadStokBarang();
                });
            },
            error: function (err) {
                if (err.status === 401) {
                    localStorage.removeItem('vet-clinic');
                    location.href = $('.baseUrl').val() + '/masuk';
                }
                $('#list-stok-mitra-vet').html('<tr class="text-center text-danger"><td colspan="9">Gagal memuat data.</td></tr>');
            }
        });
    }

    // Search stok
    $('#search-stok').on('keyup', function (e) {
        if (e.which === 13) {
            currentPageStok = 1;
            loadStokBarang();
        }
    });
    $('#tab-stok .fa-search').on('click', function () {
        currentPageStok = 1;
        loadStokBarang();
    });

    // Perhitungan otomatis profit modal
    function hitungModalProfit() {
        const modal = parseFloat($('#input-harga-modal').val()) || 0;
        const jual = parseFloat($('#input-harga-jual').val()) || 0;
        const profit = jual - modal;
        if (jual > 0 || modal > 0) {
            $('#input-profit').val(formatRupiah(profit));
            if (jual <= modal && jual > 0) {
                $('#err-harga-jual').text('Harga Jual harus lebih besar dari Harga Modal!');
            } else {
                $('#err-harga-jual').empty();
            }
        } else {
            $('#input-profit').val('Rp 0');
        }
    }
    $('#input-harga-modal, #input-harga-jual').on('input keyup change', hitungModalProfit);

    // Tombol Edit Item (Modal)
    $(document).on('click', '.btn-edit-item', function () {
        modalActionState = 'edit-item';
        selectedItemId = $(this).data('id');
        $('#modal-tambah-barang-title').text('Ubah Data Barang');
        $('#input-nama-barang').val($(this).data('nama'));
        $('#input-satuan').val($(this).data('satuan'));
        $('#input-harga-modal').val($(this).data('modal') || 0);
        $('#input-harga-jual').val($(this).data('jual') || 0);
        hitungModalProfit();
        $('#wrap-stok-awal').hide();
        $('.validate-error').empty();
        $('#modal-tambah-barang').modal('show');
    });

    // Simpan Edit Barang dari Modal
    $('#btnSimpanBarang').on('click', function () {
        $('.validate-error').empty();
        const nama = $('#input-nama-barang').val().trim();
        const satuan = $('#input-satuan').val().trim();
        const modal = $('#input-harga-modal').val().trim();
        const jual = $('#input-harga-jual').val().trim();

        let hasError = false;
        if (!nama) { $('#err-nama-barang').text('Nama barang wajib diisi'); hasError = true; }
        if (!satuan) { $('#err-satuan').text('Satuan wajib diisi'); hasError = true; }
        if (!modal || isNaN(modal) || parseFloat(modal) < 0) { $('#err-harga-modal').text('Harga modal harus angka valid (>= 0)'); hasError = true; }
        if (!jual || isNaN(jual) || parseFloat(jual) < 0) { $('#err-harga-jual').text('Harga jual harus angka valid (>= 0)'); hasError = true; }
        if (!hasError && parseFloat(jual) <= parseFloat(modal)) {
            $('#err-harga-jual').text('Harga Jual harus lebih besar dari Harga Modal!');
            hasError = true;
        }
        if (hasError) return;

        $('#btnSimpanBarang').prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items',
            type: 'PUT',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                id: selectedItemId,
                nama_barang: nama,
                satuan: satuan,
                harga_modal: modal,
                harga_jual: jual
            },
            success: function (resp) {
                $('#modal-tambah-barang').modal('hide');
                showNotice('Sukses', resp.message || 'Data barang berhasil diubah.');
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                handleAjaxError(err, '#err-global-barang');
            },
            complete: function () {
                $('#btnSimpanBarang').prop('disabled', false).text('Simpan');
            }
        });
    });

    // Tombol Tambah Stok Cepat
    $(document).on('click', '.btn-quick-stock', function () {
        selectedItemId = $(this).data('id');
        $('#stok-nama-barang-display').val($(this).data('nama'));
        $('#stok-current').val($(this).data('stok'));
        $('#stok-tambah-qty').val('');
        $('#stok-tambah-keterangan').val('');
        $('.validate-error').empty();
        $('#modal-tambah-stok').modal('show');
    });

    // Submit Tambah Stok
    $('#btnSimpanTambahStok').on('click', function () {
        $('.validate-error').empty();
        const qty = $('#stok-tambah-qty').val();
        const ket = $('#stok-tambah-keterangan').val();

        if (!qty || parseInt(qty) <= 0) {
            $('#err-stok-tambah-qty').text('Jumlah stok harus angka bulat lebih dari 0');
            return;
        }

        $('#btnSimpanTambahStok').prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/tambah-stok',
            type: 'POST',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                item_id: selectedItemId,
                quantity: qty,
                keterangan: ket
            },
            success: function (resp) {
                $('#modal-tambah-stok').modal('hide');
                showNotice('Sukses', resp.message || 'Stok berhasil ditambahkan.');
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                handleAjaxError(err, '#err-global-stok');
            },
            complete: function () {
                $('#btnSimpanTambahStok').prop('disabled', false).text('Tambah Stok');
            }
        });
    });

    // Tombol Hapus Item
    $(document).on('click', '.btn-delete-item', function () {
        selectedItemId = $(this).data('id');
        const nama = $(this).data('nama');
        modalActionState = 'delete-item';
        $('#modal-confirmation .box-body').html(`Apakah Anda yakin ingin menghapus barang <strong>"${nama}"</strong>?`);
        $('#modal-confirmation').modal('show');
    });

    // Download Log Penambahan Stok ke Excel
    $('#btnExportStockLog').on('click', function () {
        const month = $('#filter-chart-month').val();
        const year = $('#filter-chart-year').val();
        const keyword = $('#search-stok').val();

        let filename = 'Log Penambahan Stok Mitra Vet.xlsx';

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/stock-histories/export-excel',
            headers: { Authorization: `Bearer ${token}` },
            type: 'GET',
            xhrFields: { responseType: 'blob' },
            data: { month: month, year: year, keyword: keyword },
            beforeSend: function () {
                if ($('#loading-screen').length) $('#loading-screen').show();
            },
            success: function (data, status, xhr) {
                let disposition = xhr.getResponseHeader('content-disposition');
                if (disposition) {
                    let matches = /"([^"]*)"/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1];
                }
                let blob = new Blob([data], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                let downloadUrl = URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                }, 100);
            },
            complete: function () {
                if ($('#loading-screen').length) $('#loading-screen').hide();
            },
            error: function (err) {
                if (err.status === 401) {
                    localStorage.removeItem('vet-clinic');
                    location.href = $('.baseUrl').val() + '/masuk';
                } else if (err.status === 403) {
                    showNotice('Akses Ditolak', 'Hanya Admin dan Dokter yang dapat mendownload log stok.');
                } else {
                    showNotice('Error', 'Gagal mendownload log penambahan stok.');
                }
            }
        });
    });

    // =========================================================================
    // 2. TAB TAMBAH BARANG (TAB 2)
    // =========================================================================

    // Perhitungan otomatis profit di tab tambah barang
    function hitungTabProfit() {
        const modal = parseFloat($('#tab-harga-modal').val()) || 0;
        const jual = parseFloat($('#tab-harga-jual').val()) || 0;
        const profit = jual - modal;
        if (jual > 0 || modal > 0) {
            $('#tab-profit').val(formatRupiah(profit));
            if (jual <= modal && jual > 0) {
                $('#err-tab-jual').text('Harga Jual harus lebih besar dari Harga Modal!');
            } else {
                $('#err-tab-jual').empty();
            }
        } else {
            $('#tab-profit').val('Rp 0');
        }
    }
    $('#tab-harga-modal, #tab-harga-jual').on('input keyup change', hitungTabProfit);

    $('#btnResetTabBarang, button[type="reset"]').on('click', function () {
        setTimeout(function () {
            $('#tab-profit').val('Rp 0');
            $('#err-tab-nama, #err-tab-satuan, #err-tab-modal, #err-tab-jual, #err-tab-stok, #err-tab-global').empty();
        }, 50);
    });

    // A. Simpan Barang Manual dari Tab
    $('#btnSimpanTabBarang').on('click', function () {
        $('#err-tab-nama, #err-tab-satuan, #err-tab-modal, #err-tab-jual, #err-tab-stok, #err-tab-global').empty();
        const nama = $('#tab-nama-barang').val().trim();
        const satuan = $('#tab-satuan').val().trim();
        const modal = $('#tab-harga-modal').val().trim();
        const jual = $('#tab-harga-jual').val().trim();
        const stokAwal = $('#tab-stok-awal').val().trim();

        let hasError = false;
        if (!nama) { $('#err-tab-nama').text('Nama barang wajib diisi'); hasError = true; }
        if (!satuan) { $('#err-tab-satuan').text('Satuan wajib diisi'); hasError = true; }
        if (!modal || isNaN(modal) || parseFloat(modal) < 0) { $('#err-tab-modal').text('Harga modal harus angka valid (>= 0)'); hasError = true; }
        if (!jual || isNaN(jual) || parseFloat(jual) < 0) { $('#err-tab-jual').text('Harga jual harus angka valid (>= 0)'); hasError = true; }
        if (!hasError && parseFloat(jual) <= parseFloat(modal)) {
            $('#err-tab-jual').text('Harga Jual harus lebih besar dari Harga Modal!');
            hasError = true;
        }
        if (stokAwal === '' || isNaN(stokAwal) || parseInt(stokAwal) < 0) {
            $('#err-tab-stok').text('Stok awal wajib diisi (>= 0)');
            hasError = true;
        }
        if (hasError) return;

        $('#btnSimpanTabBarang').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items',
            type: 'POST',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                nama_barang: nama,
                satuan: satuan,
                harga_modal: modal,
                harga_jual: jual,
                stok: stokAwal
            },
            success: function (resp) {
                $('#form-tab-tambah-barang')[0].reset();
                $('#tab-profit').val('Rp 0');
                showNotice('Sukses', resp.message || 'Barang baru berhasil ditambahkan.');
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                handleAjaxError(err, '#err-tab-global');
            },
            complete: function () {
                $('#btnSimpanTabBarang').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Barang');
            }
        });
    });

    // B. Download Template Excel
    $(document).on('click', '.btn-download-template-act', function () {
        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items/download-template',
            headers: { Authorization: `Bearer ${token}` },
            type: 'GET',
            xhrFields: { responseType: 'blob' },
            beforeSend: function () {
                if ($('#loading-screen').length) $('#loading-screen').show();
            },
            success: function (data, status, xhr) {
                let filename = 'Template Produk Mitra Vet.xlsx';
                let disposition = xhr.getResponseHeader('content-disposition');
                if (disposition) {
                    let matches = /"([^"]*)"/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1];
                }
                let blob = new Blob([data], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                let downloadUrl = URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                }, 100);
            },
            complete: function () {
                if ($('#loading-screen').length) $('#loading-screen').hide();
            },
            error: function (err) {
                if (err.status === 401) {
                    localStorage.removeItem('vet-clinic');
                    location.href = $('.baseUrl').val() + '/masuk';
                } else {
                    showNotice('Error', 'Gagal mendownload template Excel.');
                }
            }
        });
    });

    // C. Upload & Proses Excel dari Tab
    $('#btnTabProsesUpload').on('click', function () {
        $('#err-tab-upload-file').empty();
        $('#tab-upload-result').hide().empty();

        const fileInput = document.getElementById('tab-file-excel');
        if (!fileInput.files.length) {
            $('#err-tab-upload-file').text('Pilih file Excel terlebih dahulu (.xlsx / .xls)');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        $('#tab-upload-progress').show();
        $('#btnTabProsesUpload').prop('disabled', true);

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items/upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { Authorization: `Bearer ${token}` },
            success: function (resp) {
                $('#tab-upload-progress').hide();
                $('#btnTabProsesUpload').prop('disabled', false);
                $('#tab-file-excel').val('');
                let successHtml = `
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <strong>${resp.message || 'Upload berhasil diproses!'}</strong>
                `;
                if (resp.updated_items && resp.updated_items.length > 0) {
                    successHtml += `<div style="margin-top:6px; font-size:12px;"><strong>Barang yang bertambah stoknya:</strong> ${resp.updated_items.join(', ')}</div>`;
                }
                if (resp.inserted_items && resp.inserted_items.length > 0) {
                    successHtml += `<div style="margin-top:4px; font-size:12px;"><strong>Barang baru ditambahkan:</strong> ${resp.inserted_items.join(', ')}</div>`;
                }
                successHtml += `</div>`;
                $('#tab-upload-result').html(successHtml).show();
                showNotice('Berhasil', resp.message || 'Data Excel berhasil diproses.');
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                $('#tab-upload-progress').hide();
                $('#btnTabProsesUpload').prop('disabled', false);
                let errHtml = '<div class="alert alert-danger"><strong>Gagal mengunggah file:</strong><ul>';
                if (err.responseJSON && err.responseJSON.errors) {
                    err.responseJSON.errors.forEach(function (e) {
                        errHtml += `<li>${e}</li>`;
                    });
                } else if (err.responseJSON && err.responseJSON.message) {
                    errHtml += `<li>${err.responseJSON.message}</li>`;
                } else {
                    errHtml += `<li>Terjadi kesalahan jaringan atau server.</li>`;
                }
                errHtml += '</ul></div>';
                $('#tab-upload-result').html(errHtml).show();
            }
        });
    });

    // =========================================================================
    // 3. ORDERAN & NOTA (TAB 3)
    // =========================================================================

    function loadOrders() {
        const keyword = $('#search-order').val();
        const month = $('#filter-order-month').val();
        const year = $('#filter-order-year').val();

        $('#list-orderan-mitra-vet').html('<tr class="text-center"><td colspan="10">Memuat data orderan...</td></tr>');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/orders',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                page: currentPageOrder,
                keyword: keyword,
                month: month,
                year: year
            },
            success: function (resp) {
                let rows = '';
                const data = resp.data || [];

                if (data.length === 0) {
                    rows = '<tr class="text-center"><td colspan="11">Belum ada orderan.</td></tr>';
                } else {
                    data.forEach(function (order, index) {
                        const no = (currentPageOrder - 1) * 50 + (index + 1);

                        // Format rincian barang yang diorder
                        let detailItemsHtml = '<span class="text-muted">-</span>';
                        if (order.items && order.items.length > 0) {
                            let itemsList = [];
                            order.items.forEach(function (it) {
                                let nama = it.nama_barang || 'Barang';
                                let satuan = it.satuan ? ` ${it.satuan}` : '';
                                itemsList.push(`<div><i class="fa fa-caret-right text-info"></i> <strong>${nama}</strong> <span class="label label-default" style="font-size:11px;">${it.quantity}${satuan}</span></div>`);
                            });
                            detailItemsHtml = `<div style="font-size:12px; line-height:1.6; text-align:left;">${itemsList.join('')}</div>`;
                        }

                        let paymentBadge = '';
                        if (order.tipe_pembayaran === 'cicilan') {
                            if (order.status_pembayaran === 'lunas' || parseFloat(order.sisa_tagihan) <= 0) {
                                paymentBadge = '<div style="margin-top:3px;"><span class="label label-success" style="font-size:10px;"><i class="fa fa-check"></i> Lunas (Cicil)</span></div>';
                            } else {
                                paymentBadge = `<div style="margin-top:3px;"><span class="label label-warning" style="font-size:10px;" title="Sisa Piutang: ${formatRupiah(order.sisa_tagihan)}"><i class="fa fa-clock-o"></i> Cicil (Sisa: ${formatRupiah(order.sisa_tagihan)})</span></div>`;
                            }
                        } else {
                            paymentBadge = '<div style="margin-top:3px;"><span class="label label-success" style="font-size:10px;"><i class="fa fa-check"></i> Lunas</span></div>';
                        }

                        let actionBtns = `
                            <a href="${$('.baseUrl').val()}/mitra-vet/nota/${order.id}/download" class="btn btn-xs btn-primary" title="Download Nota (PDF)">
                                <i class="fa fa-download"></i> Nota
                            </a>
                        `;

                        if (parseFloat(order.sisa_tagihan) > 0) {
                            actionBtns += `
                                <a href="${$('.baseUrl').val()}/mitra-vet/orders/${order.id}/invoice-penagihan" class="btn btn-xs btn-danger" title="Download Invoice Penagihan (PDF)">
                                    <i class="fa fa-file-pdf-o"></i> Tagihan
                                </a>
                            `;
                        }

                        if (typeof role !== 'undefined' && role.toLowerCase() === 'admin') {
                            actionBtns += `
                                <button class="btn btn-xs btn-danger btn-delete-order" data-id="${order.id}" data-no="${order.nomor_order}" title="Batalkan & Hapus Order">
                                    <i class="fa fa-trash"></i>
                                </button>
                            `;
                        }

                        rows += `
                            <tr>
                                <td>${no}</td>
                                <td><span class="label label-info">${order.nomor_order}</span></td>
                                <td><strong>${order.nama_pembeli}</strong></td>
                                <td>${order.no_hp}</td>
                                <td>${detailItemsHtml}</td>
                                <td>${formatRupiah(order.total_harga)}</td>
                                <td>${formatRupiah(order.biaya_ongkir)}</td>
                                <td>
                                    <strong>${formatRupiah(order.grand_total)}</strong>
                                    ${paymentBadge}
                                </td>
                                <td>${order.created_by || '-'}</td>
                                <td>${order.created_at}</td>
                                <td>${actionBtns}</td>
                            </tr>
                        `;
                    });
                }

                $('#list-orderan-mitra-vet').html(rows);
                renderPagination('#pagination-orderan', resp.total_paging, currentPageOrder, function (p) {
                    currentPageOrder = p;
                    loadOrders();
                });
            },
            error: function () {
                $('#list-orderan-mitra-vet').html('<tr class="text-center text-danger"><td colspan="11">Gagal memuat orderan.</td></tr>');
            }
        });
    }

    $('#search-order').on('keyup', function (e) {
        if (e.which === 13) {
            currentPageOrder = 1;
            loadOrders();
        }
    });

    $('#filter-order-month, #filter-order-year').on('change', function () {
        currentPageOrder = 1;
        loadOrders();
    });

    // Export Excel Orderan (Disort berdasarkan Nama Pembeli)
    $('#btnExportOrderExcel').on('click', function () {
        const month = $('#filter-order-month').val();
        const year = $('#filter-order-year').val();
        const keyword = $('#search-order').val();

        let filename = 'Laporan Orderan Mitra Vet.xlsx';

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/orders/export-excel',
            headers: { Authorization: `Bearer ${token}` },
            type: 'GET',
            xhrFields: { responseType: 'blob' },
            data: { month: month, year: year, keyword: keyword },
            beforeSend: function () {
                if ($('#loading-screen').length) $('#loading-screen').show();
            },
            success: function (data, status, xhr) {
                let disposition = xhr.getResponseHeader('content-disposition');
                if (disposition) {
                    let matches = /"([^"]*)"/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1];
                }
                let blob = new Blob([data], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                let downloadUrl = URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                }, 100);
            },
            complete: function () {
                if ($('#loading-screen').length) $('#loading-screen').hide();
            },
            error: function (err) {
                if (err.status === 401) {
                    localStorage.removeItem('vet-clinic');
                    location.href = $('.baseUrl').val() + '/masuk';
                } else {
                    showNotice('Error', 'Gagal mendownload data orderan.');
                }
            }
        });
    });

    // Download Struk PDF per Pelanggan / Mitra (Bulanan / Tahunan)
    $('#btnModalStrukPelanggan').on('click', function () {
        $('#err-struk-customer, #err-struk-year, #err-struk-global').empty();
        $('#struk-year').val(currentYear);
        $('#struk-month').val(currentMonth);

        // Load customers ke dropdown
        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/customers',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            success: function (customers) {
                let opt = '<option value="">-- Cari & Pilih Pelanggan / Mitra --</option>';
                customers.forEach(function (c) {
                    let hp = c.no_hp ? ` (${c.no_hp})` : '';
                    opt += `<option value="${c.id}">${c.nama}${hp}</option>`;
                });
                $('#struk-customer-id').html(opt);

                $('#struk-customer-id').select2({
                    placeholder: '-- Cari & Pilih Pelanggan / Mitra --',
                    allowClear: true,
                    dropdownParent: $('#modal-struk-pelanggan'),
                    width: '100%'
                });

                $('#modal-struk-pelanggan').modal('show');
            },
            error: function () {
                showNotice('Gagal', 'Gagal memuat daftar pelanggan.');
            }
        });
    });

    // Proses Download Struk PDF Pelanggan
    $('#btnProsesDownloadStruk').on('click', function () {
        $('#err-struk-customer, #err-struk-year, #err-struk-global').empty();
        const customerId = $('#struk-customer-id').val();
        const year = $('#struk-year').val();
        const month = $('#struk-month').val();

        let hasErr = false;
        if (!customerId) {
            $('#err-struk-customer').text('Pilih pelanggan/mitra terlebih dahulu');
            hasErr = true;
        }
        if (!year) {
            $('#err-struk-year').text('Pilih tahun');
            hasErr = true;
        }
        if (hasErr) return;

        $('#btnProsesDownloadStruk').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan PDF...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/orders/customer-pdf',
            type: 'GET',
            headers: { Authorization: `Bearer ${token}` },
            xhrFields: { responseType: 'blob' },
            data: {
                customer_id: customerId,
                year: year,
                month: month
            },
            success: function (data, status, xhr) {
                let filename = 'Struk_Rekap_Pelanggan.pdf';
                let disposition = xhr.getResponseHeader('content-disposition');
                if (disposition) {
                    let matches = /"([^"]*)"/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1];
                }
                let blob = new Blob([data], { type: 'application/pdf' });
                let downloadUrl = URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                }, 100);

                $('#modal-struk-pelanggan').modal('hide');
                showNotice('Berhasil', 'Struk PDF riwayat order pelanggan berhasil diunduh.');
            },
            error: function (err) {
                const reader = new FileReader();
                reader.onload = function () {
                    try {
                        const json = JSON.parse(reader.result);
                        $('#err-struk-global').text(json.message || 'Gagal mendownload struk PDF.');
                    } catch (e) {
                        $('#err-struk-global').text('Gagal mendownload struk PDF atau tidak ada transaksi pada periode ini.');
                    }
                };
                if (err.responseText || err.response) {
                    reader.readAsText(err.response || err.responseText);
                } else {
                    $('#err-struk-global').text('Gagal mendownload struk PDF atau tidak ada transaksi pada periode ini.');
                }
            },
            complete: function () {
                $('#btnProsesDownloadStruk').prop('disabled', false).html('<i class="fa fa-download"></i> Download Struk PDF');
            }
        });
    });

    // Inisialisasi Select2 pencarian pada dropdown Mitra & Barang di dalam Modal Order
    function initOrderSelect2() {
        $('#order-select-customer').select2({
            placeholder: '-- Cari & Pilih Pelanggan/Mitra --',
            allowClear: true,
            dropdownParent: $('#modal-order'),
            width: '100%'
        });

        $('#order-select-item').select2({
            placeholder: '-- Cari & Pilih Barang --',
            allowClear: true,
            dropdownParent: $('#modal-order'),
            width: '100%'
        });
    }

    // Load customer dropdown untuk modal orderan
    function loadCustomerDropdown(selectCustomerId = null) {
        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/customers',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            success: function (customers) {
                availableCustomersList = customers;
                let opt = '<option value="">-- Cari & Pilih Pelanggan/Mitra --</option>';
                customers.forEach(function (c) {
                    opt += `<option value="${c.id}">${c.nama}</option>`;
                });
                $('#order-select-customer').html(opt);
                initOrderSelect2();

                if (selectCustomerId) {
                    $('#order-select-customer').val(selectCustomerId).trigger('change');
                }
            }
        });
    }

    // Auto-fill No HP dan Alamat saat Pelanggan dipilih dari Dropdown
    $('#order-select-customer').on('change', function () {
        const custId = parseInt($(this).val());
        if (!custId) {
            $('#order-no-hp').val('');
            $('#order-alamat').val('');
            return;
        }

        const found = availableCustomersList.find(c => c.id === custId);
        if (found) {
            $('#order-no-hp').val(found.no_hp);
            $('#order-alamat').val(found.alamat);
        }
    });

    // Buka Modal Tambah Customer Baru Cepat
    $('#btnBukaModalCustomer').on('click', function () {
        $('#form-tambah-customer')[0].reset();
        $('#err-cust-nama, #err-cust-hp, #err-cust-alamat, #err-cust-global').empty();
        $('#modal-tambah-customer').modal('show');
    });

    // Simpan Customer Baru
    $('#btnSimpanCustomerBaru').on('click', function () {
        $('#err-cust-nama, #err-cust-hp, #err-cust-alamat, #err-cust-global').empty();
        const nama = $('#cust-nama').val().trim();
        const hp = $('#cust-hp').val().trim();
        const alamat = $('#cust-alamat').val().trim();

        let hasError = false;
        if (!nama) { $('#err-cust-nama').text('Nama wajib diisi'); hasError = true; }
        if (!hp) { $('#err-cust-hp').text('No HP wajib diisi'); hasError = true; }
        if (!alamat) { $('#err-cust-alamat').text('Alamat wajib diisi'); hasError = true; }
        if (hasError) return;

        $('#btnSimpanCustomerBaru').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/customers',
            type: 'POST',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: { nama: nama, no_hp: hp, alamat: alamat },
            success: function (resp) {
                $('#modal-tambah-customer').modal('hide');
                loadCustomerDropdown(resp.customer.id);
            },
            error: function (err) {
                handleAjaxError(err, '#err-cust-global');
            },
            complete: function () {
                $('#btnSimpanCustomerBaru').prop('disabled', false).html('<i class="fa fa-check"></i> Simpan & Pilih');
            }
        });
    });

    // Form Order Baru
    $('#btnBuatOrder').on('click', function () {
        orderItemsList = [];
        $('#form-order')[0].reset();
        $('#order-ongkir').val('0');
        $('#order-tipe-pembayaran').val('lunas');
        $('#order-jumlah-termin').val(3);
        $('#order-uang-muka').val(0);
        $('#order-jatuh-tempo').val('');
        $('.wrap-order-cicilan').hide();
        renderOrderItemsTable();
        $('.validate-error').empty();

        // Load item dropdown
        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/items/dropdown',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            success: function (items) {
                availableItemsDropdown = items;
                let opt = '<option value="">-- Cari & Pilih Barang --</option>';
                items.forEach(function (i) {
                    opt += `<option value="${i.id}">${i.nama_barang} (Stok: ${i.stok} ${i.satuan}) - ${formatRupiah(i.harga_satuan)}</option>`;
                });
                $('#order-select-item').html(opt);

                // Load customer dropdown lalu tampilkan modal
                loadCustomerDropdown();
                $('#modal-order').modal('show');
            }
        });
    });

    // Tambah item ke draf order
    $('#btnAddItemToOrder').on('click', function () {
        $('#err-order-items').empty();
        const selectedId = parseInt($('#order-select-item').val());
        const qty = parseInt($('#order-item-qty').val());

        if (!selectedId) {
            $('#err-order-items').text('Pilih barang terlebih dahulu');
            return;
        }
        if (!qty || qty <= 0) {
            $('#err-order-items').text('Jumlah qty minimal 1');
            return;
        }

        const found = availableItemsDropdown.find(i => i.id === selectedId);
        if (!found) return;

        if (qty > found.stok) {
            $('#err-order-items').text(`Stok tidak mencukupi! Tersedia hanya ${found.stok}`);
            return;
        }

        // Cek jika item sudah ada dalam draf
        const existingIdx = orderItemsList.findIndex(i => i.item_id === selectedId);
        if (existingIdx !== -1) {
            const totalQty = orderItemsList[existingIdx].quantity + qty;
            if (totalQty > found.stok) {
                $('#err-order-items').text(`Total order melebihi stok! Tersedia: ${found.stok}`);
                return;
            }
            orderItemsList[existingIdx].quantity = totalQty;
            orderItemsList[existingIdx].subtotal = totalQty * parseFloat(found.harga_satuan);
        } else {
            orderItemsList.push({
                item_id: found.id,
                nama_barang: found.nama_barang,
                satuan: found.satuan,
                harga_satuan: parseFloat(found.harga_satuan),
                quantity: qty,
                subtotal: qty * parseFloat(found.harga_satuan)
            });
        }

        $('#order-item-qty').val(1);
        $('#order-select-item').val('').trigger('change');
        renderOrderItemsTable();
    });

    // Hapus item dari draf
    $(document).on('click', '.btn-remove-order-item', function () {
        const idx = $(this).data('idx');
        orderItemsList.splice(idx, 1);
        renderOrderItemsTable();
    });

    // Update ongkir trigger
    $('#order-ongkir').on('input keyup', function () {
        renderOrderItemsTable();
    });

    function renderOrderItemsTable() {
        let rows = '';
        let totalHarga = 0;

        if (orderItemsList.length === 0) {
            rows = '<tr class="text-center"><td colspan="6">Belum ada item dipilih.</td></tr>';
        } else {
            orderItemsList.forEach(function (oi, idx) {
                totalHarga += oi.subtotal;
                rows += `
                    <tr class="order-item-row">
                        <td>${oi.nama_barang}</td>
                        <td>${oi.satuan}</td>
                        <td>${oi.quantity}</td>
                        <td>${formatRupiah(oi.harga_satuan)}</td>
                        <td>${formatRupiah(oi.subtotal)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-danger btn-remove-order-item" data-idx="${idx}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        let ongkir = parseFloat($('#order-ongkir').val()) || 0;
        let grandTotal = totalHarga + ongkir;

        $('#list-order-items').html(rows);
        $('#order-total-harga').text(formatRupiah(totalHarga));
        $('#order-total-ongkir').text(formatRupiah(ongkir));
        $('#order-grand-total').text(formatRupiah(grandTotal));

        hitungEstimasiAngsuran();
    }

    // Toggle cicilan di modal order
    $('#order-tipe-pembayaran').on('change', function () {
        if ($(this).val() === 'cicilan') {
            $('.wrap-order-cicilan').slideDown();
            hitungEstimasiAngsuran();
        } else {
            $('.wrap-order-cicilan').slideUp();
        }
    });

    function hitungEstimasiAngsuran() {
        let totalHarga = 0;
        orderItemsList.forEach(oi => { totalHarga += oi.subtotal; });
        let ongkir = parseFloat($('#order-ongkir').val()) || 0;
        let grandTotal = totalHarga + ongkir;

        let dp = parseFloat($('#order-uang-muka').val()) || 0;
        let termin = parseInt($('#order-jumlah-termin').val()) || 1;
        let sisa = Math.max(grandTotal - dp, 0);
        let angsuran = termin > 0 ? (sisa / termin) : sisa;

        $('#order-estimasi-angsuran').val(formatRupiah(Math.round(angsuran)));
        $('#order-keterangan-sisa').text(`Sisa Piutang: ${formatRupiah(sisa)}`);
    }

    $('#order-uang-muka, #order-jumlah-termin').on('input keyup change', hitungEstimasiAngsuran);

    // Submit Order Baru
    $('#btnSimpanOrder').on('click', function () {
        $('.validate-error').empty();
        const customerId = $('#order-select-customer').val();
        const namaPembeli = $('#order-select-customer option:selected').text().trim();
        const noHp = $('#order-no-hp').val().trim();
        const alamat = $('#order-alamat').val().trim();
        const ongkir = $('#order-ongkir').val().trim() || 0;
        const catatan = $('#order-catatan').val().trim();
        const tipeBayar = $('#order-tipe-pembayaran').val() || 'lunas';
        const termin = parseInt($('#order-jumlah-termin').val()) || 1;
        const dp = parseFloat($('#order-uang-muka').val()) || 0;
        const tempo = $('#order-jatuh-tempo').val();

        let hasError = false;
        if (!customerId) { $('#err-order-nama').text('Pilih pelanggan terlebih dahulu'); hasError = true; }
        if (!noHp) { $('#err-order-hp').text('Nomor HP wajib diisi'); hasError = true; }
        if (!alamat) { $('#err-order-alamat').text('Alamat pengiriman wajib diisi'); hasError = true; }
        if (orderItemsList.length === 0) {
            $('#err-order-items').text('Tambahkan minimal 1 item barang ke orderan');
            hasError = true;
        }
        if (tipeBayar === 'cicilan') {
            if (!tempo) {
                $('#err-order-tempo').text('Tanggal jatuh tempo wajib diisi');
                hasError = true;
            }
            if (dp < 0) {
                $('#err-order-dp').text('Uang muka tidak boleh negatif');
                hasError = true;
            }
        }
        if (hasError) return;

        const payload = {
            customer_id: customerId,
            nama_pembeli: namaPembeli,
            no_hp: noHp,
            alamat: alamat,
            biaya_ongkir: ongkir,
            catatan: catatan,
            tipe_pembayaran: tipeBayar,
            jumlah_termin: termin,
            uang_muka: dp,
            tanggal_jatuh_tempo: tempo,
            items: orderItemsList.map(i => ({ item_id: i.item_id, quantity: i.quantity }))
        };

        $('#btnSimpanOrder').prop('disabled', true).text('Memproses Order...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/orders',
            type: 'POST',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: payload,
            success: function (resp) {
                $('#modal-order').modal('hide');
                showNotice('Sukses', `Order ${resp.nomor_order} berhasil dibuat! Nota siap diunduh.`);

                if (resp.order_id) {
                    window.open($('.baseUrl').val() + '/mitra-vet/nota/' + resp.order_id + '/download', '_blank');
                }

                loadOrders();
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                handleAjaxError(err, '#err-global-order');
            },
            complete: function () {
                $('#btnSimpanOrder').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan & Buat Nota');
            }
        });
    });

    // Tombol Hapus Order
    $(document).on('click', '.btn-delete-order', function () {
        selectedOrderId = $(this).data('id');
        const no = $(this).data('no');
        modalActionState = 'delete-order';
        $('#modal-confirmation .box-body').html(`Apakah Anda yakin ingin membatalkan & menghapus order <strong>"${no}"</strong>? Stok barang yang terjual akan otomatis dikembalikan ke gudang.`);
        $('#modal-confirmation').modal('show');
    });

    // Submit Konfirmasi Modal Global
    $('#submitConfirm').on('click', function () {
        if (modalActionState === 'delete-item') {
            $.ajax({
                url: $('.baseUrl').val() + '/api/mitra-vet/items',
                type: 'DELETE',
                dataType: 'JSON',
                headers: { Authorization: `Bearer ${token}` },
                data: { id: selectedItemId },
                success: function (resp) {
                    $('#modal-confirmation').modal('hide');
                    showNotice('Berhasil', resp.message || 'Barang berhasil dihapus.');
                    loadStokBarang();
                    loadChart();
                },
                error: function (err) {
                    $('#modal-confirmation').modal('hide');
                    showNotice('Gagal', err.responseJSON ? err.responseJSON.message : 'Gagal menghapus data.');
                }
            });
        } else if (modalActionState === 'delete-order') {
            $.ajax({
                url: $('.baseUrl').val() + '/api/mitra-vet/orders',
                type: 'DELETE',
                dataType: 'JSON',
                headers: { Authorization: `Bearer ${token}` },
                data: { id: selectedOrderId },
                success: function (resp) {
                    $('#modal-confirmation').modal('hide');
                    showNotice('Berhasil', resp.message || 'Order berhasil dibatalkan dan stok telah dikembalikan.');
                    loadOrders();
                    loadStokBarang();
                    loadChart();
                },
                error: function (err) {
                    $('#modal-confirmation').modal('hide');
                    showNotice('Gagal', err.responseJSON ? err.responseJSON.message : 'Gagal membatalkan order.');
                }
            });
        }
    });

    // =========================================================================
    // 4. PENAGIHAN & PIUTANG (TAB 4)
    // =========================================================================

    let currentPagePiutang = 1;

    function loadPiutang() {
        const status = $('#filter-piutang-status').val();
        const customerId = $('#filter-piutang-customer').val();
        const keyword = $('#search-piutang').val().trim();

        $('#list-penagihan-mitra-vet').html('<tr class="text-center"><td colspan="11">Memuat data penagihan & piutang...</td></tr>');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/piutang',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                page: currentPagePiutang,
                status: status,
                customer_id: customerId,
                keyword: keyword
            },
            success: function (resp) {
                // Update summary cards
                if (resp.summary) {
                    $('#piutang-total-sisa').text(formatRupiah(resp.summary.total_sisa_piutang));
                    $('#piutang-total-tempo').text(formatRupiah(resp.summary.total_jatuh_tempo));
                    $('#piutang-total-terbayar').text(formatRupiah(resp.summary.total_tertagih));
                }

                let rows = '';
                const data = resp.data || [];

                if (data.length === 0) {
                    rows = '<tr class="text-center"><td colspan="11">Tidak ada data piutang / penagihan.</td></tr>';
                } else {
                    data.forEach(function (ord, idx) {
                        const no = (currentPagePiutang - 1) * 50 + (idx + 1);

                        // Jatuh tempo badge
                        let tempoBadge = ord.tgl_tempo || '-';
                        if (ord.is_jatuh_tempo) {
                            tempoBadge = `<span class="label label-danger" title="Sudah lewat jatuh tempo!"><i class="fa fa-warning"></i> ${ord.tgl_tempo}</span>`;
                        }

                        // Status badge
                        let statusBadge = '';
                        if (parseFloat(ord.sisa_tagihan) <= 0) {
                            statusBadge = '<span class="label label-success"><i class="fa fa-check"></i> Lunas</span>';
                        } else if (ord.is_jatuh_tempo) {
                            statusBadge = '<span class="label label-danger"><i class="fa fa-exclamation-triangle"></i> Jatuh Tempo</span>';
                        } else {
                            statusBadge = '<span class="label label-warning"><i class="fa fa-clock-o"></i> Cicilan</span>';
                        }

                        // Sisa tagihan format
                        let sisaText = parseFloat(ord.sisa_tagihan) > 0 
                            ? `<strong class="text-danger">${formatRupiah(ord.sisa_tagihan)}</strong>` 
                            : `<span class="text-success">${formatRupiah(0)}</span>`;

                        let actionBtns = `
                            <a href="${$('.baseUrl').val()}/mitra-vet/orders/${ord.id}/invoice-penagihan" class="btn btn-xs btn-danger" title="Download Invoice Penagihan (PDF)">
                                <i class="fa fa-file-pdf-o"></i> Tagihan
                            </a>
                        `;

                        if (parseFloat(ord.sisa_tagihan) > 0) {
                            actionBtns = `
                                <button class="btn btn-xs btn-success btn-bayar-cicilan" 
                                    data-id="${ord.id}" 
                                    data-no="${ord.nomor_order}" 
                                    data-klien="${ord.nama_pembeli}" 
                                    data-total="${ord.grand_total}" 
                                    data-sisa="${ord.sisa_tagihan}" 
                                    data-termin="${ord.jumlah_termin}" 
                                    data-masuk="${ord.total_cicilan_masuk}" 
                                    title="Input Pembayaran Cicilan">
                                    <i class="fa fa-money"></i> Bayar
                                </button>
                            ` + actionBtns;
                        }

                        actionBtns += `
                            <button class="btn btn-xs btn-info btn-lihat-riwayat" data-id="${ord.id}" title="Lihat Riwayat Cicilan">
                                <i class="fa fa-history"></i> Riwayat
                            </button>
                        `;

                        rows += `
                            <tr>
                                <td>${no}</td>
                                <td><span class="label label-info">${ord.nomor_order}</span></td>
                                <td><strong>${ord.nama_pembeli}</strong></td>
                                <td>${ord.tgl_order}</td>
                                <td>${tempoBadge}</td>
                                <td>${formatRupiah(ord.grand_total)}</td>
                                <td><span class="text-green">${formatRupiah(ord.total_terbayar)}</span></td>
                                <td>${sisaText}</td>
                                <td><span class="badge bg-purple">${ord.total_cicilan_masuk} / ${ord.jumlah_termin}x</span></td>
                                <td>${statusBadge}</td>
                                <td>${actionBtns}</td>
                            </tr>
                        `;
                    });
                }

                $('#list-penagihan-mitra-vet').html(rows);
                renderPagination('#pagination-penagihan', resp.total_paging, currentPagePiutang, function (p) {
                    currentPagePiutang = p;
                    loadPiutang();
                });
            },
            error: function () {
                $('#list-penagihan-mitra-vet').html('<tr class="text-center text-danger"><td colspan="11">Gagal memuat data piutang.</td></tr>');
            }
        });
    }

    function loadCustomerPiutangFilter() {
        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/customers',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            success: function (custs) {
                let opt = '<option value="">-- Semua Pelanggan --</option>';
                custs.forEach(c => { opt += `<option value="${c.id}">${c.nama}</option>`; });
                $('#filter-piutang-customer').html(opt);
            }
        });
    }

    // Filter event handlers Tab Penagihan
    $('#filter-piutang-status, #filter-piutang-customer').on('change', function () {
        currentPagePiutang = 1;
        loadPiutang();
    });

    $('#search-piutang').on('keyup', function (e) {
        if (e.which === 13) {
            currentPagePiutang = 1;
            loadPiutang();
        }
    });

    // Buka Modal Bayar Cicilan
    $(document).on('click', '.btn-bayar-cicilan', function () {
        const orderId = $(this).data('id');
        const noOrder = $(this).data('no');
        const klien = $(this).data('klien');
        const total = parseFloat($(this).data('total')) || 0;
        const sisa = parseFloat($(this).data('sisa')) || 0;
        const termin = parseInt($(this).data('termin')) || 1;
        const cicilanMasuk = parseInt($(this).data('masuk')) || 0;
        const nextCicilan = cicilanMasuk + 1;

        const sisaTermin = Math.max(termin - cicilanMasuk, 1);
        const rekomendasiAngsuran = Math.round(sisa / sisaTermin);

        $('#bayar-order-id').val(orderId);
        $('#bayar-display-no').text(noOrder);
        $('#bayar-display-klien').text(klien);
        $('#bayar-display-total').text(formatRupiah(total));
        $('#bayar-display-sisa').text(formatRupiah(sisa));
        $('#bayar-display-cicilan-ke').val(`Cicilan ke-${nextCicilan} dari ${termin} termin`);
        $('#bayar-display-rekomendasi').val(formatRupiah(rekomendasiAngsuran));
        $('#bayar-nominal').val(rekomendasiAngsuran > 0 ? rekomendasiAngsuran : sisa);
        $('#bayar-catatan').val('');
        $('.validate-error').empty();

        $('#modal-bayar-cicilan').modal('show');
    });

    // Submit Bayar Cicilan
    $('#btnSimpanBayarCicilan').on('click', function () {
        $('#err-bayar-nominal, #err-bayar-tanggal, #err-bayar-global').empty();
        const orderId = $('#bayar-order-id').val();
        const nominal = parseFloat($('#bayar-nominal').val()) || 0;
        const tgl = $('#bayar-tanggal').val();
        const metode = $('#bayar-metode').val();
        const catatan = $('#bayar-catatan').val().trim();

        let hasErr = false;
        if (!nominal || nominal <= 0) {
            $('#err-bayar-nominal').text('Nominal pembayaran harus lebih besar dari 0');
            hasErr = true;
        }
        if (!tgl) {
            $('#err-bayar-tanggal').text('Tanggal pembayaran wajib diisi');
            hasErr = true;
        }
        if (hasErr) return;

        $('#btnSimpanBayarCicilan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/piutang/bayar',
            type: 'POST',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: {
                order_id: orderId,
                jumlah_bayar: nominal,
                tanggal_bayar: tgl,
                metode_pembayaran: metode,
                catatan: catatan
            },
            success: function (resp) {
                $('#modal-bayar-cicilan').modal('hide');
                showNotice('Berhasil', resp.message || 'Pembayaran cicilan berhasil dicatat!');
                loadPiutang();
                loadOrders();
                loadStokBarang();
                loadChart();
            },
            error: function (err) {
                handleAjaxError(err, '#err-bayar-global');
            },
            complete: function () {
                $('#btnSimpanBayarCicilan').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pembayaran');
            }
        });
    });

    // Buka Modal Riwayat Cicilan
    $(document).on('click', '.btn-lihat-riwayat', function () {
        const orderId = $(this).data('id');
        $('#list-riwayat-pembayaran').html('<tr class="text-center"><td colspan="8">Memuat riwayat...</td></tr>');
        $('#modal-riwayat-cicilan').modal('show');

        $.ajax({
            url: $('.baseUrl').val() + `/api/mitra-vet/piutang/${orderId}/riwayat`,
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            success: function (resp) {
                const ord = resp.order;
                const payments = resp.payments || [];

                $('#riwayat-no-order').text(ord.nomor_order);
                $('#riwayat-klien').text(ord.nama_pembeli);
                $('#riwayat-total-tagihan').text(formatRupiah(ord.grand_total));
                $('#riwayat-total-terbayar').text(formatRupiah(ord.total_terbayar));
                $('#riwayat-sisa-piutang').text(formatRupiah(ord.sisa_tagihan));

                let statusText = '';
                if (parseFloat(ord.sisa_tagihan) <= 0) {
                    statusText = '<span class="label label-success"><i class="fa fa-check"></i> Lunas</span>';
                } else {
                    statusText = `<span class="label label-warning"><i class="fa fa-clock-o"></i> Cicilan (${ord.jumlah_termin}x)</span>`;
                }
                $('#riwayat-status').html(statusText);

                let pRows = '';
                if (payments.length === 0) {
                    pRows = '<tr class="text-center"><td colspan="8">Belum ada pembayaran yang tercatat.</td></tr>';
                } else {
                    payments.forEach(function (p, idx) {
                        let labelTermin = p.cicilan_ke === 0 ? '<span class="label label-info">Uang Muka (DP)</span>' : `<strong>Cicilan ke-${p.cicilan_ke}</strong>`;
                        let staffName = p.creator ? p.creator.fullname : '-';
                        pRows += `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><code>${p.nomor_pembayaran}</code></td>
                                <td>${labelTermin}</td>
                                <td>${p.tanggal_bayar}</td>
                                <td>${p.metode_pembayaran.toUpperCase()}</td>
                                <td class="text-right"><strong>${formatRupiah(p.jumlah_bayar)}</strong></td>
                                <td>${p.catatan || '-'}</td>
                                <td>${staffName}</td>
                            </tr>
                        `;
                    });
                }

                $('#list-riwayat-pembayaran').html(pRows);
            },
            error: function () {
                $('#list-riwayat-pembayaran').html('<tr class="text-center text-danger"><td colspan="8">Gagal memuat riwayat pembayaran.</td></tr>');
            }
        });
    });

    // =========================================================================
    // 5. GRAFIK HIGHCHARTS
    // =========================================================================

    function loadChart() {
        const month = $('#filter-chart-month').val();
        const year = $('#filter-chart-year').val();

        $.ajax({
            url: $('.baseUrl').val() + '/api/mitra-vet/chart',
            type: 'GET',
            dataType: 'JSON',
            headers: { Authorization: `Bearer ${token}` },
            data: { month: month, year: year },
            success: function (resp) {
                $('#summary-masuk-qty').text(resp.summary.total_masuk_qty || 0);
                $('#summary-masuk-nilai').text(formatRupiah(resp.summary.total_masuk_nilai));
                $('#summary-keluar-qty').text(resp.summary.total_keluar_qty || 0);
                $('#summary-keluar-nilai').text(formatRupiah(resp.summary.total_keluar_nilai));
                if (resp.summary.total_profit_bulan !== undefined) {
                    $('#total-profit-bulan').text(formatRupiah(resp.summary.total_profit_bulan));
                }
                if (resp.summary.total_profit_keseluruhan !== undefined) {
                    $('#total-profit-keseluruhan').text(formatRupiah(resp.summary.total_profit_keseluruhan));
                }

                const daysInMonth = new Date(year, month, 0).getDate();
                const categories = [];
                const masukData = [];
                const keluarData = [];

                const mapMasuk = {};
                (resp.masuk || []).forEach(m => { mapMasuk[m.hari] = parseFloat(m.total_nilai); });
                const mapKeluar = {};
                (resp.keluar || []).forEach(k => { mapKeluar[k.hari] = parseFloat(k.total_nilai); });

                for (let d = 1; d <= daysInMonth; d++) {
                    categories.push('Tgl ' + d);
                    masukData.push(mapMasuk[d] || 0);
                    keluarData.push(mapKeluar[d] || 0);
                }

                Highcharts.chart('chart-mitra-vet', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories: categories, crosshair: true },
                    yAxis: {
                        min: 0,
                        title: { text: 'Nilai Aset (Rp)' },
                        labels: {
                            formatter: function () {
                                return 'Rp ' + (this.value / 1000).toLocaleString('id-ID') + 'k';
                            }
                        }
                    },
                    tooltip: {
                        shared: true,
                        useHTML: true,
                        headerFormat: '<small>{point.key}</small><table>',
                        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                            '<td style="padding:0"><b>Rp {point.y:,.0f}</b></td></tr>',
                        footerFormat: '</table>'
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0.1,
                            borderWidth: 0
                        }
                    },
                    series: [
                        { name: 'Aset Masuk', data: masukData, color: '#00a65a' },
                        { name: 'Aset Keluar', data: keluarData, color: '#f39c12' }
                    ],
                    credits: { enabled: false }
                });
            }
        });
    }

    $('#filter-chart-month, #filter-chart-year').on('change', function () {
        loadChart();
    });

    // =========================================================================
    // 5. HELPER FUNCTIONS
    // =========================================================================

    function showNotice(title, message) {
        if ($('#msg-box').length) {
            $('#msg-box .modal-title').text(title);
            $('#msg-box .modal-body').text(message);
            $('#msg-box').modal('show');
        } else {
            alert(message);
        }
    }

    function handleAjaxError(err, targetElement) {
        let msg = '';
        if (err.status === 422 && err.responseJSON && err.responseJSON.errors) {
            err.responseJSON.errors.forEach(function (e) {
                msg += `<div>${e}</div>`;
            });
        } else if (err.responseJSON && err.responseJSON.message) {
            msg = `<div>${err.responseJSON.message}</div>`;
        } else {
            msg = '<div>Terjadi kesalahan pada sistem. Silakan coba lagi.</div>';
        }
        $(targetElement).html(msg);
    }

    function renderPagination(selector, totalPages, curPage, onPageClick) {
        $(selector).empty();
        if (totalPages <= 1) return;

        let html = '';
        if (curPage > 1) {
            html += `<li><a href="javascript:void(0)" class="page-link" data-page="${curPage - 1}">&laquo;</a></li>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === curPage) {
                html += `<li class="active"><a href="javascript:void(0)">${i}</a></li>`;
            } else if (i === 1 || i === totalPages || (i >= curPage - 2 && i <= curPage + 2)) {
                html += `<li><a href="javascript:void(0)" class="page-link" data-page="${i}">${i}</a></li>`;
            } else if (i === curPage - 3 || i === curPage + 3) {
                html += `<li class="disabled"><a href="javascript:void(0)">...</a></li>`;
            }
        }

        if (curPage < totalPages) {
            html += `<li><a href="javascript:void(0)" class="page-link" data-page="${curPage + 1}">&raquo;</a></li>`;
        }

        $(selector).html(html);
        $(selector).find('.page-link').on('click', function () {
            onPageClick(parseInt($(this).data('page')));
        });
    }

    // Tab change trigger
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#tab-orderan') {
            loadOrders();
        } else if (target === '#tab-stok') {
            loadStokBarang();
            loadChart();
        } else if (target === '#tab-penagihan') {
            loadPiutang();
            loadCustomerPiutangFilter();
        }
    });

    $('#modal-order').on('shown.bs.modal', function () {
        initOrderSelect2();
    });

    // Inisialisasi awal
    if (userRole === 'resepsionis') {
        loadOrders();
    } else {
        loadStokBarang();
        loadChart();
    }
});
