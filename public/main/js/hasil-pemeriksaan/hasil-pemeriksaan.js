$(document).ready(function() {

  let optCabang = '';
	let getCurrentPage = 1;

  let paramUrlSetup = {
		orderby:'',
		column: '',
    keyword: '',
    branchId: ''
  };

  // if (role.toLowerCase() == 'resepsionis') {
	// 	window.location.href = $('.baseUrl').val() + `/unauthorized`;
	// } else {
    if (role.toLowerCase() != 'admin') {
      $('#filterCabang').hide();
    } else {
      loadCabang();
      $('#filterCabang').select2({ placeholder: 'Cabang', allowClear: true });
    }

		loadHasilPemeriksaan();
	// }

  $('#filterCabang').on('select2:select', function () { onFilterCabang($(this).val()); });
  $('#filterCabang').on("select2:unselect", function () { onFilterCabang($(this).val()); });

  $('.input-search-section .fa').click(function() {
		onSearch($('.input-search-section input').val());
	});

	$('.input-search-section input').keypress(function(e) {
		if (e.which == 13) { onSearch($(this).val()); }
	});

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

		loadHasilPemeriksaan();
  });

  $('.openFormAdd').click(function() {
    window.location.href = $('.baseUrl').val() + `/hasil-pemeriksaan/tambah`;
  });

  function onSearch(keyword) {
		paramUrlSetup.keyword = keyword;
		loadHasilPemeriksaan();
	}

	function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
		loadHasilPemeriksaan();
  }

  $('#submitConfirm').click(function() {
    // process delete
    $.ajax({
      url     : $('.baseUrl').val() + '/api/hasil-pemeriksaan',
      headers : { 'Authorization': `Bearer ${token}` },
      type    : 'DELETE',
      data	  : { id: getId },
      beforeSend: function() { $('#loading-screen').show(); },
      success: function(data) {
        $('#modal-confirmation .modal-title').text('Peringatan');
        $('#modal-confirmation').modal('toggle');

        $("#msg-box .modal-body").text('Berhasil menghapus data');
        $('#msg-box').modal('show');

        loadHasilPemeriksaan();

      }, complete: function() { $('#loading-screen').hide(); }
      , error: function(err) {
        if (err.status == 401) {
          localStorage.removeItem('vet-clinic');
          location.href = $('.baseUrl').val() + '/masuk';
        }
      }
    });
  });

  function loadHasilPemeriksaan() {
    getId = null; modalState = '';

    $.ajax({
			url     : $('.baseUrl').val() + '/api/hasil-pemeriksaan',
			headers : { 'Authorization': `Bearer ${token}` },
			type    : 'GET',
			data	  : { orderby: paramUrlSetup.orderby, column: paramUrlSetup.column, keyword: paramUrlSetup.keyword, branch_id: paramUrlSetup.branchId, page: getCurrentPage },
			beforeSend: function() { $('#loading-screen').show(); },
			success: function(resp) {
				const getData = resp.data;
				let listHasilPemeriksaan = '';
				$('#list-hasil-pemeriksaan tr').remove();

        if (getData.length) {
          $.each(getData, function(idx, v) {
            const roleLower   = role.toLowerCase();
            const isOver24h   = (new Date() - new Date(v.created_at)) > 24 * 60 * 60 * 1000;
            const isCreator   = String(v.user_id) === String(userId);

            // Print: resepsionis, dokter, dan admin saja
            const printDisabled = (roleLower !== 'resepsionis' && roleLower !== 'dokter' && roleLower !== 'admin');

            // Edit: admin bebas; dokter hanya milik sendiri & dalam 24 jam; role lain tidak bisa
            let editDisabled = true;
            let editTitle = 'Anda tidak memiliki akses untuk mengedit';
            if (roleLower === 'admin') {
              editDisabled = false; editTitle = '';
            } else if (roleLower === 'dokter' && isCreator && !isOver24h) {
              editDisabled = false; editTitle = '';
            } else if (roleLower === 'dokter' && !isCreator) {
              editTitle = 'Anda tidak bisa mengedit hasil pemeriksaan dokter lain';
            } else if (roleLower === 'dokter' && isCreator && isOver24h) {
              editTitle = 'Waktu edit sudah melebihi 24 jam';
            }

            // Delete: hanya admin
            const deleteDisabled = (roleLower !== 'admin');

            listHasilPemeriksaan += `<tr>`
              + `<td>${++idx}</td>`
              + `<td>${v.registration_number}</td>`
              + `<td>${v.created_at}</td>`
              + `<td>${v.patient_number}</td>`
              + `<td>${v.pet_category}</td>`
              + `<td>${v.pet_name}</td>`
              + `<td>${v.owner_name}</td>`
              + `<td>${v.complaint}</td>`
              + `<td>${(v.status_outpatient_inpatient == 1) ? 'Rawat Inap' : 'Rawat Jalan'}</td>`
              + `<td>${v.created_by}</td>`
              + `<td>
                  <button type="button" class="btn btn-info openDetail" value=${v.id} title="Detail"><i class="fa fa-eye" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-info onCetak m-r-3px" ${printDisabled ? 'disabled title="Hanya resepsionis, dokter, dan admin yang dapat mencetak"' : ''} value=${v.id}><i class="fa fa-print" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-warning openFormEdit" ${editDisabled ? `disabled title="${editTitle}"` : ''} value=${v.id}><i class="fa fa-pencil" aria-hidden="true"></i></button>
                  <button type="button" class="btn btn-danger openFormDelete" ${deleteDisabled ? 'disabled title="Hanya admin yang dapat menghapus"' : ''} value=${v.id}><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                </td>`
              + `</tr>`;
          });
        } else {
          listHasilPemeriksaan += `<tr class="text-center"><td colspan="11">Tidak ada data.</td></tr>`;
        }
				$('#list-hasil-pemeriksaan').append(listHasilPemeriksaan);

				generatePagination(getCurrentPage, resp.total_paging);

				function generateBedge(status) {
					let bedge = '';
          let num = parseInt(status);
					switch (num) {
						case 1:
							bedge = '<span class="label label-success">Selesai</span>';
							break;
						default:
							bedge = '<span class="label label-warning">Belum</span>';
							break;
					}
					return bedge;
        }

        $('.openDetail').click(function() {
          window.location.href = $('.baseUrl').val() + `/hasil-pemeriksaan/detail/${$(this).val()}`;
        });

        $('.onCetak').click(function() {
          window.open($('.baseUrl').val() + '/hasil-pemeriksaan/cetak/' + $(this).val() + '?token=' + token, '_blank');
        });

				$('.openFormEdit').click(function() {
            window.location.href = $('.baseUrl').val() + `/hasil-pemeriksaan/edit/${$(this).val()}`;
				});

				$('.openFormDelete').click(function() {
					getId = $(this).val();
					modalState = 'delete';
					$('#modal-confirmation .modal-title').text('Peringatan');
					$('#modal-confirmation .box-body').text('Anda yakin ingin menghapus data ini?');
					$('#modal-confirmation').modal('show');
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

					loadHasilPemeriksaan()
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
