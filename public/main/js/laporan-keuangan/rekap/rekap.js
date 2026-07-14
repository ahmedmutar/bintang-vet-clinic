$(document).ready(function () {
  let optCabang = "";
  let optPeriode = "";
  let suppressDateChange = false;
  let getCurrentPage = 1;
  let paramUrlSetup = {
    orderby: "",
    column: "",
    monthFrom: "",
    yearFrom: "",
    monthTo: "",
    yearTo: "",
    branchId: "",
    periodeId: "",
    year: "",
  };

  if (role.toLowerCase() == "dokter" || role.toLowerCase() == "resepsionis") {
    $("#filterCabang").hide();
    $("#filterPeriode").hide();
    $(".section-right-box-title").css("width", "unset");
    $(".section-right-box-title .btn-download-excel").css(
      "margin-right",
      "unset"
    );
  } else {
    $("#filterCabang").select2({ placeholder: "Cabang", allowClear: true });
    loadCabang();

    $("#filterPeriode").select2({ placeholder: "Periode", allowClear: true });
    loadPeriode();
  }

  const datepickerOpts = {
    autoclose: true,
    clearBtn: true,
    format: 'mm-yyyy',
    todayHighlight: true,
    startView: 'months',
    minViewMode: 'months',
  };

  $('#datepickerFrom').datepicker(datepickerOpts).on('changeDate', function(e) {
    if (suppressDateChange) return;
    const parts = e.format().split('-');
    paramUrlSetup.monthFrom = parts[0];
    paramUrlSetup.yearFrom  = parts[1];
    if (paramUrlSetup.monthTo && paramUrlSetup.yearTo) {
      widgetRekap();
      loadLaporanKeuanganRekap();
    }
  });

  $('#datepickerTo').datepicker(datepickerOpts).on('changeDate', function(e) {
    if (suppressDateChange) return;
    const parts = e.format().split('-');
    paramUrlSetup.monthTo = parts[0];
    paramUrlSetup.yearTo  = parts[1];
    if (paramUrlSetup.monthFrom && paramUrlSetup.yearFrom) {
      widgetRekap();
      loadLaporanKeuanganRekap();
    }
  });

  // Isi opsi tahun untuk mode Tahunan (5 tahun terakhir)
  const currentYear = new Date().getFullYear();
  $('#filterYear').append('<option value="">-- Pilih Tahun --</option>');
  for (let y = currentYear; y >= currentYear - 4; y--) {
    $('#filterYear').append(`<option value="${y}">${y}</option>`);
  }
  $('#filterYear').on('change', function () {
    const year = $(this).val();
    if (!year) return;
    paramUrlSetup.year  = year;
    paramUrlSetup.month = '';
    widgetRekap();
    loadLaporanKeuanganRekap();
  });

  // Tampilkan placeholder — API tidak dipanggil sampai user memilih Periode
  $('#list-laporan-keuangan-rekap').html(
    '<tr class="text-center"><td colspan="7" style="padding:24px; color:#888;">Silahkan pilih <strong>Periode</strong> untuk menampilkan data.</td></tr>'
  );

  $("#filterCabang").on("select2:select", function () {
    onFilterCabang($(this).val());
  });
  $("#filterCabang").on("select2:unselect", function () {
    onFilterCabang($(this).val());
  });

  $("#filterPeriode").on("select2:select", function () {
    onFilterPeriode($(this).val());
  });
  $("#filterPeriode").on("select2:unselect", function () {
    onFilterPeriode($(this).val());
  });

  $(".btn-download-excel").click(function () {
    const getBranchId  = paramUrlSetup.branchId;
    const getPeriodeId = paramUrlSetup.periodeId;

    if (!getBranchId) {
      $("#msg-box .modal-body").text("Pilih cabang dulu!");
      $("#msg-box").modal("show");
      return;
    }

    if (!getPeriodeId) {
      $("#msg-box .modal-body").text("Pilih periode dulu!");
      $("#msg-box").modal("show");
      return;
    }

    // Periode Bulanan: butuh rentang bulan
    if (getPeriodeId == 1 && (!paramUrlSetup.monthFrom || !paramUrlSetup.yearFrom || !paramUrlSetup.monthTo || !paramUrlSetup.yearTo)) {
      $("#msg-box .modal-body").text("Pilih rentang bulan dulu!");
      $("#msg-box").modal("show");
      return;
    }

    // Periode Tahunan: butuh tahun
    if (getPeriodeId == 2 && !paramUrlSetup.year) {
      $("#msg-box .modal-body").text("Pilih tahun dulu!");
      $("#msg-box").modal("show");
      return;
    }

    $.ajax({
      url: $(".baseUrl").val() + "/api/laporan-keuangan/rekap/download",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        branch_id:  getBranchId,
        periode:    getPeriodeId,
        month_from: paramUrlSetup.monthFrom,
        year_from:  paramUrlSetup.yearFrom,
        month_to:   paramUrlSetup.monthTo,
        year_to:    paramUrlSetup.yearTo,
        year:       paramUrlSetup.year,
      },
      xhrFields: { responseType: "blob" },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data, status, xhr) {
        let disposition = xhr.getResponseHeader("content-disposition");
        let matches = /"([^"]*)"/.exec(disposition);
        let filename = matches != null && matches[1] ? matches[1] : "file.xlsx";
        let blob = new Blob([data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        let downloadUrl = URL.createObjectURL(blob);
        let a = document.createElement("a");
        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  });

  $(".onOrdering").click(function () {
    const column = $(this).attr("data");
    const orderBy = $(this).attr("orderby");
    $('.onOrdering[data="' + column + '"]')
      .children()
      .remove();

    if (orderBy == "none" || orderBy == "asc") {
      $(this).attr("orderby", "desc");
      $(this).append('<span class="fa fa-sort-desc"></span>');
    } else if (orderBy == "desc") {
      $(this).attr("orderby", "asc");
      $(this).append('<span class="fa fa-sort-asc"></span>');
    }

    paramUrlSetup.orderby = $(this).attr("orderby");
    paramUrlSetup.column = column;

    loadLaporanKeuanganRekap();
  });

  function onFilterCabang(value) {
    paramUrlSetup.branchId = value;
    if (paramUrlSetup.periodeId) {
      widgetRekap();
      loadLaporanKeuanganRekap();
    }
  }

  function onFilterPeriode(value) {
    paramUrlSetup.periodeId = value;
    paramUrlSetup.monthFrom = '';
    paramUrlSetup.yearFrom  = '';
    paramUrlSetup.monthTo   = '';
    paramUrlSetup.yearTo    = '';

    if (value == 1) {
      $('#dateFilterLabel').text('Pilih Rentang Bulan');
      $('#monthYearPicker').css('display', 'flex');
      $('#filterYear').hide();
      $('#dateFilterSection').show();
      suppressDateChange = true;
      $('#datepickerFrom').datepicker('clearDates');
      $('#datepickerTo').datepicker('clearDates');
      suppressDateChange = false;
    } else if (value == 2) {
      $('#dateFilterLabel').text('Pilih Tahun');
      $('#monthYearPicker').hide();
      $('#filterYear').val('').show();
      $('#dateFilterSection').show();
    } else if (value == 3) {
      $('#dateFilterSection').hide();
      widgetRekap();
      loadLaporanKeuanganRekap();
    } else {
      $('#dateFilterSection').hide();
      $('#list-laporan-keuangan-rekap').html(
        '<tr class="text-center"><td colspan="7" style="padding:24px; color:#888;">Silahkan pilih <strong>Periode</strong> untuk menampilkan data.</td></tr>'
      );
    }
  }

  function loadLaporanKeuanganRekap() {
    const params = {
      orderby:    paramUrlSetup.orderby,
      column:     paramUrlSetup.column,
      month_from: paramUrlSetup.monthFrom,
      year_from:  paramUrlSetup.yearFrom,
      month_to:   paramUrlSetup.monthTo,
      year_to:    paramUrlSetup.yearTo,
      branch_id:  paramUrlSetup.branchId,
      periode:    paramUrlSetup.periodeId,
      year:       paramUrlSetup.year,
      page:       getCurrentPage,
    };

    $("#loading-screen").show();

    $.when(
      $.ajax({
        url: $(".baseUrl").val() + "/api/laporan-keuangan/rekap/table",
        headers: { Authorization: `Bearer ${token}` },
        type: "GET",
        data: params,
      }),
      $.ajax({
        url: $(".baseUrl").val() + "/api/laporan-keuangan/rekap/patient-summary",
        headers: { Authorization: `Bearer ${token}` },
        type: "GET",
        data: params,
      })
    ).then(function (finansialResult, patientResult) {
      const getData     = finansialResult[0];
      const patientData = patientResult[0];
      const complaints  = patientData.complaints || [];

      // Map dates => row untuk lookup cepat
      const patientMap = {};
      (patientData.data || []).forEach(function (row) {
        patientMap[row.dates] = row;
      });

      $("#list-laporan-keuangan-rekap tr").remove();

      let html = "";

      if (getData.length) {
        $.each(getData, function (idx, v) {
          // Baris keuangan utama
          html +=
            `<tr>` +
            `<td>${++idx}</td>` +
            `<td>${v.dates}</td>` +
            `<td>${Number(v.total_omset || 0).toLocaleString('id-ID')}</td>` +
            `<td>${Number(v.discount   || 0).toLocaleString('id-ID')}</td>` +
            `<td>${Number(v.expenses   || 0).toLocaleString('id-ID')}</td>` +
            `<td>${Number(v.sallary    || 0).toLocaleString('id-ID')}</td>` +
            `<td>${Number(v.netto      || 0).toLocaleString('id-ID')}</td>` +
            `</tr>`;

          // Sub-baris keluhan di bawah setiap periode
          if (complaints.length) {
            const pRow = patientMap[v.dates] || {};

            let thCells = complaints.map(c =>
              `<th style="padding:3px 10px; text-align:center; font-weight:600; color:#555; border-bottom:1px solid #c8d8f0; white-space:nowrap;">${c}</th>`
            ).join('');
            thCells += `<th style="padding:3px 10px; text-align:center; font-weight:700; color:#2a5298; border-bottom:1px solid #c8d8f0; white-space:nowrap;">Total</th>`;

            let tdCells = complaints.map(function (c) {
              const count = pRow[c] || 0;
              return `<td style="padding:3px 10px; text-align:center; color:#333;">${count > 0 ? count : '-'}</td>`;
            }).join('');
            tdCells += `<td style="padding:3px 10px; text-align:center; font-weight:700; color:#2a5298;">${pRow.total || 0}</td>`;

            html +=
              `<tr style="background:#eef3fb;">` +
              `<td colspan="7" style="padding:4px 12px 8px 28px; border-top:none;">` +
              `<table style="font-size:12px; border-collapse:collapse;">` +
              `<thead><tr>${thCells}</tr></thead>` +
              `<tbody><tr>${tdCells}</tr></tbody>` +
              `</table>` +
              `</td></tr>`;
          }
        });
      } else {
        html = `<tr class="text-center"><td colspan="7">Tidak ada data.</td></tr>`;
      }

      $("#list-laporan-keuangan-rekap").append(html);

      generatePagination(getCurrentPage, getData.total_paging);

      $(".pagination > li > a").click(function () {
        const getClassName = this.className;
        const getNumber = parseFloat($(this).text());

        if (
          (getCurrentPage === 1 && getClassName.includes("arrow-left")) ||
          (getCurrentPage === getData.total_paging &&
            getClassName.includes("arrow-right"))
        ) {
          return;
        }

        if (getClassName.includes("arrow-left")) {
          getCurrentPage = getCurrentPage - 1;
        } else if (getClassName.includes("arrow-right")) {
          getCurrentPage = getCurrentPage + 1;
        } else {
          getCurrentPage = getNumber;
        }

        loadLaporanKeuanganRekap();
      });

    }, function (err) {
      if (err.status == 401) {
        localStorage.removeItem("vet-clinic");
        location.href = $(".baseUrl").val() + "/masuk";
      } else {
        $("#list-laporan-keuangan-rekap").html(
          `<tr class="text-center"><td colspan="7" style="color:red;">Gagal memuat data (${err.status}). Coba ulangi.</td></tr>`
        );
      }
    }).always(function () {
      $("#loading-screen").hide();
    });
  }

  function loadCabang() {
    $.ajax({
      url: $(".baseUrl").val() + "/api/cabang",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        optCabang += `<option value=''>Cabang</option>`;

        if (data.length) {
          for (let i = 0; i < data.length; i++) {
            optCabang += `<option value=${data[i].id}>${data[i].branch_name}</option>`;
          }
        }
        $("#filterCabang").append(optCabang);
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function loadPeriode() {
    $.ajax({
      url: $(".baseUrl").val() + "/api/laporan-keuangan/rekap/listperiode",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (data) {
        optPeriode += `<option value=''>Periode</option>`;

        if (data.length) {
          for (let i = 0; i < data.length; i++) {
            optPeriode += `<option value=${data[i].id}>${data[i].periode}</option>`;
          }
        }
        $("#filterPeriode").append(optPeriode);
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }

  function widgetRekap() {
    $.ajax({
      url: $(".baseUrl").val() + "/api/laporan-keuangan/rekap/chart",
      headers: { Authorization: `Bearer ${token}` },
      type: "GET",
      data: {
        periode:    paramUrlSetup.periodeId,
        branch_id:  paramUrlSetup.branchId,
        month_from: paramUrlSetup.monthFrom,
        year_from:  paramUrlSetup.yearFrom,
        month_to:   paramUrlSetup.monthTo,
        year_to:    paramUrlSetup.yearTo,
        year:       paramUrlSetup.year,
      },
      beforeSend: function () {
        $("#loading-screen").show();
      },
      success: function (resp) {
        const categories = [];
        const series = {
          omset     : [],
          discount  : [],
          expenses  : [],
          sallary   : [],
          netto     : [],
        };

        resp.forEach((dt) => {
          categories.push(dt.periode);
          series.omset   .push(parseFloat(dt.total_omset) || 0);
          series.discount.push(parseFloat(dt.discount)    || 0);
          series.expenses.push(parseFloat(dt.expenses)    || 0);
          series.sallary .push(parseFloat(dt.sallary)     || 0);
          series.netto   .push(parseFloat(dt.netto)       || 0);
        });

        Highcharts.chart("rekapWidget", {
          chart  : { type: "line" },
          title  : { text: "" },
          credits: { enabled: false },
          xAxis  : { categories: categories },
          yAxis  : { title: { text: "Nominal (Rp)" } },
          legend : { enabled: true },
          plotOptions: { column: { dataLabels: { enabled: false } } },
          series: [
            { name: "Total Omset"  , data: series.omset    },
            { name: "Diskon"       , data: series.discount },
            { name: "Pengeluaran"  , data: series.expenses },
            { name: "Penggajian"   , data: series.sallary  },
            { name: "Netto"        , data: series.netto    },
          ],
        });
      },
      complete: function () {
        $("#loading-screen").hide();
      },
      error: function (err) {
        if (err.status == 401) {
          localStorage.removeItem("vet-clinic");
          location.href = $(".baseUrl").val() + "/masuk";
        }
      },
    });
  }
});
