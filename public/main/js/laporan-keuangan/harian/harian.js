$(document).ready(function() {

  let optCabang = '';
	let getCurrentPage = 1;
  let paramUrlSetup = {
		orderby:'',
		column: '',
    date: '',
    branchId: ''
  };

  // if (role.toLowerCase() == 'resepsionis') {
	// 	window.location.href = $('.baseUrl').val() + `/unauthorized`;
	// } else {
    if (role.toLowerCase() == 'dokter' || role.toLowerCase() == 'resepsionis') {
      $('#filterCabang').hide();
      $('.section-right-box-title').css('width', 'unset');
      $('.section-right-box-title .btn-download-excel').css('margin-right', 'unset');
    } else {
      $('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });
      loadCabang();
    }

    $('#datepicker').datepicker({
      autoclose: true,
      clearBtn: true,
      format: 'yyyy-mm-dd',
      todayHighlight: true,
      }).on('changeDate', function(e) {
        paramUrlSetup.date = e.format();
        loadLaporanKeuanganHarian();
    });
	// }

  // loadLaporanKeuanganHarian();

  $('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
  $('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

  $('.onOrdering').click(function() {
		const column = $(this).attr('data');
		const orderBy = $(this).attr('orderby');
		$('.onOrdering[data="'+column+'"]').children().remove();

		if (orderBy == 'none' || orderBy == 'asc') {
			$(this).attr('orderby', 'desc');
			$(this).append('<span class="fa fa-sort-desc"></span>');

		} else if(orderBy == 'desc') {
			$(this).attr('orderby', 'asc');
			$(this).append('<span class="fa fa-sort-asc"></span>');
		}

		paramUrlSetup.orderby = $(this).attr('orderby');
		paramUrlSetup.column = column;

		loadLaporanKeuanganHarian();
  });

  $('.btn-download-excel').click(function() {
    const getBranchId = (role.toLowerCase() == 'dokter' || role.toLowerCase() == 'resepsionis') ? branchId : paramUrlSetup.branchId;

    if (getBranchId) {
      $.ajax({
        url     : $('.baseUrl').val() + '/api/laporan-keuangan/harian/download',
        headers : { 'Authorization': `Bearer ${token}` },
        type    : 'GET',
        data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, date: paramUrlSetup.date, branch_id: getBranchId },
        xhrFields: { responseType: 'blob' },
        beforeSend: function() { $('#loading-screen').show(); },
        success: function(data, status, xhr) {
          let disposition = xhr.getResponseHeader('content-disposition');
          let matches = /"([^"]*)"/.exec(disposition);
          let filename = (matches != null && matches[1] ? matches[1] : 'file.xlsx');
          let blob = new Blob([data],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
          let downloadUrl = URL.createObjectURL(blob);
          let a = document.createElement("a");

          a.href = downloadUrl;
          a.download = filename
          document.body.appendChild(a);
          a.click();

        }, complete: function() { $('#loading-screen').hide(); },
        error: function(err) {
          if (err.status == 401) {
            localStorage.removeItem('vet-clinic');
            location.href = $('.baseUrl').val() + '/masuk';
          }
        }
      });
    } else {
      $("#msg-box .modal-body").text('Pilih cabang dahulu!');
      $('#msg-box').modal('show');
    }
	});

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
		// loadLaporanKeuanganHarian();
  }

  function loadLaporanKeuanganHarian() {
    $.ajax({
			url     : $('.baseUrl').val() + '/api/laporan-keuangan/harian',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, date: paramUrlSetup.date, branch_id: paramUrlSetup.branchId, page: getCurrentPage },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(resp) {

        const getData = resp.data;
				let listLaporanKeuanganHarian = '';

				$('#list-laporan-keuangan-harian tr').remove();

				if (getData.length) {
					$.each(getData, function(idx, v) {
						listLaporanKeuanganHarian += `<tr>`
							+ `<td>${++idx}</td>`
							+ `<td>${v.registration_number}</td>`
              + `<td>${v.created_at}</td>`
							+ `<td>${v.patient_number}</td>`
							+ `<td>${v.pet_category}</td>`
							+ `<td>${v.pet_name}</td>`
							+ `<td>${v.complaint}</td>`
							+ `<td>${(v.status_outpatient_inpatient == 1) ? 'Rawat Inap' : 'Rawat Jalan'}</td>`
							+ `<td>${
                Number(v.price_overall || 0).toLocaleString('id-ID')
                // typeof(v.price_overall) == 'number' ? v.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${
                Number(v.capital_price || 0).toLocaleString('id-ID')
                // typeof(v.capital_price) == 'number' ? v.capital_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${
                Number(v.doctor_fee || 0).toLocaleString('id-ID')
                // typeof(v.doctor_fee) == 'number' ? v.doctor_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${
                Number(v.petshop_fee || 0).toLocaleString('id-ID')
                // typeof(v.petshop_fee)== 'number' ? v.petshop_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${
                Number(v.amount_discount || 0).toLocaleString('id-ID')
                // typeof(v.amount_discount)== 'number' ? v.amount_discount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${
                Number(v.fee_doctor_after_discount || 0).toLocaleString('id-ID')
                // typeof(v.fee_doctor_after_discount)== 'number' ? v.fee_doctor_after_discount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''

              }</td>`
							+ `<td>${v.created_by}</td>`
							+ `<td>
									<button type="button" class="btn btn-info openDetail" value=${v.list_of_payment_id} title="Detail"><i class="fa fa-eye" aria-hidden="true"></i></button>
								</td>`
							+ `</tr>`;
					});
				} else { listLaporanKeuanganHarian += `<tr class="text-center"><td colspan="14">Tidak ada data.</td></tr>`; }

				$('#list-laporan-keuangan-harian').append(listLaporanKeuanganHarian);

				generatePagination(getCurrentPage, resp.total_paging);

				const priceOverall = (resp.price_overall > -1) ? resp.price_overall.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const capitalPrice = (resp.capital_price > -1) ? resp.capital_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const docterFee    = (resp.doctor_fee > -1) ? resp.doctor_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const petshopFee   = (resp.petshop_fee > -1) ? resp.petshop_fee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const amountDiscount   = (resp.amount_discount > -1) ? resp.amount_discount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const expenses   = (resp.expenses > -1) ? resp.expenses.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';
				const netProfit   = (resp.net_profit > -1) ? resp.net_profit.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '-';

        $('#total-keseluruhan-txt').text(`Rp. ${priceOverall}`);
        $('#harga-modal-txt').text(`Rp. ${capitalPrice}`);
        $('#fee-dokter-txt').text(`Rp. ${docterFee}`);
        $('#nominal-diskon-txt').text(`Rp. ${amountDiscount}`);
        $('#fee-petshop-txt').text(`Rp. ${petshopFee}`);
        $('#pengeluaran-txt').text(`Rp. ${expenses}`);
        $('#fee-bersih-txt').text(`Rp. ${netProfit}`);

        $('.openDetail').click(function() {
					window.location.href = $('.baseUrl').val() + `/laporan-keuangan-harian/detail/${$(this).val()}?date=${paramUrlSetup.date}`;
        });

				$('.pagination > li > a').click(function() {
					const getClassName = this.className;
					const getNumber = parseFloat($(this).text());

					if ((getCurrentPage === 1 && getClassName.includes('arrow-left')
						|| (getCurrentPage === resp.total_paging && getClassName.includes('arrow-right')))) { return; }

					if (getClassName.includes('arrow-left')) {
						getCurrentPage = getCurrentPage - 1;
					} else if (getClassName.includes('arrow-right')) {
						getCurrentPage = getCurrentPage + 1;
					} else {
						getCurrentPage = getNumber;
					}

					loadLaporanKeuanganHarian();
				});

			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
  }

  function loadCabang() {
		$.ajax({
			url     : $('.baseUrl').val() + '/api/cabang',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(data) {
				optCabang += `<option value=''>Cabang</option>`

				if (data.length) {
					for (let i = 0 ; i < data.length ; i++) {
						optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
					}
				}
				$('#filterCabang').append(optCabang);
			}, complete: function() { $('#loading-screen').hide(); },
			error: function(err) {
				if (err.status == 401) {
					localStorage.removeItem('vet-clinic');
					location.href = $('.baseUrl').val() + '/masuk';
				}
			}
		});
	}

});
